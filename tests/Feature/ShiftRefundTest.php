<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\CashDrawerService;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_refund_requires_enough_expected_cash_in_the_processing_drawer(): void
    {
        [$cashier,$manager,$drawer,$product,$sale] = $this->sale();
        $this->actingAs($manager);
        $today = app(CashDrawerService::class)->openDrawer($manager->employee_id, 100);
        try {
            app(RefundService::class)->refund($sale, [], 'changed_mind', null, ['requested_by_employee_id' => $manager->employee_id, 'approved_by_employee_id' => $manager->employee_id], ['condition' => 'resellable', 'operation_key' => (string) Str::uuid()]);
            $this->fail('Unfunded cash refund accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('drawer', $e->errors());
        }
        $this->assertEquals(100, $today->fresh()->expected_cash);
        $this->assertDatabaseCount('sale_refund', 0);
        $this->assertEquals(4, $product->fresh()->stockQuantity());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function sale(string $method = 'cash'): array
    {
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $manager = Employee::where('username', 'manager')->firstOrFail();
        $this->actingAs($cashier);
        $drawer = app(CashDrawerService::class)->openDrawer($cashier->employee_id, 1000);
        $product = Product::create(['product_name' => 'Return item', 'barcode' => Product::generateBarcode(), 'unit_price' => 500, 'cost_price' => 100, 'reorder_level' => 0]);
        Inventory::create(['product_id' => $product->product_id, 'stock_quantity' => 5]);
        $sale = app(CheckoutService::class)->checkout(['items' => [['product_id' => $product->product_id, 'quantity' => 1]], 'payment_method' => $method, 'amount_paid' => 500], $cashier->employee_id);

        return [$cashier, $manager, $drawer, $product, $sale];
    }

    public function test_next_day_other_cashier_refund_reduces_current_drawer_only(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(9));
        [$cashier,$manager,$drawer,$product,$sale] = $this->sale();
        $closed = app(CashDrawerService::class)->closeDrawer($cashier->employee_id, 1500);
        $snapshot = $closed->report_snapshot;
        $this->travel(1)->days();
        $this->actingAs($manager);
        $today = app(CashDrawerService::class)->openDrawer($manager->employee_id, 1000);
        $refund = app(RefundService::class)->refund($sale, [], 'changed_mind', null, ['requested_by_employee_id' => $manager->employee_id, 'approved_by_employee_id' => $manager->employee_id], ['condition' => 'resellable', 'operation_key' => (string) Str::uuid()]);
        $this->assertEquals(500, $today->fresh()->expected_cash);
        $this->assertSame($snapshot, $closed->fresh()->report_snapshot);
        $this->assertSame($today->drawer_id, $refund->drawer_id);
        $this->assertEquals(5, $product->fresh()->stockQuantity());
    }

    public function test_damaged_return_never_restores_sellable_inventory(): void
    {
        [$cashier,$manager,$drawer,$product,$sale] = $this->sale();
        $refund = app(RefundService::class)->refund($sale, [], 'damaged', null, ['requested_by_employee_id' => $cashier->employee_id, 'approved_by_employee_id' => $manager->employee_id], ['condition' => 'damaged', 'operation_key' => (string) Str::uuid()]);
        $this->assertEquals(4, $product->fresh()->stockQuantity());
        $this->assertEquals(1000, $drawer->fresh()->expected_cash);
        $this->assertDatabaseMissing('inventory_outbox_event', ['refund_id' => $refund->refund_id]);
        $this->assertSame('damaged', $refund->items()->first()->condition);
    }

    public function test_card_refund_has_no_effect_on_drawer_cash(): void
    {
        [$cashier,$manager,$drawer,$product,$sale] = $this->sale('card');
        $refund = app(RefundService::class)->refund($sale, [], 'changed_mind', null, ['requested_by_employee_id' => $cashier->employee_id, 'approved_by_employee_id' => $manager->employee_id], ['condition' => 'resellable', 'settlement_reference' => 'merchant-refund-123', 'operation_key' => (string) Str::uuid()]);
        $this->assertEquals(1000, $drawer->fresh()->expected_cash);
        $this->assertDatabaseHas('drawer_movements', ['refund_id' => $refund->refund_id, 'amount' => -500, 'payment_method' => 'card']);
    }

    public function test_retry_does_not_refund_or_restock_twice(): void
    {
        [$cashier,$manager,$drawer,$product,$sale] = $this->sale();
        $options = ['condition' => 'resellable', 'operation_key' => (string) Str::uuid()];
        $approval = ['requested_by_employee_id' => $cashier->employee_id, 'approved_by_employee_id' => $manager->employee_id];
        $service = app(RefundService::class);
        $first = $service->refund($sale, [], 'changed_mind', null, $approval, $options);
        $second = $service->refund($sale, [], 'changed_mind', null, $approval, $options);
        $this->assertSame($first->refund_id, $second->refund_id);
        $this->assertEquals(1000, $drawer->fresh()->expected_cash);
        $this->assertDatabaseCount('sale_refund', 1);
        $this->assertEquals(5, $product->fresh()->stockQuantity());
    }
}
