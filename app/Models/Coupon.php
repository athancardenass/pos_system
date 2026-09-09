<?php

namespace App\Models;

use App\Models\Concerns\HasDateWindow;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A digital coupon: cashier-entered code, percentage or fixed peso value, with
 * optional global and per-customer usage limits.
 *
 * Validation and redemption math live in App\Services\PromotionService.
 */
class Coupon extends Model
{
    use HasDateWindow;

    protected $table = 'coupon';

    protected $primaryKey = 'coupon_id';

    public const TYPES = ['percentage', 'fixed'];

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_purchase',
        'max_uses',
        'used_count',
        'per_customer_limit',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Codes are matched case-insensitively by the cashier, so they are always
     * stored uppercase — one canonical form keeps the unique index honest.
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : strtoupper(trim($value)),
        );
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id', 'coupon_id');
    }

    /**
     * The "active + in date window" rule lives in App\Models\Concerns\HasDateWindow
     * (isLive()) — single source of truth shared with Promotion.
     */

    /**
     * Has the global usage cap been spent? null max_uses = unlimited.
     */
    public function isExhausted(): bool
    {
        return $this->max_uses !== null && (int) $this->used_count >= (int) $this->max_uses;
    }

    /**
     * "20% off" / "₱50 off".
     */
    public function ruleLabel(): string
    {
        return $this->type === 'percentage'
            ? round((float) $this->value, 2).'% off'
            : '₱'.number_format((float) $this->value, 2).' off';
    }

    /**
     * "3 of 10 used" / "2 used (unlimited)".
     */
    public function usageLabel(): string
    {
        $used = (int) $this->used_count;

        return $this->max_uses === null
            ? "{$used} used (unlimited)"
            : "{$used} of {$this->max_uses} used";
    }
}
