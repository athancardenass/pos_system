<?php

namespace App\Services;

use App\Models\DiscountOutboxEvent;

class DiscountOutboxService
{
    public function recordUsageEvent(int $saleId, ?int $discountId, ?array $snapshot): void
    {
        if ($discountId === null) {
            return;
        }

        DiscountOutboxEvent::query()->create([
            'sale_transaction_id' => $saleId,
            'discount_id' => $discountId,
            'event_type' => 'discount.applied',
            'payload' => ['snapshot' => $snapshot],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
        ]);
    }
}
