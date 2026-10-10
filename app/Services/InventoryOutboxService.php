<?php

namespace App\Services;

use App\Models\InventoryOutboxEvent;
use Illuminate\Support\Facades\DB;

class InventoryOutboxService
{
    /** @param list<array{product_id: int, quantity: float|int|string}> $items */
    public function recordSaleEvent(int $saleId, array $items): void
    {
        foreach ($this->aggregate($items) as $productId => $quantity) {
            $this->record('sale.completed', $saleId, null, $productId, -$quantity);
        }
    }

    /** @param list<array{product_id: int, quantity: float|int|string}> $items */
    public function recordRefundEvent(int $saleId, int $refundId, array $items): void
    {
        foreach ($this->aggregate($items) as $productId => $quantity) {
            $this->record('sale.refunded', $saleId, $refundId, $productId, $quantity);
        }
    }

    /** @param array<int, float> $items */
    private function recordRefundItems(int $saleId, int $refundId, array $items): void
    {
        foreach ($items as $productId => $quantity) {
            if ($quantity > 0) {
                $this->record('sale.refunded', $saleId, $refundId, (int) $productId, (float) $quantity);
            }
        }
    }

    /** @param list<array{product_id: int, quantity: float|int|string}> $items
     * @return array<int, float>
     */
    private function aggregate(array $items): array
    {
        $totals = [];
        foreach ($items as $item) {
            $id = (int) $item['product_id'];
            $totals[$id] = ($totals[$id] ?? 0.0) + (float) $item['quantity'];
        }

        return $totals;
    }

    private function record(string $type, int $saleId, ?int $refundId, int $productId, float $quantity): void
    {
        $inventorySku = DB::table('inventory_product_mappings')
            ->where('pos_product_id', $productId)
            ->value('inventory_sku');

        InventoryOutboxEvent::query()->create([
            'event_type' => $type,
            'sale_transaction_id' => $saleId,
            'refund_id' => $refundId,
            'product_id' => $productId,
            'inventory_sku' => $inventorySku,
            'quantity' => round($quantity, 3),
            'payload' => [
                'sale_id' => $saleId,
                'refund_id' => $refundId,
                'product_id' => $productId,
                'quantity_delta' => round($quantity, 3),
            ],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
        ]);
    }
}
