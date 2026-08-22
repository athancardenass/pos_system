<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payment';

    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'payment_method',
        'amount_paid',
        'change_amount',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function saleTransaction(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }
}
