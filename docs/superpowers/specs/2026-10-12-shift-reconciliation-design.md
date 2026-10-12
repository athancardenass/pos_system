# Shift reconciliation and exchanges

Approved basis: the user's master plan and instruction to build it, plus approved defaults on 2026-10-12.

## Outcomes
Cashiers can record cash in/out, transfer cash between open shifts, refund another cashier's earlier receipt, exchange items, review/count/close a shift and print its saved report. The POS shows elapsed time from the saved opening timestamp. Preserve the existing POS appearance and removed duplicate CRM panel/success popup.

## Accounting
Append-only drawer movements belong to the processing shift. Types: sale, refund, exchange, manual_in, manual_out, transfer_in, transfer_out, transfer_reversal. Signed amount is the actual settled payment after change, with cash/card/e-wallet identified. Sale/refund references supply gross replacement/return values separately. Exchange creates a linked return and replacement sale, but records only their net settlement in drawer movements. Never double count replacement credit as collected cash.

Expected cash = starting balance + cash movement sum. New shifts start at opening float. Total Cash In/Out are positive/negative cash movement sums, with distinct type subtotals. Gross sales means final completed receipt values before subsequent returns (after original discounts); processed refunds are dated to their processing shift. Net sales = gross sales - returns. Card/e-wallet totals stay outside drawer cash. A historical sale's later refunded status must not remove its original shift collection.

Pre-feature shifts keep their existing expected balance as a disclosed brought-forward amount. Do not invent historical movement detail. Legacy closed shifts remain viewable with limited-detail labels. New closure saves an immutable JSON report including cashier/time/movement/sales/refund detail; later refunds or renamed products do not rewrite it. Difference = actual counted - expected; require an explanation for discrepancies. Reject a stale client expected balance and closing with pending transfers or pending unverified payments.

## Cash and transfers
Manual movements require positive amounts with two decimal places, reason, unique request UUID, active shift and manager authorization. Transfers deduct from the source on initiation, remain in transit, and credit the destination only after the destination cashier confirms receipt. Both shifts must be open; shared transfer reference and manager approval are recorded. Pending transfers block closure of either shift. Sender may cancel with manager authorization before receipt, restoring source cash through a reversal. Same request UUID never duplicates a movement; changed data with that UUID is rejected. Transfer never cancels a pre-existing shortage.

## Refunds and exchanges
All cashier refunds/exchanges require an existing manager PIN grant scoped to their action data. Existing signed-in-manager self-approval behavior is preserved and audited. Any authorized cashier may process another cashier's receipt, including next day. Refund is linked to original sale and current shift; historical closed reports are untouched. Original payment method is used, and non-cash settlements require a reference/confirmation in the form (POS records merchant-app/terminal settlement; it does not call a payment gateway). Refunds/exchanges require an open processing shift. Preserve seven-day policy, allowing manager-approved overrides with audit flag.

Returned quantities cannot exceed remaining purchased quantity, including previous exchanges. Money uses cumulative proportional original paid values to avoid repeated rounding over-refunds. Condition per return operation: resellable restores sellable local stock and existing Inventory return event; damaged records condition/quantity without restoring sellable stock. Inventory currently has no damaged-stock endpoint: keep an auditable POS return record, do not send a fabricated stock increase; Inventory damaged-stock API remains external follow-up.

Exchange atomically records return, replacement receipt, payment difference and Inventory/CRM outbox effects. Replacement uses current POS price and no new discount; returned credit uses original paid value. Same price has no cash movement; higher price collects difference; lower price refunds difference through original payment method. Operator confirms settlement; original receipt and replacement receipt show exchange links and actual difference. Stock failure rolls back the whole exchange. Unique operation UUID and source locks prevent retry duplication. Refund and exchange share refundable quantities.

Exchange settlement always uses the original payment method. Loyalty preserves points for a zero-difference exchange; only net new spending earns additional points and a cheaper exchange reverses only the net refund through the existing CRM event contract. These are builder decisions to prevent split-tender ambiguity and duplicate minimum-point awards.

## Implementation constraints
Use existing Laravel/PHP/MariaDB/Blade stack; no new dependencies or customer/address schema changes. Business logic in services; money/stock changes transactional with row locks. Preserve working-tree changes; no checkout/reset or automatic commits. Source identities/tokens/config remain private and untouched. Existing checkout callers without an open shift retain compatibility, but no drawer entry is fabricated; POS UI requires an open shift. New manual/refund/exchange functions strictly require one. Open-shift creation refuses an existing shift rather than silently closing it.

## Verification
TDD for reconciliation, cash and non-cash collections, cross-day/cross-cashier refunds, fractional/partial/full returns, damaged isolation, duplicate/reused keys, both transfer sides/receipt/cancel/closure blocking, exchange price differences/stock rollback, report authorization/immutability and shift UI. Run full php artisan test, Blade compilation, whitespace checks, live MariaDB migrations after private backup, and bounded browser inspection. Remote Inventory stock events keep the current supported sale/refund contract.
