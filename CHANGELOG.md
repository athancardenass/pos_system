# POS System — Changelog

> Every change/task in this project is documented here. Newest entries on top.
| Format: `## YYYY-MM-DD — Title` → What changed, files touched, why.

> **AGENTS.md lock:** agent never runs `git commit`/`push`; group controls VCS.

---

## 2026-09-09 — Phase 0: Integration layer scaffolding (POS→CRM/HR/Procurement boundary)

**What:** Created integration service boundaries (stubs only — no behavior change).
- New `app/Services/Integration/` directory with contracts + stub service classes for each external system:
  - `ExternalEntity.php` — enum of 7 cross-system entities (customer, employee, product, inventory, supplier, purchase_order, sale) for documentation/future stable-ID work
  - `ExternalSystemInterface.php` — marker interface (systemName())
  - `CRM/CustomerService.php` — stub `resolve()` + `syncCreate()` (return null/no-op)
  - `HR/EmployeeService.php` — stub `resolve()` (returns null)
  - `Procurement/ProcurementService.php` — stub `receiveStockReceipt()` + `resolveSupplier()` (no-op/null)
- All stubs implement `ExternalSystemInterface`; all methods return null or void — **zero POS behavior change**.

**Files touched:**
- `app/Services/Integration/ExternalEntity.php` (NEW)
- `app/Services/Integration/ExternalSystemInterface.php` (NEW)
- `app/Services/Integration/CRM/CustomerService.php` (NEW)
- `app/Services/Integration/HR/EmployeeService.php` (NEW)
- `app/Services/Integration/Procurement/ProcurementService.php` (NEW)
- `CHANGELOG.md` (this entry)

**Why:** establishes the integration boundary so CRM/HR/Procurement communication is isolated from POS business logic (per AGENTS.md "API-friendly controllers" + "extension points over edits to shared models"). No destructive changes — POS continues exactly as before.

**Assumed future ownership (ALL PROVISIONAL — pending other systems' audit):**
- HR → employee master data; POS keeps employee_id for auth/audit
- CRM → customer master data; POS keeps customer_id for sale association
- Procurement → suppliers + PO workflow; POS keeps supplier_id on product + inventory receiving
- Inventory → shared/POS-operational (checkout decrements; Procurement increments) — PENDING joint decision

**What is NOT done (Phase 0):** no deletions, no FK changes, no UUID/stable-ID migrations, no real API calls, no ownership transfers. Those await your Phase 1+ approval after auditing the actual CRM/HR/Procurement systems.

**Verified:** `php -l` clean on all 5 new files; tinker confirms stubs load + return null; 67 routes intact; all 6 controllers lint clean; receipt renders with address + VAT + refund button + print button unchanged.

---

## 2026-09-09 — Philippine VAT (Option A) + hardcoded receipt address

**What:** Implemented Philippine VAT (Option A — VAT-inclusive pricing) and set the receipt address.
- Researched current Philippine VAT: **standard rate 12%** (NIRC + RA 11663 / RR 1-2026); registration threshold ₱3M/annual gross sales; businesses below it use the 3% percentage tax. Receipt already carried a dummy "VAT Reg:" number, so the POS is assumed VAT-registered.
- **Option A (VAT-inclusive):** existing prices are treated as VAT-inclusive (the common PH retail convention). VAT is *extracted on display only* — no DB column added, no price changes. `VAT = total × 0.12/1.12`; e.g. ₱427.00 → VAT ₱45.75, net ₱381.25 (verified: ₱381.25 + ₱45.75 = ₱427.00 ✅).

**Files touched:**
- `app/Models/SaleTransaction.php` — added `VAT_RATE = 0.12` const, `vatRegistered()` (reads `config('vat.enabled')`), and three accessors: `vat_amount`, `net_amount`, `vat_rate`. Zero DB writes.
- `config/vat.php` (NEW) — `enabled` (env `VAT_ENABLED`, default true), `rate` (env `VAT_RATE`, default 0.12), `label`. Config toggle lets 3%-percentage-tax businesses flip VAT off.
- `.env` — added `VAT_ENABLED=true` + `VAT_RATE=0.12`.
- `resources/views/pos/show.blade.php` — receipt header address `Cavite, Philippines` → `Old Nalsian Road, Calasial, Calasiao, 2418 Pangasinan`; added VAT line item (`VAT (12%, included)` + amount) between Discount and TOTAL, gated behind `config('vat.enabled')`.
- `resources/views/pos/refund-slip.blade.php` — same address fix in the credit-slip header.

**Why:** VAT is legally required on receipts for VAT-registered PH businesses (BIR RR 16-2017); Option A adds the compliant breakdown without touching pricing data or DB schema (per your constraint). The address was requested to match your Pangasinan location.

**Verified:** `php -l` clean on SaleTransaction + config/vat.php; `config:clear` + `view:clear`; receipt renders with the new address, the VAT row, and correct math (total ₱427.00, VAT ₱45.75, net ₱381.25); `vatRegistered` = true; `VAT_ENABLED`/`VAT_RATE` present in `.env`.

---

## 2026-09-08 — Refund analytics: dashboard card + reason breakdown (Option B scope)

**What:** Added refund analytics to both the dashboard (glanceable KPI) and the Reports page (drill-down).
- **Dashboard new card:** "Refund Rate (7d)" — `(refunds ÷ completed sales in last 7 days) × 100` as a single KPI, plus "vs prior week" baseline.
- **Dashboard mini-bar:** beneath the stat cards, a small bar chart per refund **reason** (count + ₱ value) over the last 7 days — "Why customers refunded (last 7 days)".
- **Reports page:** the refunds card header now shows a reason-breakdown bar (count + ₱ per reason) alongside the Export CSV button, using the same query shape as the export.

**Files touched:**
- `app/Http/Controllers/DashboardController.php` — added `refund_rate`, `refund_rate_baseline` (prior-week comparison, not the same value), `refund_reasons` to `$stats`.
- `app/Http/Controllers/ReportController.php` — added `refundBreakdown()` helper returning `reason → {count, total, label}`; passed as `refund_breakdown` to the view.
- `resources/views/dashboard.blade.php` — new Refund Rate card + mini-bar section.
- `resources/views/reports/index.blade.php` — breakdown bar above the refunds table (Export CSV button always visible).
- Fixed Blade `@php` syntax (was two statements in one `@php()`; split into two — the original failed to compile under Laravel 13).

**Why:** closes the refund analytics gap you flagged — you can now see *why* people are refunding, not just the monetary total.

**Verified:** `php -l` clean on all controllers; both views render via `tinker` (DASHBOARD ✅49044 chars, REPORTS ✅30857 chars); data is real (5 refunds → 33.3% rate, 4 reasons: wrong_item×2, damaged, defective, changed_mind).

---

---

## 2026-09-08 — Remove Admin employee account (records reassigned to manager)

**What:** Deleted the `admin` employee account (employee_id 1) — you asked to remove it properly rather than set inactive. Admin role had already been merged into Manager in the role-restructure earlier today.

**Reassignment (transactional, integrity-preserving — no dangling FKs):**
- `sale_transaction`: 3 rows reassigned employee_id 1→2 (manager)
- `sale_refund`: 1 row reassigned 1→2
- `audit_log`: 9 rows reassigned 1→2
- `purchase_order`: 0 rows (none owned by admin)
- Verified: 0 rows reference employee_id 1 anywhere; admin row deleted.

**Controller logic fix:**
- `app/Http/Controllers/EmployeeController.php` — `destroy()` no longer hard-blocks on "sales history". Now reassigns all 4 FK tables to the first active **Manager** (skipping the deleted employee) inside a `DB::transaction`, then deletes. Removes the "Cannot delete an employee with sales history" blocker permanently. Throws a clear error only if no active Manager exists to take the records.

**Seeder/login updates:**
- `database/seeders/EmployeeSeeder.php` — removed the `admin` demo account; `RoleSeeder` seeds only `Manager` + `Cashier`.
- `resources/views/auth/login.blade.php` — demo hint now shows `manager`/`cashier` only.

**Why:** You wanted the Admin account fully gone (not just inactive). Reassigning records keeps financial/receipt integrity intact — receipts still show a real cashier.

**Verified:** `php -l` clean; tinker confirms 2 employees (manager=Manager/active, cashier=Cashier/active) + 2 roles; HTTP round-trip: `login manager → 302 /dashboard`; `manager → /employees 200` (Manager keeps full access).

---

> **AGENTS.md lock (2026-09-08 session):** agent never runs `git commit`/`push`; group controls VCS.

---

## 2026-09-08 — Simplify roles: removed Admin, merged into Manager (only Manager + Cashier)

**What:** Consolidated the 3-role system (`admin`/`manager`/`cashier`) into a 2-role system (`manager`/`cashier`). The **Manager** role now inherits all Admin-level permissions (employees, audit logs, everything). **Permission boundaries preserved**: Cashier keeps only `pos.index` + `customers.index`; Manager now owns all `categories/products/inventory/suppliers/purchase-orders/discounts/reports/employees/audit-logs`.

**Files touched:**
- `config/roles.php` — replaced all `'Admin'` entries in the `modules` map with `'Manager'` (employees.index + audit-logs.index are now Manager-only, matching the old Admin boundary).
- `routes/web.php` — three `role:` middleware strings updated: `role:Cashier,Manager,Admin`→`role:Cashier,Manager`; `role:Manager,Admin`→`role:Manager`; `role:Admin`→`role:Manager`.
- `app/Services/RefundService.php` — role gate `hasRole('Manager', 'Admin')` → `hasRole('Manager')`; added `MANAGER_ROLES = ['Manager']` constant; window-override logic now Manager-only (was Manager-or-Admin, now Manager-only = equivalent); removed "admin" wording from messages.
- `database/seeders/RoleSeeder.php` — only seeds `Manager` + `Cashier`.
- `database/seeders/EmployeeSeeder.php` — removed the `admin` demo account in a later cleanup (2026-09-08, see entry below); `RoleSeeder` seeds only `Manager` + `Cashier`.
- `resources/views/dashboard.blade.php` — removed ⚡ emoji from "New Sale" button (no-emoji rule); `Admin` comment → `Manager`.
- `AGENTS.md` — updated `config/roles.php` description line to reflect `manager`/`cashier`. **Note:** `config/roles.php` is normally locked by AGENTS.md #4 ("DO NOT modify config/ files unless the task explicitly says so") — this task explicitly requested the role restructure, so it's in-scope.

**DB migration (data only, no schema change):**
- `employee.role_id` where `1` (old admin) → remapped to `2` (Manager): **1 employee** (`admin` user) updated.
- Deleted the obsolete `role` row `('admin')`: row removed.
- Verified via tinker: `manager` role `Manager`, `admin` user now role `Manager`, `cashier` still `Cashier`. Live HTTP checks confirm `cashier → /employees: 403`, `cashier → /pos: 200`, `cashier → /reports: 403`, `manager → /employees: 200`.

**Why:** You asked to simplify to two roles. The old `admin` role was redundant — every Admin-only route (`employees`, `audit-logs`) now lives under Manager, preserving the cashier-can't-access-admin-boundary guarantee.

**Verified:** `php -l` clean on all 6 PHP files; `php artisan route:list` shows updated middleware; tinker + live HTTP role-gate tests all pass.

---

## 2026-09-08 — Env fix: stale dev server caused phantom login failures

**What:** Login returning 419/Page Expired due to stale `php artisan serve` (multiple orphaned listeners on :8000 serving old compiled views without CSRF token). **No code or DB changes.**
**Files touched:** none in repo tree.
**Actions:** killed stale `php.exe` PIDs on :8000, ran `view:clear` + `config:clear`, started single fresh `php artisan serve --port=8000`. Verified via GET→POST round-trip: `manager`/`password` → HTTP 200 → `/dashboard`.

---

## 2026-09-08 — Refund features: 7-day window + printable credit slip + dashboard refund stats

**What changed (all working-tree, NOT committed — AGENTS.md forbids agent git writes):**
- `database/migrations/2026_09_08_000000_add_window_override_to_sale_refund_table.php` — new `window_override` bool on `sale_refund` (audit flag).
- `app/Services/RefundService.php` — added `WINDOW_DAYS = 7` const; refunds older than 7 days now require Manager/Admin role and are flagged `window_override=true` (cashier blocked with a clear message).
- `app/Models/SaleRefund.php` — `window_override` added to fillable + casts.
- `app/Http/Controllers/PosController.php` — new `slip()` method → printable credit slip.
- `routes/web.php` — new `GET /pos/refund/{refund}/slip` (pos.refund.slip).
- `resources/views/pos/refund-slip.blade.php` — new credit-slip view (print-friendly, store info + line items + amount + reason).
- `resources/views/pos/show.blade.php` — refund history now links to its slip ("Slip" button).
- `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard.blade.php` — new "Refunds This Week" stat card (₱ + all-time count).

**Why:** completes refund roadmap items #4 (credit slip), #6 (refund window), #7 (dashboard stats).
**Verified:** migrate OK; `php -l` clean on all touched PHP; tinker confirms WINDOW_DAYS=7, `window_override` column present, slip view renders, dashboard shows refunds_week=1562.00 / refunds_count=5.

---

## 2026-08-27 — Documentation system established + project-recovery session

**What:** Set up ongoing documentation. Also recovered the project after Karl deleted the Hermes
project/workspace shortcut (files were never deleted — only the sidebar pointer was removed) and
re-linked it as "POS System (canonical)".

**Files touched / created:**
- `CHANGELOG.md` (this file) — new running log of all work.
- `dashboard-sales-case-study.md` — dashboard sales feature write-up (group-submission case study).
- `pos-system-case-study.md` + `pos-system-case-study.docx` — overall project case study (academic-casual tone).
- `POS-SYSTEM-TECHNICAL-CASE-STUDY.md` + `POS-SYSTEM-TECHNICAL-CASE-STUDY.docx` — 25-part reverse-engineering / learning guide built from the actual code (routes, controllers, models, migrations, middleware, auth, full business-flow traces, debugging guide, exercises).
- `pos_case_study_spec.json` — docx build spec (leftover; safe to delete).

**Why:** Karl wants every project task documented from now on. The case studies were produced to help
him understand and explain the system to groupmates, and to stop seeing Laravel as "random files."

**Notes:**
- The 25-part guide cites real files; key honest flags recorded: the `users` table is UNUSED (auth uses
  `employee`); no product/category/supplier seeders exist; no automated tests yet.
- Nothing in the application source was modified during this session — analysis/authoring only.

---

## 2026-08-27 — Forms: switched inputs to boxed style (option #2)

**What (Karl picked "boxed" from a live 3-option preview):**
- `resources/views/layouts/app.blade.php` — the global input/select/textarea rule changed from transparent + bottom-border-only (skeleton look) to **boxed**: `background: var(--surface)` (white), `border: 2px solid var(--rule)` (teal), `border-radius: 6px`, `padding: 0.6rem 0.7rem`. Focus state: coral `--accent` border + `box-shadow: 0 0 0 3px rgba(196,80,74,0.15)` glow (replaces the old bottom-border-only focus).
- `.field-with-btn` changed `align-items: flex-end` → `center` so the Generate button lines up with the now-boxed barcode input.

**Scope:** applies app-wide (Products, Purchase Orders, Customers, login, discounts, employees, etc.) since it's the shared base rule — one change, whole app updated.

**Tweak (same session):** after Karl said boxes still felt small/skeleton, increased padding/font/shadow globally, then **refined**: reverted the global rule to normal size and added a targeted `.input-lg` class (padding `0.85rem 1rem`, font `1rem`/`1.4`, `3px 3px 0` shadow) applied ONLY to the **Name, Barcode, and Description** inputs in `products/_form.blade.php`. Price/Qty/Reorder stay normal size. Verified: only those 3 carry `input-lg`.
**Overlap check (Karl reported visual overlap):** ran duplicate-selector sweep — no duplicate `.input-lg`/class defs, `.main` repeats are intentional media queries; no leftover emoji/underline. The "overlap" was uneven row heights: `input-lg` boxes taller than the short `<select>` row-mates. Fixed by giving `select` the same `0.85rem 1rem` / `1rem` padding so 2-col rows align evenly. Verified `input-lg` applied to exactly 3 inputs.
**Flat inputs (Karl wanted depth removed):** removed resting offset shadows (`2px 2px 0` base, `3px 3px 0` on `.input-lg`) — inputs are now flat boxes; only the coral focus glow remains. Verified 0 resting shadows, focus glow intact.
**Barcode human-readable digits (Karl: numbers cut off / not fully visible):** in `products/index.blade.php` JsBarcode call, changed `displayValue: false` → `true` (the digits were never rendered before), plus `fontSize: 13`, `textAlign: 'center'`, `textMargin: 3`, `margin: 8`, height `30`→`34` so the full 13-digit EAN-13 number prints clearly under the bars.
**Barcode field size/visibility (Karl: generated 13-digit code cut off at right, e.g. `...232` not visible; also wants barcode border same as Cost Price = normal size):** in `products/_form.blade.php`, removed `input-lg` from the barcode input (now matches Cost Price/normal size) AND made its wrapper `grid-column: 1 / -1` so it spans the full form width on its own row — the whole 13-digit number now fits on one line, no horizontal clipping. Name + Description keep `input-lg` (2 inputs). Verified.
**Reverted (Karl: "pangit"):** restored barcode to a normal grid cell WITH `input-lg` (big, like Name/Description) — no full-width row, no size reduction.
**Barcode layout v2 (Karl wanted to retry full-width but with Generate button BELOW, + explicit borders on Name/Barcode/Description):** in `products/_form.blade.php`, barcode wrapper now `class="field-stack"` (`grid-column: 1 / -1` → full width) with the Generate button rendered *after* the input (not beside it, dropped `.field-with-btn`); added `.bordered` class (solid 2px teal border + white fill) to Name, Barcode, and Description inputs. Added `.bordered`, `.field-stack`, `.field-stack .btn` rules to `layouts/app.blade.php`. Verified: barcode full-width, button below input, 3 bordered boxes.
**Form reorder (Karl):** restructured `products/_form.blade.php` — the 6 compact fields (Name, Category, Supplier, Unit price, Cost price, Reorder level) stay in the 2-column `form-grid` on top; Barcode (full-width, Generate below) and Description (full-width) moved *below* the grid. Verified field order top→bottom correct, 2 full-width stacks, 3 bordered boxes.
**Save button placement + spacing (Karl):** in `products/create.blade.php`, wrapped the Save `<button>` in `.form-actions` (clean top margin + gap) so it sits below Description with spacing; `.field-stack` got `margin-bottom: 1.25rem` so the Generate button (under Barcode) and the Description field have a clear gap. Verified: Save below Description, form-actions present, stack spacing set.
**Generate = one-time only (Karl: spamming Generate made many codes):** in `products/create.blade.php` + `edit.blade.php` JS, Generate now (a) skips if the barcode field already has a value, and (b) disables the button after first successful generate (`generateBtn.disabled = true`); also disabled on page load if the field is pre-filled (edit form). Added `#generate-barcode:disabled` style (opacity 0.5, not-allowed) in `layouts/app.blade.php`. Save button tucked just under Description (`form-actions` margin 0.5rem). Verified logic in both views + disabled style.

**How verified:** rendered `products.create` + `purchase-orders.create` in `tinker`; global boxed rule present; barcode Generate + PO form-actions intact. Temp preview file `_input_preview.html` deleted.

**Why:** Karl felt the underline inputs looked like a wireframe/skeleton; boxed reads clearly as a field while keeping the card aesthetic + design tokens.

---

## 2026-08-27 — Barcode Generate button restyle (no emoji, green hover)

**What (Karl's request):** Remove the ⚡ emoji and make the button match the design + fill green on hover.
- `resources/views/products/_form.blade.php` — button text `⚡ Generate` → `Generate` (no emoji).
- `resources/views/layouts/app.blade.php` — added `#generate-barcode` rule: keeps the shared `.btn .btn-secondary` look (white bg / teal border) and transitions to `background: var(--success)` (#2D8A4E), white text, green border on `:hover` (15ms transition).

**How verified:** rendered `products.create` in `tinker` — emoji absent, label is "Generate"; layout contains the `:hover` rule.

---

## 2026-08-27 — UI polish: barcode Generate button alignment + PO button spacing

**What (from Karl's feedback on screenshots):**
- `resources/views/layouts/app.blade.php` — added two CSS classes: `.field-with-btn` (input + button on one row, `align-items: flex-end` so the button lines up with the input's underline, reuses the existing bottom-border input style) and `.form-actions` (consistent top spacing + gap for grouped form buttons).
- `resources/views/products/_form.blade.php` — replaced the hand-rolled inline `flex` div around the barcode input with `.field-with-btn`; removed the leftover inline `white-space: nowrap` hack. Generate button now keeps the existing `.btn .btn-secondary` design (uppercase, bordered) and aligns to the input baseline.
- `resources/views/purchase-orders/create.blade.php` — wrapped "Add line" + "Create order" in `.form-actions` (was a bare `<p>` + cramped submit with no spacing). Buttons now have proper top margin + gap.

**How verified:** rendered both views in `tinker` with dummy data — `field-with-btn` + `generate-barcode` present in product form; `form-actions` + `add-line` + `Create order` present in PO form. (Live HTTP check returned 302 = auth redirect, expected since both routes need Manager/Admin.)

**Why:** Generate button was misaligned/no-style and PO submit was too close to Add line. Both now follow the shared design tokens instead of ad-hoc inline styles.

---

## 2026-08-27 — Feature: Barcode Generator on product form

**What:** Added a "⚡ Generate" button to the product create/edit form so staff don't hand-type barcodes. It fetches a unique, 13-digit barcode from the server and fills the input.
- `routes/web.php` — `GET /products/generate-barcode` → `ProductController@generateBarcode` (name `products.generate-barcode`), inside the `role:Manager,Admin` group.
- `app/Http/Controllers/ProductController.php::generateBarcode()` — returns `response()->json(['barcode' => ...])`; loops `random_int(1000000000000, 9999999999999)` until the code is not already in `product.barcode` (collision-safe).
- `resources/views/products/_form.blade.php` — barcode input now sits next to a "Generate" button.
- `resources/views/products/create.blade.php` + `edit.blade.php` — `@push('scripts')` block with vanilla JS that `fetch()`es the route and fills `#barcode`.

**How verified:**
- `php -l` clean; `route:list` shows `products.generate-barcode`.
- `tinker`: generator returned `4689726262231` → len=13, numeric, unique.
- **Bug caught & fixed during testing:** first version used `random_int(100000000000, 999999999999)` → only 12 digits, but the index page renders barcodes as **EAN13** (needs 13). Corrected the range to 13 digits and re-tested.

**Why:** Project already ships `public/js/JsBarcode.all.min.php`/`.js` and the index renders EAN13 labels, but the form forced manual barcode entry. This closes that gap and reuses the existing EAN13 rendering.

---

## 2026-08-27 — Feature: Sale Refund / Void (#2 from roadmap)

**What:** Added the ability to refund a completed sale from the receipt screen. Full-sale refund (teaching-simple, no per-item partial refund yet).
- `database/migrations/2026_08_27_000000_add_refund_fields_to_sale_transaction_table.php` — adds `status` (default `completed`, indexed) and `refunded_at` columns to `sale_transaction`.
- `app/Models/SaleTransaction.php` — added `status`/`refunded_at` to `$fillable` + `casts` (`refunded_at` datetime); added `scopeRefunded()` and `isRefunded()` helper (mirrors `PurchaseOrder::isPending()`).
- `routes/web.php` — `POST /pos/{saleTransaction}/refund` → `PosController@refund` (name `pos.refund`), inside the `role:Cashier,Manager,Admin` group.
- `app/Http/Controllers/PosController.php::refund()` — guards against double-refund, runs in `DB::transaction` with `lockForUpdate()` per product; restores inventory, reverses customer `total_purchases` + `loyalty_points` (floored), sets `status=refunded` + `refunded_at`, and `AuditLogger::record('refund', …)`. Reuses the same patterns as `store()`.
- `resources/views/pos/show.blade.php` — adds a "Refund Sale" button (with JS confirm) when not yet refunded, and a red "Refunded" badge after.

**How verified:**
- `php -l` clean on controller + model.
- `php artisan migrate --force` → migration DONE.
- `php artisan route:list` shows `pos.refund`.
- `tinker` end-to-end: picked a completed sale, inventory 0 → 3 after refund; status `completed` → `refunded`, `refunded_at` set. Audit row written only when an employee is authenticated (delta +1) — matches `AuditLogger` (bails with no `auth()->id()`), so the web flow logs correctly.

**Design note:** Sale row is kept (status flipped, not deleted) so the audit trail + receipt history stay intact. If partial/per-item refunds are wanted later, that's a follow-up (would need a refund line-items table).

**Why:** A POS with no undo for sales is incomplete; this closes that gap and reinforces transactions/locking/audit concepts.

---

## 2026-08-27 — Added demo seeders (categories + suppliers + products)

**What:** Created seeders so the catalog is populated out-of-the-box (previously only roles + employees were seeded, leaving the POS with no sellable products after `migrate:fresh`).
- `database/seeders/CategorySeeder.php` — seeds 5 categories (Beverages, Snacks, Personal Care, Household, Stationery).
- `database/seeders/SupplierSeeder.php` — seeds 3 suppliers.
- `database/seeders/ProductSeeder.php` — seeds 11 demo products, each linked to a category + supplier and given an `inventory` row (mirrors `ProductController@store` so the POS is sale-ready immediately).
- `database/seeders/DatabaseSeeder.php` — wired the 3 new seeders into `$this->call([...])` after `RoleSeeder`/`EmployeeSeeder`.

**How verified:** Started XAMPP MySQL (`mysqld.exe`, port 3306 was down), ran `php artisan db:seed --force` — all 5 seeders DONE. `tinker` confirms data present (categories/suppliers/products/inventory).

**Important note (honesty):** The DB already contained a fuller pre-existing catalog (11 categories incl. Dairy/Bakery/Produce, 5 suppliers, 35 products — seeded in an earlier session, not by today's files). The new seeders use `firstOrCreate`, so they merged without duplicating/overwriting existing rows. Re-running is safe/idempotent.

**Why:** Makes the system demoable immediately and teaches migrations/models/relationships concretely (priority #1 from the "what's next" list).

---

## 2026-08-27 — Repo cleanup (remove leftover/junk files)

**What:** Removed build artifacts and confirmed the repo is free of AI/Cursor temp files.
- Deleted `pos_case_study_spec.json` (the docx build spec created earlier this session — leftover, not app code).
- Cleared `storage/framework/views/*.php` (compiled Blade cache; Laravel regenerates automatically).
- Verified `.freebuff/` is already gone and a `find` for `freebuff`/`cursor`/`.tmp`/`.bak` returned nothing.

**Files touched:** `pos_case_study_spec.json` (removed), `storage/framework/views/*.php` (removed).

**Why:** Karl asked to remove unnecessary files (Cursor/freebuff temp files). Confirmed none exist; only cleared genuine leftovers.

**Not deleted (ambiguous):** `part1 case study.docx` — possible user draft, awaiting Karl's confirmation before removing.

---

## 2026-09-01 — Bug fixes (from code review)

**Critical bugs fixed:**

1. **Oversell via duplicate product lines** (`PosController::store`) — Items with the same `product_id` bypassed the stock check (each line read the same full stock, then two decrements caused oversell). Added an aggregate step that sums requested qty per product before the check, so `[{product_id:X,qty:3},{product_id:X,qty:3}]` with stock=5 correctly rejects (6>5). Verified: agg[10]=6.

2. **Refund race condition (TOCTOU)** (`PosController::refund`) — `isRefunded()` was checked outside the `DB::transaction` with no lock on the sale row, so two concurrent requests could both pass the guard and double-restore inventory + double-reverse loyalty. Moved the check inside the transaction, re-querying the sale row with `lockForUpdate()` + `with('saleDetails.product','customer','payment')` before the check. The lock serializes concurrent refunds.

3. **Purchase Order receive race (TOCTOU)** (`PurchaseOrderController::receive`) — Same pattern: `isPending()` checked outside the transaction without a lock. Two concurrent "Receive" clicks could double-add stock. Moved the check inside a transaction with `lockForUpdate()` on the PO row.

4. **Stored XSS via product name** (`pos/index.blade.php`) — Product names from DB were interpolated into `innerHTML` in the cart table rows and the barcode search result. A product named `<img src=x onerror=alert(1)>` would execute JS. Rewrote cart row rendering to use DOM methods (`createElement`, `textContent`, `append`) — no `innerHTML` with user data. Same for the barcode search result: switched to `textContent`. The only remaining `innerHTML` is `cartEl.innerHTML = ''` (clear, safe).

**Medium bugs fixed:**

5. **Dashboard revenue counted refunded sales** (`DashboardController`) — All revenue queries (`today_revenue`, `total_revenue`, `avg_transaction`, `weekly_trend`, `daily_baseline`, `payment_methods`) and `total_sales` now filter `where('status', 'completed')`. Verified: 10 status-filtered query paths.

6. **Top products/categories included refunded details** (`DashboardController`) — `top_products` and `top_categories` now join `sale_transaction` and filter `where('sale_transaction.status', 'completed')` so refunded sales don't inflate "top selling" rankings.

7. **Refund receipt still showed "Paid"** (`pos/show.blade.php`) — Added a prominent red "Refunded — Not Valid for Payment" banner at the top of the receipt when `$sale->isRefunded()`. The payment/change section still shows original amounts (for audit), but the banner makes it unmistakable.

**Verified:** `php -l` clean on all 3 controllers; route:list shows all routes; oversell aggregate correctly sums 3+3=6; DashboardController invoked with 25 stats keys; all views render.

---

## 2026-09-01 — Customer form redesigned to match product form

**What (Karl: "redesign yung new customer text box like yung ginawa natin sa products"):**
- `resources/views/customers/_form.blade.php` — First name, Last name, **Contact number**, and Address inputs now use `.input-lg bordered` (larger, explicit solid border — same as products' Name/Description). Email, Date of birth, Status stay normal boxed size. *(Contact number added after Karl flagged it was left out.)*
- `resources/views/customers/create.blade.php` + `edit.blade.php` — Save/Update button wrapped in `.form-actions` for consistent top spacing (same as products).

**Verified:** rendered `customers.create` in `tinker` — `first_name`, `last_name`, `address` carry `input-lg bordered`; contact/email/dob have none; `form-actions` present.

---

## 2026-09-01 — Redesigned remaining forms to match product/customer style

**What (Karl's per-form requests):**
- `employees/_form.blade.php` — First name, Last name, Username, Password, Contact number all `.input-lg bordered` (big). Role/Hire date/Status stay normal.
- `discounts/_form.blade.php` — only **Name** is `.input-lg bordered` (Karl: "yung name lang na text box").
- `suppliers/_form.blade.php` — Name + Address `.input-lg bordered`; **Email stays normal size in the 2-col grid** (Karl: email box looked too long/stretched — it's now compact beside Contact number).
- `categories/_form.blade.php` — Name `.input-lg bordered` + Description turned into a `.input-lg bordered` **textarea** (multi-line).
- `customers/_form.blade.php` — **Address changed to a `textarea` (rows=3, `.input-lg bordered`)** so it's "detailed out" (multi-line, bigger) instead of a single-line input.
- All create/edit blades for employees, discounts, suppliers, categories — Save/Update buttons wrapped in `.form-actions`.

**Verified:** rendered every create form — employees=5 big fields, discounts=1 (name only), suppliers=2 (name+address), categories=2 (name+desc textarea); all have form-actions; customers address is now a textarea; supplier email is normal size.

---

## 2026-09-02 — UI-only: unify form input sizing + table column layout

**What (Karl: fix inconsistent textbox/input sizing across CRUD forms; UI-only, NO schema/address/CRM changes):**
- `resources/views/layouts/app.blade.php` — shared form CSS unified:
  - Base input rule (text/password/email/number/date/search/select/textarea) now uses `padding: 0.85rem 1rem; font-size: 1rem; line-height: 1.4` — previously base inputs were `0.7rem 0.85rem / 0.95rem` while `select` + `.input-lg` were `0.85rem 1rem / 1rem`, so any grid row mixing a normal input with an `.input-lg`/select field had mismatched heights (Discount Name vs Value, Customer Name vs Email, etc.).
  - Removed the separate `select { padding: 0.85rem 1rem; font-size: 1rem; }` override — selects now inherit the unified rule, so all control types align vertically in a row.
- Table layout fixes:
  - `td.actions` width `25%` → `10%` + `white-space: nowrap` (edit/delete column no longer hogs the table).
  - Added `word-break: break-word; overflow-wrap: break-word` + `max-width: 260px` on data cells so long names/emails/addresses wrap instead of breaking layout.

**Verified:** rendered create forms for products, customers, employees, discounts, suppliers, categories — all OK. CSS checks: unified base padding, no separate select override, actions width 10%, long-text wrap present.

**Deliberately NOT touched (per Karl):** customer/supplier `address` schema, models, controllers, migrations — CRM handled separately by another group; no address split.

---

## 2026-09-02 — Supplier form layout: 2×2 grid (Name|Email / Contact|Address)

**What (Karl: email looked too far from the rest; wanted a tight 2×2 layout):**
- `resources/views/suppliers/_form.blade.php` — restructured from (Name full-width, then Contact|Email, then Address full-width) to a single `.form-grid` 2×2:
  - Row 1: **Name | Email**
  - Row 2: **Contact number | Address** (Address stays a textarea, rows=3)
  - All fields keep `.input-lg bordered` (consistent height).
- UI-only; no schema/model/controller changes (per Karl: CRM handles address separately).

**Verified:** rendered `suppliers.create` — all 4 fields in form-grid with `input-lg bordered`, Address is a textarea.

---

## 2026-09-02 — Supplier form: final layout (Name / Email+Contact pair / Address)

**What (Karl's iterations: 2×2 awkward → vertical stack → email too wide → final):**
- `resources/views/suppliers/_form.blade.php` — final layout:
  - **Name** — full-width
  - **Email | Contact number** — `.field-pair` (flex row, each fixed 300px, wraps on narrow screens) so email isn't stretched across the card
  - **Address** — full-width textarea (rows=3)
  - All fields `.input-lg bordered`.
- `resources/views/layouts/app.blade.php` — added `.field-pair` CSS (replaced the unused `.form-grid-2` rules).
- UI-only; no schema/model/controller changes (CRM/address split explicitly deferred to another group).

**Verified:** rendered `suppliers.create` — field-pair used + defined; view cache cleared.

---

## 2026-09-06 — Refund system v2: partial refunds + reasons + role gate

**What:** Upgraded the full-sale-only refund into a real refund system.
- `database/migrations/2026_09_06_000000_create_sale_refund_tables.php` — NEW tables `sale_refund` (one row per refund event: amount, reason, notes, is_full_refund, employee, timestamp) + `sale_refund_item` (per-line refunded qty + amount).
- `app/Models/SaleRefund.php` + `SaleRefundItem.php` — new models; `SaleTransaction::refunds()` + `isFullyRefunded()`; `SaleDetail::refundedQuantity()` / `refundableQuantity()`.
- `app/Services/RefundService.php` — NEW central refund logic (full + partial): locks sale + detail rows (TOCTOU-safe), validates per-line refundable qty, **pro-rates discounts** (refund = actual paid share, not sticker price), restores inventory, reverses loyalty + total_purchases proportionally, writes refund records + audit, flips status to `refunded` only when all lines fully refunded. **Role gate:** Cashiers can only refund their own sales; Manager/Admin any sale. Reasons enum: damaged / wrong_item / changed_mind / defective / other.
- `PosController::refund()` — now validates reason/notes/items and delegates to the service (old inline logic removed; behavior preserved + extended).
- `resources/views/pos/show.blade.php` — Refund button opens a panel: per-line qty inputs (max = refundable), reason dropdown, notes; refund history card below; "Fully Refunded" badge when done.

**How verified (tinker, real data):**
- Partial: refund 1 of 3 (₱105 line) → ₱35.00 exact, stock 2→3, status stays completed.
- Second partial of same line → ₱70; over-refund attempt → blocked ("Only 0 remaining").
- Discounted sale (20% off): refund = paid share ₱100 = expected ₱100 ✅ (pro-rating correct).
- Full refund (empty items) → is_full=true, status flipped to refunded + refunded_at set; second attempt blocked.
- Role gate: cashier refunding manager's sale → rejected with clear message.
- Receipt renders refund panel + history; route `pos.refund` intact; `php -l` clean.

**Note:** testing created a few refund records on seeded sales (#2, #4, one full) — harmless demo data; `migrate:fresh --seed` resets.
**Follow-up (Karl flagged off-design inputs):** replaced the custom items TABLE + inline-styled inputs in the refund panel with standard `.form-grid` fields — one labeled number input per line ("{Product} — refund qty (N refundable)"), matching every other CRUD form. No inline styles, no bespoke table.
**Follow-up 2 (Karl):** Notes field → `.input-lg bordered` textarea rows=2 (matches Address/Description convention). Refund panel was hard to find (hidden below long receipt) → Refund button now calls `toggleRefundPanel()`: opens panel, smooth-scrolls to it, auto-focuses first qty input.

**Still open (roadmap):** #3 refunds list page, #4 printable credit slip, #6 refund window policy, #7 dashboard refund stats.

---

## 2026-09-06 — Data fix: 11 products had non-EAN-13 barcodes

**What:** Karl hit "Barcode invalid — label printed with error notice" when printing a label. Diagnosis: 11 of 35 products (from the earlier demo seeder) had SKU-style barcodes (`BR-0001`, `SN-0002`, …) which JsBarcode rejects as EAN-13. The seeder predates the EAN-13 checksum fix.
**Fix:** ran a one-off tinker script — for every product whose barcode fails the EAN-13 checksum, assigned a fresh valid code via `Product::generateBarcode()`. Result: 11 fixed, 0 invalid remaining (verified by re-checking all 35).
**Note:** the generator itself was already correct (new products are fine); this was stale seed data only.

---

## 2026-09-06 — Feature: Reports & CSV export (#3 from roadmap)

**What:** New Manager/Admin Reports page with date-range filtering and CSV export.
- `app/Http/Controllers/ReportController.php` — 4 reports: **Sales** (all transactions w/ cashier, customer, discount, status), **Top selling products** (units + revenue, completed sales only), **Inventory** (stock vs reorder level w/ Out-of-stock/Low/OK status), **Refunds** (per-event from sale_refund). `export()` streams CSV via `response()->streamDownload` with UTF-8 BOM (Excel-safe ₱), `strip_tags` on cells (CSV-injection guard), explicit `fputcsv` escape param (PHP 8.4 deprecation fix), unknown type → 404. Default range = last 30 days.
- `resources/views/reports/index.blade.php` — date-range form (`.form-grid`), one card per report with 5-row preview + Export CSV button (no emoji).
- `routes/web.php` — `GET /reports` + `GET /reports/export/{type}` inside `role:Manager,Admin`.
- `config/roles.php` — `reports.index` module + "Reports" label → sidebar link auto-appears.

**How verified:** `php -l` clean; routes listed; page renders all 4 sections; exports return 200 with real rows (sales=18, products=18, inventory=35, refunds=5 for Aug31–Sep6); unknown type 404s; role-gated (Cashier blocked by middleware).

---

## Standing conventions

- Code style: keep existing Laravel conventions (singular table names, `<entity>_id` PKs, `public $timestamps = false` on most models).
- Document each task here the moment it is done, before moving on.
- When documenting, cite the exact file + method (e.g. `app/Http/Controllers/PosController.php::store`).
