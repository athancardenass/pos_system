<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\SaleDetail;
use App\Models\SaleTransaction;
use App\Models\SupervisorActionPermission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorRoleTest extends TestCase
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

    private function supervisor(string $username = 'supervisor', string $pin = '3579'): Employee
    {
        $role = Role::query()->whereRaw('LOWER(role_name) = ?', ['supervisor'])->firstOrFail();

        $employee = Employee::query()->create([
            'role_id' => $role->role_id,
            'first_name' => 'Shift',
            'last_name' => 'Supervisor',
            'username' => $username,
            'password' => 'password1234',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $employee->manager_pin_hash = Hash::make($pin);
        $employee->save();

        return $employee;
    }

    private function grant(Employee $employee, string $action, float $limit): void
    {
        SupervisorActionPermission::query()->create([
            'employee_id' => $employee->employee_id,
            'action' => $action,
            'max_amount' => $limit,
        ]);
    }

    public function test_supervisor_role_is_seeded_and_only_gets_pos_access(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)->get(route('pos.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('customers.index'))->assertForbidden();
        $this->assertDatabaseHas('role', ['role_name' => 'Supervisor']);
        $this->assertSame([], $supervisor->supervisorActionPermissions()->get()->all());
    }

    public function test_manager_can_assign_and_revoke_supervisor_permissions(): void
    {
        $manager = $this->employee('manager');
        $supervisor = $this->supervisor();

        $this->actingAs($manager)
            ->get(route('employees.supervisor-permissions.edit', $supervisor))
            ->assertOk()
            ->assertSee('Cash movements')
            ->assertSee('Maximum amount');

        $this->actingAs($manager)
            ->put(route('employees.supervisor-permissions.update', $supervisor), [
                'permissions' => ['cash_movement' => '500.00', 'cash_drawer_open' => '1000.00'],
            ])
            ->assertRedirect(route('employees.supervisor-permissions.edit', $supervisor));

        $this->assertDatabaseHas('supervisor_action_permission', [
            'employee_id' => $supervisor->employee_id,
            'action' => 'cash_movement',
            'max_amount' => '500.00',
        ]);

        $this->actingAs($manager)
            ->put(route('employees.supervisor-permissions.update', $supervisor), ['permissions' => []])
            ->assertRedirect(route('employees.supervisor-permissions.edit', $supervisor));
        $this->assertDatabaseMissing('supervisor_action_permission', ['employee_id' => $supervisor->employee_id]);
        $this->actingAs($supervisor)
            ->put(route('employees.supervisor-permissions.update', $supervisor), ['permissions' => ['cash_movement' => '500']])
            ->assertForbidden();
    }

    public function test_changing_a_supervisor_to_cashier_clears_their_pin_and_permissions(): void
    {
        $manager = $this->employee('manager');
        $supervisor = $this->supervisor();
        $this->grant($supervisor, 'cash_movement', 500);
        $cashierRole = Role::query()->whereRaw('LOWER(role_name) = ?', ['cashier'])->firstOrFail();

        $this->actingAs($manager)->put(route('employees.update', $supervisor), [
            'role_id' => $cashierRole->role_id,
            'first_name' => $supervisor->first_name,
            'last_name' => $supervisor->last_name,
            'username' => $supervisor->username,
            'password' => '',
            'password_confirmation' => '',
            'hire_date' => $supervisor->hire_date->toDateString(),
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $this->assertNull($supervisor->fresh()->manager_pin_hash);
        $this->assertDatabaseMissing('supervisor_action_permission', ['employee_id' => $supervisor->employee_id]);
    }

    public function test_cashier_can_use_a_supervisor_pin_only_for_assigned_actions_under_the_cap(): void
    {
        $cashier = $this->employee('cashier');
        $supervisor = $this->supervisor();
        $this->grant($supervisor, 'cash_movement', 500);

        $payload = [
            'action' => 'cash_movement',
            'pin' => '3579',
            'register_id' => 'REG 01',
            'details' => [
                'kind' => 'manual_in',
                'amount' => 500,
                'reason' => 'Change fund',
                'operation_key' => '7e739eb0-b89f-4b29-96b0-c7f38f9fe019',
            ],
        ];

        $this->actingAs($cashier)
            ->postJson(route('manager-authorization.authorize'), $payload)
            ->assertOk()
            ->assertJsonPath('approved_by', $supervisor->displayName());

        $this->assertDatabaseHas('audit_log', [
            'action' => 'cash_movement',
            'requested_by_employee_id' => $cashier->employee_id,
            'approved_by_employee_id' => $supervisor->employee_id,
        ]);

        $payload['details']['amount'] = 500.01;
        $payload['details']['operation_key'] = 'd01f92fa-bfe8-47bc-a35d-a5136923a987';
        $this->actingAs($cashier)
            ->postJson(route('manager-authorization.authorize'), $payload)
            ->assertUnprocessable();

        $payload['action'] = 'discount_apply';
        $payload['details'] = ['discount_id' => 1, 'discount_name' => 'Member', 'discount_amount' => 20];
        $this->actingAs($cashier)
            ->postJson(route('manager-authorization.authorize'), $payload)
            ->assertUnprocessable();
    }

    public function test_supervisor_can_perform_assigned_actions_but_cannot_approve_their_own_over_cap_action(): void
    {
        $supervisor = $this->supervisor();
        $this->grant($supervisor, 'cash_movement', 500);

        $this->actingAs($supervisor)
            ->postJson(route('manager-authorization.authorize'), [
                'action' => 'cash_movement',
                'register_id' => 'REG 01',
                'details' => [
                    'kind' => 'manual_out',
                    'amount' => 400,
                    'reason' => 'Safe drop',
                    'operation_key' => '35c182ce-b6fc-4acd-a519-09c644210e42',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('approved_by', $supervisor->displayName());

        $this->assertDatabaseHas('audit_log', [
            'action' => 'cash_movement',
            'requested_by_employee_id' => $supervisor->employee_id,
            'approved_by_employee_id' => $supervisor->employee_id,
        ]);

        $this->actingAs($supervisor)
            ->postJson(route('manager-authorization.authorize'), [
                'action' => 'cash_movement',
                'register_id' => 'REG 01',
                'details' => [
                    'kind' => 'manual_out',
                    'amount' => 501,
                    'reason' => 'Safe drop',
                    'operation_key' => '454a4dc8-953b-405a-a334-c6a89072e89b',
                ],
            ])
            ->assertStatus(428)
            ->assertJsonPath('pin_required', true);

        $this->actingAs($supervisor)
            ->postJson(route('manager-authorization.authorize'), [
                'action' => 'cash_movement',
                'pin' => '3579',
                'register_id' => 'REG 01',
                'details' => [
                    'kind' => 'manual_out',
                    'amount' => 501,
                    'reason' => 'Safe drop',
                    'operation_key' => '454a4dc8-953b-405a-a334-c6a89072e89b',
                ],
            ])
            ->assertUnprocessable();

        $this->assertSame(1, AuditLog::query()->where('action', 'cash_movement')->count());
    }

    public function test_supervisor_approval_cannot_be_used_after_a_manager_revokes_it(): void
    {
        $manager = $this->employee('manager');
        $cashier = $this->employee('cashier');
        $supervisor = $this->supervisor();
        $this->grant($supervisor, 'cash_drawer_open', 1000);

        $token = $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), [
            'action' => 'cash_drawer_open',
            'pin' => '3579',
            'register_id' => 'REG 01',
            'details' => ['opening_cash' => 500],
        ])->assertOk()->json('token');

        $this->actingAs($manager)
            ->put(route('employees.supervisor-permissions.update', $supervisor), ['permissions' => []])
            ->assertRedirect();

        $this->actingAs($cashier)
            ->postJson(route('cash-drawer.open'), [
                'opening_cash' => 500,
                'manager_authorization_token' => $token,
                'register_id' => 'REG 01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('manager_authorization_token');

        $this->assertDatabaseCount('cash_drawers', 0);
    }

    public function test_supervisor_pin_cannot_authorize_a_refund_outside_the_standard_window(): void
    {
        $cashier = $this->employee('cashier');
        $supervisor = $this->supervisor();
        $this->grant($supervisor, 'sale_refund', 1000);
        $product = Product::query()->create([
            'product_name' => 'Returnable Item',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 500,
            'cost_price' => 250,
            'reorder_level' => 5,
        ]);
        $sale = SaleTransaction::query()->create([
            'employee_id' => $cashier->employee_id,
            'transaction_date' => now()->subDays(8),
            'subtotal' => 500,
            'total_amount' => 500,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        $detail = SaleDetail::query()->create([
            'transaction_id' => $sale->transaction_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);

        $this->actingAs($cashier)
            ->postJson(route('manager-authorization.authorize'), [
                'action' => 'sale_refund',
                'pin' => '3579',
                'register_id' => 'REG 01',
                'reason' => 'wrong_item',
                'details' => [
                    'sale_id' => $sale->transaction_id,
                    'items' => [$detail->sale_detail_id => 1],
                    'condition' => 'resellable',
                    'operation_key' => 'f5d326b8-54a3-4f23-88f4-922d48ea9262',
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_new_supervisor_account_requires_an_approval_pin(): void
    {
        $manager = $this->employee('manager');
        $role = Role::query()->whereRaw('LOWER(role_name) = ?', ['supervisor'])->firstOrFail();

        $this->actingAs($manager)->from(route('employees.create'))->post(route('employees.store'), [
            'role_id' => $role->role_id,
            'first_name' => 'Jamie',
            'last_name' => 'Lead',
            'username' => 'jamie-lead',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ])->assertRedirect(route('employees.create'))->assertSessionHasErrors('manager_pin');

        $this->actingAs($manager)->post(route('employees.store'), [
            'role_id' => $role->role_id,
            'first_name' => 'Jamie',
            'last_name' => 'Lead',
            'username' => 'jamie-lead',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
            'manager_pin' => '4680',
            'manager_pin_confirmation' => '4680',
        ])->assertRedirect(route('employees.index'));

        $created = Employee::query()->where('username', 'jamie-lead')->firstOrFail();
        $this->assertTrue(Hash::check('4680', $created->manager_pin_hash));
        $this->assertSame(0, $created->supervisorActionPermissions()->count());
    }
}
