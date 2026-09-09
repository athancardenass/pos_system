<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SalePromotion;
use App\Models\SaleTransaction;
use App\Services\PromotionService;
use App\Services\RefundService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end promotion-engine tests: what actually happens at the register.
 *
 * The engine is only observable through a checkout, so every test here posts a real
 * cart and asserts the stored sale, the audit rows, stock, and (where relevant) the
 * rendered receipt.
 */
class PromotionEngineTest extends TestCase
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

    private function product(string $name, float $price, float $stock = 50, ?Category $category = null): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'category_id' => $category?->category_id,
            'barcode' => Product::generateBarcode(),
            'unit_price' => $price,
            'cost_price' => round($price / 2, 2),
            'reorder_level' => 5,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    private function lastSale(): SaleTransaction
    {
        return SaleTransaction::query()->latest('transaction_id')->firstOrFail();
    }

    public function test_checkout_applies_an_active_promotion_and_records_the_audit_row(): void
    {
        $product = $this->product('Bundle Cola', 100.00);

        $promotion = Promotion::query()->create([
            'name' => 'Opening 10 percent', 'type' => 'percentage', 'scope' => 'product',
            'scope_id' => $product->product_id, 'value' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 90.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = $this->lastSale();

        // What is stored is the ORIGINAL price times qty, plus a separate promo_discount:
        // the engine never rewrites unit prices.
        $this->assertEquals(100.00, $sale->subtotal);
        $this->assertEquals(10.00, $sale->promo_discount);
        $this->assertEquals(0.0, $sale->coupon_discount);
        $this->assertEquals(90.00, $sale->total_amount);
        $this->assertEquals(100.00, (float) $sale->saleDetails()->first()->unit_price);

        $audit = $sale->appliedPromotions()->firstOrFail();
        $this->assertEquals($promotion->promotion_id, $audit->promotion_id);
        $this->assertEquals(10.00, $audit->amount_discounted);
        $this->assertEquals('Opening 10 percent', $audit->snapshot['name']);
        $this->assertEquals('percentage', $audit->snapshot['type']);
    }

    public function test_promotion_snapshot_survives_later_edits_to_the_rule(): void
    {
        $product = $this->product('Snapshot Soda', 100.00);
        $promotion = Promotion::query()->create([
            'name' => 'Old name', 'type' => 'percentage', 'scope' => 'cart', 'value' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ]);

        // A manager renames and re-values the rule after the sale...
        $promotion->update(['name' => 'Renamed', 'value' => 50]);

        $snapshot = $this->lastSale()->appliedPromotions()->firstOrFail()->snapshot;

        // ...the historical receipt must still read what was in force at the time.
        $this->assertEquals('Old name', $snapshot['name']);
        $this->assertEquals(10.0, $snapshot['value']);
    }

    public function test_checkout_stack_order_is_promotion_then_manual_then_coupon(): void
    {
        $product = $this->product('Stacking Item', 1000.00);

        Promotion::query()->create([
            'name' => 'Ten off everything', 'type' => 'percentage', 'scope' => 'cart', 'value' => 10, 'is_active' => true,
        ]);

        $discount = Discount::query()->create([
            'discount_name' => 'Half price', 'discount_type' => 'percentage', 'discount_value' => 50,
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(),
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'FLAT100', 'type' => 'fixed', 'value' => 100, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'discount_id' => $discount->discount_id,
                'coupon_code' => 'flat100',
                'payment_method' => 'cash',
                'amount_paid' => 350.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = $this->lastSale();

        // 1000 -> promo 100 -> manual 50% of 900 = 450 -> coupon 100 -> 350.
        $this->assertEquals(1000.00, $sale->subtotal);
        $this->assertEquals(100.00, $sale->promo_discount);
        $this->assertEquals(450.00, $sale->manualDiscountAmount());
        $this->assertEquals(100.00, $sale->coupon_discount);
        $this->assertEquals(350.00, $sale->total_amount);

        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('coupon_redemption', [
            'coupon_id' => $coupon->coupon_id,
            'transaction_id' => $sale->transaction_id,
            'amount_applied' => 100.00,
        ]);
    }

    public function test_manual_discount_still_works_alongside_the_engine(): void
    {
        $product = $this->product('Legacy Item', 100.00);

        $discount = Discount::query()->create([
            'discount_name' => 'Ten percent', 'discount_type' => 'percentage', 'discount_value' => 10,
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(),
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'discount_id' => $discount->discount_id,
            'payment_method' => 'cash',
            'amount_paid' => 90.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ]);

        $sale = $this->lastSale();

        // No promotions, no coupon: identical to the pre-engine behaviour.
        $this->assertEquals(0.0, $sale->promo_discount);
        $this->assertEquals(0.0, $sale->coupon_discount);
        $this->assertEquals(90.00, $sale->total_amount);
        $this->assertEquals(10.00, $sale->manualDiscountAmount());
        $this->assertEquals(0, SalePromotion::query()->count());
    }

    public function test_total_floors_at_zero_and_an_unspent_coupon_is_not_redeemed(): void
    {
        $product = $this->product('Freebie', 100.00);

        Promotion::query()->create([
            'name' => 'Everything free', 'type' => 'percentage', 'scope' => 'cart', 'value' => 100, 'is_active' => true,
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'TOOLATE', 'type' => 'fixed', 'value' => 50, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'coupon_code' => 'TOOLATE',
                'payment_method' => 'cash',
                'amount_paid' => 0.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = $this->lastSale();

        // The promotion already zeroed the cart, so the coupon had nothing to take —
        // it must not burn one of its uses.
        $this->assertEquals(100.00, $sale->promo_discount);
        $this->assertEquals(0.0, $sale->coupon_discount);
        $this->assertEquals(0.0, $sale->total_amount);
        $this->assertEquals(0, $coupon->fresh()->used_count);
        $this->assertEquals(0, $sale->couponRedemptions()->count());
    }

    public function test_invalid_coupon_blocks_the_sale_and_leaves_nothing_behind(): void
    {
        $product = $this->product('Guarded Item', 100.00);
        $coupon = Coupon::query()->create([
            'code' => 'USEDUP', 'type' => 'fixed', 'value' => 10, 'max_uses' => 1, 'is_active' => true,
        ]);
        Coupon::query()->where('coupon_id', $coupon->coupon_id)->update(['used_count' => 1]);

        $before = SaleTransaction::query()->count();

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'coupon_code' => 'USEDUP',
                'payment_method' => 'cash',
                'amount_paid' => 100.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('coupon_code');

        // Nothing was written: the whole checkout transaction rolled back.
        $this->assertEquals($before, SaleTransaction::query()->count());
        $this->assertEquals(50, $product->fresh()->inventory->stock_quantity);
        $this->assertEquals(0, SalePromotion::query()->count());
    }

    public function test_unknown_coupon_code_is_rejected_on_the_coupon_field(): void
    {
        $product = $this->product('Unknown Code Item', 100.00);
        $before = SaleTransaction::query()->count();

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'coupon_code' => 'GHOST',
                'payment_method' => 'cash',
                'amount_paid' => 100.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('coupon_code');

        $this->assertEquals($before, SaleTransaction::query()->count());
    }

    public function test_per_customer_limit_is_enforced_across_sales(): void
    {
        $product = $this->product('Loyal Cola', 100.00);
        $customer = Customer::query()->create([
            'first_name' => 'Nina', 'last_name' => 'Noon', 'customer_status' => 'active',
        ]);
        Coupon::query()->create([
            'code' => 'ONCE', 'type' => 'fixed', 'value' => 10, 'per_customer_limit' => 1, 'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $customer->customer_id,
            'coupon_code' => 'ONCE',
            'payment_method' => 'cash',
            'amount_paid' => 90.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ];

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), $payload)->assertRedirect();
        $this->assertEquals(90.00, $this->lastSale()->total_amount);

        // Same customer, second visit: rejected, and the first sale is untouched.
        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), $payload)
            ->assertSessionHasErrors('coupon_code');

        $this->assertEquals(1, SaleTransaction::query()->where('customer_id', $customer->customer_id)->count());
        $this->assertEquals(1, Coupon::query()->where('code', 'ONCE')->value('used_count'));
    }

    public function test_two_cashiers_cannot_both_consume_the_last_use_of_a_coupon(): void
    {
        $product = $this->product('Scarce Item', 100.00);
        $coupon = Coupon::query()->create([
            'code' => 'LASTONE', 'type' => 'fixed', 'value' => 10, 'max_uses' => 1, 'is_active' => true,
        ]);

        $posted = [];

        // A fresh service instance per request mirrors two separate processes: whatever
        // one cashier loaded, the other cannot see. On sqlite lockForUpdate() is a no-op,
        // so this run is effectively sequential — the point is that exactly ONE survives.
        foreach (['cashier', 'manager'] as $username) {
            $service = new PromotionService;
            $sale = SaleTransaction::query()->create([
                'employee_id' => $this->employee($username)->employee_id,
                'transaction_date' => now(),
                'subtotal' => 100,
                'total_amount' => 90,
                'payment_method' => 'cash',
            ]);

            $validated = null;

            try {
                $validated = $service->validateCoupon('LASTONE', 100.00, null);
                $service->redeemCoupon($validated, $sale, null, $service->couponDiscount($validated, 90.00));
                $posted[] = $sale->transaction_id;
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $posted[] = null;
            }
        }

        $this->assertSame(1, count(array_filter($posted)));
        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->assertEquals(1, \App\Models\CouponRedemption::query()->where('coupon_id', $coupon->coupon_id)->count());
    }

    public function test_expired_and_inactive_promotions_do_not_discount(): void
    {
        $product = $this->product('Plain Item', 100.00);

        Promotion::query()->create([
            'name' => 'Expired', 'type' => 'percentage', 'scope' => 'cart', 'value' => 50,
            'ends_at' => now()->subMinute(), 'is_active' => true,
        ]);
        Promotion::query()->create([
            'name' => 'Sleepy', 'type' => 'percentage', 'scope' => 'cart', 'value' => 50, 'is_active' => false,
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ]);

        $this->assertEquals(100.00, $this->lastSale()->total_amount);
        $this->assertEquals(0.0, $this->lastSale()->promo_discount);
    }

    public function test_stock_and_dashboard_are_unaffected_by_the_discount_stack(): void
    {
        $product = $this->product('Revenue Safe', 200.00);

        Promotion::query()->create([
            'name' => 'Quarter off', 'type' => 'percentage', 'scope' => 'cart', 'value' => 25, 'is_active' => true,
        ]);

        $before = $product->fresh()->inventory->stock_quantity;
        $revenueBefore = (float) SaleTransaction::query()->where('status', 'completed')->sum('total_amount');

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            // The cashier must hand over the DISCOUNTED total, not the list price.
            'amount_paid' => 450.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 3]],
        ]);

        // Stock moves on quantity, never on price.
        $this->assertEquals($before - 3, $product->fresh()->inventory->stock_quantity);

        $sale = $this->lastSale();
        $this->assertEquals(600.00, $sale->subtotal);
        $this->assertEquals(150.00, $sale->promo_discount);
        $this->assertEquals(450.00, $sale->total_amount);
        $this->assertEquals('completed', $sale->status);

        // Revenue reporting reads total_amount of completed sales, which is already net
        // of promotions and coupons. Measured as a delta so demo data cannot skew it.
        $revenueAfter = (float) SaleTransaction::query()->where('status', 'completed')->sum('total_amount');
        $this->assertEquals(450.00, round($revenueAfter - $revenueBefore, 2));
    }

    public function test_refund_pro_rates_against_the_discounted_total(): void
    {
        $product = $this->product('Refundable', 100.00);
        $stockAtStart = $product->fresh()->inventory->stock_quantity;

        Promotion::query()->create([
            'name' => 'Half off', 'type' => 'percentage', 'scope' => 'cart', 'value' => 50, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
        ]);

        $sale = $this->lastSale();
        $this->assertEquals(100.00, $sale->total_amount);
        // The sale took its units off the shelf first, as always.
        $this->assertEquals($stockAtStart - 2, $product->fresh()->inventory->stock_quantity);

        $refund = app(RefundService::class)->refund($sale, [], 'damaged', null);

        // The customer paid 100, so 100 comes back — not the 200 list price.
        $this->assertEquals(100.00, $refund->refund_amount);
        // A full refund puts every unit back on the shelf.
        $this->assertEquals($stockAtStart, $product->fresh()->inventory->stock_quantity);
        $this->assertEquals('refunded', $sale->fresh()->status);

        // The promotion audit rows are kept: sale_promotion is ON DELETE RESTRICT.
        $this->assertEquals(1, $sale->appliedPromotions()->count());
        $this->assertEquals(1, $sale->refunds()->count());
    }

    public function test_new_pages_follow_the_design_system(): void
    {
        $manager = $this->employee('manager');

        foreach (['promotions.create', 'promotions.index', 'coupons.create', 'coupons.index'] as $route) {
            $response = $this->actingAs($manager)->get(route($route));
            $response->assertOk();

            $html = $response->getContent();

            // Boxed flat inputs, grid wrappers, no inline style attributes, no emoji.
            $this->assertStringNotContainsString('style="', $html, "{$route} rendered an inline style");

            preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $html, $emoji);
            $this->assertSame([], $emoji, "{$route} rendered an emoji: " . ($emoji[0] ?? ''));

            if (str_ends_with($route, '.create')) {
                $this->assertStringContainsString('form-grid', $html, "{$route} is not built on .form-grid");
                $this->assertStringContainsString('input-lg bordered', $html, "{$route} has no prominent text field");
            }
        }

        // The register gets exactly one coupon field, and it is a boxed labelled input.
        $pos = $this->actingAs($this->employee('cashier'))->get(route('pos.index'));
        $this->assertSame(1, substr_count($pos->getContent(), 'name="coupon_code"'));
    }

    public function test_receipt_prints_promotion_and_coupon_savings_separately(): void
    {
        $product = $this->product('Receipt Item', 100.00);
        $discount = Discount::query()->create([
            'discount_name' => 'Five off', 'discount_type' => 'fixed', 'discount_value' => 5,
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(),
        ]);

        Promotion::query()->create([
            'name' => 'Twelve percent', 'type' => 'percentage', 'scope' => 'cart', 'value' => 12, 'is_active' => true,
        ]);
        Coupon::query()->create([
            'code' => 'RECEIPT', 'type' => 'fixed', 'value' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'discount_id' => $discount->discount_id,
            'coupon_code' => 'RECEIPT',
            'payment_method' => 'cash',
            'amount_paid' => 73.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ]);

        $sale = $this->lastSale();
        // 100 -> promo 12 -> manual 5 off 88 -> coupon 10 => 73.
        $this->assertEquals(73.00, $sale->total_amount);

        $receipt = $this->actingAs($this->employee('cashier'))
            ->get(route('pos.show', $sale))
            ->assertOk();

        // Every saving is its own line, and the manual line must not be re-derived from
        // subtotal - total (that would swallow the promo and coupon too).
        $receipt->assertSee('Twelve percent')
            ->assertSee('−₱12.00')
            ->assertSee('−₱5.00')
            ->assertSee('Coupon RECEIPT')
            ->assertSee('−₱10.00')
            ->assertSee('73.00');

        // The two NEW savings rows (promotion + coupon) use the shared receipt classes
        // added to the layout; the pre-existing manual-discount row keeps its old markup.
        $this->assertEquals(
            2,
            substr_count($receipt->getContent(), 'class="receipt-line receipt-save"')
        );
    }

    public function test_cashier_cannot_manage_promotions_or_coupons(): void
    {
        $this->actingAs($this->employee('cashier'))->get(route('promotions.index'))->assertForbidden();
        $this->actingAs($this->employee('cashier'))->get(route('coupons.index'))->assertForbidden();
        $this->actingAs($this->employee('cashier'))->post(route('coupons.store'), [
            'code' => 'SNEAKY', 'type' => 'fixed', 'value' => 10, 'is_active' => 1,
        ])->assertForbidden();

        $this->assertEquals(0, Coupon::query()->where('code', 'SNEAKY')->count());
    }

    public function test_manager_can_create_and_edit_a_promotion_and_a_coupon(): void
    {
        $product = $this->product('Manager Item', 100.00);
        $category = Category::query()->create(['category_name' => 'Bundleables']);

        $manager = $this->employee('manager');

        $this->actingAs($manager)->get(route('promotions.create'))->assertOk();
        $this->actingAs($manager)->get(route('coupons.create'))->assertOk();

        $this->actingAs($manager)->post(route('promotions.store'), [
            'name' => 'Manager BOGO',
            'type' => 'buy_x_get_y',
            'scope' => 'product',
            'scope_id' => $product->product_id,
            'x_qty' => 2,
            'y_qty' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('promotions.index'));

        $promotion = Promotion::query()->where('name', 'Manager BOGO')->firstOrFail();
        $this->assertEquals('buy_x_get_y', $promotion->type);
        $this->assertEquals($product->product_id, $promotion->scope_id);

        // Coupon codes are canonicalised to upper case by the controller.
        $this->actingAs($manager)->post(route('coupons.store'), [
            'code' => 'welcome20',
            'description' => 'Welcome twenty percent',
            'type' => 'percentage',
            'value' => 20,
            'min_purchase' => 500,
            'max_uses' => 100,
            'per_customer_limit' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('coupons.index'));

        $coupon = Coupon::query()->where('code', 'WELCOME20')->firstOrFail();
        $this->assertEquals(100, $coupon->max_uses);

        $this->actingAs($manager)->put(route('coupons.update', $coupon), [
            'code' => 'WELCOME25',
            'type' => 'percentage',
            'value' => 25,
            'min_purchase' => 500,
            'max_uses' => 50,
            'per_customer_limit' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('coupons.index'));

        $this->assertEquals('WELCOME25', $coupon->fresh()->code);
        $this->assertEquals(50, $coupon->fresh()->max_uses);

        // Both lists render.
        $this->actingAs($manager)->get(route('promotions.index'))->assertOk()->assertSee('Manager BOGO');
        $this->actingAs($manager)->get(route('coupons.index'))->assertOk()->assertSee('WELCOME25');

        $this->assertNotNull($category);
    }

    public function test_promotion_form_validation_rejects_nonsense_rules(): void
    {
        $manager = $this->employee('manager');

        // Percentage above 100.
        $this->actingAs($manager)->post(route('promotions.store'), [
            'name' => 'Impossible', 'type' => 'percentage', 'scope' => 'cart', 'value' => 150, 'is_active' => 1,
        ])->assertSessionHasErrors('value');

        // A product-scoped rule with no product.
        $this->actingAs($manager)->post(route('promotions.store'), [
            'name' => 'Homeless', 'type' => 'fixed', 'scope' => 'product', 'value' => 10, 'is_active' => 1,
        ])->assertSessionHasErrors('scope_id');

        // A BOGO rule with no quantities.
        $this->actingAs($manager)->post(route('promotions.store'), [
            'name' => 'No quantities', 'type' => 'buy_x_get_y', 'scope' => 'cart', 'is_active' => 1,
        ])->assertSessionHasErrors(['x_qty', 'y_qty']);

        // Ends before it starts.
        $this->actingAs($manager)->post(route('promotions.store'), [
            'name' => 'Time travel', 'type' => 'fixed', 'scope' => 'cart', 'value' => 5,
            'starts_at' => now()->addDay()->format('Y-m-d\H:i'),
            'ends_at' => now()->format('Y-m-d\H:i'),
            'is_active' => 1,
        ])->assertSessionHasErrors('ends_at');

        // Duplicate coupon code.
        Coupon::query()->create(['code' => 'TAKEN', 'type' => 'fixed', 'value' => 5, 'is_active' => true]);
        $this->actingAs($manager)->post(route('coupons.store'), [
            'code' => 'taken', 'type' => 'fixed', 'value' => 5, 'is_active' => 1,
        ])->assertSessionHasErrors('code');

        $this->assertEquals(0, Promotion::query()->whereIn('name', ['Impossible', 'Homeless', 'No quantities', 'Time travel'])->count());
    }

    public function test_spent_coupon_and_applied_promotion_cannot_be_deleted(): void
    {
        $manager = $this->employee('manager');
        $product = $this->product('History Item', 100.00);

        $promotion = Promotion::query()->create([
            'name' => 'History promo', 'type' => 'percentage', 'scope' => 'cart', 'value' => 10, 'is_active' => true,
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'HISTORY', 'type' => 'fixed', 'value' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->employee('cashier'))->post(route('pos.store'), [
            'coupon_code' => 'HISTORY',
            'payment_method' => 'cash',
            'amount_paid' => 80.00,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ]);

        $this->actingAs($manager)->delete(route('promotions.destroy', $promotion))
            ->assertSessionHas('error');
        $this->actingAs($manager)->delete(route('coupons.destroy', $coupon))
            ->assertSessionHas('error');

        $this->assertNotNull($promotion->fresh());
        $this->assertNotNull($coupon->fresh());

        // An untouched rule is deletable.
        $spare = Promotion::query()->create([
            'name' => 'Spare', 'type' => 'percentage', 'scope' => 'cart', 'value' => 1, 'is_active' => true,
        ]);
        $this->actingAs($manager)->delete(route('promotions.destroy', $spare))->assertRedirect(route('promotions.index'));
        $this->assertNull($spare->fresh());
    }
}
