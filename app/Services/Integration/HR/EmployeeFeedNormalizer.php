<?php

namespace App\Services\Integration\HR;

class EmployeeFeedNormalizer
{
    /** Adapt the HR team's POS feed without deriving identity from names/email. */
    public static function normalize(array $employee): array
    {
        if (! array_key_exists('external_id', $employee) && is_string($employee['employee_no'] ?? null)) {
            $employee['external_id'] = 'hrms:employee:'.$employee['employee_no'];
        }
        if (! array_key_exists('employment_status', $employee)) {
            $employee['employment_status'] = $employee['status'] ?? null;
        }
        if (! array_key_exists('contact_number', $employee)) {
            $employee['contact_number'] = $employee['mobile'] ?? null;
        }
        if (! array_key_exists('display_name', $employee)) {
            $employee['display_name'] = $employee['full_name'] ?? trim(
                (is_string($employee['first_name'] ?? null) ? $employee['first_name'] : '').' '.
                (is_string($employee['last_name'] ?? null) ? $employee['last_name'] : '')
            );
        }
        return $employee;
    }
}
