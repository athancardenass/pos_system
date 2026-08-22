<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_order';

    protected $primaryKey = 'purchase_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'employee_id',
        'order_date',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_id', 'purchase_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
