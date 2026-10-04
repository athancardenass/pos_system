<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_inventory_update_accepts_decimal_stock_to_three_places(): void
    {
        $product = $this->product();
        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);

        $this->actingAs($this->employee('manager'))
            ->put(route('inventory.update', $inventory), ['stock_quantity' => '2.375'])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory', [
            'inventory_id' => $inventory->inventory_id,
            'stock_quantity' => '2.375',
        ]);

        $this->actingAs($this->employee('manager'))
            ->put(route('inventory.update', $inventory), ['stock_quantity' => '2.3755'])
            ->assertSessionHasErrors('stock_quantity');
    }

    public function test_purchase_orders_accept_decimal_quantities_to_three_places(): void
    {
        $product = $this->product();
        $payload = $this->purchaseOrderPayload($product->product_id, now()->toDateString(), '1.275');
        $initialOrderCount = PurchaseOrder::query()->count();

        $this->actingAs($this->employee('manager'))
            ->post(route('purchase-orders.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_order_details', [
            'product_id' => $product->product_id,
            'quantity' => '1.275',
        ]);

        $payload['items'][0]['quantity'] = '1.2755';
        $this->actingAs($this->employee('manager'))
            ->post(route('purchase-orders.store'), $payload)
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('purchase_order', $initialOrderCount + 1);
    }

    public function test_customer_and_employee_dates_obey_the_form_minimum_and_today_limit(): void
    {
        $customerPayload = [
            'first_name' => 'Validation',
            'last_name' => 'Customer',
            'customer_status' => 'active',
        ];

        foreach ([now()->addDay()->toDateString(), '1899-12-31'] as $date) {
            $this->actingAs($this->employee('manager'))
                ->post(route('customers.store'), $customerPayload + ['date_of_birth' => $date])
                ->assertSessionHasErrors('date_of_birth');
        }

        $employeePayload = [
            'role_id' => Role::query()->where('role_name', 'Cashier')->firstOrFail()->role_id,
            'first_name' => 'Validation',
            'last_name' => 'Employee',
            'password' => 'secret123',
            'status' => 'active',
        ];

        foreach ([now()->addDay()->toDateString(), '1899-12-31'] as $index => $date) {
            $this->actingAs($this->employee('manager'))
                ->post(route('employees.store'), $employeePayload + [
                    'username' => 'validation-employee-'.$index,
                    'hire_date' => $date,
                ])
                ->assertSessionHasErrors('hire_date');
        }
    }

    public function test_purchase_order_date_obeys_the_seven_day_and_thirty_day_window(): void
    {
        $product = $this->product();
        $invalidDates = [now()->subDays(8)->toDateString(), now()->addDays(31)->toDateString()];
        $initialOrderCount = PurchaseOrder::query()->count();

        foreach ($invalidDates as $date) {
            $this->actingAs($this->employee('manager'))
                ->post(route('purchase-orders.store'), $this->purchaseOrderPayload($product->product_id, $date))
                ->assertSessionHasErrors('order_date');
        }

        $validDates = [now()->subDays(7)->toDateString(), now()->addDays(30)->toDateString()];

        foreach ($validDates as $date) {
            $this->actingAs($this->employee('manager'))
                ->post(route('purchase-orders.store'), $this->purchaseOrderPayload($product->product_id, $date))
                ->assertRedirect();
        }

        $this->assertDatabaseCount('purchase_order', $initialOrderCount + 2);
    }

    public function test_product_barcode_must_be_a_valid_ean13(): void
    {
        $validBarcode = Product::generateBarcode();
        $lastDigit = (int) substr($validBarcode, -1);
        $badChecksumBarcode = substr($validBarcode, 0, 12).(string) (($lastDigit + 1) % 10);

        foreach (['SNACK-001', $badChecksumBarcode] as $barcode) {
            $this->actingAs($this->employee('manager'))
                ->post(route('products.store'), $this->productPayload($barcode))
                ->assertSessionHasErrors('barcode');
        }

        $this->actingAs($this->employee('manager'))
            ->post(route('products.store'), $this->productPayload($validBarcode))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product', ['barcode' => $validBarcode]);
    }

    public function test_fresh_schema_drops_unused_users_but_keeps_framework_tables(): void
    {
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }

    private function product(): Product
    {
        return Product::query()->create($this->productPayload(Product::generateBarcode()));
    }

    private function productPayload(string $barcode): array
    {
        return [
            'product_name' => 'Validation Item',
            'barcode' => $barcode,
            'unit_price' => 12.50,
            'cost_price' => 8.00,
            'reorder_level' => 1,
        ];
    }

    private function purchaseOrderPayload(int $productId, string $orderDate, string $quantity = '1.250'): array
    {
        return [
            'supplier_id' => Supplier::query()->firstOrFail()->supplier_id,
            'order_date' => $orderDate,
            'items' => [[
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_cost' => '8.00',
            ]],
        ];
    }
}
