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

class InventoryServiceTest extends TestCase
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
            'username' => 'testclerk',
            'password' => bcrypt('secret'),
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($employee);

        $this->service = $this->app->make(InventoryService::class);
    }

    /**
     * @return array{0: Product, 1: Inventory}
     */
    private function makeProduct(int $reorderLevel = 5, int $startStock = 10): array
    {
        $product = Product::query()->create([
            'product_name' => 'Item '.uniqid(),
            'barcode' => Product::generateBarcode(),
            'unit_price' => 10,
            'cost_price' => 5,
            'reorder_level' => $reorderLevel,
        ]);

        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => $startStock,
        ]);

        return [$product, $inventory];
    }

    public function test_adjust_stock_deducts_and_records_movement(): void
    {
        [$product] = $this->makeProduct(reorderLevel: 5, startStock: 10);

        $this->service->adjustStock($product->product_id, -3, 'sale', 'sale_transaction', 1, 'Sale line');

        $this->assertEquals(7, $this->service->getCurrentStock($product->product_id));

        $movement = StockMovement::query()->latest('movement_id')->firstOrFail();
        $this->assertEquals('sale', $movement->movement_type);
        $this->assertEquals(-3, $movement->quantity);
        $this->assertEquals(10, $movement->stock_before);
        $this->assertEquals(7, $movement->stock_after);
        $this->assertEquals('sale_transaction', $movement->reference_type);
        $this->assertEquals(1, $movement->reference_id);
        $this->assertEquals('Sale line', $movement->reason);
    }

    public function test_adjust_stock_can_add_stock(): void
    {
        [$product] = $this->makeProduct(reorderLevel: 5, startStock: 2);

        $this->service->adjustStock($product->product_id, 5, 'purchase', 'purchase_order', 7);

        $this->assertEquals(7, $this->service->getCurrentStock($product->product_id));
        $this->assertDatabaseHas('stock_movement', [
            'product_id' => $product->product_id,
            'movement_type' => 'purchase',
        ]);
    }

    public function test_adjust_stock_creates_inventory_when_missing(): void
    {
        $product = Product::query()->create([
            'product_name' => 'NoInv '.uniqid(),
            'barcode' => Product::generateBarcode(),
            'unit_price' => 10,
            'cost_price' => 5,
            'reorder_level' => 5,
        ]);

        $this->service->adjustStock($product->product_id, 4, 'purchase', 'purchase_order', 2);

        $this->assertEquals(4, $this->service->getCurrentStock($product->product_id));
    }

    public function test_get_stock_history_returns_movements_in_order(): void
    {
        [$product] = $this->makeProduct(startStock: 10);

        $this->service->adjustStock($product->product_id, -2, 'sale', 'sale_transaction', 1);
        $this->service->adjustStock($product->product_id, -1, 'refund', 'sale_refund', 1);

        $history = $this->service->getStockHistory($product->product_id);

        $this->assertCount(2, $history);
        $this->assertEquals(8, $history[0]->stock_after);
        $this->assertEquals(7, $history[1]->stock_after);
    }

    public function test_check_reorder_needed_returns_correct_signal(): void
    {
        [$low] = $this->makeProduct(reorderLevel: 5, startStock: 3);
        $this->assertEquals('low_stock', $this->service->checkReorderNeeded($low->product_id));

        [$out] = $this->makeProduct(reorderLevel: 5, startStock: 0);
        $this->assertEquals('out_of_stock', $this->service->checkReorderNeeded($out->product_id));

        [$ok] = $this->makeProduct(reorderLevel: 5, startStock: 9);
        $this->assertNull($this->service->checkReorderNeeded($ok->product_id));

        [$critical] = $this->makeProduct(reorderLevel: 5, startStock: 2);
        $critical->update(['critical_reorder_level' => 3]);
        $this->assertEquals('critical', $this->service->checkReorderNeeded($critical->product_id));
    }
}
