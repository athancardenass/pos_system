<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleTransaction extends Model
{
    use HasFactory;

    protected $table = 'sale_transaction';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'employee_id',
        'discount_id',
        'transaction_date',
        'subtotal',
        'total_amount',
        'promo_discount',
        'coupon_discount',
        'payment_method',
        'status',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'refunded_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'promo_discount' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
        ];
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id', 'discount_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'transaction_id', 'transaction_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'transaction_id');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'transaction_id', 'transaction_id');
    }

    public function refunds()
    {
        return $this->hasMany(SaleRefund::class, 'transaction_id', 'transaction_id');
    }

    /** Auto-promotions that fired on this sale (promotion engine audit rows). */
    public function appliedPromotions()
    {
        return $this->hasMany(SalePromotion::class, 'transaction_id', 'transaction_id');
    }

    /** Coupons redeemed on this sale. */
    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class, 'transaction_id', 'transaction_id');
    }

    /** Total saved through the promotion engine (promotions + coupon). */
    public function promotionSavings(): float
    {
        return round((float) $this->promo_discount + (float) $this->coupon_discount, 2);
    }

    /**
     * The legacy manual-discount amount (the cashier's discount_id picker).
     *
     * It is never stored: the checkout order is
     *   subtotal -> promotions -> manual discount -> coupon -> total
     * and Discount::applyTo() is deterministic, so replaying it on the post-promotion
     * remainder reproduces exactly what was charged — including sales made before the
     * promotion engine existed (promo_discount defaults to 0).
     */
    public function manualDiscountAmount(): float
    {
        if (! $this->discount) {
            return 0.0;
        }

        $afterPromotions = round((float) $this->subtotal - (float) $this->promo_discount, 2);

        return round($afterPromotions - (float) $this->discount->applyTo($afterPromotions), 2);
    }

    public function isFullyRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /*
    |---------------------------------------------------------------------
    | Philippine VAT (Option A — VAT-inclusive pricing)
    |---------------------------------------------------------------------
    | Your product prices are treated as VAT-inclusive (the standard PH
    | retail convention). The 12% VAT is extracted here on display only
    | — no DB column is added and prices are never changed.
    | BIR (RR 16-2017) requires the VAT amount on every receipt.
    |
    | VAT = total × rate/(1+rate)   = total × 0.12/1.12 ≈ total × 0.10714
    | e.g. ₱660.00 → VAT ₱70.71, net ₱589.29
    |
    | Toggleable via config/vat.php (enabled, rate) for the 3% percentage-
    | tax businesses below the PHP 3,000,000 threshold.
    */

    /** Whether VAT applies (configurable; true by default for VAT-registered). */
    public function vatRegistered(): bool
    {
        return (bool) config('vat.enabled', true);
    }

    /** VAT component of total_amount under VAT-inclusive pricing. */
    public function getVatAmountAttribute(): float
    {
        if (! $this->vatRegistered()) {
            return 0.0;
        }

        return round((float) $this->total_amount * self::VAT_RATE / (1 + self::VAT_RATE), 2);
    }

    /** Net sales amount before VAT (= total − VAT). */
    public function getNetAmountAttribute(): float
    {
        return $this->vatRegistered()
            ? round((float) $this->total_amount - $this->vat_amount, 2)
            : (float) $this->total_amount;
    }

    /** Standard Philippine VAT rate (12%, per NIRC + RA 11633 / RR 1-2026). */
    public const VAT_RATE = 0.12;

    /** The configured VAT rate as a percentage (e.g. 12 for "12%"). */
    public function getVatRateAttribute(): float
    {
        return (float) config('vat.rate', self::VAT_RATE);
    }
}
