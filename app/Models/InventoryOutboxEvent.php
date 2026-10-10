<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOutboxEvent extends Model
{
    protected $table = 'inventory_outbox_event';

    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'sale_transaction_id',
        'refund_id',
        'product_id',
        'inventory_sku',
        'quantity',
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
            'quantity' => 'decimal:3',
            'payload' => 'array',
            'available_at' => 'datetime',
            'created_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
