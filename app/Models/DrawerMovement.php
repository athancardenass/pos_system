<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawerMovement extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function sale()
    {
        return $this->belongsTo(SaleTransaction::class, 'sale_id', 'transaction_id');
    }

    public function refund()
    {
        return $this->belongsTo(SaleRefund::class, 'refund_id', 'refund_id');
    }
}
