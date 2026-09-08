<?php

namespace App\Services\Integration\HR;

use App\Models\Employee;
use App\Services\Integration\ExternalSystemInterface;

/**
 * HR integration boundary (Phase 0 — stub).
 *
 * FUTURE: HR owns employee master data. POS retains the local employee_id
 * for authentication, cashier assignment, and audit trails.
 *
 * ASSUMPTION (requires validation): HR owns employee records.
 * Pending: HR system inspection — unknown API shape, ID scheme, and
 * whether SSO/OAuth is used for authentication.
 */
class EmployeeService implements ExternalSystemInterface
{
    public function systemName(): string
    {
        return 'hr';
    }

    /**
     * Resolve a POS employee against the HR master.
     * Phase 0: returns null (POS continues using local auth).
     *
     * @return string|null The HR external employee ID, or null.
     */
    public function resolve(Employee $employee): ?string
    {
        return null;
    }
}
