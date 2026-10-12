<?php

namespace Tests\Feature;

use App\Models\CashDrawer;
use App\Models\Employee;
use App\Models\Product;
use App\Services\CardVerificationService;
use App\Services\CashDrawerService;
use App\Services\ShiftReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
    }

    public function test_cash_movements_reconcile_without_counting_non_cash(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 1000);
        $service->record($drawer, ['kind' => 'sale', 'amount' => 500, 'payment_method' => 'cash', 'operation_key' => 'sale:1']);
        $service->record($drawer, ['kind' => 'sale', 'amount' => 700, 'payment_method' => 'card', 'operation_key' => 'sale:2']);
        $service->record($drawer, ['kind' => 'manual_in', 'amount' => 200, 'payment_method' => 'cash', 'operation_key' => 'manual:1', 'reason' => 'Change fund']);
        $service->record($drawer, ['kind' => 'refund', 'amount' => -100, 'payment_method' => 'cash', 'operation_key' => 'refund:1']);
        $service->record($drawer, ['kind' => 'manual_out', 'amount' => -50, 'payment_method' => 'cash', 'operation_key' => 'manual:2', 'reason' => 'Bank pickup']);
        $report = app(ShiftReportService::class)->summary($drawer->fresh());
        $this->assertEquals(1550, $drawer->fresh()->expected_cash);
        $this->assertEquals(700, $report['cash_in']);
        $this->assertEquals(150, $report['cash_out']);
        $this->assertEquals(700, $report['payment_totals']['card']);
    }

    public function test_duplicate_opening_does_not_silently_close_the_existing_shift(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 1000);
        try {
            $service->openDrawer(auth()->id(), 10);
            $this->fail('Duplicate opening accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('drawer', $e->errors());
        }
        $this->assertSame('open', $drawer->fresh()->status);
        $this->assertSame(1, CashDrawer::where('status', 'open')->count());
    }

    public function test_report_snapshot_stays_fixed_after_later_activity(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 1000);
        $service->record($drawer, ['kind' => 'manual_in', 'amount' => 100, 'payment_method' => 'cash', 'operation_key' => 'manual:1', 'reason' => 'Float']);
        $closed = $service->closeDrawer(auth()->id(), 1100);
        $this->assertEquals(1100, $closed->report_snapshot['expected_cash']);
        $service->openDrawer(auth()->id(), 50);
        $this->assertSame($closed->report_snapshot, app(ShiftReportService::class)->summary($closed->fresh()));
        $this->assertNotEmpty($closed->report_snapshot['closed_at']);
    }

    public function test_shortage_requires_an_explanation_and_stale_count_is_rejected(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 1000);
        try {
            $service->closeDrawer(auth()->id(), 900);
            $this->fail('Missing discrepancy explanation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation_notes', $e->errors());
        }
        try {
            $service->closeDrawer(auth()->id(), 900, 'Counted twice', 800);
            $this->fail('Stale expected accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('expected_cash', $e->errors());
        }
        $this->assertSame('open', $drawer->fresh()->status);
        $this->assertEquals(-100, $service->closeDrawer(auth()->id(), 900, 'Counted twice', 1000)->difference);
    }

    public function test_repeated_ledger_key_does_not_duplicate_cash(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 100);
        $entry = ['kind' => 'manual_in', 'amount' => 50, 'payment_method' => 'cash', 'operation_key' => 'same-key', 'reason' => 'Float'];
        $service->record($drawer, $entry);
        $service->record($drawer, $entry);
        $this->assertEquals(150, $drawer->fresh()->expected_cash);
        $this->assertDatabaseCount('drawer_movements', 1);
        $this->expectException(ValidationException::class);
        $service->record($drawer, array_replace($entry, ['amount' => 60]));
    }

    public function test_closed_shift_cannot_start_a_new_pending_payment(): void
    {
        $drawers = app(CashDrawerService::class);
        $drawers->openDrawer(auth()->id(), 1000);
        $drawers->closeDrawer(auth()->id(), 1000);
        $product = Product::whereHas('inventory', fn ($q) => $q->where('stock_quantity', '>', 0))->firstOrFail();
        $stock = $product->stockQuantity();
        try {
            app(CardVerificationService::class)->submit(['items' => [['product_id' => $product->product_id, 'quantity' => 1]], 'amount_paid' => $product->unit_price, 'payment_method' => 'card', 'payment_provider' => 'Visa', 'reference_number' => 'test-closed-shift', 'register_id' => 'REG 01'], auth()->id());
            $this->fail('Pending payment accepted after closure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('drawer', $e->errors());
        }
        $this->assertEquals($stock, $product->fresh()->stockQuantity());
        $this->assertDatabaseCount('pending_card_verifications', 0);
    }
}
