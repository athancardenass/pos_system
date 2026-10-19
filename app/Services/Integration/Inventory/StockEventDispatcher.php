<?php

namespace App\Services\Integration\Inventory;

use App\Models\InventoryOutboxEvent;
use App\Services\Integration\Endpoint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StockEventDispatcher
{
    public function dispatch(): int
    {
        $url = rtrim((string) config('services.inventory.base_url'), '/');
        $token = (string) config('services.inventory.token');
        $instance = (string) config('services.pos_instance_id');
        if (! Endpoint::allowed($url, 'inventory') || $token === '' || $instance === '') throw new RuntimeException('Inventory URL, token, and POS instance ID are required.');
        $count = 0;
        $blockedSkus = [];
        // Keep each SKU ordered while allowing unrelated products to synchronize.
        foreach (InventoryOutboxEvent::whereNull('processed_at')->whereNotNull('inventory_sku')->orderBy('event_id')->limit(100)->get() as $event) {
            if (isset($blockedSkus[$event->inventory_sku])) continue;
            try {
                $response = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(15)
                    ->withOptions(['verify' => true, 'allow_redirects' => false])
                    ->post($url.'/api/v1/pos/stock-events', [
                        'event_key' => $instance.':'.$event->event_id, 'sku' => $event->inventory_sku,
                        'event_type' => $event->event_type, 'quantity_delta' => (float) $event->quantity,
                    ]);
            } catch (ConnectionException) {
                $event->update(['attempts' => $event->attempts + 1, 'last_error' => 'Inventory connection failed; acknowledgement not received.']);
                $blockedSkus[$event->inventory_sku] = true;
                continue;
            }
            if (! $response->successful() || $response->json('accepted') !== true) {
                $event->update(['attempts' => $event->attempts + 1, 'last_error' => 'Inventory rejected event; HTTP '.$response->status()]);
                $blockedSkus[$event->inventory_sku] = true;
                continue;
            }
            $event->update(['status' => 'processed', 'processed_at' => now(), 'attempts' => $event->attempts + 1, 'last_error' => null]);
            $count++;
        }
        if ($blockedSkus !== []) throw new RuntimeException('Some Inventory stock updates remain pending; unrelated products were still attempted.');
        return $count;
    }
}
