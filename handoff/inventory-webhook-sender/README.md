# Inventory automatic webhook sender

Install on the Inventory laptop, not POS. This kit matches the pasted Inventory product schema and MySQL/MariaDB. It does not edit ProductController or existing routes. Database triggers record relevant product/stock inserts, updates, and deletes in the same transaction as the source change. Rolled-back changes send nothing. The worker retries with stable IDs, and POS acknowledges duplicates safely.

1. Extract this ZIP to the Inventory Desktop as `inventory-webhook-sender`.
2. Confirm Inventory `.env` already contains `POS_WEBHOOK_URL` and `POS_WEBHOOK_TOKEN` from the successful connection test. This ZIP contains no tokens.
3. Open PowerShell in the extracted folder and run:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\install.ps1 -ProjectPath "C:\xampp\htdocs\inventory-laravel"
   ```

4. Start the sender and leave its window running:

   ```powershell
   cd C:\xampp\htdocs\inventory-laravel
   php artisan pos:webhooks
   ```

5. Edit/save a product or change stock in Inventory. Expect `Delivered: ...; pending retries: 0` in the sender window. POS processes notifications every two seconds; refresh POS to see the catalog change. Initial existing records are covered by POS polling; installing this kit does not fabricate product changes or send initial events.

Before installation, keep a normal Inventory database backup. The migration requires CREATE TRIGGER permission; it creates an outbox table and three specifically named triggers, without changing existing product columns or data. Run only one sender worker. Restart it after changing URL/token settings. MySQL/MariaDB transaction guarantees require transactional InnoDB product/outbox tables.

HTTP 200/202 with `accepted: true` acknowledges delivery. Offline/invalid-token responses retain events for retry. The outbox `last_error`, `attempts`, and `delivered_at` identify delivery state. No tokens are logged. Existing polling remains fallback if this sender is stopped.

Rollback removes the kit's triggers/outbox through its migration's down method; retain pending outbox data first. Do not run an unrestricted rollback that could revert unrelated Inventory migrations. If their developer has a different schema/database engine, adapt the kit before installation.
