<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Services\CheckoutService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private CheckoutService $service;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->employee = Employee::query()->create([
            'role_id' => Role::query()->where('role_name', 'Manager')->firstOrFail()->role_id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'username' => 'msantos',
            'password' => bcrypt('password'),
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->employee);
        $this->service = $this->app->make(CheckoutService::class);
    }

    private function createProduct(string $name, float $price, float $stock): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'barcode' => Product::generateBarcode(),
            'unit_price' => $price,
            'cost_price' => round($price * 0.7, 2),
            'reorder_level' => 5,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    public function test_successful_checkout_creates_sale_and_deducts_inventory(): void
    {
        $product = $this->createProduct('Coke 1.5L', 75.00, 20);

        $data = [
            'customer_id' => null,
            'discount_id' => null,
            'coupon_code' => null,
            'payment_method' => 'cash',
            'amount_paid' => 200.00,
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
            ],
        ];

        $sale = $this->service->checkout($data, $this->employee->employee_id);

        $this->assertNotNull($sale);
        $this->assertEquals(150.00, (float) $sale->total_amount);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(18.00, (float) $product->stockQuantity());
        $this->assertDatabaseHas('payment', [
            'transaction_id' => $sale->transaction_id,
            'amount_paid' => 200.00,
            'change_amount' => 50.00,
        ]);
        $this->assertDatabaseHas('receipt', [
            'transaction_id' => $sale->transaction_id,
        ]);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $product = $this->createProduct('Bread', 50.00, 1);

        $data = [
            'customer_id' => null,
            'discount_id' => null,
            'coupon_code' => null,
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 5],
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->service->checkout($data, $this->employee->employee_id);
    }
}
