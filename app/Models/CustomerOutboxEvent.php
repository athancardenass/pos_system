<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOutboxEvent extends Model
{
    protected $table = 'customer_outbox_event';

    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'sale_transaction_id',
        'refund_id',
        'customer_id',
        'crm_external_id',
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
