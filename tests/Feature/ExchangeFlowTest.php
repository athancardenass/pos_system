<?php

namespace Tests\Feature;

use App\Models\CustomerOutboxEvent;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\CashDrawerService;
use App\Services\CheckoutService;
use App\Services\ExchangeService;
use App\Services\RefundService;
use App\Services\ShiftReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExchangeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cheaper_exchange_requires_cash_for_the_difference_only(): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture(70);
        app(CashDrawerService::class)->record($drawer, ['kind' => 'manual_out', 'payment_method' => 'cash', 'amount' => -1080, 'reason' => 'Cash pickup', 'operation_key' => 'fixture-pickup']);
        $this->assertEquals(20, $drawer->fresh()->expected_cash);
        try {
            app(ExchangeService::class)->exchange($sale, $data, ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id]);
            $this->fail('Unfunded exchange payout accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('drawer', $e->errors());
        }
        $this->assertEquals(20, $drawer->fresh()->expected_cash);
        $this->assertDatabaseCount('sale_exchanges', 0);
        $this->assertEquals(4, $original->fresh()->stockQuantity());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public static function prices(): array
    {
        return [[100, 0, 1100], [150, 50, 1150], [70, -30, 1070]];
    }

    private function fixture(float $price, ?int $customerId = null): array
    {
        $employee = Employee::where('username', 'manager')->firstOrFail();
        $this->actingAs($employee);
        $drawer = app(CashDrawerService::class)->openDrawer($employee->employee_id, 1000);
        $original = $this->product('Original', 100);
        $replacement = $this->product('Replacement', $price);
        $sale = app(CheckoutService::class)->checkout(['customer_id' => $customerId, 'items' => [['product_id' => $original->product_id, 'quantity' => 1]], 'payment_method' => 'cash', 'amount_paid' => 100], $employee->employee_id);
        $line = $sale->saleDetails()->first();
        $data = ['items' => [$line->sale_detail_id => 1], 'replacements' => [['product_id' => $replacement->product_id, 'quantity' => 1]], 'reason' => 'wrong_item', 'condition' => 'resellable', 'operation_key' => (string) Str::uuid(), 'settlement_confirmed' => true];

        return [$employee, $drawer, $original, $replacement, $sale, $data];
    }

    private function product(string $name, float $price): Product
    {
        $p = Product::create(['product_name' => $name, 'barcode' => Product::generateBarcode(), 'unit_price' => $price, 'cost_price' => 1, 'reorder_level' => 0]);
        Inventory::create(['product_id' => $p->product_id, 'stock_quantity' => 5]);

        return $p;
    }

    #[DataProvider('prices')]
    public function test_exchange_counts_only_actual_difference(float $price, float $difference, float $expected): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture($price);
        $exchange = app(ExchangeService::class)->exchange($sale, $data, ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id]);
        $this->assertEquals($difference, $exchange->difference);
        $this->assertEquals($expected, $drawer->fresh()->expected_cash);
        $this->assertEquals(5, $original->fresh()->stockQuantity());
        $this->assertEquals(4, $replacement->fresh()->stockQuantity());
        $this->assertDatabaseCount('drawer_movements', 2);
        $report = app(ShiftReportService::class)->summary($drawer->fresh());
        $this->assertEquals(100 + $price, $report['gross_sales']);
        $this->assertEquals(100, $report['refunds']);
        $this->assertEquals($price, $report['net_sales']);
        $repeat = app(ExchangeService::class)->exchange($sale, $data, ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id]);
        $this->assertSame($exchange->id, $repeat->id);
        $this->assertEquals($expected, $drawer->fresh()->expected_cash);
    }

    public function test_stock_failure_rolls_back_return_cash_and_outboxes(): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture(150);
        $replacement->inventory->update(['stock_quantity' => 0]);
        $saleCount = SaleTransaction::count();
        try {
            app(ExchangeService::class)->exchange($sale, $data, ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id]);
            $this->fail('Out-of-stock exchange accepted');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $this->assertEquals(4, $original->fresh()->stockQuantity());
        $this->assertEquals(1100, $drawer->fresh()->expected_cash);
        $this->assertDatabaseCount('sale_refund', 0);
        $this->assertDatabaseCount('sale_exchanges', 0);
        $this->assertDatabaseCount('sale_transaction', $saleCount);
        $this->assertDatabaseCount('inventory_outbox_event', 1);
    }

    public function test_retry_with_changed_replacement_is_rejected(): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture(100);
        $approval = ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id];
        app(ExchangeService::class)->exchange($sale, $data, $approval);
        $data['replacements'][0]['quantity'] = 2;
        $this->expectException(ValidationException::class);
        app(ExchangeService::class)->exchange($sale, $data, $approval);
    }

    public function test_refunding_equal_price_replacement_reverses_original_loyalty_basis(): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture(100, 987654);
        $approval = ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id];
        $exchange = app(ExchangeService::class)->exchange($sale, $data, $approval);
        $this->assertDatabaseCount('customer_outbox_event', 1);
        app(RefundService::class)->refund($exchange->replacement, [], 'changed_mind', null, $approval, ['condition' => 'resellable', 'operation_key' => (string) Str::uuid()]);
        $this->assertDatabaseHas('customer_outbox_event', ['event_type' => 'sale.refunded', 'sale_transaction_id' => $sale->transaction_id]);
        $this->assertDatabaseMissing('customer_outbox_event', ['event_type' => 'sale.refunded', 'sale_transaction_id' => $exchange->replacement_sale_id]);
    }

    public function test_refunding_upgraded_replacement_reverses_both_original_credit_and_new_spend(): void
    {
        [$employee,$drawer,$original,$replacement,$sale,$data] = $this->fixture(150, 987654);
        $approval = ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id];
        $exchange = app(ExchangeService::class)->exchange($sale, $data, $approval);
        app(RefundService::class)->refund($exchange->replacement, [], 'changed_mind', null, $approval, ['condition' => 'resellable', 'operation_key' => (string) Str::uuid()]);
        $events = CustomerOutboxEvent::where('event_type', 'sale.refunded')->get();
        $this->assertCount(2, $events);
        $this->assertEquals(100, $events->firstWhere('sale_transaction_id', $sale->transaction_id)->payload['amount']);
        $this->assertEquals(50, $events->firstWhere('sale_transaction_id', $exchange->replacement_sale_id)->payload['amount']);
    }

    public function test_full_discounted_multiline_exchange_uses_every_paid_cent(): void
    {
        $employee = Employee::where('username', 'manager')->firstOrFail();
        $this->actingAs($employee);
        app(CashDrawerService::class)->openDrawer($employee->employee_id, 1000);
        $products = [$this->product('A', 50), $this->product('B', 50), $this->product('C', 50)];
        $replacement = $this->product('Replacement', 100);
        $discount = Discount::create(['discount_name' => 'Fixed 50', 'discount_type' => 'fixed', 'discount_value' => 50]);
        $sale = app(CheckoutService::class)->checkout(['discount_id' => $discount->discount_id, 'items' => array_map(fn ($p) => ['product_id' => $p->product_id, 'quantity' => 1], $products), 'payment_method' => 'cash', 'amount_paid' => 100], $employee->employee_id);
        $data = ['items' => $sale->saleDetails->pluck('quantity', 'sale_detail_id')->all(), 'replacements' => [['product_id' => $replacement->product_id, 'quantity' => 1]], 'reason' => 'wrong_item', 'condition' => 'resellable', 'operation_key' => (string) Str::uuid(), 'settlement_confirmed' => true];
        $quote = app(ExchangeService::class)->preview($sale, $data);
        $this->assertEquals(100, $quote['returned_value']);
        $this->assertEquals(0, $quote['difference']);
        $exchange = app(ExchangeService::class)->exchange($sale, $data, ['requested_by_employee_id' => $employee->employee_id, 'approved_by_employee_id' => $employee->employee_id]);
        $this->assertEquals(100, $exchange->refund->refund_amount);
        $this->assertEquals(0, $exchange->difference);
    }
}
