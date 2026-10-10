<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }

    private function setManagerPin(string $pin = '2468'): Employee
    {
        $manager = $this->employee('manager');
        $manager->manager_pin_hash = Hash::make($pin);
        $manager->save();

        return $manager;
    }

    public function test_cashier_must_get_manager_approval_for_each_sensitive_action_and_both_attempts_are_audited(): void
    {
        $manager = $this->setManagerPin();
        $cashier = $this->employee('cashier');
        $actions = [
            ['cart_item_void', ['product_id' => 1, 'product_name' => 'Coffee', 'quantity' => 1, 'line_total' => 120], 'damaged'],
            ['held_transaction_delete', ['hold_number' => 'HLD-0001', 'item_count' => 2, 'total' => 240], 'changed_mind'],
            ['discount_apply', ['discount_id' => 1, 'discount_name' => 'Senior', 'discount_amount' => 20], null],
            ['sale_refund', ['sale_id' => 1, 'items' => []], 'wrong_item'],
            ['cash_drawer_open', ['opening_cash' => 1000], null],
        ];

        foreach ($actions as [$action, $details, $reason]) {
            $payload = [
                'action' => $action,
                'pin' => '9999',
                'register_id' => 'REG 01',
                'details' => $details,
            ];
            if ($reason) $payload['reason'] = $reason;

            $this->actingAs($cashier)
                ->postJson(route('manager-authorization.authorize'), $payload)
                ->assertUnprocessable();

            $payload['pin'] = '2468';
            $this->actingAs($cashier)
                ->postJson(route('manager-authorization.authorize'), $payload)
                ->assertOk()
                ->assertJsonPath('ok', true);
        }

        $this->assertSame(5, AuditLog::query()->where('action', 'manager_pin_failed')->count());
        $this->assertSame(5, AuditLog::query()->whereIn('action', array_column($actions, 0))->count());

        $success = AuditLog::query()->where('action', 'cart_item_void')->firstOrFail();
        $this->assertSame($cashier->employee_id, $success->requested_by_employee_id);
        $this->assertSame($manager->employee_id, $success->approved_by_employee_id);
        $this->assertSame('REG 01', $success->register_id);
        $this->assertSame('damaged', $success->details['reason']);

        $this->actingAs($manager)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Requested by')
            ->assertSee('Approved by')
            ->assertSee('REG 01')
            ->assertSee($cashier->username)
            ->assertSee($manager->username)
            ->assertSee('cart_item_void');
    }

    public function test_third_wrong_pin_locks_authorization_for_sixty_seconds_and_logs_attempts(): void
    {
        $this->setManagerPin();
        $cashier = $this->employee('cashier');
        $payload = [
            'action' => 'cart_item_void',
            'pin' => '0000',
            'register_id' => 'REG 01',
            'details' => ['product_id' => 1, 'product_name' => 'Coffee', 'quantity' => 1, 'line_total' => 120],
            'reason' => 'damaged',
        ];

        $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), $payload)->assertUnprocessable();
        $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), $payload)->assertUnprocessable();
        $this->actingAs($cashier)
            ->postJson(route('manager-authorization.authorize'), $payload)
            ->assertStatus(429)
            ->assertJsonPath('locked_for', 60);

        $payload['pin'] = '2468';
        $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), $payload)->assertStatus(429);
        $this->assertSame(3, AuditLog::query()->where('action', 'manager_pin_failed')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'manager_pin_locked_out')->count());
    }

    public function test_cashier_cannot_apply_a_discount_to_checkout_without_a_matching_one_use_grant(): void
    {
        $manager = $this->setManagerPin();
        $cashier = $this->employee('cashier');
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create(['product_id' => $product->product_id, 'stock_quantity' => 5]);
        $discount = Discount::query()->create([
            'discount_name' => 'Member 20% Off',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        $salePayload = [
            'discount_id' => $discount->discount_id,
            'register_id' => 'REG 01',
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ];
        $saleCountBefore = SaleTransaction::query()->count();

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.store'), $salePayload)
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('manager_authorization_token');
        $this->assertSame($saleCountBefore, SaleTransaction::query()->count());

        $grant = $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), [
            'action' => 'discount_apply',
            'pin' => '2468',
            'register_id' => 'REG 01',
            'details' => ['discount_id' => $discount->discount_id, 'discount_name' => 'Senior Citizen', 'discount_amount' => 20],
        ])->assertOk()->json('token');

        $this->actingAs($cashier)
            ->post(route('pos.store'), $salePayload + ['manager_authorization_token' => $grant])
            ->assertRedirect();
        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $this->assertEquals(80.00, (float) $sale->total_amount);

        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.store'), $salePayload + ['manager_authorization_token' => $grant])
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('manager_authorization_token');
        $this->assertSame($saleCountBefore + 1, SaleTransaction::query()->count());
        $this->assertDatabaseHas('audit_log', [
            'action' => 'discount_applied_to_sale',
            'requested_by_employee_id' => $cashier->employee_id,
            'approved_by_employee_id' => $manager->employee_id,
        ]);
    }

    public function test_cash_drawer_open_requires_and_consumes_manager_approval(): void
    {
        $manager = $this->setManagerPin();
        $cashier = $this->employee('cashier');
        $this->actingAs($cashier)
            ->postJson(route('cash-drawer.open'), ['opening_cash' => 500])
            ->assertUnprocessable();

        $grant = $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), [
            'action' => 'cash_drawer_open',
            'pin' => '2468',
            'register_id' => 'REG 01',
            'details' => ['opening_cash' => 500],
        ])->assertOk()->json('token');

        $this->actingAs($cashier)
            ->postJson(route('cash-drawer.open'), ['opening_cash' => 500, 'manager_authorization_token' => $grant, 'register_id' => 'REG 01'])
            ->assertOk();

        $opened = AuditLog::query()->where('action', 'cash_drawer_opened')->firstOrFail();
        $this->assertSame($cashier->employee_id, $opened->requested_by_employee_id);
        $this->assertSame($manager->employee_id, $opened->approved_by_employee_id);
        $this->assertSame('REG 01', $opened->register_id);
    }

    public function test_manager_can_authorize_without_pin_and_employee_pin_is_saved_only_as_a_hash(): void
    {
        $manager = $this->employee('manager');
        $managerApproval = $this->actingAs($manager)->postJson(route('manager-authorization.authorize'), [
            'action' => 'discount_apply',
            'register_id' => 'REG 01',
            'details' => ['discount_id' => 1, 'discount_name' => 'Senior Citizen', 'discount_amount' => 20],
        ])->assertOk();
        $this->assertNotEmpty($managerApproval->json('token'));
        $this->assertDatabaseHas('audit_log', [
            'action' => 'discount_apply',
            'requested_by_employee_id' => $manager->employee_id,
            'approved_by_employee_id' => $manager->employee_id,
        ]);

        $managerRole = Role::query()->whereRaw('LOWER(role_name) = ?', ['manager'])->firstOrFail();
        $this->actingAs($manager)->post(route('employees.store'), [
            'role_id' => $managerRole->role_id,
            'first_name' => 'Manager',
            'last_name' => 'Test',
            'username' => 'test-manager-pin',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
            'manager_pin' => '1357',
            'manager_pin_confirmation' => '1357',
        ])->assertRedirect(route('employees.index'));

        $created = Employee::query()->where('username', 'test-manager-pin')->firstOrFail();
        $this->assertTrue(Hash::check('1357', $created->manager_pin_hash));
        $this->assertNotSame('1357', $created->manager_pin_hash);
        $this->assertArrayNotHasKey('manager_pin_hash', $created->toArray());
    }
}
