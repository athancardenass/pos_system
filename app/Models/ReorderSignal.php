<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderSignal extends Model
{
    protected $table = 'reorder_signal';

    protected $primaryKey = 'signal_id';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'signal_type',
        'current_stock',
        'reorder_level',
        'suggested_quantity',
        'supplier_id',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'suggested_quantity' => 'decimal:3',
            'created_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    /**
     * Scope signals matching a status (default: open).
     */
    public function scopeWithStatus(Builder $query, string $status = 'open'): Builder
    {
        return $query->where('status', $status)->orderBy('signal_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
