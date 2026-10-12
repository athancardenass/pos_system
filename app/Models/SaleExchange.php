<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleExchange extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['difference' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function original()
    {
        return $this->belongsTo(SaleTransaction::class, 'original_sale_id', 'transaction_id');
    }

    public function replacement()
    {
        return $this->belongsTo(SaleTransaction::class, 'replacement_sale_id', 'transaction_id');
    }

    public function refund()
    {
        return $this->belongsTo(SaleRefund::class, 'refund_id', 'refund_id');
    }
}
