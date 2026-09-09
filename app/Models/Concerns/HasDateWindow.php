<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * The ONE definition of the "switched on AND inside its date window" rule
 * (Promotion and Coupon — see CHANGELOG 2026-09-09 cleanup).
 *
 * Promotion::isLive() and Coupon::isLive() were near-identical copies, and the
 * original PromotionService re-implemented the same window inline; both now
 * resolve to this single method. Semantics are EXACT-TIME: window bounds are
 * datetimes compared against the current moment with no day-boundary rounding.
 *
 * The legacy Discount model deliberately keeps a DIFFERENT whole-DAY rule
 * (its window is stored as `date` columns) — see Discount::isActive(), which
 * documents why that difference is intentional rather than an oversight.
 *
 * Requires the using model to cast: is_active (boolean), starts_at (datetime,
 * nullable), ends_at (datetime, nullable).
 */
trait HasDateWindow
{
    /**
     * Is this rule switched on and inside its date window right now?
     * Expired rows are ignored at checkout, never deleted.
     */
    public function isLive(?Carbon $on = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $on = $on ?? now();

        if ($this->starts_at && $on->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $on->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
