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
        'vat_rate',
        'promo_discount',
        'coupon_discount',
        'senior_pwd_type',
        'senior_pwd_name',
        'senior_pwd_id_number',
        'discount_snapshot',
        'tax_snapshot',
        'checkout_idempotency_key',
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
            'vat_rate' => 'decimal:4',
            'promo_discount' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'discount_snapshot' => 'array',
            'tax_snapshot' => 'array',
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
        if (is_array($this->discount_snapshot) && array_key_exists('amount', $this->discount_snapshot)) {
            return round((float) $this->discount_snapshot['amount'], 2);
        }

        if (! $this->discount) {
            return 0.0;
        }

        $afterPromotions = round((float) $this->subtotal - (float) $this->promo_discount, 2);

        return round($afterPromotions - (float) $this->discount->applyTo($afterPromotions), 2);
    }

    /** @return array<string, mixed>|null */
    public function discountBreakdown(): ?array
    {
        if (is_array($this->discount_snapshot) && $this->discount_snapshot !== []) {
            return $this->discount_snapshot;
        }

        if (! $this->discount) {
            return null;
        }

        return [
            'type' => 'standard',
            'name' => (string) $this->discount->discount_name,
            'reference' => 'DISC-'.str_pad((string) $this->discount->discount_id, 3, '0', STR_PAD_LEFT),
            'amount' => $this->manualDiscountAmount(),
        ];
    }

    /** @return array{vat_rate: float, vatable_sales: float, vat_amount: float, vat_exempt_sales: float, vat_exemption_amount: float, vat_enabled: bool} */
    public function taxBreakdown(): array
    {
        $snapshot = $this->tax_snapshot;
        if (is_array($snapshot) && array_key_exists('vat_amount', $snapshot)) {
            return [
                'vat_rate' => (float) ($snapshot['vat_rate'] ?? $this->vat_rate),
                'vatable_sales' => (float) ($snapshot['vatable_sales'] ?? 0),
                'vat_amount' => (float) $snapshot['vat_amount'],
                'vat_exempt_sales' => (float) ($snapshot['vat_exempt_sales'] ?? 0),
                'vat_exemption_amount' => (float) ($snapshot['vat_exemption_amount'] ?? 0),
                'vat_enabled' => (bool) ($snapshot['vat_enabled'] ?? $this->vatRegistered()),
            ];
        }

        return [
            'vat_rate' => $this->vat_rate,
            'vatable_sales' => $this->vatRegistered() ? $this->net_amount : 0.0,
            'vat_amount' => $this->vat_amount,
            'vat_exempt_sales' => 0.0,
            'vat_exemption_amount' => 0.0,
            'vat_enabled' => $this->vatRegistered(),
        ];
    }

    public function isFullyRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /*
    |---------------------------------------------------------------------
    | Philippine VAT (Option A — VAT-inclusive pricing)
    |---------------------------------------------------------------------
    | Product prices are VAT-inclusive. The VAT component is extracted for
    | receipt display using the rate saved with this sale; prices and checkout
    | totals are not changed by the VAT calculation.
    | BIR (RR 16-2017) requires the VAT amount on every receipt.
    |
    | VAT = total × rate/(1+rate)
    | e.g. ₱660.00 → VAT ₱70.71, net ₱589.29
    |
    | VAT registration remains controlled by config/vat.php. Managers can
    | update the rate for future sales from the VAT settings page.
    */

    /** Whether VAT applies (configurable; true by default for VAT-registered). */
    public function vatRegistered(): bool
    {
        return (bool) config('vat.enabled', true);
    }

    /** VAT component of total_amount under VAT-inclusive pricing. */
    public function getVatAmountAttribute(): float
    {
        $snapshot = $this->tax_snapshot;
        if (is_array($snapshot) && array_key_exists('vat_amount', $snapshot)) {
            return round((float) $snapshot['vat_amount'], 2);
        }

        if (! $this->vatRegistered()) {
            return 0.0;
        }

        $rate = $this->vat_rate;

        return round((float) $this->total_amount * $rate / (1 + $rate), 2);
    }

    /** Net sales amount before VAT (= total − VAT). */
    public function getNetAmountAttribute(): float
    {
        return $this->vatRegistered()
            ? round((float) $this->total_amount - $this->vat_amount, 2)
            : (float) $this->total_amount;
    }

    /** Default VAT rate used for new installations and pre-setting fallbacks. */
    public const VAT_RATE = 0.12;

    /** Sale-time rate, with the configured default for unsaved/legacy instances. */
    public function getVatRateAttribute(mixed $value): float
    {
        return $value !== null ? (float) $value : (float) config('vat.rate', self::VAT_RATE);
    }
}
