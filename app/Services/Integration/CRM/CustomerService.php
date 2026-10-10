<?php

namespace App\Services\Integration\CRM;

use App\Models\Customer;
use App\Services\Integration\ExternalSystemInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * CRM integration boundary (Phase 0 — stub).
 *
 * FUTURE: resolves/creates customers in the CRM system.
 * POS keeps only a nullable integer customer_id reference on sale history;
 * that value is a CRM-owned identifier, not a local foreign key.
 *
 * ASSUMPTION (requires validation): CRM owns customer master data.
 * Pending: CRM system inspection — we do not yet know its API shape,
 * ID scheme, or field structure.
 */
class CustomerService implements ExternalSystemInterface
{
    public function systemName(): string
    {
        return 'crm';
    }

    /**
     * Look up or create a customer in CRM, returning the CRM-side identifier.
     * Phase 0: not implemented. Returns null so POS behaviour is unchanged.
     *
     * @return string|null The CRM external ID, or null if not yet integrated.
     */
    public function resolve(Customer $customer): ?string
    {
        return null;
    }

    /**
     * Sync a new POS customer to CRM (called from CustomerController::store).
     * Phase 0: stub — logs intent, performs no external call.
     */
    public function syncCreate(Customer $customer): void
    {
        // Phase 1+: implement CRM API call here.
    }

    /**
     * Search the CRM's active customer directory without creating a local mapping.
     *
     * @return array{status: int, customers: list<array{external_id: string, customer_code: string, display_name: string, status: string}>, total: int}
     */
    public function searchReadOnly(string $search): array
    {
        $baseUrl = rtrim(trim((string) config('services.crm.base_url')), '/');
        $token = trim((string) config('services.crm.token'));

        if ($baseUrl === '' || $token === '' || !\App\Services\Integration\Endpoint::allowed($baseUrl)) {
            return ['status' => 503, 'customers' => [], 'total' => 0];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->get($baseUrl.'/api/v1/customers', [
                    'search' => $search,
                    'status' => 'Active',
                    'page' => 1,
                    'per_page' => 10,
                ]);
        } catch (ConnectionException) {
            return ['status' => 504, 'customers' => [], 'total' => 0];
        }

        if (! $response->successful()) {
            return ['status' => 502, 'customers' => [], 'total' => 0];
        }

        $payload = $response->json();
        $total = is_array($payload)
            ? filter_var(data_get($payload, 'meta.total'), FILTER_VALIDATE_INT)
            : false;

        if (! is_array($payload) || ! is_array($payload['data'] ?? null) || $total === false || $total < 0) {
            return ['status' => 502, 'customers' => [], 'total' => 0];
        }

        $customers = [];

        foreach ($payload['data'] as $customer) {
            if (! is_array($customer)) {
                return ['status' => 502, 'customers' => [], 'total' => 0];
            }

            foreach (['external_id', 'customer_code', 'display_name', 'status'] as $field) {
                if (! is_string($customer[$field] ?? null) || trim($customer[$field]) === '') {
                    return ['status' => 502, 'customers' => [], 'total' => 0];
                }
            }

            $loyalty = $customer['loyalty'] ?? null;
            $discountEligibility = $customer['discount_eligibility'] ?? null;
            if (! is_array($loyalty)
                || ! is_int($loyalty['points'] ?? null)
                || ! is_string($loyalty['membership_type'] ?? null)
                || ! is_bool($loyalty['eligible'] ?? null)
                || ! is_string($loyalty['label'] ?? null)
                || ! is_int($loyalty['remaining'] ?? null)
                || ! is_array($discountEligibility)
                || ! is_bool($discountEligibility['eligible'] ?? null)
                || ! (is_string($discountEligibility['type'] ?? null) || ($discountEligibility['type'] ?? null) === null)
                || ($discountEligibility['eligible'] && ! in_array($discountEligibility['type'], ['Senior Citizen', 'PWD'], true))) {
                return ['status' => 502, 'customers' => [], 'total' => 0];
            }

            $customers[] = [
                'external_id' => $customer['external_id'],
                'customer_code' => $customer['customer_code'],
                'display_name' => $customer['display_name'],
                'status' => $customer['status'],
                'loyalty' => [
                    'points' => $loyalty['points'],
                    'membership_type' => $loyalty['membership_type'],
                    'eligible' => $loyalty['eligible'],
                    'label' => $loyalty['label'],
                    'remaining' => $loyalty['remaining'],
                ],
                'discount_eligibility' => [
                    'eligible' => $discountEligibility['eligible'],
                    'type' => $discountEligibility['type'],
                ],
            ];
        }

        return ['status' => 200, 'customers' => $customers, 'total' => (int) $total];
    }

    /**
     * Resolve an exact CRM identity and keep POS's historical integer key intact.
     * No local row is created unless CRM returns exactly one matching active customer.
     *
     * @return array{status:int,customer:array{customer_id:int,external_id:string,display_name:string,contact:?string}|null}
     */
    public function syncFromCrm(string $externalId): array
    {
        if (! preg_match('/^crm:customer:([A-Za-z0-9][A-Za-z0-9._:-]*)$/D', $externalId, $matches)) {
            return ['status' => 422, 'customer' => null];
        }

        $baseUrl = rtrim(trim((string) config('services.crm.base_url')), '/');
        $token = trim((string) config('services.crm.token'));
        if ($baseUrl === '' || $token === '' || !\App\Services\Integration\Endpoint::allowed($baseUrl)) {
            return ['status' => 503, 'customer' => null];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->get($baseUrl.'/api/v1/customers', [
                    'customer_code' => $matches[1],
                    'status' => 'Active',
                    'page' => 1,
                    'per_page' => 2,
                ]);
        } catch (ConnectionException) {
            return ['status' => 504, 'customer' => null];
        }

        if (! $response->successful()) {
            return ['status' => 502, 'customer' => null];
        }

        $payload = $response->json();
        $total = is_array($payload) ? filter_var(data_get($payload, 'meta.total'), FILTER_VALIDATE_INT) : false;
        $customers = is_array($payload) ? ($payload['data'] ?? null) : null;
        if (! is_array($customers) || $total === false || $total < 0) {
            return ['status' => 502, 'customer' => null];
        }
        if ($total === 0 && $customers === []) {
            return ['status' => 404, 'customer' => null];
        }
        if ($total !== 1 || count($customers) !== 1 || ! is_array($customers[0])) {
            return ['status' => 502, 'customer' => null];
        }

        $crm = $customers[0];
        foreach (['external_id', 'customer_code', 'first_name', 'last_name', 'status'] as $field) {
            if (! is_string($crm[$field] ?? null) || trim($crm[$field]) === '') {
                return ['status' => 502, 'customer' => null];
            }
        }
        foreach (['email', 'phone'] as $field) {
            if (! (is_string($crm[$field] ?? null) || ($crm[$field] ?? null) === null)) {
                return ['status' => 502, 'customer' => null];
            }
        }
        $discountEligibility = $crm['discount_eligibility'] ?? null;
        if (! is_array($discountEligibility)
            || ! is_bool($discountEligibility['eligible'] ?? null)
            || ! (is_string($discountEligibility['type'] ?? null) || ($discountEligibility['type'] ?? null) === null)
            || (($discountEligibility['eligible'] ?? false) && ! in_array($discountEligibility['type'], ['Senior Citizen', 'PWD'], true))) {
            return ['status' => 502, 'customer' => null];
        }
        if ($crm['external_id'] !== $externalId || $crm['customer_code'] !== $matches[1] || $crm['status'] !== 'Active') {
            return ['status' => 502, 'customer' => null];
        }

        $email = trim((string) ($crm['email'] ?? ''));
        $customer = DB::transaction(function () use ($crm, $discountEligibility, $externalId, $email): ?Customer {
            $existing = Customer::query()->where('crm_external_id', $externalId)->lockForUpdate()->first();
            if ($email !== '' && Customer::query()
                ->where('email', $email)
                ->where(function ($query) use ($externalId): void {
                    $query->whereNull('crm_external_id')->orWhere('crm_external_id', '<>', $externalId);
                })
                ->exists()) {
                return null;
            }

            $attributes = [
                'first_name' => trim($crm['first_name']),
                'last_name' => trim($crm['last_name']),
                'contact_number' => filled($crm['phone'] ?? null) ? trim((string) $crm['phone']) : null,
                'email' => $email !== '' ? $email : null,
                'customer_status' => 'active',
                'crm_discount_eligible' => $discountEligibility['eligible'],
                'crm_discount_type' => $discountEligibility['eligible'] ? $discountEligibility['type'] : null,
            ];

            if ($existing) {
                $existing->fill($attributes)->save();

                return $existing->refresh();
            }

            return Customer::query()->create([
                ...$attributes,
                'crm_external_id' => $externalId,
                'loyalty_points' => 0,
                'total_purchases' => 0,
            ]);
        });

        if (! $customer) {
            return ['status' => 409, 'customer' => null];
        }

        return [
            'status' => $customer->wasRecentlyCreated ? 201 : 200,
            'customer' => [
                'customer_id' => (int) $customer->customer_id,
                'external_id' => $externalId,
                'display_name' => $customer->fullName(),
                'contact' => $customer->contact_number,
                'discount_eligibility' => [
                    'eligible' => (bool) $customer->crm_discount_eligible,
                    'type' => $customer->crm_discount_type,
                ],
            ],
        ];
    }
}
