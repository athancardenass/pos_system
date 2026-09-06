<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleRefund extends Model
{
    protected $table = 'sale_refund';
    protected $primaryKey = 'refund_id';
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'employee_id',
        'refund_amount',
        'reason',
        'notes',
        'is_full_refund',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'is_full_refund' => 'boolean',
            'refunded_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleRefundItem::class, 'refund_id', 'refund_id');
    }
}
