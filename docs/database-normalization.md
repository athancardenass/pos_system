# Database normalization review — 2026-10-10

Reviewed all 38 tables in the imported `pos_system` database, their keys and foreign keys, and the application reads/writes. This is a practical normalization review, not a claim that every operational table is strict third normal form.

## Applied changes

- Removed `promotion`, `coupon`, `sale_promotion`, and `coupon_redemption`, plus `sale_transaction.promo_discount` and `coupon_discount`. Removed the unused feature views, styles, and date-window trait. Existing routes and checkout already excluded these features. Historical migrations remain so existing installations can migrate forward and roll back schema.
- Removed `payment.payment_method`. `payment.transaction_id` is unique and references a sale, so duplicating the sale's method allowed inconsistent values. The method is stored once on `sale_transaction`; `Payment::payment_method` reads the related sale. Checkout and the realistic seeder no longer write a duplicate. The migration refuses orphan payments or conflicting methods before destructive DDL.
- Subsequently removed `purchase_order` and `purchase_order_details` at the user's request, together with their models, controller, unused views, supplier relationship, and seed data. The database now has 32 tables. Existing stock movements remain historical records, including any past purchase references. Supplier/product records remain available.

## Whole-schema decisions

| Tables | Decision and dependency |
| --- | --- |
| role, employee | Role names have their own unique keyed table; employees reference roles. Employee credentials belong to employees. |
| category, supplier, product | Category and supplier attributes live separately from products with foreign keys. Names are not assumed unique: different products/suppliers may legitimately share display names. Addresses remain single free-text postal values; there is no verified structured address contract to decompose. |
| inventory | Unique product foreign key gives one balance per product. Current balance is retained as a transactional cache for locked stock checks. |
| purchase_order, purchase_order_details | Removed after the review at the user's request. Local order creation and receiving are no longer available. |
| sale_transaction, sale_details, payment, receipt | Header, repeating items, tender details, and receipt identity are separated. Payment and receipt each enforce one row per sale. Payment method duplication is removed. Line price and VAT rate represent sale-time facts; totals are rounded financial records retained for refunds and reports. |
| discount, vat_settings, receipt_settings, register_receipt_counters | Definitions/settings remain separate from sales. Register counters are keyed by register. Sale/receipt snapshots intentionally preserve the original tax, discount, and printed details after settings change. |
| sale_refund, sale_refund_item | Refund header and repeating refunded lines are separated and reference the original sale detail. Refund amounts are historical allocations of discounted sale totals, not current product prices. |
| cash_drawers | Opening/closing/expected/actual cash and difference are reconciliation facts cached at closing; they remain to avoid altering historical cash reconciliation behavior. |
| stock_movement, reorder_signal | Before/after stock and reorder thresholds describe the event time, rather than today's inventory/product values. Polymorphic source references and alert snapshots remain intentional. |
| customer | Cached CRM profile, loyalty balance, and purchase summary remain externally owned values; local sales are not the complete source of customer history. |
| customer_outbox_event, discount_outbox_event, inventory_outbox_event | Durable event envelopes retain immutable payloads and delivery metadata. JSON payloads are integration messages, not repeating operational columns to split. External customer identifiers are deliberately not constrained to the local customer cache. |
| pending_card_verifications, pending_ewallet_verifications, payment_reference_registry | Pending checkout payloads are immutable requests awaiting approval. Fingerprints enforce reference uniqueness, encrypted references support authorized reveal, and registry source references span several tables. These serve different purposes rather than interchangeable copies. |
| audit_log | Event-time descriptions/details and actor identifiers preserve accountability. |
| migrations, job_batches, sessions, password_reset_tokens, users | Framework/imported infrastructure tables, excluded from domain redesign. `users` is an unused import artifact; employee authentication remains authoritative. |

## Recovery and verification

A pre-change SQL backup is stored outside the project in the local temporary `pos-system-db-backups` directory. Rollback restores the removed schema and backfills payment methods from sales; removed promotion/coupon data must be recovered from that backup. This database had zero sales, payments, promotions, and coupons before migration.

Regression tests cover feature removal, payment-method reads after a sale changes, conflicting legacy values stopping migration, and preservation of payment amounts. Full application tests use an isolated SQLite in-memory database; the live migration and schema checks run against MariaDB.

MariaDB checked all 34 remaining tables successfully, and login returned HTTP 200. The two normalization tests pass with 14 assertions. The full suite is not green: integration code/tests expect `inventory_product_mappings` and CRM eligibility columns that are missing from the existing migrations/import. These require a separate integration-schema repair; they were not introduced by removing promotions/coupons or the duplicate payment method.
