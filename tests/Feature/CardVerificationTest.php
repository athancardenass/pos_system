<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\PendingCardVerification;
use App\Models\Product;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CardVerificationTest extends TestCase
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

    private function stockedProduct(): Product
    {
        $product = Product::query()->create([
            'product_name' => 'Card verification item',
            'barcode' => '4006381333931',
            'unit_price' => 25.00,
            'cost_price' => 12.00,
            'reorder_level' => 1,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        return $product;
    }

    private function requestData(Product $product, string $approval = 'TERM-APPROVAL-8842', string $last4 = '4242'): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => 'card',
            'payment_provider' => 'Visa',
            'reference_number' => $approval,
            'card_last4' => $last4,
            'amount_paid' => '50.00',
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
        ];
    }

    public function test_card_waits_for_manager_review_encrypts_reference_and_masks_it_everywhere(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');
        $approval = 'TERM-APPROVAL-8842';
        $salesBefore = SaleTransaction::query()->count();
        $this->actingAs($cashier)->post(route('pos.store'), $this->requestData($product, $approval));

        $pending = PendingCardVerification::query()->firstOrFail();
        $this->assertSame(PendingCardVerification::STATUS_PENDING, $pending->status);
        $this->assertSame('4242', $pending->card_last4);
        $this->assertStringNotContainsString($approval, $pending->reference_ciphertext);
        $this->assertArrayNotHasKey('reference_number', $pending->checkout_payload);
        $this->assertArrayNotHasKey('card_last4', $pending->checkout_payload);
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());

        $maskedCard = $pending->maskedReference();
        $this->actingAs($cashier)
            ->get(route('pos.pending-card.index'))
            ->assertOk()
            ->assertSee($maskedCard)
            ->assertDontSee($approval);
        $this->actingAs($cashier)
            ->get(route('pos.pending-card.show', $pending))
            ->assertOk()
            ->assertSee($maskedCard)
            ->assertDontSee($approval)
            ->assertDontSee('data-url="'.route('pos.pending-card.reveal', $pending).'"', false);
        $this->actingAs($cashier)
            ->post(route('pos.pending-card.reveal', $pending))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('pos.pending-card.show', $pending))
            ->assertOk()
            ->assertSee($maskedCard)
            ->assertSee($pending->maskedApprovalCode())
            ->assertDontSee($approval)
            ->assertSee('data-reference-reveal', false)
            ->assertSee('10000', false);
        $this->actingAs($manager)
            ->post(route('pos.pending-card.reveal', $pending))
            ->assertOk()
            ->assertExactJson(['reference' => $approval]);

        $revealLog = AuditLog::query()->where('action', 'payment_reference_revealed')->latest('log_id')->firstOrFail();
        $this->assertSame('card', data_get($revealLog->details, 'payment_method'));
        $this->assertStringNotContainsString($approval, (string) $revealLog->description);
        $this->assertStringNotContainsString($approval, json_encode($revealLog->details));

        $this->actingAs($cashier)
            ->post(route('pos.pending-card.verify', $pending), ['terminal_checked' => '1'])
            ->assertForbidden();
        $this->actingAs($manager)
            ->post(route('pos.pending-card.verify', $pending), [])
            ->assertSessionHasErrors('terminal_checked');
        $this->actingAs($manager)
            ->post(route('pos.pending-card.verify', $pending), ['terminal_checked' => '1'])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $sale->load('payment');
        $this->assertSame('card', $sale->payment->payment_method);
        $this->assertSame($approval, $sale->payment->revealedReference());
        $this->assertSame('4242', $sale->payment->card_last4);
        $this->assertNull($sale->payment->reference_number);
        $this->assertNotNull($sale->payment->reference_fingerprint);

        $this->actingAs($manager)
            ->get(route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]))
            ->assertOk()
            ->assertSee($maskedCard)
            ->assertDontSee($approval);
        $this->actingAs($manager)
            ->get(route('reports.export', ['type' => 'sales']))
            ->assertOk()
            ->assertDontSee($approval);
    }

    public function test_duplicate_card_approval_code_is_rejected_even_when_last_four_differs(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $approval = 'TERM-APPROVAL-8842';
        $salesBefore = SaleTransaction::query()->count();

        $this->actingAs($cashier)
            ->post(route('pos.store'), $this->requestData($product, $approval, '4242'))
            ->assertRedirect();
        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.store'), $this->requestData($product, $approval, '9191'))
            ->assertSessionHasErrors('reference_number');

        $this->assertSame(1, PendingCardVerification::query()->count());
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());
    }

    public function test_card_rejection_releases_reserved_inventory_and_logs_no_approval_code(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');
        $approval = 'TERM-APPROVAL-REJECT';
        $salesBefore = SaleTransaction::query()->count();
        $this->actingAs($cashier)->post(route('pos.store'), $this->requestData($product, $approval));
        $pending = PendingCardVerification::query()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('pos.pending-card.reject', $pending), ['reason' => 'Approval '.$approval.' not found on terminal'])
            ->assertRedirect(route('pos.pending-card.show', $pending));

        $this->assertSame(PendingCardVerification::STATUS_REJECTED, $pending->fresh()->status);
        $this->assertSame(10.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());
        $auditLog = AuditLog::query()->where('action', 'card_payment_rejected')->firstOrFail();
        $this->assertStringNotContainsString($approval, (string) $auditLog->description);
        $this->assertStringNotContainsString($approval, (string) $pending->fresh()->resolution_note);
    }

    public function test_pos_input_visibility_toggle_and_legacy_audit_output_are_masked(): void
    {
        $manager = $this->employee('manager');
        $rawReference = 'LEGACY-WALLET-9912';
        AuditLog::query()->create([
            'employee_id' => $manager->employee_id,
            'action' => 'payment_pending',
            'table_affected' => 'pending_ewallet_verifications',
            'record_id' => 999,
            'action_timestamp' => now(),
            'description' => 'E-wallet payment GCash / '.$rawReference.' submitted for manager verification',
        ]);

        $this->actingAs($this->employee('cashier'))
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('data-payment-visibility-toggle', false)
            ->assertSee('maxlength="100"', false);

        $this->actingAs($manager)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Payment reference submitted for manager verification.')
            ->assertDontSee($rawReference);
    }
}
