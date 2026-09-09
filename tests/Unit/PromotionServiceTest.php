<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\SaleTransaction;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * PromotionService unit tests — the engine is exercised with real rows and real carts,
 * so every expected peso figure below is hand-computed from the documented rules.
 */
class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionService;
    }

    private function product(string $name, float $price, ?Category $category = null): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'category_id' => $category?->category_id,
            // Barcodes are EAN-13 and NOT NULL: always mint them through the model helper.
            'barcode' => Product::generateBarcode(),
            'unit_price' => $price,
            'cost_price' => round($price / 2, 2),
            'reorder_level' => 5,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 100,
        ]);

        return $product;
    }

    /**
     * Build the cart-lines array the service documents, plus its subtotal.
     *
     * @param  array<int, array{0: Product, 1: float}>  $pairs
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private function cart(array $pairs): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach ($pairs as [$product, $qty]) {
            $lines[] = [
                'product_id' => (int) $product->product_id,
                'category_id' => $product->category_id ? (int) $product->category_id : null,
                'quantity' => (float) $qty,
                'unit_price' => (float) $product->unit_price,
            ];
            $subtotal += round((float) $product->unit_price * (float) $qty, 2);
        }

        return [$lines, round($subtotal, 2)];
    }

    private function promotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'name' => 'Rule',
            'type' => 'percentage',
            'scope' => 'cart',
            'value' => 10,
            'is_active' => true,
        ], $attributes));
    }

    private function coupon(array $attributes = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ], $attributes));
    }

    private function sale(?Customer $customer = null): SaleTransaction
    {
        $role = Role::query()->firstOrCreate(['role_name' => 'Cashier']);

        $employee = Employee::query()->create([
            'username' => 'cashier'.uniqid(),
            'password' => 'password',
            'first_name' => 'Test',
            'last_name' => 'Cashier',
            'hire_date' => now()->subYear()->toDateString(),
            'role_id' => $role->role_id,
        ]);

        return SaleTransaction::query()->create([
            'customer_id' => $customer?->customer_id,
            'employee_id' => $employee->employee_id,
            'transaction_date' => now(),
            'subtotal' => 100,
            'total_amount' => 100,
            'payment_method' => 'cash',
        ]);
    }

    private function customer(string $first = 'Ana', string $last = 'Cruz'): Customer
    {
        return Customer::query()->create([
            'first_name' => $first,
            'last_name' => $last,
            'customer_status' => 'active',
        ]);
    }

    /**
     * Run a callback that must throw ValidationException; return the field it failed on.
     */
    private function failedField(callable $callback): string
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return (string) array_key_first($exception->errors());
        }

        $this->fail('Expected a ValidationException was not thrown.');
    }

    /*
    |------------------------------------------------------------------
    | Promotion matching + math
    |------------------------------------------------------------------
    */

    public function test_percentage_promotion_on_the_whole_cart(): void
    {
        [$lines, $subtotal] = $this->cart([
            [$this->product('A', 100.00), 3],
            [$this->product('B', 50.00), 2],
        ]);

        $this->assertEquals(400.00, $subtotal);

        $this->promotion(['name' => 'Ten percent', 'value' => 10]);

        $result = $this->service->applyPromotions($lines, $subtotal);

        $this->assertEquals(40.00, $result['total_discount']);
        $this->assertCount(1, $result['applied']);
        $this->assertEquals(40.00, $result['applied'][0]['amount']);
        $this->assertEquals('Ten percent', $result['applied'][0]['snapshot']['name']);
    }

    public function test_product_scope_only_discounts_that_product(): void
    {
        [$lines, $subtotal] = $this->cart([
            [$this->product('Target', 100.00), 2],
            [$this->product('Other', 100.00), 1],
        ]);

        $target = Product::query()->where('product_name', 'Target')->firstOrFail();
        $this->promotion(['value' => 10, 'scope' => 'product', 'scope_id' => $target->product_id]);

        // 10% of the ₱200 of Target, not of the ₱300 cart.
        $this->assertEquals(20.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_category_scope_only_discounts_that_category(): void
    {
        $drinks = Category::query()->create(['category_name' => 'Softdrinks']);
        $snacks = Category::query()->create(['category_name' => 'Snacks']);

        [$lines, $subtotal] = $this->cart([
            [$this->product('Cola', 30.00, $drinks), 4],
            [$this->product('Chips', 25.00, $snacks), 4],
        ]);

        $this->assertEquals(220.00, $subtotal);

        $this->promotion(['value' => 10, 'scope' => 'category', 'scope_id' => $drinks->category_id]);

        $this->assertEquals(12.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_a_promotion_whose_scope_is_not_in_the_cart_is_ignored(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('Rice', 50.00), 2]]);
        $other = $this->product('Beans', 20.00);

        $this->promotion(['scope' => 'product', 'scope_id' => $other->product_id, 'value' => 50]);

        $this->assertEquals(0.0, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_fixed_promotion_is_capped_at_its_scope_subtotal(): void
    {
        [$lines, $subtotal] = $this->cart([[$a = $this->product('A', 20.00), 1]]);

        $this->promotion(['type' => 'fixed', 'value' => 500, 'scope' => 'product', 'scope_id' => $a->product_id]);

        $this->assertEquals(20.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_percentage_promotion_can_never_discount_more_than_its_scope(): void
    {
        $drinks = Category::query()->create(['category_name' => 'Softdrinks']);

        [$lines, $subtotal] = $this->cart([
            [$this->product('Cola', 50.00, $drinks), 3],
            [$this->product('Rice', 250.00), 1],
        ]);

        $this->promotion(['type' => 'percentage', 'value' => 100, 'scope' => 'category', 'scope_id' => $drinks->category_id]);

        $this->assertEquals(150.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_bundle_price_discounts_each_complete_group(): void
    {
        [$lines, $subtotal] = $this->cart([
            [$this->product('Bread', 30.00), 1],
            [$this->product('Butter', 20.00), 1],
            [$this->product('Jam', 10.00), 1],
            [$this->product('Milk', 100.00), 1],
        ]);

        $this->promotion(['type' => 'bundle_price', 'value' => null, 'bundle_qty' => 3, 'bundle_price' => 45]);

        // Only one group of 3 forms (the cheapest units), so the ₱100 Milk stays full price:
        // (10 + 20 + 30) - 45 = 15.
        $this->assertEquals(15.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_bundle_price_applies_per_group_and_never_goes_negative(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('Item', 20.00), 5]]);

        $this->promotion(['type' => 'bundle_price', 'value' => null, 'bundle_qty' => 2, 'bundle_price' => 30]);

        // 5 units -> 2 groups: (40 - 30) + (40 - 30) = 20, 5th unit at full price.
        $this->assertEquals(20.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);

        // A "bundle" priced above its parts must not create a negative discount.
        Promotion::query()->update(['bundle_price' => 90]);
        $this->assertEquals(0.0, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_bundle_price_needs_a_full_group_to_fire(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('Item', 20.00), 2]]);

        $this->promotion(['type' => 'bundle_price', 'value' => null, 'bundle_qty' => 3, 'bundle_price' => 45]);

        $this->assertEquals(0.0, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_buy_x_get_y_gives_away_one_cheapest_unit_per_group(): void
    {
        [$lines, $subtotal] = $this->cart([
            [$a = $this->product('Cola', 25.00), 3],
            [$this->product('Sprite', 30.00), 1],
        ]);

        $this->promotion([
            'type' => 'buy_x_get_y', 'value' => null, 'scope' => 'product',
            'scope_id' => $a->product_id, 'x_qty' => 2, 'y_qty' => 1,
        ]);

        // 3 colas -> floor(3/2) = 1 group -> one free unit, the cheapest in scope (25).
        $this->assertEquals(25.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_buy_x_get_y_free_units_track_the_number_bought(): void
    {
        [$lines, $subtotal] = $this->cart([[$a = $this->product('Cola', 25.00), 4]]);

        $this->promotion([
            'type' => 'buy_x_get_y', 'value' => null, 'scope' => 'product',
            'scope_id' => $a->product_id, 'x_qty' => 2, 'y_qty' => 1,
        ]);

        // 4 units -> floor(4/2) = 2 free -> 25 + 25.
        $this->assertEquals(50.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    public function test_buy_x_get_y_across_a_category_gives_away_the_cheapest_units(): void
    {
        $drinks = Category::query()->create(['category_name' => 'Softdrinks']);

        [$lines, $subtotal] = $this->cart([
            [$this->product('Cola', 30.00, $drinks), 2],
            [$this->product('Water', 20.00, $drinks), 2],
        ]);

        $this->promotion([
            'type' => 'buy_x_get_y', 'value' => null, 'scope' => 'category',
            'scope_id' => $drinks->category_id, 'x_qty' => 2, 'y_qty' => 1,
        ]);

        // 4 units -> 2 free, cheapest first: 20 + 20 (never the 30-peso Cola).
        $this->assertEquals(40.00, $this->service->applyPromotions($lines, $subtotal)['total_discount']);
    }

    /*
    |------------------------------------------------------------------
    | Eligibility, ordering, caps
    |------------------------------------------------------------------
    */

    public function test_inactive_and_out_of_window_promotions_are_not_eligible(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('A', 100.00), 1]]);

        $this->promotion(['name' => 'Off', 'is_active' => false]);
        $this->promotion(['name' => 'Later', 'starts_at' => now()->addDay()]);
        $this->promotion(['name' => 'Ago', 'ends_at' => now()->subDay()]);
        $live = $this->promotion(['name' => 'Live', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

        $eligible = $this->service->eligiblePromotions($lines, $subtotal);

        $this->assertCount(1, $eligible);
        $this->assertEquals($live->promotion_id, $eligible->first()->promotion_id);
    }

    public function test_biggest_saving_is_applied_first_regardless_of_creation_order(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('A', 100.00), 1]]);

        $small = $this->promotion(['name' => 'Small', 'value' => 5]);
        $big = $this->promotion(['name' => 'Big', 'value' => 40]);

        $applied = $this->service->applyPromotions($lines, $subtotal)['applied'];

        $this->assertSame([$big->promotion_id, $small->promotion_id], array_column($applied, 'promotion_id'));
        $this->assertEquals(45.00, array_sum(array_column($applied, 'amount')));
    }

    public function test_apply_promotions_is_deterministic_for_the_same_cart(): void
    {
        [$lines, $subtotal] = $this->cart([
            [$a = $this->product('A', 100.00), 2],
            [$b = $this->product('B', 50.00), 3],
        ]);

        $this->promotion(['name' => 'Bogo', 'type' => 'buy_x_get_y', 'scope' => 'product', 'scope_id' => $b->product_id, 'x_qty' => 2, 'y_qty' => 1, 'value' => null]);
        $this->promotion(['name' => 'Ten', 'type' => 'percentage', 'scope' => 'product', 'scope_id' => $a->product_id, 'value' => 10]);
        $this->promotion(['name' => 'Fifteen', 'type' => 'fixed', 'scope' => 'cart', 'value' => 15]);

        $first = $this->service->applyPromotions($lines, $subtotal);
        $second = $this->service->applyPromotions($lines, $subtotal);

        $this->assertSame($first['applied'], $second['applied']);
        $this->assertEquals($first['total_discount'], $second['total_discount']);
        // Hand-computed: fixed 15 + 10% of 200 (20) + one free 50-peso B => 85.
        $this->assertEquals(85.00, $first['total_discount']);
    }

    public function test_stacked_promotions_cannot_discount_more_than_the_cart(): void
    {
        [$lines, $subtotal] = $this->cart([[$this->product('A', 100.00), 1]]);

        // 60% then 50% of the same ₱100 line: 60 + 50 would be 110 on a 100 cart.
        $this->promotion(['name' => 'Sixty', 'value' => 60]);
        $this->promotion(['name' => 'Fifty', 'value' => 50]);

        $result = $this->service->applyPromotions($lines, $subtotal);

        $this->assertEquals(100.00, $result['total_discount']);
        $this->assertEquals([60.0, 40.0], array_column($result['applied'], 'amount'));
    }

    public function test_empty_cart_produces_no_discount(): void
    {
        $this->promotion(['value' => 50]);

        $result = $this->service->applyPromotions([], 0.0);

        $this->assertEquals(0.0, $result['total_discount']);
        $this->assertSame([], $result['applied']);
    }

    /*
    |------------------------------------------------------------------
    | Coupons
    |------------------------------------------------------------------
    */

    public function test_codes_are_matched_case_insensitively(): void
    {
        $coupon = $this->coupon(['code' => 'save10']);

        // The model mutator canonicalises the stored code, so lookups can be exact.
        $this->assertEquals('SAVE10', $coupon->fresh()->code);
        $this->assertEquals($coupon->coupon_id, $this->service->validateCoupon('SavE10', 100.00, null)->coupon_id);
    }

    public function test_unknown_code_is_rejected_on_the_coupon_field(): void
    {
        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('NOPE', 100.00, null);
        }));
    }

    public function test_blank_code_is_rejected(): void
    {
        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('   ', 100.00, null);
        }));
    }

    public function test_minimum_purchase_is_enforced(): void
    {
        $this->coupon(['min_purchase' => 500]);

        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('SAVE10', 499.99, null);
        }));

        $this->assertEquals('SAVE10', $this->service->validateCoupon('SAVE10', 500.00, null)->code);
    }

    public function test_inactive_and_expired_codes_are_rejected(): void
    {
        $this->coupon(['is_active' => false]);
        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('SAVE10', 100.00, null);
        }));

        Coupon::query()->update(['is_active' => true, 'starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('SAVE10', 100.00, null);
        }));
    }

    public function test_exhausted_code_is_rejected(): void
    {
        $coupon = $this->coupon(['max_uses' => 2]);
        Coupon::query()->where('coupon_id', $coupon->coupon_id)->update(['used_count' => 2]);

        $this->assertSame('coupon_code', $this->failedField(function () {
            $this->service->validateCoupon('SAVE10', 100.00, null);
        }));
    }

    public function test_per_customer_limit_is_enforced_for_that_customer_only(): void
    {
        $coupon = $this->coupon(['per_customer_limit' => 1]);
        $ana = $this->customer('Ana', 'Cruz');
        $ben = $this->customer('Ben', 'Fox');

        $this->service->redeemCoupon($coupon, $this->sale($ana), $ana, 10.00);

        $this->assertSame('coupon_code', $this->failedField(function () use ($ana) {
            $this->service->validateCoupon('SAVE10', 100.00, $ana->customer_id);
        }));

        // Another customer, and walk-ins, are unaffected by Ana's usage.
        $this->assertEquals('SAVE10', $this->service->validateCoupon('SAVE10', 100.00, $ben->customer_id)->code);
        $this->assertEquals('SAVE10', $this->service->validateCoupon('SAVE10', 100.00, null)->code);
    }

    public function test_coupon_discount_uses_the_running_remainder_not_the_subtotal(): void
    {
        $percentage = $this->coupon(['code' => 'PCT10', 'type' => 'percentage', 'value' => 10]);

        // After promotions took 100 off a 500 cart, 10% is 40 — not 50.
        $this->assertEquals(40.00, $this->service->couponDiscount($percentage, 400.00));

        $fixed = $this->coupon(['code' => 'FLAT500', 'type' => 'fixed', 'value' => 500]);
        $this->assertEquals(120.00, $this->service->couponDiscount($fixed, 120.00));

        // Already at zero: the coupon takes nothing and, per the checkout contract, is not spent.
        $this->assertEquals(0.0, $this->service->couponDiscount($fixed, 0.0));
        $this->assertEquals(0.0, $this->service->couponDiscount($fixed, -50.0));
    }

    public function test_redeeming_records_the_redemption_and_bumps_usage(): void
    {
        $coupon = $this->coupon(['max_uses' => 5]);
        $customer = $this->customer();
        $sale = $this->sale($customer);

        $this->service->redeemCoupon($coupon->fresh(), $sale, $customer, 12.34);

        $this->assertDatabaseHas('coupon_redemption', [
            'coupon_id' => $coupon->coupon_id,
            'transaction_id' => $sale->transaction_id,
            'customer_id' => $customer->customer_id,
            'amount_applied' => 12.34,
        ]);
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_redeeming_walk_in_sale_records_no_customer(): void
    {
        $coupon = $this->coupon();
        $sale = $this->sale();

        $this->service->redeemCoupon($coupon, $sale, null, 5.00);

        $this->assertDatabaseHas('coupon_redemption', [
            'coupon_id' => $coupon->coupon_id,
            'customer_id' => null,
        ]);
    }

    public function test_redemption_re_checks_the_cap_after_locking_the_row(): void
    {
        $coupon = $this->coupon(['max_uses' => 1]);

        $this->service->redeemCoupon($coupon, $this->sale(), null, 10.00);

        // A second cashier still holding the pre-checkout (stale) model must not get a
        // second use: redeemCoupon re-reads the row under lockForUpdate() and re-checks.
        $this->assertSame('coupon_code', $this->failedField(function () use ($coupon) {
            $this->service->redeemCoupon($coupon, $this->sale(), null, 10.00);
        }));

        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->assertEquals(1, \App\Models\CouponRedemption::query()->count());
    }
}
