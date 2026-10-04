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

    /**
     * Record a cashier-sensitive action with separate requester and approver identities.
     * Existing audit rows keep using record() and remain untouched.
     *
     * @param array<string, mixed> $details
     */
    public static function recordSensitive(
        string $action,
        int $requestedByEmployeeId,
        ?int $approvedByEmployeeId,
        ?string $registerId,
        array $details,
        ?string $tableAffected = null,
        ?int $recordId = null,
        ?string $description = null,
    ): void {
        AuditLog::query()->create([
            'employee_id' => $requestedByEmployeeId,
            'requested_by_employee_id' => $requestedByEmployeeId,
            'approved_by_employee_id' => $approvedByEmployeeId,
            'register_id' => $registerId,
            'action' => $action,
            'table_affected' => $tableAffected,
            'record_id' => $recordId,
            'description' => $description,
            'details' => $details,
            'action_timestamp' => now(),
        ]);
    }
}
