<?php

namespace App\Services\Integration\CRM;

use App\Models\Customer;
use App\Models\CustomerOutboxEvent;

class CustomerLoyaltyDispatcher
{
    public function __construct(private CustomerLoyaltyEventClient $client) {}

    /** Process oldest events first, so a refund follows its original sale. */
    public function dispatch(): array
    {
        $sent = 0;
        $failed = 0;
        $blockedCustomers = [];
        foreach (CustomerOutboxEvent::whereNull('processed_at')->orderBy('event_id')->limit(100)->get() as $event) {
            if (isset($blockedCustomers[$event->customer_id])) continue;
            $externalId = $event->crm_external_id;
            if (! filled($externalId)) {
                // Only an explicitly established mapping may repair an old event.
                $externalId = Customer::whereKey($event->customer_id)->value('crm_external_id');
                if (filled($externalId)) $event->update(['crm_external_id' => $externalId]);
            }
            $result = filled($externalId) ? $this->client->send(
                (int) $event->event_id, $event->event_type, (int) $event->sale_transaction_id,
                $event->refund_id !== null ? (int) $event->refund_id : null,
                $externalId, (float) data_get($event->payload, 'amount'),
            ) : ['accepted' => false, 'status' => 422];

            if (! $result['accepted']) {
                $event->update([
                    'attempts' => $event->attempts + 1,
                    'last_error' => filled($externalId) ? 'CRM loyalty event not acknowledged; HTTP '.$result['status'] : 'CRM customer identity mapping is required.',
                ]);
                $blockedCustomers[$event->customer_id] = true;
                $failed++;
                continue;
            }
            $event->update([
                'status' => 'processed', 'processed_at' => now(),
                'attempts' => $event->attempts + 1, 'last_error' => null,
            ]);
            $sent++;
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}
