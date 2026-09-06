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
        return (int) $this->refundItems()->sum('quantity');
    }

    /** Quantity still refundable on this line. */
    public function refundableQuantity(): int
    {
        return max(0, (int) $this->quantity - $this->refundedQuantity());
    }
}