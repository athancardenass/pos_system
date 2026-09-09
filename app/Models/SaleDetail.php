<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    protected $table = 'sale_details';
    protected $primaryKey = 'sale_detail_id';
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function saleTransaction()
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function refundItems()
    {
        return $this->hasMany(SaleRefundItem::class, 'sale_detail_id', 'sale_detail_id');
    }

    /** Quantity of this line already refunded across all refunds. */
    public function refundedQuantity(): int
    {
        // When the caller eager-aggregated the sum (PosController::show loads
        // withSum('refundItems as refunded_qty', 'quantity')), use that number —
        // the key exists even when SUM() is NULL (no refund rows => 0), so no
        // per-line query is needed (N+1 fix, behavior identical).
        if (array_key_exists('refunded_qty', $this->getAttributes())) {
            return (int) $this->refunded_qty;
        }

        // Live per-row query otherwise: callers like RefundService run this INSIDE
        // the locked refund transaction and must see the freshest totals, so this
        // path is deliberately kept exactly as it was.
        return (int) $this->refundItems()->sum('quantity');
    }

    /** Quantity still refundable on this line. */
    public function refundableQuantity(): int
    {
        return max(0, (int) $this->quantity - $this->refundedQuantity());
    }
}