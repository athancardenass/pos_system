<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'history' => 'array', 'quantity' => 'decimal:3',
            'expected_cash' => 'decimal:2', 'actual_cash' => 'decimal:2', 'difference' => 'decimal:2'];
    }
}
