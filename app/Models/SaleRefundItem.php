<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRefundItem extends Model
{
    protected $table = 'sale_refund_item';
    protected $primaryKey = 'refund_item_id';
    public $timestamps = false;

    protected $fillable = [
        'refund_id',
        'sale_detail_id',
        'quantity',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(SaleRefund::class, 'refund_id', 'refund_id');
    }

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class, 'sale_detail_id', 'sale_detail_id');
    }
}
