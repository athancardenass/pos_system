<?php

namespace App\Services\Integration\Procurement;

use App\Services\Integration\ExternalSystemInterface;

/**
 * Procurement integration boundary (Phase 0 — stub).
 *
 * FUTURE: Procurement owns suppliers, purchase orders, PO approval, and
 * the receiving workflow. When Procurement "receives" a PO, it should
 * increment POS inventory.stock_quantity.
 *
 * ASSUMPTION (requires validation): Procurement owns supplier + PO master data.
 * Pending: Procurement system inspection — unknown API shape, ID scheme,
 * and how it will notify POS of stock receipts.
 *
 * Local purchase-order tables and receiving UI have been removed.
 * POS retains supplier references on products. External stock receiving
 * requires a separately implemented integration contract.
 */
class ProcurementService implements ExternalSystemInterface
{
    public function systemName(): string
    {
        return 'procurement';
    }

    /**
     * Handle a stock-receipt event from Procurement.
     * Phase 0: stub — no-op. External receiving is not implemented.
     *
     * @param string $externalPOId  The Procurement-side PO identifier.
     * @param array  $items         Lines: [['external_product_id' => x, 'quantity' => n], ...]
     */
    public function receiveStockReceipt(string $externalPOId, array $items): void
    {
        // Phase 1+: map external IDs to local products, update inventory.
    }

    /**
     * Resolve a local supplier to its Procurement-side identifier.
     * Phase 0: returns null.
     */
    public function resolveSupplier(?int $localSupplierId): ?string
    {
        return null;
    }
}
