<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Adjust the stock level for a product and record the change as a
     * StockMovement. Runs inside a transaction with a row lock so concurrent
     * callers cannot read/interleave the same inventory row (TOCTOU guard).
     *
     * @param  int  $productId
     * @param  float  $quantity  signed delta: negative deducts, positive adds
     * @param  string  $type  movement_type enum value (sale, purchase, refund, ...)
     * @param  string  $referenceType  e.g. 'sale_transaction', 'purchase_order'
     * @param  int  $referenceId  id of the referencing row
     * @param  string|null  $reason  optional human-readable reason
     */
    public function adjustStock(
        int $productId,
        float $quantity,
        string $type,
        string $referenceType,
        int $referenceId,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($productId, $quantity, $type, $referenceType, $referenceId, $reason): void {
            $inventory = Inventory::query()
                ->lockForUpdate()
                ->firstOrCreate(['product_id' => $productId], ['stock_quantity' => 0]);

            $before = (float) $inventory->stock_quantity;
            $inventory->stock_quantity = round($before + $quantity, 3);
            $inventory->save();

            StockMovement::create([
                'product_id' => $productId,
                'movement_type' => $type,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $inventory->stock_quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'employee_id' => auth()->id(),
            ]);
        });
    }

    /**
     * Current stock quantity for a product (0 when no inventory row exists).
     */
    public function getCurrentStock(int $productId): float
    {
        $inventory = Inventory::query()->where('product_id', $productId)->first();

        return $inventory ? (float) $inventory->stock_quantity : 0.0;
    }

    /**
     * Chronological stock movement history for a product.
     */
    public function getStockHistory(int $productId): Collection
    {
        return StockMovement::query()
            ->where('product_id', $productId)
            ->orderBy('movement_id')
            ->get();
    }

    /**
     * Returns the reorder signal type for the product's current stock, or null
     * when stock is above both the reorder and critical levels.
     */
    public function checkReorderNeeded(int $productId): ?string
    {
        $inventory = Inventory::query()->where('product_id', $productId)->first();

        if ($inventory === null) {
            return null;
        }

        $product = $inventory->product;
        $stock = (float) $inventory->stock_quantity;
        $reorderLevel = $product ? (float) ($product->reorder_level ?? 0) : 0.0;
        $criticalLevel = $product ? (float) ($product->critical_reorder_level ?? 0) : 0.0;

        if ($stock <= 0) {
            return 'out_of_stock';
        }

        if ($criticalLevel > 0 && $stock <= $criticalLevel) {
            return 'critical';
        }

        if ($stock <= $reorderLevel) {
            return 'low_stock';
        }

        return null;
    }
}
