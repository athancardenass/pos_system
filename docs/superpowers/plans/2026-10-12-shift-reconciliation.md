# Shift Reconciliation Implementation Plan

> For agentic workers: Use superpowers:executing-plans to implement task by task. Preserve the current checkout and user edits; no commits or branch operations.

**Goal:** Deliver cashier shift cash accounting, transfers, approved returns/exchanges and printable immutable closing reports.
**Architecture:** Ledger entries record settled amounts against a locked shift; immutable snapshots freeze closure. Existing checkout/refund and durable stock/loyalty outboxes are reused for exchanges inside one transaction.
**Tech Stack:** Laravel, PHP, MariaDB, Blade, existing vanilla JavaScript.
**Spec:** docs/superpowers/specs/2026-10-12-shift-reconciliation-design.md

## Global Constraints
No new dependencies or customer/address schema changes. Business logic in services. Transactional locked mutations; stable request IDs. Preserve current UI and local edits. No automatic Git operations. Existing checkout-without-shift compatibility; new return/exchange/movement endpoints require open processing shift. Manager self-approval follows incumbent policy.

## Review Focus
- Cross-day refund must affect the processing shift and preserve original closed snapshot.
- Duplicate/reused operation IDs must not issue cash, stock or loyalty twice.
- Pending transfer/pending payment blocks closure; destination owns receipt confirmation.
- Damaged return never increases available stock or sends a resellable-return event.
- Exchange credit is not physical tender; replacement stock failures roll back return and settlement.

### Task 1: Ledger and shift lifecycle
Files: migration 2026_10_12_000001_create_shift_accounting.php; Models/CashDrawer.php, DrawerMovement.php, DrawerTransfer.php, SaleExchange.php; Services/CashDrawerService.php, ShiftReportService.php; tests/Feature/ShiftAccountingTest.php.
Interfaces: lockOpenDrawer(int $employeeId, bool $required=true): ?CashDrawer; record(CashDrawer $drawer, array $entry): DrawerMovement; summary(CashDrawer $drawer): array; closeDrawer(int $employeeId, float $actualCash, ?string $notes=null, ?float $expected=null): CashDrawer.
- [x] Write tests for positive/negative/mixed-method reconciliation, immutable report, duplicate opening and discrepancy explanation.
- [x] Run `php artisan test --filter=ShiftAccountingTest` and observe missing functionality.
- [x] Add additive schema and locked lifecycle/ledger/report services, carrying forward legacy expected balances.
- [x] Rerun tests; record result in progress ledger.

### Task 2: Cash movements and confirmed transfers
Files: Services/DrawerTransferService.php; Http/Controllers/DrawerMovementController.php, CashDrawerController.php, ManagerAuthorizationController.php; Services/ManagerAuthorizationService.php; routes/web.php; tests/Feature/DrawerTransferTest.php.
Interfaces: initiate(int $employeeId, array $data, array $approval): DrawerTransfer; accept(int $employeeId, DrawerTransfer $transfer): DrawerTransfer; cancel(int $employeeId, DrawerTransfer $transfer, array $approval): DrawerTransfer. manual(int $employeeId,array $data,array $approval): DrawerMovement.
- [x] Write tests with opening 1000/1500, transfer 500: source 500/destination 1500 before acceptance, 2000 after; cancel restores source; duplicates stable, strangers blocked, closure pending rejected.
- [x] Run the targeted tests red.
- [x] Implement deterministic drawer locks, scoped grants, positive amount/reason/UUID validation, ownership, receipt/cancel routes.
- [x] Rerun tests green.

### Task 3: Checkout and approved cross-day returns
Files: Services/CheckoutService.php, RefundService.php; Models/SaleRefund.php, SaleRefundItem.php; Http/Controllers/PosController.php; tests/Feature/ShiftRefundTest.php, existing RefundFlowTest.php.
Interfaces: recordSale(SaleTransaction $sale, ?CashDrawer $drawer): void; refund(...existing arguments..., array $options=[]): SaleRefund; options include condition, operation_key, settlement_reference, record_movement.
- [x] Write tests: day1 sale 500 closed; day2 other cashier starts1000 and refunds500 => expected500, original report unchanged; damaged return stock unchanged; card refund cash unchanged; fractional remaining refund capped to paid value; key reuse rejected.
- [x] Run red, implement shared quantity checks and current-shift refund ledger, condition/payout metadata, duplicate handling and original-method settlement.
- [x] Run targeted existing refund/payment tests green; update old fixtures to explicitly open a shift and choose resellable where they expect stock restoration.

### Task 4: Atomic exchanges
Files: Services/ExchangeService.php; Http/Controllers/ExchangeController.php; routes/web.php; tests/Feature/ExchangeFlowTest.php.
Interfaces: exchange(SaleTransaction $sale,array $data,array $approval): SaleExchange; preview returns returned_value,replacement_value,difference,payment_method.
- [x] Write tests for 100 return against 100/150/70 replacement: settlement0/+50/-30, available stock movements and only one ledger entry; out-of-stock rolls back everything; repeated UUID stable; over-return rejects; non-cash confirmation; prior exchange quantity excluded.
- [x] Run red, implement shared return credit with internal no-movement refund/checkout, stable linked replacement receipt and actual net settlement, scoped approval.
- [x] Run green and check Inventory/CRM outbox effects.

### Task 5: Cashier controls and saved reports
Files: resources/views/cash-drawers/{index,show,workspace}.blade.php, resources/views/pos/{index,show}.blade.php; resources/views/components/manager-pin-modal.blade.php; public/js/shift-timer.js; tests/Feature/ShiftUiTest.php.
- [x] Write HTTP tests for own report access, manager access, stranger denial, cash workspace/actions, returned condition/exchange fields and live shift status data.
- [x] Run red; implement existing-styled Cash movements page with transfer receipt, preview/closing report, protected non-cash refund/exchange confirmation and exchange receipt links. Main POS timer uses saved timestamp/server clock and updates each second; closure handles validation inline, no success alert.
- [x] Run green; verify timer with Node controlled clock and render templates.

### Task 6: Release verification
- [x] Full `php artisan test` with local PDO SQLite enabled; read failures and fix causes with regression tests.
- [x] Review full change with fresh reviewer, repair material findings and rerun affected tests/full suite as needed.
- [x] Back up live MariaDB privately; apply only additive migration; verify counts/legacy balances/foreign keys.
- [x] Inspect browser desktop/mobile controls and report; apply one correction batch if needed.
- [x] Update CHANGELOG.md and user guide; report actual checks and external damaged-stock/API limits.
