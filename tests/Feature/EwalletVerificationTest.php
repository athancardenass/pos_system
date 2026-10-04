<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\AuditLog;
use App\Models\PendingEwalletVerification;
use App\Models\Product;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EwalletVerificationTest extends TestCase
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
            'product_name' => 'Verification Item',
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

    private function requestData(Product $product, string $reference = 'GCASH-REF-10001'): array
    {
        return [
            'payment_method' => 'e-wallet',
            'payment_provider' => 'GCash',
            'reference_number' => $reference,
            'amount_paid' => '50.00',
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
            ],
        ];
    }

    public function test_ewallet_request_reserves_stock_and_waits_for_manager_verification(): void
    {
        $product = $this->stockedProduct();
        $salesBefore = SaleTransaction::query()->count();

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), $this->requestData($product));

        $pending = PendingEwalletVerification::query()->firstOrFail();
        $response->assertRedirect(route('pos.pending-ewallet.show', $pending));
        $this->assertSame(PendingEwalletVerification::STATUS_PENDING, $pending->status);
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());
        $this->assertDatabaseMissing('payment', ['reference_number' => 'GCASH-REF-10001']);
        $this->assertSame('', $pending->reference_number);
        $this->assertStringNotContainsString('GCASH-REF-10001', $pending->reference_ciphertext);
        $this->assertArrayNotHasKey('reference_number', $pending->checkout_payload);
        $this->assertNull($pending->sale_transaction_id);
    }

    public function test_cashier_sees_own_pending_queue_and_manager_sees_all_requests(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');

        $this->actingAs($cashier)
            ->get(route('pos.pending-ewallet.index'))
            ->assertOk()
            ->assertSee('No pending e-wallet payments');

        $this->actingAs($cashier)->post(route('pos.store'), $this->requestData($product));
        $pending = PendingEwalletVerification::query()->firstOrFail();
        $otherCashier = Employee::query()->create([
            'role_id' => $cashier->role_id,
            'first_name' => 'Second',
            'last_name' => 'Cashier',
            'username' => 'cashier-two',
            'password' => 'password',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $this->actingAs($otherCashier)->post(
            route('pos.store'),
            $this->requestData($product, 'GCASH-REF-20002'),
        );

        $this->actingAs($cashier)
            ->get(route('pos.pending-ewallet.index'))
            ->assertOk()
            ->assertSee($pending->maskedReference())
            ->assertDontSee('GCASH-REF-10001')
            ->assertDontSee('GCASH-REF-20002');
        $this->actingAs($cashier)
            ->get(route('pos.pending-ewallet.show', $pending))
            ->assertOk()
            ->assertSee($pending->maskedReference())
            ->assertDontSee('GCASH-REF-10001')
            ->assertDontSee('data-url="'.route('pos.pending-ewallet.reveal', $pending).'"', false)
            ->assertDontSee('action="'.route('pos.pending-ewallet.verify', $pending).'"', false)
            ->assertDontSee('action="'.route('pos.pending-ewallet.reject', $pending).'"', false);
        $this->actingAs($cashier)
            ->post(route('pos.pending-ewallet.reveal', $pending))
            ->assertForbidden();
        $cashierPos = $this->actingAs($cashier)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Held')
            ->assertSee('Pending E-Wallet')
            ->assertSee('No held transactions')
            ->assertSee('has-pending');
        $cashierMarkup = $cashierPos->getContent();
        $heldOffset = strpos($cashierMarkup, '>Held</span>');
        $walletOffset = strpos($cashierMarkup, '>Pending E-Wallet</span>');
        $shiftOffset = strpos($cashierMarkup, 'id="drawer-toggle-label"');
        $this->assertNotFalse($heldOffset);
        $this->assertNotFalse($walletOffset);
        $this->assertNotFalse($shiftOffset);
        $this->assertLessThan($walletOffset, $heldOffset);
        $this->assertLessThan($shiftOffset, $walletOffset);
        $this->assertStringContainsString('.pos-header-action:hover, .pos-header-action:focus-visible {', $cashierMarkup);
        $this->assertStringContainsString('text-decoration: none;', $cashierMarkup);
        $this->assertStringContainsString("remove.className = 'btn btn-danger';", $cashierMarkup);
        $this->actingAs($manager)
            ->get(route('pos.pending-ewallet.index'))
            ->assertOk()
            ->assertSee($pending->maskedReference())
            ->assertDontSee('GCASH-REF-10001')
            ->assertDontSee('GCASH-REF-20002');
        $this->actingAs($manager)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Pending E-Wallet');
    }

    public function test_manager_must_confirm_merchant_app_payment_before_sale_is_completed(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');

        $this->actingAs($cashier)->post(route('pos.store'), $this->requestData($product));
        $pending = PendingEwalletVerification::query()->firstOrFail();
        $salesBefore = SaleTransaction::query()->count();

        $this->actingAs($manager)
            ->get(route('pos.pending-ewallet.show', $pending))
            ->assertOk()
            ->assertSee('Amount to verify')
            ->assertSee('Provider')
            ->assertSee('Reference number')
            ->assertSee('Cart summary')
            ->assertSee('data-ewallet-primary-action', false)
            ->assertSee('Verify and complete sale')
            ->assertSee('Enter')
            ->assertSee('primaryAction.focus({ preventScroll: true })', false)
            ->assertSee('if (submissionStarted)', false);

        $this->actingAs($manager)
            ->post(route('pos.pending-ewallet.reveal', $pending))
            ->assertOk()
            ->assertExactJson(['reference' => 'GCASH-REF-10001']);
        $revealLog = AuditLog::query()->where('action', 'payment_reference_revealed')->latest('log_id')->firstOrFail();
        $this->assertSame('pending_ewallet_verifications', $revealLog->table_affected);
        $this->assertStringNotContainsString('GCASH-REF-10001', (string) $revealLog->description);
        $this->assertStringNotContainsString('GCASH-REF-10001', json_encode($revealLog->details));

        $this->actingAs($cashier)
            ->post(route('pos.pending-ewallet.verify', $pending), ['merchant_checked' => '1'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('pos.pending-ewallet.verify', $pending), [])
            ->assertSessionHasErrors('merchant_checked');
        $this->assertSame($salesBefore, SaleTransaction::query()->count());

        $response = $this->actingAs($manager)
            ->post(route('pos.pending-ewallet.verify', $pending), ['merchant_checked' => '1']);

        $pending->refresh();
        $sale = SaleTransaction::query()->findOrFail($pending->sale_transaction_id);
        $response->assertRedirect(route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]));
        $this->assertSame(PendingEwalletVerification::STATUS_VERIFIED, $pending->status);
        $this->assertSame($manager->employee_id, $pending->verified_by_employee_id);
        $this->assertNotNull($pending->verified_at);
        $this->assertSame('completed', $sale->status);
        $this->assertSame('e-wallet', $sale->payment_method);
        $this->assertSame('GCASH-REF-10001', $sale->payment->revealedReference());
        $this->assertNull($sale->payment->reference_number);
        $receipt = $this->actingAs($manager)
            ->get(route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]))
            ->assertOk()
            ->assertSee($sale->payment->maskedReference())
            ->assertDontSee('GCASH-REF-10001');
        $this->assertSame('GCash', $sale->payment->payment_provider);
        $this->assertNotNull($sale->receipt);
        $this->actingAs($manager)
            ->get(route('pos.pending-ewallet.show', $pending))
            ->assertOk()
            ->assertSee('Payment verified')
            ->assertSee($sale->receipt->receipt_number)
            ->assertSee('Next sale');
        $this->actingAs($manager)
            ->get(route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]))
            ->assertOk()
            ->assertSee('Sale complete')
            ->assertSee($sale->receipt->receipt_number)
            ->assertSee('Next sale')
            ->assertSee('data-ewallet-next-sale', false)
            ->assertSee("document.querySelector('[data-ewallet-next-sale]')?.focus", false);
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
    }

    public function test_manager_rejection_releases_reserved_inventory_and_records_reason(): void
    {
        $product = $this->stockedProduct();
        $manager = $this->employee('manager');

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), $this->requestData($product));
        $pending = PendingEwalletVerification::query()->firstOrFail();
        $salesBefore = SaleTransaction::query()->count();

        $this->actingAs($manager)
            ->post(route('pos.pending-ewallet.reject', $pending), ['reason' => 'Reference GCASH-REF-10001 not found in merchant app'])
            ->assertRedirect(route('pos.pending-ewallet.show', $pending));

        $pending->refresh();
        $this->assertSame(PendingEwalletVerification::STATUS_REJECTED, $pending->status);
        $this->assertSame($manager->employee_id, $pending->rejected_by_employee_id);
        $this->assertStringNotContainsString('GCASH-REF-10001', (string) $pending->resolution_note);
        $this->assertSame(10.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());
        $this->actingAs($manager)
            ->get(route('pos.pending-ewallet.show', $pending))
            ->assertOk()
            ->assertSee('Payment rejected')
            ->assertSee('Back to POS')
            ->assertSee('data-ewallet-return-to-pos', false)
            ->assertSee('Enter')
            ->assertSee('window.location.assign(returnToPos.href)', false)
            ->assertSee('if (returnNavigationStarted) return;', false);
    }

    public function test_ewallet_reference_cannot_be_submitted_twice(): void
    {
        $product = $this->stockedProduct();
        $cashier = $this->employee('cashier');
        $data = $this->requestData($product);

        $this->actingAs($cashier)->post(route('pos.store'), $data)->assertRedirect();
        $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.store'), $data)
            ->assertSessionHasErrors('reference_number');

        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame(1, PendingEwalletVerification::query()->count());
    }

    public function test_expired_request_releases_reserved_inventory(): void
    {
        $product = $this->stockedProduct();

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), $this->requestData($product));
        $pending = PendingEwalletVerification::query()->firstOrFail();
        $pending->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->employee('manager'))
            ->get(route('pos.pending-ewallet.index'))
            ->assertOk();

        $this->assertSame(PendingEwalletVerification::STATUS_EXPIRED, $pending->fresh()->status);
        $this->assertSame(10.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
    }
}
