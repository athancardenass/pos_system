<?php

namespace App\Services;

use App\Models\CustomerOutboxEvent;
use Illuminate\Support\Facades\DB;

class CustomerOutboxService
{
    public function recordSaleEvent(int $saleId, ?int $customerId, float $amount): void
    {
        if ($customerId === null || $amount <= 0) {
            return;
        }

        $crmExternalId = DB::table('customer')->where('customer_id', $customerId)->value('crm_external_id');

        CustomerOutboxEvent::query()->create([
            'event_type' => 'sale.completed',
            'sale_transaction_id' => $saleId,
            'customer_id' => $customerId,
            'crm_external_id' => $crmExternalId,
            'payload' => [
                'sale_id' => $saleId,
                'customer_id' => $customerId,
                'amount' => round($amount, 2),
            ],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
        ]);
    }

    public function recordRefundEvent(int $saleId, int $refundId, ?int $customerId, float $amount): void
    {
        if ($customerId === null || $amount <= 0) {
            return;
        }

        $crmExternalId = DB::table('customer')->where('customer_id', $customerId)->value('crm_external_id');

        CustomerOutboxEvent::query()->create([
            'event_type' => 'sale.refunded',
            'sale_transaction_id' => $saleId,
            'refund_id' => $refundId,
            'customer_id' => $customerId,
            'crm_external_id' => $crmExternalId,
            'payload' => [
                'sale_id' => $saleId,
                'refund_id' => $refundId,
                'customer_id' => $customerId,
                'amount' => round($amount, 2),
            ],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
        ]);
    }
}
