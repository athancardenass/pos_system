<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $table = 'discount';

    protected $primaryKey = 'discount_id';

    public $timestamps = false;

    protected $fillable = [
        'discount_name',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function saleTransactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class, 'discount_id', 'discount_id');
    }

    public function isActive(?\DateTimeInterface $on = null): bool
    {
        $on = $on ? \Illuminate\Support\Carbon::parse($on)->startOfDay() : now()->startOfDay();

        if ($this->start_date && $on->lt($this->start_date->startOfDay())) {
            return false;
        }

        if ($this->end_date && $on->gt($this->end_date->startOfDay())) {
            return false;
        }

        return true;
    }

    public function applyTo(float $subtotal): float
    {
        $amount = $this->discount_type === 'percentage'
            ? $subtotal * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return max(0, round($subtotal - $amount, 2));
    }
}
