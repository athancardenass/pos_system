<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ReorderSignal;
use App\Models\Role;
use App\Services\ReorderSignalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReorderSignalService $signals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $employee = Employee::query()->create([
            'role_id' => Role::query()->where('role_name', 'Manager')->firstOrFail()->role_id,
            'first_name' => 'Test',
            'last_name' => 'Clerk',
            'username' => 'signalclerk',
            'password' => bcrypt('secret'),
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($employee);

        $this->signals = $this->app->make(ReorderSignalService::class);
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

    public function test_create_signal_snapshots_stock_and_suggests_quantity(): void
    {
        [$product] = $this->makeProduct(reorderLevel: 10, startStock: 4);

        $signal = $this->signals->createSignal($product->product_id, 'low_stock');

        $this->assertEquals('low_stock', $signal->signal_type);
        $this->assertEquals(4, $signal->current_stock);
        $this->assertEquals(10, $signal->reorder_level);
        $this->assertEquals(6, $signal->suggested_quantity);
        $this->assertEquals('open', $signal->status);
    }

    public function test_scan_for_low_stock_opens_signals_for_below_reorder(): void
    {
        [$low] = $this->makeProduct(reorderLevel: 5, startStock: 2);
        [$out] = $this->makeProduct(reorderLevel: 5, startStock: 0);
        [$ok] = $this->makeProduct(reorderLevel: 5, startStock: 9);

        $this->signals->scanForLowStock();

        $this->assertDatabaseHas('reorder_signal', [
            'product_id' => $low->product_id,
            'signal_type' => 'low_stock',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('reorder_signal', [
            'product_id' => $out->product_id,
            'signal_type' => 'out_of_stock',
            'status' => 'open',
        ]);
        $this->assertDatabaseMissing('reorder_signal', [
            'product_id' => $ok->product_id,
        ]);
    }

    public function test_scan_does_not_duplicate_open_signals(): void
    {
        [$low] = $this->makeProduct(reorderLevel: 5, startStock: 2);

        $this->signals->scanForLowStock();
        $this->signals->scanForLowStock();

        $this->assertCount(1, ReorderSignal::query()
            ->where('product_id', $low->product_id)
            ->where('signal_type', 'low_stock')
            ->where('status', 'open')
            ->get());
    }

    public function test_resolve_signal_dismisses_it(): void
    {
        [$product] = $this->makeProduct(reorderLevel: 5, startStock: 2);

        $signal = $this->signals->createSignal($product->product_id, 'low_stock');

        $this->signals->resolveSignal($signal->signal_id);

        $signal->refresh();
        $this->assertEquals('dismissed', $signal->status);
        $this->assertNotNull($signal->resolved_at);
    }

    public function test_get_signals_filters_by_status(): void
    {
        [$p1] = $this->makeProduct(reorderLevel: 5, startStock: 2);
        [$p2] = $this->makeProduct(reorderLevel: 5, startStock: 1);

        $this->signals->createSignal($p1->product_id, 'low_stock');
        $second = $this->signals->createSignal($p2->product_id, 'low_stock');
        $this->signals->resolveSignal($second->signal_id);

        $this->assertCount(1, $this->signals->getSignals('open'));
        $this->assertCount(1, $this->signals->getSignals('dismissed'));
    }
}
