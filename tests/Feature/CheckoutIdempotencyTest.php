<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\PendingCardVerification;
use App\Models\PendingEwalletVerification;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutIdempotencyTest extends TestCase
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

    private function stockedProduct(string $name, float $stock = 10): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'barcode' => Product::generateBarcode(),
            'unit_price' => 25.00,
            'cost_price' => 12.00,
            'reorder_level' => 1,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    private function checkoutData(Product $product, string $paymentMethod, string $key): array
    {
        $data = [
            'idempotency_key' => $key,
            'payment_method' => $paymentMethod,
            'amount_paid' => 50.00,
            'register_id' => 'REG 01',
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
            ],
        ];

        if ($paymentMethod === 'card') {
            $data['payment_provider'] = 'Visa';
            $data['reference_number'] = 'APPROVAL-'.Str::upper(Str::random(8));
        }

        if ($paymentMethod === 'e-wallet') {
            $data['payment_provider'] = 'GCash';
            $data['reference_number'] = 'GCASH-'.Str::upper(Str::random(8));
        }

        return $data;
    }

    public function test_cash_retries_return_original_receipt_without_repeating_sale_effects(): void
    {
        $cashier = $this->employee('cashier');

        foreach (['cash'] as $method) {
            $product = $this->stockedProduct(ucfirst($method).' retry item');
            $key = (string) Str::uuid();
            $data = $this->checkoutData($product, $method, $key);
            $salesBefore = SaleTransaction::query()->count();
            $receiptsBefore = Receipt::query()->count();

            $firstResponse = $this->actingAs($cashier)->post(route('pos.store'), $data);
            $sale = SaleTransaction::query()->where('checkout_idempotency_key', $key)->firstOrFail();
            $receiptUrl = route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]);
            $firstResponse->assertRedirect($receiptUrl);

            $this->actingAs($cashier)
                ->post(route('pos.store'), $data)
                ->assertRedirect($receiptUrl)
                ->assertSessionHas('status', 'This checkout was already completed. Showing its original receipt.');

            $this->assertSame($salesBefore + 1, SaleTransaction::query()->count());
            $this->assertSame($receiptsBefore + 1, Receipt::query()->count());
            $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
            $this->assertSame(1, Receipt::query()->where('transaction_id', $sale->transaction_id)->count());
        }
    }

    public function test_card_retry_reuses_pending_review_then_returns_the_completed_receipt(): void
    {
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');
        $product = $this->stockedProduct('Card retry item');
        $key = (string) Str::uuid();
        $data = $this->checkoutData($product, 'card', $key);
        $salesBefore = SaleTransaction::query()->count();
        $receiptsBefore = Receipt::query()->count();

        $firstResponse = $this->actingAs($cashier)->post(route('pos.store'), $data);
        $pending = PendingCardVerification::query()->where('checkout_idempotency_key', $key)->firstOrFail();
        $pendingUrl = route('pos.pending-card.show', $pending);
        $firstResponse->assertRedirect($pendingUrl);

        $this->actingAs($cashier)
            ->post(route('pos.store'), $data)
            ->assertRedirect($pendingUrl)
            ->assertSessionHas('status', 'This Card payment is already awaiting manager verification.');

        $this->assertSame($salesBefore, SaleTransaction::query()->count());
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));

        $this->actingAs($manager)
            ->post(route('pos.pending-card.verify', $pending), ['terminal_checked' => '1'])
            ->assertRedirect();
        $sale = SaleTransaction::query()->where('checkout_idempotency_key', $key)->firstOrFail();
        $receiptUrl = route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.store'), $data)
            ->assertRedirect($receiptUrl)
            ->assertSessionHas('status', 'This checkout was already completed. Showing its original receipt.');

        $this->assertSame($salesBefore + 1, SaleTransaction::query()->count());
        $this->assertSame($receiptsBefore + 1, Receipt::query()->count());
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
    }

    public function test_repeated_ewallet_submission_and_verification_reuse_the_pending_request_and_sale(): void
    {
        $cashier = $this->employee('cashier');
        $manager = $this->employee('manager');
        $product = $this->stockedProduct('E-wallet retry item');
        $key = (string) Str::uuid();
        $data = $this->checkoutData($product, 'e-wallet', $key);
        $salesBefore = SaleTransaction::query()->count();
        $receiptsBefore = Receipt::query()->count();

        $firstResponse = $this->actingAs($cashier)->post(route('pos.store'), $data);
        $pending = PendingEwalletVerification::query()->where('checkout_idempotency_key', $key)->firstOrFail();
        $pendingUrl = route('pos.pending-ewallet.show', $pending);
        $firstResponse->assertRedirect($pendingUrl);

        $this->actingAs($cashier)
            ->post(route('pos.store'), $data)
            ->assertRedirect($pendingUrl);

        $this->assertSame(1, PendingEwalletVerification::query()->where('checkout_idempotency_key', $key)->count());
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame($salesBefore, SaleTransaction::query()->count());

        $verifyUrl = route('pos.pending-ewallet.verify', $pending);
        $receiptResponse = $this->actingAs($manager)
            ->post($verifyUrl, ['merchant_checked' => '1']);
        $sale = SaleTransaction::query()->where('checkout_idempotency_key', $key)->firstOrFail();
        $receiptUrl = route('pos.show', ['saleTransaction' => $sale, 'completed' => 1]);
        $receiptResponse->assertRedirect($receiptUrl);

        $this->actingAs($manager)
            ->post($verifyUrl, ['merchant_checked' => '1'])
            ->assertRedirect($receiptUrl);

        $this->assertSame($salesBefore + 1, SaleTransaction::query()->count());
        $this->assertSame($receiptsBefore + 1, Receipt::query()->count());
        $this->assertSame(8.0, (float) Inventory::query()->where('product_id', $product->product_id)->value('stock_quantity'));
        $this->assertSame((int) $sale->transaction_id, (int) $pending->fresh()->sale_transaction_id);
    }

    public function test_pos_keeps_a_stable_checkout_key_and_reloads_a_back_forward_cached_checkout(): void
    {
        $this->actingAs($this->employee('cashier'))
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('id="store-idempotency-key"', false)
            ->assertSee('checkoutSubmissionStarted', false)
            ->assertSee('crypto.randomUUID()', false)
            ->assertSee("window.addEventListener('pageshow'", false)
            ->assertSee('window.history.replaceState', false);
    }

    public function test_pos_cart_additions_follow_latest_row_without_interrupting_manual_scroll(): void
    {
        $this->actingAs($this->employee('cashier'))
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('.pos-cart-row-highlight > td', false)
            ->assertSee('cartScrollContainer.scrollHeight - cartScrollContainer.clientHeight - cartScrollContainer.scrollTop <= 32', false)
            ->assertSee('revealHighlightedIfFollowing && wasFollowingLatest', false)
            ->assertSee("cartScrollContainer.scrollTo({", false)
            ->assertSee("behavior: 'smooth'", false)
            ->assertSee("highlightedRow.classList.add('pos-cart-row-highlight')", false);
    }
}
