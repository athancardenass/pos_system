# Inventory webhooks → POS

The POS receiver and durable worker are implemented. Inventory's developer must configure a sender on their laptop; the POS cannot install remote code on that laptop.

## Connection details to share

```
POST http://10.36.189.57:8000/api/v1/inventory/product-updated
Authorization: Bearer <POS INTEGRATION_API_TOKEN>
Content-Type: application/json
Accept: application/json
```

Use the POS receiver token from `C:\xampp\htdocs\pos_system\.env`, not Inventory's own receiver token. Do not put tokens in browser JavaScript or public documentation. The endpoint currently uses the existing HTTP LAN setup; use trusted HTTPS when deploying beyond this setup.

Payload:

```json
{"event_id":"inventory-product-change-unique-id","sku":"BEV-3411"}
```

Create a stable unique `event_id` for each committed source change. Retry the same event with the same ID/SKU if delivery times out. Sending the same event twice does not apply stock twice. The notification deliberately does not contain authoritative stock: POS fetches current data from Inventory's authenticated catalog API.

Responses:

- HTTP 202: `{"accepted":true,"duplicate":false}` — durably queued.
- HTTP 200: `{"accepted":true,"duplicate":true}` — already received.
- HTTP 401: missing/invalid token.
- HTTP 409: an event ID was reused for another SKU.
- HTTP 422: invalid/missing event fields.
- HTTP 429/5xx or connection failure: retry with backoff and the same ID.

## Inventory sender implementation

A ready-to-install MySQL/MariaDB sender kit is available as `handoff/inventory-webhook-sender.zip`. It records relevant `products` changes using triggers in the source transaction, so the pasted Inventory `action/save/delete` controller requires no edits. Follow the included README and `install.ps1`, then leave `php artisan pos:webhooks` running on the Inventory laptop. The installer does not configure or expose tokens; it uses the already tested `POS_WEBHOOK_URL` and `POS_WEBHOOK_TOKEN` in their `.env`.

After committing product creation/editing/deactivation/deletion or a stock change, store a notification in Inventory's own durable outbox. A sender worker POSTs it to the POS URL and marks it delivered on HTTP 200/202 with `accepted: true`. Failed requests retain their event ID for retry. Do not send HTTP inside the source stock transaction or roll back the source change because POS is offline.

Laravel request example for their sender (read URL/token from server configuration):

```php
$response = Http::acceptJson()
    ->withToken(config('services.pos.webhook_token'))
    ->connectTimeout(3)->timeout(10)
    ->withOptions(['allow_redirects' => false, 'verify' => true])
    ->post(config('services.pos.webhook_url'), [
        'event_id' => $outboxEvent->event_id,
        'sku' => $outboxEvent->sku,
    ]);
// Acknowledge only if status is 200/202 and accepted === true.
```

## POS worker and firewall

POS now listens on `0.0.0.0:8000`. On the POS laptop, run this once in Administrator PowerShell to allow only the Inventory laptop through the Private-network firewall:

```powershell
New-NetFirewallRule -DisplayName "POS Inventory Webhooks" -Direction Inbound -Protocol TCP -LocalPort 8000 -RemoteAddress 10.36.189.247 -Action Allow -Profile Private
```

Keep `php artisan integrations:webhooks` running. It checks the durable inbox every two seconds and coalesces notifications into an authoritative Inventory catalog refresh, sharing a lock with scheduled sync. It also sends queued POS sale/refund stock events promptly even without an incoming notification. Unacknowledged stock events and local payment reservations remain protected from snapshot overwrites. Network failures retry with backoff. Minute polling remains as reconciliation backup.


Restart this worker and the scheduler after API/token configuration or worker-code changes. Refresh the current POS product page/terminal to display changed catalog data; the receiver updates the database without automatically reloading the browser.

## Verification

Authentication, malformed requests, duplicate/conflicting identities, failure retention/retry, and authoritative stock were verified with isolated tests. A local authenticated notification for the real SKU `BEV-3411` returned HTTP 202 and was processed without inventing a sale or stock delta. Cross-laptop delivery remains pending until the POS firewall rule and Inventory sender are configured.
