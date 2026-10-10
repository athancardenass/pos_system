<?php

namespace App\Services\Integration\HR;

use App\Models\Employee;
use App\Services\Integration\ExternalSystemInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * HRMS integration boundary.
 *
 * FUTURE: HR owns employee master data. POS retains the local employee_id
 * for authentication, cashier assignment, and audit trails.
 *
 * HRMS owns employee records. This read-only lookup uses the stable
 * employee_no key and never changes POS authentication or employee_id.
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

    /**
     * Look up one HRMS employee by its exact employee number.
     *
     * @return array{status: int, employee: array{external_id: string, employee_no: string, display_name: string, employment_status: string, position: string|null}|null, total: int}
     */
    public function lookupByEmployeeNo(string $employeeNo): array
    {
        $baseUrl = rtrim(trim((string) config('services.hrms.base_url')), '/');
        $token = trim((string) config('services.hrms.token'));

        if ($baseUrl === '' || $token === '' || !\App\Services\Integration\Endpoint::allowed($baseUrl, 'hrms')) {
            return ['status' => 503, 'employee' => null, 'total' => 0];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->get($baseUrl.config('services.hrms.employees_path', '/api/v1/employees'), [
                    'employee_no' => $employeeNo,
                    'page' => 1,
                    'per_page' => 2,
                ]);
        } catch (ConnectionException) {
            return ['status' => 504, 'employee' => null, 'total' => 0];
        }

        if (! $response->successful()) {
            return ['status' => 502, 'employee' => null, 'total' => 0];
        }

        $payload = $response->json();
        if (is_array($payload) && ! isset($payload['meta']) && array_key_exists('count', $payload)) {
            if (! is_array($payload['data'] ?? null) || ! is_int($payload['count'])
                || $payload['count'] !== count($payload['data'])) {
                return ['status' => 502, 'employee' => null, 'total' => 0];
            }
            $payload['data'] = array_values(array_filter($payload['data'],
                static fn ($employee) => is_array($employee) && ($employee['employee_no'] ?? null) === $employeeNo));
            $payload['data'] = array_map(EmployeeFeedNormalizer::normalize(...), $payload['data']);
            $payload['meta'] = ['total' => count($payload['data'])];
        }
        $total = is_array($payload)
            ? filter_var(data_get($payload, 'meta.total'), FILTER_VALIDATE_INT)
            : false;

        if (! is_array($payload) || ! is_array($payload['data'] ?? null) || $total === false || $total < 0) {
            return ['status' => 502, 'employee' => null, 'total' => 0];
        }

        $matches = array_values(array_filter(
            $payload['data'],
            static fn ($employee): bool => is_array($employee) && ($employee['employee_no'] ?? null) === $employeeNo,
        ));

        if (count($matches) !== (int) $total) {
            return ['status' => 502, 'employee' => null, 'total' => 0];
        }

        if ($matches === []) {
            return ['status' => 200, 'employee' => null, 'total' => 0];
        }

        if (count($matches) !== 1) {
            return ['status' => 502, 'employee' => null, 'total' => 0];
        }

        $employee = $matches[0];
        $expectedExternalId = 'hrms:employee:'.$employeeNo;
        if (($employee['external_id'] ?? null) !== $expectedExternalId
            || ! is_string($employee['display_name'] ?? null)
            || ! is_string($employee['employment_status'] ?? null)
            || ! (is_string($employee['position'] ?? null) || ($employee['position'] ?? null) === null)) {
            return ['status' => 502, 'employee' => null, 'total' => 0];
        }

        return [
            'status' => 200,
            'employee' => [
                'external_id' => $expectedExternalId,
                'employee_no' => $employeeNo,
                'display_name' => $employee['display_name'],
                'employment_status' => $employee['employment_status'],
                'position' => $employee['position'],
            ],
            'total' => 1,
        ];
    }
}
