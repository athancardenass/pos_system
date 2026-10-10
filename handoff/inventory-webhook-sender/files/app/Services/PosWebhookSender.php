<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PosWebhookSender
{
    public function sendPending(): array
    {
        $url = trim((string) config('pos_webhook.url'));
        $token = trim((string) config('pos_webhook.token'));
        $parts = parse_url($url);
        if ($token === '' || ! is_array($parts) || empty($parts['host'])
            || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Set POS_WEBHOOK_URL and POS_WEBHOOK_TOKEN in Inventory .env.');
        }
        $sent = $failed = 0;
        $events = DB::table('pos_webhook_outbox')->whereNull('delivered_at')
            ->where('available_at', '<=', now())->orderBy('created_at')->orderBy('event_id')->limit(20)->get();
        foreach ($events as $record) {
            // Inventory uses associative PDO fetches in some deployments.
            $event = (object) $record;
            try {
                $response = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(8)
                    ->withOptions(['allow_redirects' => false, 'verify' => true])
                    ->post($url, ['event_id' => 'inventory:'.$event->event_id, 'sku' => $event->sku]);
                if (! in_array($response->status(), [200, 202], true) || $response->json('accepted') !== true) {
                    throw new RuntimeException('POS did not acknowledge notification; HTTP '.$response->status());
                }
                DB::table('pos_webhook_outbox')->where('event_id', $event->event_id)->whereNull('delivered_at')
                    ->update(['delivered_at' => now(), 'last_error' => null, 'attempts' => DB::raw('attempts + 1')]);
                $sent++;
            } catch (Throwable $exception) {
                DB::table('pos_webhook_outbox')->where('event_id', $event->event_id)->whereNull('delivered_at')
                    ->update([
                        'attempts' => DB::raw('attempts + 1'),
                        'available_at' => now()->addSeconds(min(300, 5 * (2 ** min($event->attempts, 6)))),
                        'last_error' => 'Delivery failed ('.class_basename($exception).'); check connection/token and retry.',
                    ]);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}
