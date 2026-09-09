<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $employee = Employee::query()->create([
            'role_id' => Role::query()->where('role_name', 'Manager')->firstOrFail()->role_id,
            'first_name' => 'Test',
            'last_name' => 'Clerk',
            'username' => 'moveclerk',
            'password' => bcrypt('secret'),
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($employee);

        $this->service = $this->app->make(InventoryService::class);
    }

    private function makeProduct(int $startStock = 5): array
    {
        $product = Product::query()->create([
            'product_name' => 'Item '.uniqid(),
            'barcode' => Product::generateBarcode(),
            'unit_price' => 10,
            'cost_price' => 5,
            'reorder_level' => 5,
        ]);

        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => $startStock,
        ]);

        return [$product, $inventory];
    }

    public function test_movement_belongs_to_product_and_employee(): void
    {
        [$product] = $this->makeProduct(startStock: 5);
        $employee = Employee::query()->where('username', 'moveclerk')->firstOrFail();

        $this->service->adjustStock($product->product_id, -1, 'sale', 'sale_transaction', 1, 'Sold one');

        $movement = StockMovement::query()->latest('movement_id')->firstOrFail();
        $this->assertInstanceOf(Product::class, $movement->product);
        $this->assertSame($product->product_id, $movement->product->product_id);
        $this->assertInstanceOf(Employee::class, $movement->employee);
        $this->assertSame($employee->employee_id, $movement->employee->employee_id);
    }

    public function test_quantity_is_cast_to_decimal(): void
    {
        [$product] = $this->makeProduct(startStock: 5);

        $this->service->adjustStock($product->product_id, -2.5, 'sale', 'sale_transaction', 1);

        $movement = StockMovement::query()->latest('movement_id')->firstOrFail();
        $this->assertSame('decimal:3', $movement->getCasts()['quantity']);
        $this->assertEquals(-2.5, $movement->quantity);
        $this->assertEquals(5, $movement->stock_before);
        $this->assertEquals(2.5, $movement->stock_after);
    }

    public function test_inventory_has_stock_movements(): void
    {
        [$product, $inventory] = $this->makeProduct(startStock: 5);

        $this->service->adjustStock($product->product_id, -1, 'sale', 'sale_transaction', 1);

        $this->assertCount(1, $inventory->fresh()->stockMovements);
        $this->assertSame($product->product_id, $inventory->fresh()->stockMovements->first()->product_id);
    }
}
