<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\ReorderSignalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function cashier(): Employee
    {
        return Employee::query()->where('username', 'cashier')->firstOrFail();
    }

    private function makeProduct(array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'product_name' => 'Foundation Probe',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 10.00,
            'cost_price' => 5.00,
            'reorder_level' => 5,
        ], $attributes));
    }

    public function test_adjust_stock_adds_positive_quantity_and_records_movement(): void
    {
        $this->actingAs($this->cashier());

        $product = $this->makeProduct();
        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 5,
        ]);

        (new InventoryService())->adjustStock(
            $product->product_id,
            3.0,
            'purchase',
            'purchase_order',
            1,
            'restock'
        );

        $this->assertEquals(8.0, (float) $inventory->fresh()->stock_quantity);

        $movement = StockMovement::query()->where('product_id', $product->product_id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('purchase', $movement->movement_type);
        $this->assertEquals(3.0, (float) $movement->quantity);
        $this->assertEquals(5.0, (float) $movement->stock_before);
        $this->assertEquals(8.0, (float) $movement->stock_after);
        $this->assertEquals($this->cashier()->employee_id, $movement->employee_id);
        $this->assertEquals('restock', $movement->reason);
    }

    public function test_adjust_stock_deducts_on_negative_quantity(): void
    {
        $this->actingAs($this->cashier());

        $product = $this->makeProduct();
        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        (new InventoryService())->adjustStock(
            $product->product_id,
            -4.0,
            'sale',
            'sale_transaction',
            1
        );

        $this->assertEquals(6.0, (float) $inventory->fresh()->stock_quantity);
    }

    public function test_adjust_stock_creates_inventory_row_when_missing(): void
    {
        $this->actingAs($this->cashier());

        $product = $this->makeProduct();

        $this->assertNull(Inventory::query()->where('product_id', $product->product_id)->first());

        (new InventoryService())->adjustStock(
            $product->product_id,
            2.5,
            'purchase',
            'purchase_order',
            1
        );

        $inventory = Inventory::query()->where('product_id', $product->product_id)->first();
        $this->assertNotNull($inventory);
        $this->assertEquals(2.5, (float) $inventory->stock_quantity);
    }

    public function test_get_current_stock_returns_float(): void
    {
        $product = $this->makeProduct();
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 12.5,
        ]);

        $this->assertEquals(12.5, (new InventoryService())->getCurrentStock($product->product_id));
    }

    public function test_get_current_stock_returns_zero_without_inventory_row(): void
    {
        $product = $this->makeProduct();

        $this->assertEquals(0.0, (new InventoryService())->getCurrentStock($product->product_id));
    }

    public function test_get_stock_history_returns_movements_for_product(): void
    {
        $this->actingAs($this->cashier());

        $product = $this->makeProduct();
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);

        $service = new InventoryService();
        $service->adjustStock($product->product_id, 5.0, 'purchase', 'purchase_order', 1);
        $service->adjustStock($product->product_id, -2.0, 'sale', 'sale_transaction', 2);

        $history = $service->getStockHistory($product->product_id);
        $this->assertCount(2, $history);
        $this->assertEquals('purchase', $history->first()->movement_type);
    }

    public function test_check_reorder_needed_detects_levels(): void
    {
        $product = $this->makeProduct(['reorder_level' => 5, 'critical_reorder_level' => 2]);
        $inventory = Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);

        $service = new InventoryService();

        $this->assertEquals('out_of_stock', $service->checkReorderNeeded($product->product_id));

        $inventory->update(['stock_quantity' => 1]);
        $this->assertEquals('critical', $service->checkReorderNeeded($product->product_id));

        $inventory->update(['stock_quantity' => 4]);
        $this->assertEquals('low_stock', $service->checkReorderNeeded($product->product_id));

        $inventory->update(['stock_quantity' => 10]);
        $this->assertNull($service->checkReorderNeeded($product->product_id));
    }

    // --- ReorderSignalService ----------------------------------------------

    public function test_scan_for_low_stock_opens_signals_only_for_low_products(): void
    {
        $low = $this->makeProduct(['reorder_level' => 5]);
        Inventory::query()->create([
            'product_id' => $low->product_id,
            'stock_quantity' => 2,
        ]);

        $healthy = $this->makeProduct(['reorder_level' => 5]);
        Inventory::query()->create([
            'product_id' => $healthy->product_id,
            'stock_quantity' => 100,
        ]);

        (new ReorderSignalService())->scanForLowStock();

        $this->assertDatabaseHas('reorder_signal', [
            'product_id' => $low->product_id,
            'signal_type' => 'low_stock',
            'status' => 'open',
        ]);
        $this->assertDatabaseMissing('reorder_signal', [
            'product_id' => $healthy->product_id,
        ]);
    }

    public function test_create_signal_snapshots_stock_and_suggests_quantity(): void
    {
        $product = $this->makeProduct(['reorder_level' => 10]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 3,
        ]);

        $signal = (new ReorderSignalService())->createSignal($product->product_id, 'low_stock');

        $this->assertEquals(3.0, (float) $signal->current_stock);
        $this->assertEquals(10.0, (float) $signal->reorder_level);
        $this->assertEquals(7.0, (float) $signal->suggested_quantity);
        $this->assertEquals('open', $signal->status);
    }

    public function test_resolve_signal_dismisses_and_timestamps(): void
    {
        $product = $this->makeProduct();
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 1,
        ]);

        $signal = (new ReorderSignalService())->createSignal($product->product_id, 'low_stock');
        (new ReorderSignalService())->resolveSignal($signal->signal_id);

        $fresh = $signal->fresh();
        $this->assertEquals('dismissed', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_get_signals_filters_by_status(): void
    {
        $product = $this->makeProduct();
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 1,
        ]);

        $service = new ReorderSignalService();
        $signal = $service->createSignal($product->product_id, 'low_stock');
        $service->resolveSignal($signal->signal_id);

        $this->assertCount(0, $service->getSignals('open'));
        $this->assertCount(1, $service->getSignals('dismissed'));
    }

    // --- StockMovement model -----------------------------------------------

    public function test_stock_movement_belongs_to_product_and_employee(): void
    {
        $employee = $this->cashier();
        $this->actingAs($employee);

        $product = $this->makeProduct();
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);

        (new InventoryService())->adjustStock(
            $product->product_id,
            4.0,
            'adjustment',
            'inventory_count',
            1
        );

        $movement = StockMovement::query()->latest('movement_id')->first();
        $this->assertInstanceOf(Product::class, $movement->product);
        $this->assertInstanceOf(Employee::class, $movement->employee);
        $this->assertEquals($employee->employee_id, $movement->employee->employee_id);
    }
}
