<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountOutboxEvent extends Model
{
    protected $table = 'discount_outbox_event';

    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'sale_transaction_id',
        'discount_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'available_at',
        'created_at',
        'processed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'created_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
