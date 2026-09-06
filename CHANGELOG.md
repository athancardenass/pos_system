# POS System — Changelog

> Every change/task in this project is documented here. Newest entries on top.
> Format: `## YYYY-MM-DD — Title` → What changed, files touched, why.

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

## Standing conventions

- Code style: keep existing Laravel conventions (singular table names, `<entity>_id` PKs, `public $timestamps = false` on most models).
- Document each task here the moment it is done, before moving on.
- When documenting, cite the exact file + method (e.g. `app/Http/Controllers/PosController.php::store`).
