<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One coupon consumption on one sale. Written inside the checkout transaction by
 * PromotionService::redeemCoupon() (which also bumps coupon.used_count under a lock).
 *
 * per_customer_limit is answered from this table, not from a counter column, so it
 * stays correct across ALL of a customer's sales.
 */
class CouponRedemption extends Model
{
    protected $table = 'coupon_redemption';

    public $timestamps = false;

    protected $primaryKey = 'redemption_id';

    protected $fillable = [
        'coupon_id',
        'transaction_id',
        'customer_id',
        'redeemed_at',
        'amount_applied',
    ];

    protected function casts(): array
    {
        return [
            'coupon_id' => 'integer',
            'transaction_id' => 'integer',
            'customer_id' => 'integer',
            'redeemed_at' => 'datetime',
            'amount_applied' => 'decimal:2',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'coupon_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}
