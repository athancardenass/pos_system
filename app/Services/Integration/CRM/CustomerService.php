<?php

namespace App\Services\Integration\CRM;

use App\Models\Customer;
use App\Services\Integration\ExternalSystemInterface;

/**
 * CRM integration boundary (Phase 0 — stub).
 *
 * FUTURE: resolves/creates customers in the CRM system.
 * POS retains the local customer_id reference for sale association.
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
}
