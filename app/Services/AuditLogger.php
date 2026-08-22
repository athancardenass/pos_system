<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public static function record(
        string $action,
        ?string $tableAffected = null,
        ?int $recordId = null,
        ?string $description = null,
    ): void {
        $employeeId = auth()->id();

        if (! $employeeId) {
            return;
        }

        AuditLog::query()->create([
            'employee_id' => $employeeId,
            'action' => $action,
            'table_affected' => $tableAffected,
            'record_id' => $recordId,
            'description' => $description,
            'action_timestamp' => now(),
        ]);
    }
}
