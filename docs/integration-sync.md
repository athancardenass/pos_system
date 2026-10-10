# POS integration synchronization

POS runs from `C:\xampp\htdocs\pos_system`. Source projects are in `D:\USB-READY\projects`.

Current HRMS connection: `http://10.36.189.221/harvest-hr-next/public/api/pos/employees`. POS stores the application root in `HRMS_API_BASE_URL`, `/api/pos/employees` in `HRMS_EMPLOYEES_PATH`, the receiver token privately in `HRMS_API_TOKEN`, and explicit `HRMS_ALLOW_HTTP=true` for this configured LAN endpoint. The endpoint returns an unpaginated `data/count/generated_at` feed; POS validates the count before applying a complete snapshot. Its `status`/`mobile` fields map to employment status/contact number; canonical identity derives only from `employee_no`. The first real employee EMP-0001 was imported successfully, retaining HR status while leaving POS login inactive until a manager configures it. Do not rerun local reference setup over these actual-laptop settings.

Current Inventory connection: the team's API root is `http://10.36.189.247/inventory-laravel/public/api`. POS stores `INVENTORY_API_BASE_URL=http://10.36.189.247/inventory-laravel/public` because clients append `/api/v1/...`; the supplied token is private in `.env`. `INVENTORY_ALLOW_HTTP=true` explicitly allows only that configured Inventory address. It does not enable HTTP for CRM/HR or other hosts. The catalog request and port-80 connection timed out from POS (`192.168.18.55`); resolve network/Apache/firewall reachability before confirming live synchronization. The local addresses below describe the reference setup, not this external Inventory connection. Rerunning local setup replaces connection settings with the local reference APIs.

| Source | Local API | POS behavior |
| --- | --- | --- |
| Inventory | `http://127.0.0.1:8001/api/v1/catalog/products` | Import product names, selling/cost prices, status, reorder point, barcode, and stable SKU mappings. Refresh stock after dispatching outstanding POS sale/refund events. |
| CRM | `http://127.0.0.1:8004/api/v1/customers` | Import customer profiles/loyalty/verified discount eligibility into Customers. Find customer searches CRM live; selecting verifies the exact customer code and uses a stable local checkout key. |
| CRM loyalty | `POST /api/v1/loyalty/events` | Award points for completed registered-customer sales and process refund reversals with retry-safe event IDs. |
| HR | `http://127.0.0.1:8003/api/v1/employees` | Import employee names, number, contact, hire date, position, and employment status into Employees. |

The source APIs expose only fields required for POS synchronization. CRM now supports exact customer-code filtering; Inventory includes selling/cost prices; HR includes separate names and contact/hire date.

## Local setup and automatic runs

Run `scripts/setup-local-integrations.ps1` after installing source-project Composer dependencies. It imports supplied fresh SQL only when the source database does not exist, configures separate receiver tokens without printing them, and enables HTTP only for explicit local development connections to loopback. Existing databases are not imported over.

Start the Inventory, HR, and CRM Laravel servers on loopback ports 8001, 8003, and 8004 respectively. Run the Inventory stock-event migration:

```
php artisan migrate --path=database/migrations/2026_10_10_000001_create_pos_stock_events.php
```

From POS:

```
php artisan integrations:sync
php artisan schedule:work
```

The scheduler runs synchronization every minute. On deployment, keep the scheduler and all source API servers running as services. Cross-laptop URLs must use trusted HTTPS; the loopback exception rejects plain HTTP to LAN addresses. Tokens stay server-side. Redirects are disabled and certificate verification remains enabled.

After changing API URLs/tokens in `.env`, run `php artisan config:clear` and restart the running `php artisan schedule:work` process. A long-running scheduler can pass its old environment to child commands, causing automatic sync to use different settings from a newly opened manual command. Stop it with Ctrl+C in its terminal, then run `php artisan schedule:work` again from the POS folder.

The current machine uses local runtime dependencies under ignored `storage/app/source-runtimes`, with source/autoload paths pointing to the USB projects. This avoids very slow USB extraction. `scripts/start-local-integrations.ps1` restarts the three APIs and sync scheduler using these runtime launchers. The normal `composer install` workflow remains supported on each destination laptop; local runtime manifests and launchers are machine-specific and should not be deployed as source.

## Identities and access

SKU, CRM customer code, and HR employee number are stable source keys. Local integer IDs remain stable for sales, receipts, and audit history. Names/emails/barcodes are never used to silently merge different identities. Conflicts stop the affected source sync; no partial directory import is committed. Missing source records become inactive, preserving history.

New HR imports have the Cashier role, inactive POS login, and an unknowable random password. A POS manager configures the password, role, and active status using the existing employee edit page. Synchronization preserves existing credentials, role, and manager PIN. HR inactive/resigned status disables POS login; syncing an active HR record never automatically reactivates a disabled POS account.

## Customer loyalty

Every completed positive-value sale linked to a registered customer queues a CRM loyalty event; walk-in sales have no customer identity and queue none. CRM awards `max(1, floor(final_sale_amount / 100))` points without a membership-level restriction. A ₱50 purchase earns 1 point, ₱150 earns 1, and ₱250 earns 2. Zero-value purchases queue no loyalty event. Refunds recalculate retained points from the remaining sale value, keeping at least one point while a positive balance remains; full refunds reverse all points originally awarded for that sale. Previously credited events retain their historical award and are never silently credited again.

Before refreshing the CRM directory, the minute sync worker sends pending events to `POST /api/v1/loyalty/events`. Accepted or duplicate acknowledgements mark an event processed. Failed delivery retains the same event ID for retry; a failed customer's refund does not overtake their original sale. Unmapped customers remain pending until their explicit CRM identity is established. CRM owns the points balance; POS refreshes it from CRM rather than incrementing a second balance locally. The other team's CRM must implement this endpoint or provide its actual contract for the POS adapter.

## Stock events

POS creates stock outbox events in the same transaction as checkout/refund. Every event has a stable key derived from `POS_INSTANCE_ID` and its outbox ID. Inventory stores an event ledger and applies each event once; retrying an acknowledged request after a lost response does not repeat the deduction. Reusing a key with a different payload returns a conflict. Do not rotate an installation's instance ID while events remain pending, and never use the same ID for two installations.

Sales deduct shelf stock first and then backroom stock. Refunded/restocked items return to shelf stock. Both quantities support three decimal places. Insufficient remote stock rejects the event and leaves it pending for resolution; it is never marked as processed locally.

Synchronization sends outstanding stock events first, then fetches Inventory's authoritative snapshot. It skips stock replacement for a product with unacknowledged events and subtracts local pending card/e-wallet reservations. Metadata still updates for such products. The sync command and schedule use an overlap lock.

This is eventual synchronization: local sales can finish while a source API is offline, and the outbox retries after connectivity returns. It does not provide a distributed stock reservation across multiple POS laptops. Conflicting offline sales require reconciliation rather than silently allowing negative Inventory stock.

## Data and verification

The supplied source exports initially contain zero products, CRM customers, and HR employees. New records entered in their source applications flow into POS on the next sync; demonstration records are not fabricated.

POS full test suite: 139 tests pass with 881 assertions. Tests include stable repeated imports, retained credentials, pending-event stock protection, CRM Find customer, checkout/refund, and exact external identities. Inventory receiver tests cover duplicate delivery, changed-payload conflicts, fractional stock, insufficient stock, and bearer authentication. Live status is recorded in CHANGELOG after verification.
