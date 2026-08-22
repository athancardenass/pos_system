<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'action',
        'table_affected',
        'record_id',
        'action_timestamp',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'action_timestamp' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
