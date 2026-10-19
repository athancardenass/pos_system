<?php

namespace App\Services\Integration\Inventory;

use App\Services\Integration\DirectoryClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LiveStockService
{
    public function configured(): bool
    {
        return filled(config('services.inventory.base_url')) || filled(config('services.inventory.token'));
    }

    /** Fetch fresh source quantities; never send or acknowledge pending events here. */
    public function snapshot(): array
    {
        if (! $this->configured()) return [];

        try {
            $pendingBefore = [];
            foreach (DB::table('inventory_outbox_event')->whereNull('processed_at')->where('quantity', '<', 0)
                ->orderBy('event_id')->sharedLock()->get(['product_id', 'quantity']) as $event) {
                $pendingBefore[$event->product_id] = ($pendingBefore[$event->product_id] ?? 0) + (float) $event->quantity;
            }
            $rows = app(DirectoryClient::class)->rows('inventory', '/api/v1/catalog/products');
            $stocks = [];
            foreach ($rows as $row) {
                $sku = $row['sku'] ?? null;
                $quantity = $row['on_hand'] ?? null;
                if (! is_string($sku) || $sku === '' || isset($stocks[$sku])
                    || ($row['external_id'] ?? null) !== 'inventory:sku:'.$sku
                    || ! is_numeric($quantity) || ! is_finite((float) $quantity) || $quantity < 0
                    || ! in_array($row['status'] ?? null, ['Active', 'Inactive'], true)) {
                    throw new \RuntimeException('Invalid Inventory stock snapshot.');
                }
                $stocks[$sku] = $row['status'] === 'Active' ? (float) $quantity : 0.0;
            }
            return ['stocks' => $stocks, 'pending_before' => $pendingBefore];
        } catch (\Illuminate\Database\QueryException $exception) {
            // Let the transaction retry database concurrency failures.
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['items' => 'Inventory stock could not be verified. Please try again when Inventory is available.']);
        }
    }

    /** Subtract unacknowledged deductions, never count unacknowledged returns as available. */
    public function available(int $productId, array $snapshot): float
    {
        $sku = DB::table('inventory_product_mappings')->where('pos_product_id', $productId)->sharedLock()->value('inventory_sku');
        $pending = (float) DB::table('inventory_outbox_event')->where('product_id', $productId)
            ->whereNull('processed_at')->where('quantity', '<', 0)->orderBy('event_id')->sharedLock()->get(['quantity'])->sum('quantity');
        // An acknowledgement during the HTTP request cannot put sold stock back.
        $pending = min($pending, (float) ($snapshot['pending_before'][$productId] ?? 0));

        $reserved = 0.0;
        foreach (['pending_card_verifications', 'pending_ewallet_verifications'] as $table) {
            $query = DB::table($table)->where('status', 'pending');
            // Current reads also see reservations committed while waiting for a product lock.
            foreach ($query->orderBy('id')->sharedLock()->get() as $reservation) {
                $payload = json_decode($reservation->checkout_payload, true, 512, JSON_THROW_ON_ERROR);
                foreach ($payload['items'] ?? [] as $item) {
                    if ((int) $item['product_id'] === $productId) $reserved += (float) $item['quantity'];
                }
            }
        }

        return max(0.0, round(($snapshot['stocks'][$sku] ?? 0.0) + $pending - $reserved, 3));
    }
}
