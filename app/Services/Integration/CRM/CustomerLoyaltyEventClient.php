<?php

namespace App\Services\Integration\CRM;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CustomerLoyaltyEventClient
{
    /**
     * Submit one POS outbox event. The caller must provide an explicit CRM
     * customer external ID; this client never derives a CRM identity from a
     * POS integer customer ID.
     *
     * @return array{status: int, accepted: bool, duplicate: bool}
     */
    public function send(
        int $outboxEventId,
        string $eventType,
        int $posTransactionId,
        ?int $posRefundId,
        string $crmCustomerExternalId,
        float $amount,
    ): array {
        $isSale = $eventType === 'sale.completed' && $posRefundId === null;
        $isRefund = $eventType === 'sale.refunded' && $posRefundId !== null && $posRefundId > 0;
        if ($outboxEventId < 1
            || $posTransactionId < 1
            || (! $isSale && ! $isRefund)
            || ! preg_match('/^crm:customer:[A-Za-z0-9][A-Za-z0-9._:-]*$/', $crmCustomerExternalId)
            || ! is_finite($amount)
            || $amount <= 0) {
            return ['status' => 422, 'accepted' => false, 'duplicate' => false];
        }

        $baseUrl = rtrim(trim((string) config('services.crm.base_url')), '/');
        $token = trim((string) config('services.crm.token'));
        if ($baseUrl === '' || $token === '' || ! \App\Services\Integration\Endpoint::allowed($baseUrl)) {
            return ['status' => 503, 'accepted' => false, 'duplicate' => false];
        }

        $eventId = 'pos:customer:'.($isSale ? 'sale' : 'refund').':'.$outboxEventId;
        $payload = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'pos_transaction_id' => $posTransactionId,
            'customer_external_id' => $crmCustomerExternalId,
            'amount' => round($amount, 2),
        ];
        if ($isRefund) {
            $payload['pos_refund_id'] = $posRefundId;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->post($baseUrl.'/api/v1/loyalty/events', $payload);
        } catch (ConnectionException) {
            return ['status' => 504, 'accepted' => false, 'duplicate' => false];
        }

        if ($response->status() === 409) {
            return ['status' => 409, 'accepted' => false, 'duplicate' => false];
        }
        if (! $response->successful()) {
            return ['status' => 502, 'accepted' => false, 'duplicate' => false];
        }

        $data = $response->json('data');
        if (! is_array($data)
            || ($data['event_id'] ?? null) !== $eventId
            || ! is_int($data['points_delta'] ?? null)
            || ! is_bool($data['duplicate'] ?? null)) {
            return ['status' => 502, 'accepted' => false, 'duplicate' => false];
        }

        return [
            'status' => $response->status(),
            'accepted' => true,
            'duplicate' => $data['duplicate'],
        ];
    }
}
