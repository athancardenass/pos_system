<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorActionPermission extends Model
{
    protected $table = 'supervisor_action_permission';

    protected $fillable = ['employee_id', 'action', 'max_amount'];

    protected function casts(): array
    {
        return ['max_amount' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
