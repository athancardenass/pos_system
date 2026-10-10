<?php

namespace App\Services\Integration\Inventory;

use App\Services\Integration\MasterSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class WebhookInbox
{
    public function accept(array $event): array
    {
        $hash = hash('sha256', $event['sku']);
        return DB::transaction(function () use ($event, $hash) {
            $inserted = DB::table('inventory_webhook_inbox')->insertOrIgnore([
                'event_id' => $event['event_id'], 'sku' => $event['sku'], 'payload_hash' => $hash,
                'available_at' => now(), 'created_at' => now(),
            ]);
            $existing = DB::table('inventory_webhook_inbox')->where('event_id', $event['event_id'])->lockForUpdate()->first();
            if (! $existing || ! hash_equals($existing->payload_hash, $hash)) {
                throw new HttpException(409, 'Event ID already has a different SKU.');
            }
            return ['accepted' => true, 'duplicate' => ! (bool) $inserted];
        });
    }

    public function process(MasterSyncService $sync, StockEventDispatcher $stock): int
    {
        $lock = Cache::lock('integrations-master-sync', 600);
        if (! $lock->get()) return 0;
        try {
            $events = DB::table('inventory_webhook_inbox')->whereNull('processed_at')
                ->where('available_at', '<=', now())->orderBy('id')->limit(100)->get();
            $pendingStock = DB::table('inventory_outbox_event')->whereNull('processed_at')
                ->whereNotNull('inventory_sku')->exists();
            if ($events->isEmpty() && ! $pendingStock) return 0;
            try {
                $stock->dispatch();
                // Coalesce notifications into one authoritative snapshot; this
                // also handles deleted products without trusting webhook stock.
                $sync->sync('inventory');
                DB::table('inventory_webhook_inbox')->whereIn('id', $events->pluck('id'))
                    ->update(['processed_at' => now(), 'last_error' => null]);
                return $events->count();
            } catch (Throwable $exception) {
                foreach ($events as $event) {
                    DB::table('inventory_webhook_inbox')->where('id', $event->id)->update([
                        'attempts' => $event->attempts + 1,
                        'available_at' => now()->addSeconds(min(300, 5 * (2 ** min($event->attempts, 6)))),
                        'last_error' => 'Inventory sync failed: '.class_basename($exception),
                    ]);
                }
                throw $exception;
            }
        } finally { $lock->release(); }
    }
}
