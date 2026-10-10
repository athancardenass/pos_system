<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $table = 'receipt';

    protected $primaryKey = 'receipt_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'receipt_number',
        'issued_date',
        'register_id',
        'sequence_number',
        'settings_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'datetime',
            'settings_snapshot' => 'array',
        ];
    }

    public function saleTransaction(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }
}
