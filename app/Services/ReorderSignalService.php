<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ReorderSignal;
use Illuminate\Database\Eloquent\Collection;

class ReorderSignalService
{
    /**
     * Scan every inventory row and open a reorder signal for any product that
     * is at or below its reorder/critical level. An already-open signal of the
     * same type is not duplicated.
     */
    public function scanForLowStock(): void
    {
        $inventories = Inventory::query()->with('product')->get();

        foreach ($inventories as $inventory) {
            $product = $inventory->product;

            if ($product === null) {
                continue;
            }

            $type = $this->resolveType(
                (float) $inventory->stock_quantity,
                (float) ($product->reorder_level ?? 0),
                (float) ($product->critical_reorder_level ?? 0)
            );

            if ($type === null) {
                continue;
            }

            $alreadyOpen = ReorderSignal::query()
                ->where('product_id', $product->product_id)
                ->where('signal_type', $type)
                ->where('status', 'open')
                ->exists();

            if (! $alreadyOpen) {
                $this->createSignal($product->product_id, $type);
            }
        }
    }

    /**
     * Create a reorder signal for a product. current_stock / reorder_level are
     * snapshotted from live data and suggested_quantity is the amount needed to
     * bring stock back up to the reorder level.
     */
    public function createSignal(int $productId, string $type): ReorderSignal
    {
        $product = Product::query()->findOrFail($productId);

        $inventory = Inventory::query()->where('product_id', $productId)->first();
        $stock = $inventory ? (float) $inventory->stock_quantity : 0.0;
        $reorderLevel = (float) ($product->reorder_level ?? 0);

        $suggested = $reorderLevel > 0 ? max(0.0, round($reorderLevel - $stock, 3)) : null;

        return ReorderSignal::create([
            'product_id' => $productId,
            'signal_type' => $type,
            'current_stock' => $stock,
            'reorder_level' => $reorderLevel,
            'suggested_quantity' => $suggested,
            'supplier_id' => $product->supplier_id,
            'status' => 'open',
        ]);
    }

    /**
     * Resolve (dismiss) an open signal.
     */
    public function resolveSignal(int $signalId): void
    {
        $signal = ReorderSignal::query()->findOrFail($signalId);

        $signal->update([
            'status' => 'dismissed',
            'resolved_at' => now(),
        ]);
    }

    /**
     * All signals with the given status (default: open), newest ordering by id.
     */
    public function getSignals(string $status = 'open'): Collection
    {
        return ReorderSignal::query()
            ->with('product', 'supplier')
            ->where('status', $status)
            ->orderBy('signal_id')
            ->get();
    }

    /**
     * @return string|null one of low_stock|critical|out_of_stock
     */
    private function resolveType(float $stock, float $reorderLevel, float $criticalLevel): ?string
    {
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
