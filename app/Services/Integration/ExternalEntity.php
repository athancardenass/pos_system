<?php

namespace App\Services\Integration;

/**
 * Entities that cross system boundaries (POS ↔ CRM, HR, Procurement).
 *
 * Phase 0: declared for documentation + audit. The stable external ID
 * column/migration is PENDING joint architecture decision.
 */
enum ExternalEntity: string
{
    case Customer = 'customer';
    case Employee = 'employee';
    case Product = 'product';
    case Inventory = 'inventory';
    case Supplier = 'supplier';
    case PurchaseOrder = 'purchase_order';
    case Sale = 'sale';
}
