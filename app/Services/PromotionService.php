<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\SaleTransaction;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * PromotionService — the single source of truth for ALL discounting math in the POS.
 *
 * Everything that can reduce what a customer pays is computed here (or, for the legacy
 * manual discount picker, in Discount::applyTo() which this service's ordering is built
 * around). Controllers validate input and call these methods; they never do arithmetic.
 *
 * Checkout stacking order (each step works on the running remainder):
 *   line subtotals -> promotions (auto) -> manual discount (discount_id) -> coupon -> total (>= 0)
 *
 * Concurrency: coupon redemption re-reads the coupon row with lockForUpdate() so two
 * cashiers cannot both consume the last use of a capped coupon (TOCTOU).
 */
class PromotionService
{
    /** Money is rounded to 2 decimals at every discount step (matches Discount::applyTo). */
    private const MONEY_EPSILON = 0.005;

    /**
     * Promotions that are switched on, inside their date window, and match this cart.
     *
     * @param  list<array{product_id:int, category_id:?int, quantity:float, unit_price:float}>  $lines
     */
    public function eligiblePromotions(array $lines, float $cartSubtotal): Collection
    {
        if ($lines === [] || $cartSubtotal <= 0) {
            return new Collection;
        }

        // Read-only query: no row lock needed. Promotions are never mutated during a
        // checkout, so a concurrent manager edit can only affect the NEXT sale. The
        // active + in-window test is delegated to Promotion::isLive() (single source of
        // truth for the date-window rule) so this service never re-implements the window
        // math. is_active is filtered at the DB for the index; isLive() re-confirms it
        // plus the exact-time window in PHP. (Behavior identical to the old inline SQL.)
        return Promotion::query()
            ->where('is_active', true)
            ->orderBy('promotion_id')
            ->get()
            ->filter(fn (Promotion $promotion) => $promotion->isLive()
                && $this->matchesScope($promotion, $lines))
            ->values();
    }

    /**
     * Apply every eligible promotion to the cart.
     *
     * Deterministic: the biggest saving is applied first (promotion_id breaks ties), and
     * the running sum is capped so promotions alone can never zero out more than the
     * cart subtotal.
     *
     * @param  list<array{product_id:int, category_id:?int, quantity:float, unit_price:float}>  $lines
     * @return array{total_discount:float, applied:list<array{promotion_id:int, amount:float, snapshot:array}>}
     */
    public function applyPromotions(array $lines, float $cartSubtotal): array
    {
        $cartSubtotal = round($cartSubtotal, 2);

        $scored = $this->eligiblePromotions($lines, $cartSubtotal)
            ->map(fn (Promotion $promotion) => [
                'promotion' => $promotion,
                'amount' => $this->discountFor($promotion, $lines, $cartSubtotal),
            ])
            ->filter(fn (array $row) => $row['amount'] > self::MONEY_EPSILON)
            ->sort(fn (array $a, array $b) => $b['amount'] <=> $a['amount']
                ?: $a['promotion']->promotion_id <=> $b['promotion']->promotion_id)
            ->values();

        $remaining = $cartSubtotal;
        $applied = [];
        $totalDiscount = 0.0;

        foreach ($scored as $row) {
            if ($remaining <= self::MONEY_EPSILON) {
                break; // stackable cap: the cart is already fully discounted
            }

            $amount = round(min($row['amount'], $remaining), 2);

            if ($amount <= 0) {
                continue;
            }

            $remaining = round($remaining - $amount, 2);
            $totalDiscount = round($totalDiscount + $amount, 2);

            $applied[] = [
                'promotion_id' => $row['promotion']->promotion_id,
                'amount' => $amount,
                'snapshot' => $row['promotion']->snapshot(),
            ];
        }

        return [
            'total_discount' => $totalDiscount,
            'applied' => $applied,
        ];
    }

    /**
     * Discount produced by ONE promotion against this cart, before any stacking cap.
     *
     * @param  list<array{product_id:int, category_id:?int, quantity:float, unit_price:float}>  $lines
     */
    public function discountFor(Promotion $promotion, array $lines, float $cartSubtotal): float
    {
        $matched = $this->matchedLines($promotion, $lines);

        if ($matched === []) {
            return 0.0;
        }

        $scopeSubtotal = round(array_sum(array_map(
            fn (array $line) => (float) $line['unit_price'] * (float) $line['quantity'],
            $matched
        )), 2);

        if ($promotion->scope === 'cart') {
            $scopeSubtotal = round($cartSubtotal, 2);
        }

        if ($scopeSubtotal <= 0) {
            return 0.0;
        }

        return match ($promotion->type) {
            'percentage' => round($scopeSubtotal * ((float) $promotion->value / 100), 2),
            // A fixed amount can never discount more than the scope it applies to.
            'fixed' => round(min((float) $promotion->value, $scopeSubtotal), 2),
            'bundle_price' => $this->bundleDiscount($matched, $promotion),
            'buy_x_get_y' => $this->buyXGetYDiscount($matched, $promotion),
            default => 0.0,
        };
    }

    /**
     * Does this cart touch the promotion's scope at all?
     *
     * @param  list<array<string,mixed>>  $lines
     */
    private function matchesScope(Promotion $promotion, array $lines): bool
    {
        return $this->matchedLines($promotion, $lines) !== [];
    }

    /**
     * The cart lines a promotion may discount:
     *   cart     -> every line
     *   product  -> lines for that product_id (duplicate lines of the same product merge)
     *   category -> lines whose product belongs to that category_id
     *
     * @param  list<array<string,mixed>>  $lines
     * @return list<array<string,mixed>>
     */
    private function matchedLines(Promotion $promotion, array $lines): array
    {
        return match ($promotion->scope) {
            'cart' => array_values($lines),
            'product' => array_values(array_filter(
                $lines,
                fn (array $line) => (int) $line['product_id'] === (int) $promotion->scope_id
            )),
            'category' => array_values(array_filter(
                $lines,
                fn (array $line) => $line['category_id'] !== null
                    && (int) $line['category_id'] === (int) $promotion->scope_id
            )),
            default => [],
        };
    }

    /**
     * bundle_price: every complete group of bundle_qty units is charged at bundle_price
     * total; leftover units stay at their normal price. Cheapest units form the bundles
     * first, so a mixed-category bundle can never discount more than it should.
     *
     * @param  list<array<string,mixed>>  $lines
     */
    private function bundleDiscount(array $lines, Promotion $promotion): float
    {
        $bundleQty = (int) ($promotion->bundle_qty ?? 0);
        $bundlePrice = (float) ($promotion->bundle_price ?? 0);

        if ($bundleQty < 1) {
            return 0.0;
        }

        $pool = $this->unitPool($lines);
        $totalUnits = array_sum(array_column($pool, 'qty'));
        $groups = (int) floor($totalUnits / $bundleQty);

        if ($groups < 1) {
            return 0.0;
        }

        $discount = 0.0;

        for ($group = 0; $group < $groups; $group++) {
            $normalGroupPrice = $this->takeUnits($pool, $bundleQty);
            // A bundle that is not actually cheaper than the sum of its parts is ignored.
            $discount += max(0, round($normalGroupPrice - $bundlePrice, 2));
        }

        return round($discount, 2);
    }

    /**
     * buy_x_get_y: for every complete group of x_qty units, y_qty units are free. The
     * free units are always the CHEAPEST ones in scope, so the giveaway is the smallest
     * possible ("buy 2 get 1" on 100/80/10 gives away 10, not 80). Incomplete groups
     * earn nothing.
     *
     * @param  list<array<string,mixed>>  $lines
     */
    private function buyXGetYDiscount(array $lines, Promotion $promotion): float
    {
        $xQty = (int) ($promotion->x_qty ?? 0);
        $yQty = (int) ($promotion->y_qty ?? 0);

        if ($xQty < 1 || $yQty < 1) {
            return 0.0;
        }

        $pool = $this->unitPool($lines);
        $totalUnits = array_sum(array_column($pool, 'qty'));
        $groups = (int) floor($totalUnits / $xQty);

        if ($groups < 1) {
            return 0.0;
        }

        // Free units can never exceed the units actually in the cart.
        $freeUnits = min($groups * $yQty, $totalUnits);

        return round($this->takeUnits($pool, $freeUnits), 2);
    }

    /**
     * Cart lines flattened into price buckets sorted cheapest first, so both the bundle
     * and the buy-X-get-Y rules can consume units deterministically (weighted products
     * carry fractional quantities, hence float qty).
     *
     * @param  list<array<string,mixed>>  $lines
     * @return list<array{price:float, qty:float}>
     */
    private function unitPool(array $lines): array
    {
        $buckets = [];

        foreach ($lines as $line) {
            $price = round((float) $line['unit_price'], 2);
            $qty = (float) $line['quantity'];

            if ($qty <= 0) {
                continue;
            }

            $key = sprintf('%.2f', $price);
            $buckets[$key] = ($buckets[$key] ?? 0) + $qty;
        }

        ksort($buckets); // cheapest price first

        return array_map(
            fn (string $price, float $qty) => ['price' => (float) $price, 'qty' => round($qty, 3)],
            array_keys($buckets),
            array_values($buckets)
        );
    }

    /**
     * Consume $units units from the (by-reference) pool, cheapest first, and return the
     * normal price those units would have cost.
     *
     * @param  list<array{price:float, qty:float}>  $pool
     */
    private function takeUnits(array &$pool, float $units): float
    {
        $cost = 0.0;

        foreach ($pool as &$bucket) {
            if ($units <= 0.0001) {
                break;
            }

            $take = min($bucket['qty'], $units);
            $cost += $take * $bucket['price'];
            $bucket['qty'] = round($bucket['qty'] - $take, 3);
            $units -= $take;
        }
        unset($bucket);

        return round($cost, 2);
    }

    /*
    |---------------------------------------------------------------------
    | Coupons
    |---------------------------------------------------------------------
    */

    /**
     * Is this code redeemable right now? Throws ValidationException keyed on
     * 'coupon_code' so the POS form redisplays the error with the cart preserved.
     *
     * @throws ValidationException
     */
    public function validateCoupon(string $code, float $cartSubtotal, ?int $customerId): Coupon
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'coupon_code' => 'Enter a coupon code, or clear the field.',
            ]);
        }

        // Codes are stored uppercase, so this lookup is case-insensitive by construction.
        $coupon = Coupon::query()->where('code', $normalized)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$normalized}\" was not found.",
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$coupon->code}\" is not active.",
            ]);
        }

        if (! $coupon->isLive()) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$coupon->code}\" is outside its valid date window.",
            ]);
        }

        if ((float) $cartSubtotal + self::MONEY_EPSILON < (float) $coupon->min_purchase) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$coupon->code}\" needs a minimum purchase of "
                    .'₱'.number_format((float) $coupon->min_purchase, 2).' (cart: ₱'
                    .number_format($cartSubtotal, 2).').',
            ]);
        }

        if ($coupon->isExhausted()) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$coupon->code}\" has reached its usage limit.",
            ]);
        }

        // Per-customer cap counts this coupon's prior redemptions across ALL of the
        // customer's sales. Walk-ins (null customer) skip the check — they have no
        // identity to limit — but still consume a global use (used_count).
        if ($coupon->per_customer_limit !== null && $customerId !== null) {
            $alreadyUsed = CouponRedemption::query()
                ->where('coupon_id', $coupon->coupon_id)
                ->where('customer_id', $customerId)
                ->count();

            if ($alreadyUsed >= (int) $coupon->per_customer_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => "Customer has already used coupon \"{$coupon->code}\" "
                        .(int) $coupon->per_customer_limit.' time(s).',
                ]);
            }
        }

        return $coupon;
    }

    /**
     * What a coupon takes off the RUNNING remainder (after promotions + manual discount),
     * never more than what is still owed.
     */
    public function couponDiscount(Coupon $coupon, float $runningRemainder): float
    {
        $remainder = max(0, round($runningRemainder, 2));

        if ($remainder <= 0) {
            return 0.0;
        }

        $amount = $coupon->type === 'percentage'
            ? $remainder * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        return round(min($amount, $remainder), 2);
    }

    /**
     * Spend the coupon: write the redemption row and bump used_count.
     *
     * MUST be called inside the caller's checkout transaction — this method does not open
     * one. The coupon row is re-read under lockForUpdate() and the usage cap re-checked
     * AFTER the lock, so two concurrent cashiers racing for the last use serialize and
     * exactly one succeeds (TOCTOU guard, same discipline as stock + refund locks).
     *
     * @throws ValidationException
     */
    public function redeemCoupon(Coupon $coupon, SaleTransaction $sale, ?Customer $customer, float $amount): void
    {
        $locked = Coupon::query()
            ->lockForUpdate()
            ->findOrFail($coupon->coupon_id);

        if ($locked->isExhausted()) {
            throw ValidationException::withMessages([
                'coupon_code' => "Coupon \"{$locked->code}\" has reached its usage limit.",
            ]);
        }

        CouponRedemption::query()->create([
            'coupon_id' => $locked->coupon_id,
            'transaction_id' => $sale->transaction_id,
            'customer_id' => $customer?->customer_id,
            'redeemed_at' => now(),
            'amount_applied' => round($amount, 2),
        ]);

        $locked->used_count = (int) $locked->used_count + 1;
        $locked->save();
    }
}
