<?php

namespace App\Services\Integration\CRM;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
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
        if ($baseUrl === '' || $token === '' || ! \App\Services\Integration\Endpoint::allowed($baseUrl, 'crm')) {
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

        // Keep the existing loyalty event flow, then synchronize the matching
        // completed sale into the CMS Orders module. Reuse the same order
        // identity on retries so the receiver can deduplicate delivery.
        if ($isSale && ! $this->sendOrderToCrm($baseUrl, $token, $posTransactionId, $crmCustomerExternalId)) {
            return ['status' => 502, 'accepted' => false, 'duplicate' => false];
        }

        return [
            'status' => $response->status(),
            'accepted' => true,
            'duplicate' => $data['duplicate'],
        ];
    }

    private function sendOrderToCrm(string $baseUrl, string $token, int $transactionId, string $externalId): bool
    {
        $sale = DB::table('sale_transaction')->where('transaction_id', $transactionId)->first();
        if (! $sale || strtolower((string) $sale->status) !== 'completed' || $sale->customer_id === null) {
            return false;
        }

        $items = DB::table('sale_details as sd')
            ->join('product as p', 'p.product_id', '=', 'sd.product_id')
            ->where('sd.transaction_id', $transactionId)
            ->orderBy('sd.sale_detail_id')
            ->get(['p.barcode', 'p.product_name', 'sd.quantity', 'sd.unit_price', 'sd.subtotal']);
        if ($items->isEmpty()) {
            return false;
        }

        $taxSnapshot = json_decode((string) ($sale->tax_snapshot ?? ''), true);
        $discountSnapshot = json_decode((string) ($sale->discount_snapshot ?? ''), true);
        $tax = is_array($taxSnapshot) ? (float) ($taxSnapshot['vat_amount'] ?? 0) : 0.0;
        $payload = [
            'event_id' => 'pos:customer:order:'.$transactionId,
            'customer_external_id' => $externalId,
            'pos_transaction_id' => $transactionId,
            'transaction_date' => (string) $sale->transaction_date,
            'subtotal' => round((float) $sale->subtotal, 2),
            'discount' => round((float) ($discountSnapshot['amount'] ?? 0), 2),
            'tax' => round($tax, 2),
            'total_amount' => round((float) $sale->total_amount, 2),
            'payment_method' => (string) $sale->payment_method,
            'payment_status' => 'Paid',
            'items' => $items->map(static fn (object $item): array => [
                'barcode' => (string) $item->barcode,
                'name' => (string) $item->product_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => round((float) $item->unit_price, 2),
                'total_price' => round((float) $item->subtotal, 2),
            ])->values()->all(),
        ];

        try {
            $orderResponse = Http::acceptJson()->withToken($token)
                ->connectTimeout(2)->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->post($baseUrl.'/api/v1/orders', $payload);
        } catch (ConnectionException) {
            return false;
        }

        if (! $orderResponse->successful()) {
            return false;
        }
        $orderData = $orderResponse->json('data');
        return is_array($orderData)
            && ($orderData['event_id'] ?? null) === $payload['event_id']
            && is_numeric($orderData['order_id'] ?? null);
    }
}
