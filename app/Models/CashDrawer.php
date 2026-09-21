<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawer extends Model
{
    protected $table = 'cash_drawers';

    protected $primaryKey = 'drawer_id';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'actual_cash',
        'difference',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function calculateDifference(): float
    {
        return round((float) $this->actual_cash - (float) $this->expected_cash, 2);
    }
}
