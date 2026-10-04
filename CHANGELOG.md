# POS System — Changelog

> Every change/task in this project is documented here. Newest entries on top.
| Format: `## YYYY-MM-DD — Title` → What changed, files touched, why.

> **AGENTS.md lock:** agent never runs `git commit`/`push`; group controls VCS.

## 2026-10-04 — Add subtle depth to info notices

**What:** Added a soft raised shadow and light surface highlight to informational flash notices.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Make notices such as pending purchase order alerts read as raised content rather than recessed panels.

## 2026-10-04 — Tighten Receipt Settings layout

**What:** Reduced the page width and header height, arranged store name and TIN in a balanced row, and gave address and receipt footer fields full-width placement.

**Files touched:** `resources/views/settings/receipt.blade.php`, `CHANGELOG.md`.

**Why:** Remove oversized spacing and uneven field columns while keeping the existing settings and save behavior.

## 2026-10-04 — Remove peach tint from form content

**What:** Set form sections and any tables inside edit/new-discount panels to white, with a subtle neutral-green hover.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Keep edit and New Discount content clean white while retaining the Slate Grav headers.

## 2026-10-04 — Apply unified style to edit forms

**What:** Added a shared Slate Grav header and compact white form panel style to all edit forms and the New Discount form. Organized inventory quantity input and aligned its save action with the other forms.

**Files touched:** `resources/views/layouts/app.blade.php`, `resources/views/suppliers/edit.blade.php`, `resources/views/categories/edit.blade.php`, `resources/views/promotions/edit.blade.php`, `resources/views/employees/edit.blade.php`, `resources/views/inventory/edit.blade.php`, `resources/views/customers/edit.blade.php`, `resources/views/coupons/edit.blade.php`, `resources/views/discounts/edit.blade.php`, `resources/views/discounts/create.blade.php`, `resources/views/products/edit.blade.php`, `CHANGELOG.md`.

**Why:** Make editing and discount creation screens visually consistent with the approved modern payment/settings screens while preserving existing routes, fields, and save behavior.

## 2026-10-04 — Refresh payment review and settings screens

**What:** Updated Pending E-Wallet and Pending Card list/review screens with slate headers, clearer pending states, responsive summaries, and consistent action styling. Reorganized VAT and receipt settings into clearer compact sections.

**Files touched:** `resources/views/pos/pending-ewallet/index.blade.php`, `resources/views/pos/pending-ewallet/show.blade.php`, `resources/views/pos/pending-card/index.blade.php`, `resources/views/pos/pending-card/show.blade.php`, `resources/views/settings/vat.blade.php`, `resources/views/settings/receipt.blade.php`, `CHANGELOG.md`.

**Why:** Make payment review and store settings easier to scan and consistent with the Slate Grav and soft green interface direction while preserving existing form actions and review behavior.

## 2026-10-04 — Align employee password fields
**What:** Made the login username span its own row so the new and confirmation password fields align side by side.
**Files touched:** `resources/views/employees/_form.blade.php`, `CHANGELOG.md`.
**Why:** Remove the unbalanced half-empty row in the employee edit form.

## 2026-10-04 — Clarify employee edit form
**What:** Grouped employee account fields into login credentials, role/status, and manager approval PIN sections; clarified that blank password/PIN fields preserve the existing values.
**Files touched:** `resources/views/employees/_form.blade.php`, `CHANGELOG.md`.
**Why:** Make employee editing easier to understand and distinguish sign-in credentials from the manager-only approval PIN.

## 2026-10-04 — Fix employee password validation rules
**What:** Split the conditional password-presence rule into individual Laravel validation rules.
**Files touched:** `app/Http/Controllers/EmployeeController.php`, `CHANGELOG.md`.
**Why:** Prevent Laravel from interpreting `nullable|string|min:8` as a nonexistent validator method.

## 2026-10-04 — Confirm employee password changes
**What:** Added password confirmation to employee create/edit validation and trimmed username input before unique validation and saving.
**Files touched:** `app/Http/Controllers/EmployeeController.php`, `resources/views/employees/_form.blade.php`, `CHANGELOG.md`.
**Why:** Prevent silent password-entry mismatches and invisible leading/trailing whitespace in usernames from locking an employee out after an edit.

## 2026-10-04 — Show shift times and style pending payment table headers
**What:** Added server-backed opened/last-closed times to the POS shift button and Slate Grav headers with white bold text and subtle depth to the Pending E-Wallet and Pending Card tables. Added cash drawer status coverage and aligned assertions with the current server-side customer search, discount picker, and link markup.
**Files touched:** `app/Http/Controllers/CashDrawerController.php`, `app/Services/CashDrawerService.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/pending-ewallet/index.blade.php`, `resources/views/pos/pending-card/index.blade.php`, `tests/Feature/CashDrawerFlowTest.php`, `tests/Feature/VatSettingsTest.php`, `tests/Unit/SaleServiceTest.php`, `CHANGELOG.md`.
**Why:** Let cashiers see when their shift opened or last closed, align payment review tables with the shared Slate Grav table-header treatment, and keep tests accurate to the implemented UI and data loading behavior.

## 2026-10-04 — Add manager PIN authorization and structured audit

- Added hashed 4–6 digit manager PIN setup, a cashier authorization keypad with a 60-second lockout after three failed attempts, and one-use server grants for discounts, refunds, and drawer opening. Protected cart/held-cart actions request manager approval and a reason; approvals and failed attempts are recorded with requester, approver, register, and structured details. Added a reversible migration with nullable employee PIN and audit fields; existing rows are unchanged.
- Files: `app/Http/Controllers/AuditLogController.php`, `app/Http/Controllers/CashDrawerController.php`, `app/Http/Controllers/EmployeeController.php`, `app/Http/Controllers/ManagerAuthorizationController.php`, `app/Http/Controllers/PosController.php`, `app/Models/AuditLog.php`, `app/Models/Employee.php`, `app/Services/AuditLogger.php`, `app/Services/ManagerAuthorizationService.php`, `app/Services/RefundService.php`, `database/migrations/2026_10_04_000003_add_manager_authorization_audit_fields.php`, `resources/views/audit-logs/index.blade.php`, `resources/views/components/manager-pin-modal.blade.php`, `resources/views/employees/_form.blade.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/show.blade.php`, `routes/web.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/ManagerAuthorizationTest.php`, `tests/Feature/RefundFlowTest.php`, `CHANGELOG.md`.

## 2026-10-04 — Add register-local Hold and Resume

- Cashiers can hold a non-empty cart and resume or remove held carts from the register header. Holds save cart quantities, customer and discount selections, cashier/register details, and a 24-hour expiry in browser storage; resuming refreshes stock and warns while reducing or skipping unavailable items. Close Shift warns when holds remain, and held carts never reserve or deduct stock.
- Added an authenticated read-only stock snapshot endpoint for resume-time stock checks. No database schema or checkout behavior changed.
- Files: `resources/views/pos/index.blade.php`, `app/Http/Controllers/PosController.php`, `app/Services/SaleService.php`, `routes/web.php`, `tests/Feature/CheckoutFlowTest.php`, `CHANGELOG.md`.

## 2026-10-04 — Improve POS cart quantities, tender, and stock cues

- Added editable whole-unit quantities and three-decimal kilogram weights with stock caps, exact cash tender, live change, and checkout blocking for short cash. Stock badges now turn yellow at 10 or less and red at zero; zero-stock products stay unavailable. Kept decimal inventory precision through the product stock accessor and included each product's unit of measure in the register payload.
- Files: `resources/views/pos/index.blade.php`, `app/Models/Product.php`, `app/Services/SaleService.php`, `tests/Feature/CheckoutFlowTest.php`, `CHANGELOG.md`.

## 2026-10-04 — Fix POS register header actions

- Grouped the cashier/shift status with readable Held, Pending E-Wallet, and primary Close Shift actions in the requested order. Kept labels visible on narrow screens, added the amber pending-count state and a held-list empty state, and made the e-wallet queue visible to cashiers for their own requests while managers see the full queue.
- Files: `resources/views/pos/index.blade.php`, `resources/views/pos/pending-ewallet/index.blade.php`, `app/Http/Controllers/PosController.php`, `routes/web.php`, `tests/Feature/EwalletVerificationTest.php`, `CHANGELOG.md`.

## 2026-10-04 — Add manager-verified e-wallet checkout

- E-wallet submissions now create a pending verification request and reserve the requested stock. A manager must confirm the completed payment, reference, and amount in the merchant app before checkout creates a sale, payment, and receipt. Rejected or 60-minute-expired requests release their stock reservation; review actions are audited.
- Added manager queue and request detail screens, a cashier status view, and POS guidance while preserving immediate cash/card checkout and existing payment/provider routes. Added lifecycle, permissions, duplicate-reference, reservation, expiry, and manager-confirmation tests.
- Files: `app/Http/Controllers/PosController.php`, `app/Models/PendingEwalletVerification.php`, `app/Services/EwalletVerificationService.php`, `database/migrations/2026_10_04_000002_create_pending_ewallet_verifications_table.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/pending-ewallet/index.blade.php`, `resources/views/pos/pending-ewallet/show.blade.php`, `routes/web.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/EwalletVerificationTest.php`, `CHANGELOG.md`.

## 2026-10-04 — Use slate green for selected navigation

- Changed the selected navigation button to a slate-green `#203C3D` surface with white text and a restrained shadow; unselected controls retain the light peach treatment.
- Files: `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

## 2026-10-04 — Style navigation links as compact buttons

- Updated the shared top navigation links to use compact rounded secondary-button surfaces, soft shadows, green hover feedback, a clear green-tinted active state with visible keyboard focus, and stronger Manrope weights for easier scanning.
- Kept Dashboard first, Reports last, and preserved module links and route behavior.
- Files: `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

## 2026-10-04 — Repair VAT settings preview

- Applied the approved VAT migration to the local preview SQLite database after the VAT settings page failed because its schema was missing.
- Added the shared `.btn` class to the Discounts page's VAT settings action and the VAT page's secondary links so they receive the intended button layout and visual styling; added page-render assertions.
- Files: `resources/views/discounts/index.blade.php`, `resources/views/settings/vat.blade.php`, `tests/Feature/VatSettingsTest.php`, `CHANGELOG.md`; applied `database/migrations/2026_10_04_000001_add_configurable_vat_rate.php` to the local preview database.

## 2026-10-04 — Add editable VAT rate and refine POS discount picker

- Added a manager-only VAT settings page backed by `vat_settings`; checkout snapshots the rate on each sale so future updates do not rewrite old receipt tax details. Updated `SaleTransaction` VAT extraction to use that saved rate while keeping VAT-inclusive totals unchanged.
- Added the VAT settings entry point beside Discount management and polished the POS discount select/preview. Kept the existing guard that prevents deleting discounts already used on sales.
- Added focused feature coverage for manager access and validation, future-sale VAT updates, sale-time VAT snapshots on receipts, the POS Discount picker, and the used-discount deletion guard.
- Files: `app/Http/Controllers/VatSettingsController.php`, `app/Models/VatSetting.php`, `app/Models/SaleTransaction.php`, `app/Services/CheckoutService.php`, `app/Services/VatSettingsService.php`, `database/migrations/2026_10_04_000001_add_configurable_vat_rate.php`, `resources/views/discounts/index.blade.php`, `resources/views/pos/index.blade.php`, `resources/views/settings/vat.blade.php`, `routes/web.php`, `tests/Feature/VatSettingsTest.php`.

## 2026-10-04 — Enrich the peach and botanical color palette

- Strengthened the light peach canvas and warm surface contrast, refined warm charcoal text, and brightened the shared green accent in `resources/views/layouts/app.blade.php` for a fresher, more dimensional appearance.
- Kept red limited to danger and error states and softened shared shadows without changing layout or behavior.

## 2026-10-04 — Reserve red for semantic alerts

- Changed the shared primary accent, focus ring, selected states, and ordinary ghost actions in `resources/views/layouts/app.blade.php` to a fresh green, keeping the light peach canvas and warm charcoal text.
- Kept red for danger actions, errors, and shortage/inactive indicators so it communicates status rather than general navigation or action styling.

## 2026-10-04 — Warm up the shared UI palette

- Replaced the slate and teal color pairing in `resources/views/layouts/app.blade.php` with warm charcoal text, fresh coral actions, and light peach surfaces so the interface feels brighter and more current.
- Updated shared selection, focus, hover, semantic, and shadow colors while preserving page layouts and application behavior.

## 2026-10-03 — Refine shared UI palette with slate and teal

- Updated shared color tokens in `resources/views/layouts/app.blade.php`: `#344450` slate text, `#203C3D` teal actions and selection, and a lighter peach canvas with warm neutral surfaces; coral remains reserved for focus and danger states.
- Applied the theme through shared module surfaces, selected/hover states, controls, links, and soft shadows without changing layout or behavior.

## 2026-10-03 — Support fractional refunds and omit unused users table

- `database/migrations/2026_10_03_000001_change_sale_refund_item_quantity_to_decimal.php` changes refund-item quantities to `DECIMAL(10,3)`. `app/Services/RefundService.php`, `app/Models/SaleDetail.php`, `app/Models/SaleRefundItem.php`, and `app/Http/Controllers/PosController.php` now preserve fractional quantities in refund limits, prorated amounts, and stock restoration; `resources/views/pos/show.blade.php` accepts thousandth-unit quantities.
- `database/migrations/2026_10_03_000002_drop_unused_users_table.php` drops only the unused `users` table after the starter migrations, so fresh installs finish without it while keeping `sessions` and `password_reset_tokens`.
- `tests/Feature/RefundFlowTest.php` covers fractional partial/full refund math and stock restoration; `tests/Feature/ValidationRulesTest.php` asserts the fresh schema keeps required framework tables and omits `users`.

## 2026-10-03 — Tighten quantity, date, barcode, and payment validation

- `app/Http/Controllers/InventoryController.php::update` and `app/Http/Controllers/PurchaseOrderController.php::store` accept quantities to three decimal places; `resources/views/inventory/edit.blade.php` and `resources/views/purchase-orders/create.blade.php` expose matching `.001` steps.
- `app/Http/Controllers/CustomerController.php::validated`, `app/Http/Controllers/EmployeeController.php::validated`, and `app/Http/Controllers/PurchaseOrderController.php::store` enforce the existing form date bounds server-side.
- `app/Http/Controllers/ProductController.php::validated` now requires a 13-digit EAN-13 barcode with a valid check digit; `tests/Feature/CrudAndPosTest.php` uses an EAN-13 barcode.
- `app/Http/Controllers/PosController.php::store` requires payment references/providers for card and e-wallet checkouts and limits received amounts to two decimal places. `tests/Feature/CheckoutFlowTest.php` and `tests/Feature/ValidationRulesTest.php` cover payment, quantity, date, and barcode validation.

## 2026-10-03 — Remove unused empty users table from local database

- Dropped the empty, unreferenced `pos_system.users` table from the canonical local database after confirming the application authenticates through `employee`.
- Kept `employee`, `password_reset_tokens`, `sessions`, and `job_batches`; they are used or remain configured by the application/framework.
- No application routes, validation rules, or business logic changed.

## 2026-10-03 — Refine discount and employee edit forms

- Reorganized the shared discount form in `resources/views/discounts/_form.blade.php` into Discount details and Availability sections, with responsive field groups and clearer value guidance; both create and edit pages use the updated form.
- Improved page context and action labels in `resources/views/discounts/create.blade.php`, `resources/views/discounts/edit.blade.php`, and `resources/views/employees/edit.blade.php`.
- Added responsive form styling in `resources/views/layouts/app.blade.php`; kept field names, request methods, routes, and save behavior unchanged.

## 2026-10-03 — Modernize shared module styling and employee form

- Updated shared styling in `resources/views/layouts/app.blade.php` with a lighter peach canvas, Manrope typography, softer surfaces, sentence-case controls, and modern teal hover/selected states across modules.
- Removed the `+ New Customer` action from `resources/views/customers/index.blade.php` because CRM will provide customer records.
- Grouped existing employee fields into Personal details and Account access sections in `resources/views/employees/_form.blade.php`; kept field names and submission behavior unchanged.
- Preserved navigation order, routes, backend behavior, and database structure.

## 2026-10-03 — Rework POS workstation visual hierarchy

- Rebuilt the POS surface styling in `resources/views/pos/index.blade.php` around the cashier flow: clearer product tiles, connected transaction/payment surfaces, visible totals, compact searchable customer rail, and fixed-height desktop layout with independent product/cart scrolling.
- Improved receipt hierarchy and made payment method and received amount explicit in `resources/views/pos/show.blade.php`; retained existing discount reference and print behavior.
- Kept navigation, routes, checkout behavior, database schema, and backend functionality untouched. Promotions and Coupons remain omitted from POS presentation.

## 2026-10-03 — Refine POS and receipt Clean SaaS presentation

- Refined `resources/views/pos/index.blade.php` surfaces, product cards, type hierarchy, focus states, discount summary, payment controls, and compact scrolling with the existing brand colors; preserved workstation layout and keyboard interactions.
- Refined `resources/views/pos/show.blade.php` into a clearer thermal receipt with separate product, quantity, unit-price, and total columns plus soft SaaS styling and thermal-width printing.
- Kept customer search, discount behavior, routes, navigation, database, checkout logic, and Promotions/Coupons scope unchanged.

## 2026-10-03 — Align register workspace and receipt hierarchy

- Moved payment method, amount due, cash received/change, and Complete Sale into the left register workspace; kept searchable Customer and Discount selection in the right rail in `resources/views/pos/index.blade.php`.
- Made the applied Discount name, `DISC-` reference, and savings explicit in Live Transaction; tightened panel spacing while retaining independent content scrolling.
- Rebuilt `resources/views/pos/show.blade.php` receipt markup into structured store, transaction, item, adjustment, totals, payment, and footer sections with class-based thermal styling.
- Updated the focused receipt presentation assertion in `tests/Feature/PromotionEngineTest.php` to check the new discount row structure.
- No route, schema, or checkout logic changed; Promotions and Coupons remain omitted from cashier presentation.

## 2026-10-03 — Redesign POS register and receipt presentation

- Rebuilt `resources/views/pos/index.blade.php` as a compact, viewport-filling supermarket workstation with product catalog, Live Transaction, searchable customer selection, existing Discount preview, and payment controls. Removed Promotions/Coupons from the POS presentation without changing their backend or schema.
- Reworked `resources/views/layouts/app.blade.php` into a compact top navigation, with Dashboard first, Reports last, and Promotions/Coupons omitted from navigation.
- Restyled `resources/views/pos/show.blade.php` as a compact thermal receipt with the existing Discount identifier/amount, customer ID, and loyalty points; omitted Promotion/Coupon lines.
- Updated `tests/Feature/PromotionEngineTest.php` assertions to match the approved presentation scope while retaining checks that existing backend checkout calculations are unchanged.
- Preserved checkout, payment, cash drawer, refund, and database behavior while making the cashier workflow fit the desktop viewport.

## 2026-10-03 — Scope update: exclude promotions and coupons from POS redesign

- Updated `docs/superpowers/specs/2026-10-03-supermarket-pos-redesign-design.md` to exclude Promotions and Coupons from POS UI, navigation, live transaction, receipt, and backend/schema work. Kept existing Discount support in scope per CRM database availability.

## 2026-10-03 — Design spec: supermarket POS workstation redesign

- Added `docs/superpowers/specs/2026-10-03-supermarket-pos-redesign-design.md` describing the approved reference direction, viewport layout, adjustment visibility, receipt hierarchy, scope boundaries, and acceptance checks. This records the implementation target before the broad UI rebuild.

---

## 2026-09-25 — SM Grocery Store Seeder, SaleService Refactor, Refund Window UI & Credit Slip Polish

**What:**
1. **SM Grocery Store Seeder (`database/seeders/SMGroceryStoreSeeder.php`):**
   - Created comprehensive Philippine SM Supermarket / SM Hypermarket demo catalog.
   - 15 authentic supermarket categories (SM Bonus & Value Essentials, Fresh Produce, Meat & Poultry, Seafood, Dairy, Rice & Condiments, Noodles, Snacks, Beverages, Household, Personal Care, Baby Care).
   - 11 top Philippine FMCG distributors & suppliers (SM Retail Central Distribution, URC, San Miguel, Monde Nissin, Nestle, Unilever, P&G, NutriAsia, Century Pacific, Del Monte, Oishi).
   - 60+ authentic Filipino grocery goods with verified EAN-13 barcodes (GS1 480 prefix + calculated checksum), realistic pricing, units of measure, and active inventory.
   - SM Advantage Card (SMAC) loyalty customer accounts with active points.
   - SM Supermarket promotions & coupons (`SMAC50`, `SMBONUS100`, `SM3DAYSALE`, `SAVEBIG15`).
   - Covered by 5 new automated feature tests in `tests/Feature/SMGroceryStoreSeederTest.php`.
2. **SaleService Refactoring (`app/Services/SaleService.php`):**
   - Extracted catalog preparation and receipt eager-loading query logic from `PosController` into a dedicated domain service.
   - PosController methods `index()` and `show()` now delegate to `SaleService::getPosIndexData()` and `SaleService::loadSaleForReceipt()`.
   - Added refund window helper calculations: `refundDaysRemaining()` and `isOutsideRefundWindow()`.
   - Covered by 3 new unit tests in `tests/Unit/SaleServiceTest.php`.
3. **Refund Window Status UI (`resources/views/pos/show.blade.php`):**
   - Added dynamic return window indicators on receipt footer and refund modal.
   - Displays real-time eligibility countdown ("Refund window active: X days remaining") or expiration banner ("Return policy expired: X days old &bull; Manager override required").
   - Added policy override warning notice inside the refund form when a transaction exceeds the 7-day limit.
4. **Credit Slip Polish (`resources/views/pos/refund-slip.blade.php`):**
   - Upgraded refund credit slip with official Philippine supermarket layout, BIR VAT Reg TIN, formatted slip numbering (`CS-00000X`), returned merchandise breakdown, and tender reimbursement note.
   - Added formal customer acknowledgment and authorized store manager signature lines.
   - Optimized thermal print styling for receipt and credit slip issuance.
5. **Academic Case Study & System Documentation Deliverables:**
   - Generated `POS_System_Documentation.docx` and `POS_Case_Study.docx` in Word document format with academic structure, architecture diagrams, database ER summaries, and evaluation metrics.

**Files touched:**
- `database/seeders/SMGroceryStoreSeeder.php`
- `app/Services/SaleService.php`
- `app/Http/Controllers/PosController.php`
- `resources/views/pos/show.blade.php`
- `resources/views/pos/refund-slip.blade.php`
- `tests/Feature/SMGroceryStoreSeederTest.php`
- `tests/Unit/SaleServiceTest.php`
- `CHANGELOG.md`
- `SYSTEM_DOCUMENTATION.md`
- `POS_System_Documentation.docx`
- `POS_Case_Study.docx`

---

## 2026-09-23 — System Architecture & Technical Documentation

**What:**
- Generated comprehensive technical and architectural specification document: `SYSTEM_DOCUMENTATION.md`.
- Documents executive summary, architecture diagram, domain services (`CheckoutService`, `PromotionService`, `RefundService`, `CashDrawerService`), multi-method payment verification, RBAC security model, database schema data dictionary, and semester integration horizon (CRM/HRMS/Procurement).

**Files touched:**
- `SYSTEM_DOCUMENTATION.md`
- `CHANGELOG.md`

---

## 2026-09-23 — Global Barcode Catcher, Web Audio Synthesizer & Receipt Refund Policy

**What:**
1. **Global Hardware Barcode Catcher (`resources/views/pos/index.blade.php`):**
   - Implemented high-speed keystroke buffering (<120ms burst window) listening globally on `window`.
   - Allows physical handheld USB/Wireless barcode scanners to add scanned items to the cart from anywhere on the screen without clicking the search input.
2. **Web Audio Synthesizer Feedback (`resources/views/pos/index.blade.php`):**
   - Built zero-dependency procedural audio synth via Web Audio API:
     - Classic 1760Hz supermarket scanner beep on valid item scan / add.
     - Low double buzz error sound on out-of-stock or invalid payment/coupon entry.
     - Cheerful 3-tone cash register chime upon completed sale submission.
3. **Receipt 7-Day Refund Policy (`resources/views/pos/show.blade.php`):**
   - Added official supermarket refund policy notice ("Exchange or refund allowed within 7 days with this official receipt") to the printed thermal receipt footer.

**Files touched:**
- `resources/views/pos/index.blade.php`
- `resources/views/pos/show.blade.php`
- `CHANGELOG.md`
94/94 tests pass.

---

## 2026-09-23 — Supermarket POS Features: Live Coupon Preview, Category Chips, Keyboard Shortcuts & Auto-Print

**What:**
1. **Live Coupon Apply & Instant Discount Preview:**
   - Added `POST /pos/check-coupon` route and `PosController::checkCoupon` endpoint utilizing `PromotionService::validateCoupon` and `PromotionService::couponDiscount`.
   - Added "Apply" button and live coupon validation feedback in `pos/index.blade.php`.
   - Grand total, discount line, and change recalculate live on screen before sale completion.
2. **Product Search Category Filter Chips:**
   - Added category filter pills (`[ All ] [ Beverages ] [ Bakery ] ...`) above the live search results in `pos/index.blade.php`.
   - Clicking a chip filters the products instantly without typing.
3. **Cashier Keyboard Shortcuts:**
   - Integrated keyboard navigation:
     - `F2`: Focus Search / Barcode scanner.
     - `F4`: Focus Cash Received field.
     - `F8`: Cycle payment methods (Cash &harr; Card &harr; E-Wallet).
     - `Enter`: Submit sale when inside payment fields.
     - `Esc`: Close modals or clear search.
   - Added sleek keyboard shortcut hint bar at the bottom of the POS terminal.
4. **Auto-Receipt Print & Continuous Queue Flow:**
   - Added `?auto_print=1` trigger on `pos.show` so thermal receipt print dialog executes automatically upon checkout.
   - Added "Next Customer" banner and global Spacebar / Enter shortcut on receipt screen to immediately reset for the next transaction in queue.
5. **Clean Supermarket Login Screen:**
   - Finalized `resources/views/auth/login.blade.php` to clean Operator Username and Security Password fields with zero emoji, perfectly centered icons, and demo credentials hint in footer.

**Files touched:**
- `app/Http/Controllers/PosController.php`
- `routes/web.php`
- `resources/views/pos/index.blade.php`
- `resources/views/pos/show.blade.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/CheckoutFlowTest.php`
- `CHANGELOG.md`
94/94 tests pass.

---

## 2026-09-23 — UI Polish & Precision Alignment (Login Icons, Security Operator Preset, Button Typography, Search Alignment)

**What:**
1. **Operator & Security Password Icons (`resources/views/auth/login.blade.php`):**
   - Removed `margin-bottom: 1.1rem` inheritance collision on `.input-with-icon input`.
   - Set mathematically centered geometry (`top: 50%; transform: translateY(-50%)`, `width: 18px; height: 18px; left: 0.95rem`) for both Operator username and Security Password lock icons.
   - Symmetrically aligned show/hide password toggle button at `right: 0.85rem`.
2. **Terminal Operator Selection Security Model (`resources/views/auth/login.blade.php`):**
   - Transformed the auto-login button bar into a professional supermarket "Select Operator Station" preset (`Cashier Station`, `Manager Station`).
   - Selecting a station pre-populates the operator username and immediately focuses the security password field for explicit credential verification, preserving demo speed without undermining supermarket security protocols.
   - Preserved development credentials note in the security footer for testing convenience.
3. **Button Typography & Color Consistency (`resources/views/layouts/app.blade.php` & `resources/views/dashboard.blade.php`):**
   - Added explicit `color: #ffffff !important;` across `.btn`, `a.btn`, `a.btn:link`, `a.btn:visited`, `a.btn:hover`, `a.btn:focus`, and `a.btn:active`.
   - Solved browser user-agent `:visited` color override that caused the Dashboard "New POS Sale" link font to appear inconsistent or dark.
   - Added clean SVG register icon to Dashboard "New POS Sale" action button.
4. **POS Product Search Magnifying Glass Icon (`resources/views/pos/index.blade.php`):**
   - Removed 1.1rem bottom margin leakage on `.pos-search-input`.
   - Vertically centered `.pos-search-icon` at exact 50% relative to the 48px input height with 0.95rem left padding.
5. **Caches & Integrity:**
   - Cleared compiled views (`view:clear`), route cache (`route:clear`), and config cache (`config:clear`).
   - Verified 93/93 tests pass without regressions.

**Files touched:**
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/pos/index.blade.php`
- `CHANGELOG.md`
93/93 tests pass.

---

## 2026-09-23 — Terminal Login Redesign & Demo Quick-Switcher

**What:**
- Redesigned the authentication screen (`resources/views/auth/login.blade.php`) to look like a modern supermarket terminal login.
- Added 1-click demo role switcher chips (`Cashier` / `Manager`) to instantly auto-fill credentials during school demos and testing.
- Added input field icons, real-time show/hide password visibility toggle, system status badge (Asia/Manila PHT online), and security footer.
- Kept the color palette and typography strictly consistent with the design system.
- Zero emoji used (pure inline SVGs).

**Files touched:**
- `resources/views/auth/login.blade.php`
- `CHANGELOG.md`
93/93 tests pass.

---

## 2026-09-23 — System-Wide UI Modernization (Dashboard, Cash Drawers, Customers, Layout)

**What:**
1. **Global Design System Upgrades (`layouts/app.blade.php`):**
   - Added `.stat-grid` and `.stat-card` modern metric components with hover elevation.
   - Added `.badge-balanced`, `.badge-shortage`, `.badge-overage` pill indicators for shift accountability.
   - Standardized table styles and card header action bars.
2. **Dashboard UI Modernization (`dashboard.blade.php`):**
   - Replaced clunky raw inline styles with modern `.stat-card` metrics (Today's Sales, Revenue, Avg Transaction, Total Sales, Refunds).
   - Cleaned up Weekly Trend daily performance bars and Payment Method breakdown cards.
   - Modernized Recent Completed Transactions table with proper typography, badges, and timestamps.
3. **Cash Drawer Sessions UI (`cash-drawers/index.blade.php`):**
   - Modernized shift closure history with status badges (`Balanced`, `Overage`, `Shortage`), formatted timestamps, and tabular figures.
4. **Customers & Loyalty UI (`customers/index.blade.php`):**
   - Modernized customer directory table with highlighted loyalty points badges, code tags for customer IDs, and clean action links.

**Files touched:**
- `resources/views/layouts/app.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/cash-drawers/index.blade.php`
- `resources/views/customers/index.blade.php`
- `CHANGELOG.md`
93/93 tests pass.

---

## 2026-09-23 — POS UI Redesign, Cash Drawer Modals, CheckoutService Extraction, Navigation Update & Payment Validations

**What:**
1. **POS UI Redesign & Clean Modals:**
   - Replaced browser `prompt()` with clean, modern in-app modals for opening (float presets ₱1k-₱10k) and closing cash drawer sessions (live difference/shortage/overage calculation).
   - Rebuilt POS screen strictly matching the two-column ASCII mockup: Left = Customer & Live Receipt items with steppers; Right = Product Search & live results; Middle = Subtotal, Discount & Huge Grand Total; Bottom = Payment method toggle & Cash Received/Change/Complete Sale.
   - Zero inline `style=""` on form controls, no emojis (clean inline SVGs used).
2. **Card & E-Wallet Validations & Payment Reference Tracking:**
   - Added migration `2026_09_23_012925_add_payment_reference_to_payment_table.php` adding `reference_number` and `payment_provider` to `payment` table.
   - Enforced client-side and backend validations:
     - Card payments require Terminal Auth / Approval Code and card network before completion (no unverified card sales).
     - E-Wallet payments require Reference / Transaction Number (e.g. GCash/Maya reference ID) before completion.
     - Cash payments require amount received >= total due.
   - Printed payment reference on the customer receipt when available.
   - Added feature test in `tests/Feature/CheckoutFlowTest.php`.
3. **Extracted `CheckoutService`:**
   - Moved the entire atomic checkout transaction logic out of `PosController::store()` into `app/Services/CheckoutService.php` (thin controller pattern).
   - Handles product stock locking (`lockForUpdate`), promotion engine evaluation, manual discounts, coupon validation, inventory adjustments, payment record, receipt, customer loyalty updates, audit logging, and cash drawer updates inside one atomic `DB::transaction`.
   - Added unit test `tests/Unit/CheckoutServiceTest.php`.
4. **Primary Navigation Refocusing (`roles.primary_navigation`):**
   - Refocused primary navigation strictly around POS operations (POS, Customers, Cash Management, Promotions, Coupons, Discounts, Reports, Dashboard, Employees, Audit Logs).
   - Hidden back-office inventory modules (`Products`, `Inventory`, `Purchase Orders`, `Suppliers`, `Categories`) from the primary sidebar without deleting their routes, models, or controllers, strictly per the professor's requirements.
5. **Customer Loyalty Card Auto-Lookup:**
   - Supported typing or scanning Customer ID or contact number in POS, automatically resolving active customer details and live loyalty points badge.
6. **App CSRF Meta Tag:**
   - Added `<meta name="csrf-token">` to `layouts/app.blade.php` to support modern async fetch requests securely.

**Files touched:**
- `app/Services/CheckoutService.php` (NEW)
- `tests/Unit/CheckoutServiceTest.php` (NEW)
- `database/migrations/2026_09_23_012925_add_payment_reference_to_payment_table.php` (NEW)
- `app/Models/Payment.php`
- `app/Http/Controllers/PosController.php`
- `app/Providers/AppServiceProvider.php`
- `config/roles.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/pos/index.blade.php`
- `resources/views/pos/show.blade.php`
- `tests/Feature/CheckoutFlowTest.php`
- `CHANGELOG.md`
93/93 tests pass.

---

## 2026-09-09 — Fix: sale audit entry now written inside the checkout transaction

**What:** `PosController::store()` previously called `AuditLogger::record()` AFTER `DB::transaction` committed — a failure between commit and the audit write would leave a completed sale with no audit trail. The call now sits inside the transaction, matching the `RefundService` pattern; sale + payment + receipt + inventory + audit log now commit or roll back as one unit.

**Files touched:** `app/Http/Controllers/PosController.php`, `CHANGELOG.md`. 44/44 tests pass.

---

## 2026-09-09 — Fix: app timezone UTC → Asia/Manila (+ one-time data shift)

**What:** POS stored/displayed all timestamps in UTC while the store operates in PHT (UTC+8) — new checkouts showed as ~5 AM instead of ~1 PM, and date-window stats (Today's Sales, weekly trend) cut off at the wrong wall-clock boundary.

**Root cause:** `config/app.php` shipped with Laravel's default `'timezone' => 'UTC'`. No bug in transaction flow — sale #233 was committed and queryable all along, just stamped 05:19 instead of 13:19.

**Changes:**
- `config/app.php` — `timezone` changed to `Asia/Manila` (EXPLICIT USER TASK exception to the config/ lock; flagged per AGENTS.md).
- One-time data shift — all app-written datetime columns (`sale_transaction.transaction_date/refunded_at`, `payment.payment_date`, `receipt.issued_date`, `sale_refund.refunded_at`, `audit_log.action_timestamp`, `stock_movement.created_at`, `inventory.last_restocked/updated_at`, `reorder_signal.created_at/resolved_at`, `customer.created_at/updated_at`) shifted +8h via guarded script; marker row `TZ_SHIFT_UTC_TO_PHT_V1` in audit_log prevents double-shifting. Idempotent.

**Verification:** now() returns PHT; dashboard Recent Transactions shows `Sep 9, 1:19 PM` for sale #233; 44/44 tests pass.

**Files touched:** `config/app.php`, CHANGELOG.md (data shift via script, no code files).

---

## 2026-09-09 — Fix: future-dated demo sales burying Recent Transactions

**What:** Fixed RealisticDataSeeder future-dating "today's" demo sales, and clamped the 3 bad rows already in the DB.

**Root cause:** Seeder created today's demo sales as `now()->subDays(0)->addHours(rand(8,20))` — landing 8-20 hours in the FUTURE (e.g. 23:39 when it was 05:00). Dashboard "Recent Transactions" sorts by `transaction_date DESC`, so these rows floated to the top forever and real new checkouts (#230-#232) appeared BELOW them, looking like the dashboard "wasn't updating".

**Changes:**
- `RealisticDataSeeder.php` — today's rows (`$day === 0`) now use `now()->subMinutes(rand(1,720))` (always in the past); other days unchanged.
- Data fix — clamped 3 future-dated sales (#225, #226, #227) back into the recent past via tinker script.

**Files touched:** `database/seeders/RealisticDataSeeder.php`, CHANGELOG.md. 44/44 tests pass.

---

## 2026-09-09 — Barcode validation + seeder fix

**What:** Added EAN-13 checksum validation to the barcode field and fixed the product seeder to generate valid barcodes.

**Changes:**
- `ProductController::validated()` — barcode field now validates EAN-13 checksum when a 13-digit numeric code is manually entered. Invalid codes are rejected with a clear message directing the user to the Generate button.
- `ProductSeeder` — removed hardcoded invalid barcodes (`BR-0001`, `SN-0001`, etc.) and now uses `Product::generateBarcode()` to produce valid EAN-13 codes for all demo products.

**Why:** Previously, manually entering a 13-digit number with an invalid check digit would pass server-side validation but fail when rendered as a barcode on labels (JsBarcode shows "Invalid EAN-13"). The seeder also created products with non-EAN-13 barcodes that couldn't be rendered.

**Files touched:** `app/Http/Controllers/ProductController.php`, `database/seeders/ProductSeeder.php`, `CHANGELOG.md`.

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

1. **Dashboard revenue counted refunded sales** (`DashboardController`) — All revenue queries (`today_revenue`, `total_revenue`, `avg_transaction`, `weekly_trend`, `daily_baseline`, `payment_methods`) and `total_sales` now filter `where('status', 'completed')`. Verified: 10 status-filtered query paths.

2. **Top products/categories included refunded details** (`DashboardController`) — `top_products` and `top_categories` now join `sale_transaction` and filter `where('sale_transaction.status', 'completed')` so refunded sales don't inflate "top selling" rankings.

3. **Refund receipt still showed "Paid"** (`pos/show.blade.php`) — Added a prominent red "Refunded — Not Valid for Payment" banner at the top of the receipt when `$sale->isRefunded()`. The payment/change section still shows original amounts (for audit), but the banner makes it unmistakable.

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

## 2026-09-09 — Phase A: Inventory Foundation (stock_movement, reorder_signal, decimal quantities, InventoryService / ReorderSignalService)

**What:** Established the auditable inventory foundation per brief. Every stock mutation now flows through `InventoryService::adjustStock()` (signed delta + `StockMovement` row, `lockForUpdate` + `DB::transaction` TOCTOU guard) and low stock is surfaced through `ReorderSignalService`.

- `database/migrations/2026_09_09_000001_create_stock_movement_table.php` — `stock_movement` (movement_id PK, product_id, movement_type enum[sale,purchase,refund,adjustment,return,damage], quantity/stock_before/stock_after decimal(10,3), reference_type/reference_id, reason, employee_id, created_at). Indexes on product_id + employee_id.
- `database/migrations/2026_09_09_000002_create_reorder_signal_table.php` — `reorder_signal` (signal_id PK, product_id, signal_type enum[low_stock,critical,out_of_stock,reorder_suggested], current_stock/reorder_level decimal(10,3), suggested_quantity nullable decimal(10,3), supplier_id nullable, status enum[open,requested,po_created,dismissed] default open, created_at, resolved_at nullable). Indexes on product_id + supplier_id.
- `database/migrations/2026_09_09_000003_add_inventory_columns_to_product_table.php` — product gains `unit_of_measure` enum (default piece, `->after('reorder_level')`), `critical_reorder_level` decimal(10,3) default 0 (`->after`), `is_active` boolean default true (`->after`).
- `database/migrations/2026_09_09_000004_change_inventory_stock_quantity_to_decimal.php` — `inventory.stock_quantity` -> decimal(10,3) default 0 (`->change()`).
- `database/migrations/2026_09_09_000005_change_sale_details_quantity_to_decimal.php` — `sale_details.quantity` -> decimal(10,3) (`->change()`).
- `database/migrations/2026_09_09_000006_change_purchase_order_details_quantity_to_decimal.php` — `purchase_order_details.quantity` -> decimal(10,3) (`->change()`).
- `app/Models/StockMovement.php` — `stock_movement` model; movement_id PK; quantity/stock_before/stock_after `decimal:3` casts; `product()` + `employee()` relations.
- `app/Models/ReorderSignal.php` — `reorder_signal` model; signal_id PK; current_stock/reorder_level/suggested_quantity `decimal:3` casts; `product()` + `supplier()` relations.
- `app/Models/Product.php` — `unit_of_measure`, `critical_reorder_level`, `is_active` added to `$fillable`; casts `critical_reorder_level => 'decimal:3'`, `is_active => 'boolean'`; `reorderSignals()` HasMany.
- `app/Models/Inventory.php` — `stockMovements()` HasMany.
- `app/Services/InventoryService.php` — `adjustStock(productId, quantity, type, referenceType, referenceId, reason?)` (signed delta, lockForUpdate + transaction, `StockMovement::create` with `employee_id => auth()->id()`), `getCurrentStock()`, `getStockHistory()`, `checkReorderNeeded()` (out_of_stock | critical | low_stock | null).
- `app/Services/ReorderSignalService.php` — `scanForLowStock()` (opens non-duplicate open signals), `createSignal(productId, type)` (snapshots current_stock/reorder_level, suggested_quantity = reorder - stock), `resolveSignal(signalId)` (status dismissed + resolved_at), `getSignals(status)`.
- `app/Http/Controllers/PosController.php::store` — sale routes stock deduction through `InventoryService::adjustStock(..., 'sale', 'sale_transaction', ...)`.
- `app/Services/RefundService.php::refund` — refunded quantity restored via `InventoryService::adjustStock(..., 'refund', 'sale_refund', $refund->refund_id, $reason)`.
- `app/Http/Controllers/PurchaseOrderController.php::receive` — PO receipt routes through `InventoryService::adjustStock(..., 'purchase', 'purchase_order', ...)`; `last_restocked` still updated.
- `tests/Feature/InventoryFoundationTest.php` — covers `adjustStock` (add / deduct / missing-row), `getCurrentStock`, `getStockHistory`, `checkReorderNeeded` level thresholds, `ReorderSignalService` scan/create/resolve/getSignals, and `StockMovement` relations.

**How verified:** `php -l` clean on every new/changed file; full suite green via `vendor/bin/phpunit -c phpunit.mysql.xml` -> 44 passed (19 existing feature tests + new foundation tests). Existing migrations were not modified (no indexes removed; only new columns/indexes added).

**Note:** The suite runs on MySQL/MariaDB (`pos_system_test`). `->after()` and `->change()` require a real MySQL connection, so the SQLite `:memory:` config cannot execute these migrations.

---

## 2026-09-25 — Fix: POS blowout bug, seeder SM label cleanup, cashier role boundaries, and reports filter UX

**What:**
1. **POS Terminal UI Blowout Fix:** Resolved the horizontal grid expansion bug caused by the addition of 15 supermarket categories in `GroceryStoreSeeder`.
2. **SM Label Removal:** Eliminated all "(SM)" / "SM Bonus" / "SMAC" / "SMDC" naming and sample branding from seeders, models, test cases, and database records. Renamed seeder class to `GroceryStoreSeeder` (`database/seeders/GroceryStoreSeeder.php`).
3. **Cashier Dashboard Restriction:** Enforced that Cashiers do not have dashboard access. Cashiers redirect directly to `/pos` on login and root access; `/dashboard` is role-gated to Manager only with 403 response for Cashier; Cashier sidebar navigation displays only POS and Customers.
4. **Reports Preset Auto-Apply Fix:** Prevented the Daily/Weekly/Monthly/Yearly quick preset buttons in Manager Reports from auto-submitting the form. Clicking a preset now fills the `from` and `to` inputs and visually marks the active button, requiring the user to explicitly click "Apply".

**Root cause:**
- POS UI: `.pos-work-grid` and `.pos-panel` in `resources/views/pos/index.blade.php` lacked `min-width: 0`. When 26 category chips rendered side-by-side inside `.pos-category-chips-bar`, CSS Grid expanded the column to `min-content` (3,544px). The search input's `autofocus` scrolled the viewport 2,800px horizontally, hiding the cart/receipt and pushing the fixed sidebar over the right-side search panel.
- Reports UI: Click listener on `.pos-quick-btn` in `resources/views/reports/index.blade.php` called `document.getElementById('report-filter-form').submit()` immediately on click, bypassing the "Apply" button.
- Cashier Permissions: `config/roles.php` and `routes/web.php` included Cashier in `dashboard` module and navigation, and `LoginController` defaulted all users to `route('dashboard')`.

**Changes:**
- `resources/views/pos/index.blade.php`: Added `min-width: 0` to `.pos-work-grid`, `.pos-panel`, `.pos-container`, and `min-width: 0; max-width: 100%` to `.pos-category-chips-bar`.
- `database/seeders/GroceryStoreSeeder.php`: Removed all "SM", "SM Bonus", "SMAC", and "SMDC" occurrences. Renamed class from `SMGroceryStoreSeeder` to `GroceryStoreSeeder`. Renamed category to `Value Essentials & Pantry Staples`, supplier to `Central Retail Distribution`, products to `Value ...`, loyalty customers, discounts, promotions, and coupons (`LOYALTY50`, `VALUE100`, `WEEKENDSALE`). Added 3 bakery items for the `Breakfast & Bakery` category.
- `config/roles.php`: Removed `Cashier` from `dashboard` in both `modules` and `primary_navigation`.
- `routes/web.php`: Role-gated `/dashboard` to `role:Manager`. Updated `/` redirect: Cashier goes to `pos.index`, Manager to `dashboard`.
- `app/Http/Controllers/Auth/LoginController.php`: Redirects Cashier to `pos.index` and Manager to `dashboard` after login.
- `resources/views/layouts/app.blade.php`: Brand logo links Cashier to `pos.index` and Manager to `dashboard`.
- `resources/views/reports/index.blade.php`: Removed immediate `.submit()` on preset button click; added `.active` styling and toggle to `.pos-quick-btn`.
- `tests/Feature/GroceryStoreSeederTest.php`: Updated assertions for renamed categories, suppliers, products, and coupons; renamed from `SMGroceryStoreSeederTest.php`.
- `tests/Feature/CrudAndPosTest.php`: Added test cases verifying Cashier 403 on `/dashboard`, redirect to `/pos`, Manager dashboard access, and Manager reports view.
- `database/seeders/RealisticDataSeeder.php`: Restocked all products above reorder threshold so catalog starts healthy after simulated transaction run.
- `note.txt`: Created comprehensive developer setup and troubleshooting guide for laptop deployment (with Windows PowerShell compatibility using `;`).

**Verification:**
- Ran `php artisan test`: 104 tests passed, 378 assertions (0 failures).
- Ran automated HTTP & DOM checks via Python:
  - Verified Cashier login redirects to `http://127.0.0.1:8000/pos`.
  - Verified Cashier visiting `/dashboard` returns HTTP 403.
  - Verified Cashier sidebar navigation has only `POS` and `Customers`.
  - Verified Manager reports page renders quick filter buttons with no `.submit()` call in script.
  - Verified `/pos` page `bodyScrollWidth` equals `bodyClientWidth` (no horizontal overflow or layout shift).
- Verified catalog stock in DB: 0 products below reorder threshold.
- Ran duplication audit: 0 duplicate barcodes, product names, category names, supplier names, promotions, coupons, or usernames.
- Cleared framework cache via `php artisan optimize:clear`.

**Files touched:**
- `app/Http/Controllers/Auth/LoginController.php`
- `config/roles.php`
- `database/seeders/RealisticDataSeeder.php`
- `database/seeders/GroceryStoreSeeder.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/pos/index.blade.php`
- `resources/views/reports/index.blade.php`
- `routes/web.php`
- `tests/Feature/CrudAndPosTest.php`
- `tests/Feature/GroceryStoreSeederTest.php`
- `note.txt`
- `CHANGELOG.md`

## Standing conventions

- Code style: keep existing Laravel conventions (singular table names, `<entity>_id` PKs, `public $timestamps = false` on most models).
- Document each task here the moment it is done, before moving on.
- When documenting, cite the exact file + method (e.g. `app/Http/Controllers/PosController.php::store`).

## 2026-10-04 — POS receipts and Senior/PWD discount policy

**What:** Added configurable thermal receipt settings, sequential per-register receipt numbers, checkout success/reprint flow, and receipt snapshots so historical receipts retain the store/tax/discount details from sale time. Added the simplified Senior Citizen/PWD policy: 20% of the VAT-exempt base across products, with required full name and ID proof; the special discount records are displayed and maintained at 20%. Kept promotion/coupon backend calculations intact and omitted them from the POS receipt presentation.

**Files touched:** `app/Http/Controllers/DiscountController.php`, `app/Http/Controllers/PosController.php`, `app/Http/Controllers/ReceiptSettingsController.php`, `app/Models/Discount.php`, `app/Models/Receipt.php`, `app/Models/SaleTransaction.php`, `app/Services/CheckoutService.php`, `app/Services/ReceiptNumberService.php`, `app/Services/ReceiptSettingsService.php`, `database/migrations/2026_10_04_000004_add_receipt_settings_and_sale_snapshots.php`, `database/seeders/RealisticDataSeeder.php`, `resources/views/dashboard.blade.php`, `resources/views/discounts/_form.blade.php`, `resources/views/discounts/index.blade.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/show.blade.php`, `resources/views/settings/receipt.blade.php`, `routes/web.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/EwalletVerificationTest.php`, `tests/Feature/ManagerAuthorizationTest.php`, `tests/Feature/PromotionEngineTest.php`, `tests/Feature/ReceiptAndSpecialDiscountTest.php`, `tests/Feature/VatSettingsTest.php`, `CHANGELOG.md`.

**Why:** Complete the approved register receipt and Senior/PWD steps while preserving existing backend behavior and keeping historical sale output auditable.

## 2026-10-04 — POS checkout idempotency

**What:** Added per-cart UUID checkout keys, persisted them across hold/resume and retry flows, and added unique nullable keys to completed sales and pending E-Wallet requests. Replayed checkout requests now return the existing receipt or pending payment without repeating stock, customer, cash-drawer, or receipt side effects. The POS now disables Complete during submission and refreshes BFCache-restored checkout pages.

**Files touched:** `app/Http/Controllers/PosController.php`, `app/Models/PendingEwalletVerification.php`, `app/Models/SaleTransaction.php`, `app/Services/CheckoutService.php`, `app/Services/EwalletVerificationService.php`, `database/migrations/2026_10_04_000005_add_checkout_idempotency_keys.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/pending-ewallet/show.blade.php`, `resources/views/pos/show.blade.php`, `tests/Feature/CheckoutIdempotencyTest.php`, `CHANGELOG.md`.

**Why:** Prevent browser Back, double-click, and network retries from completing the same cashier checkout more than once, including E-Wallet submission and verification.

## 2026-10-04 — POS register header and held-cart actions

**What:** Removed link underlines from the Pending E-Wallet header action on hover and focus, added subtle background/shadow feedback to the register header actions, and restored the visible danger styling on held-cart removal.

**Files touched:** `resources/views/pos/index.blade.php`, `tests/Feature/EwalletVerificationTest.php`, `CHANGELOG.md`.

**Why:** Keep register actions visually consistent and make the held-cart Remove control clearly visible to cashiers.

## 2026-10-04 — POS live transaction auto-scroll

**What:** Added smooth cart scrolling to the newest product row, and brief highlighting for product quantity increases. Existing-row updates only scroll when the cashier was already following the latest cart row; manual upward scrolling remains in place. The highlight respects reduced-motion preferences.

**Files touched:** `resources/views/pos/index.blade.php`, `tests/Feature/CheckoutIdempotencyTest.php`, `CHANGELOG.md`.

**Why:** Keep newly scanned and updated items easy to spot in long live transactions without interrupting cashiers reviewing earlier rows.

## 2026-10-04 — E-Wallet review and result screens

**What:** Reorganized E-Wallet review around the amount, provider, reference, customer, and cart summary. Added focused Verify and Reject actions with duplicate-submit protection, a rejection confirmation with a Back to POS action, and an E-Wallet completion action labeled Next sale with an Enter shortcut. Kept manager-only verification and rejection routes unchanged.

**Files touched:** `resources/views/pos/pending-ewallet/show.blade.php`, `resources/views/pos/show.blade.php`, `app/Http/Controllers/PosController.php`, `tests/Feature/EwalletVerificationTest.php`, `CHANGELOG.md`.

**Why:** Make merchant-app review and its success or rejection outcome clear for the cashier while preserving existing authorization and checkout behavior.

## 2026-10-04 — Card and E-Wallet checkout validation

**What:** Added server-side provider allowlists, distinct Card approval-code and E-Wallet reference limits, rejection of card-number-shaped values in the approval-code field, and four-digit validation for optional card last-four input. E-Wallet input now accepts the server-supported 100-character maximum.

**Files touched:** `app/Http/Controllers/PosController.php`, `resources/views/pos/index.blade.php`, `tests/Feature/CheckoutFlowTest.php`, `CHANGELOG.md`.

**Why:** Reject unsupported payment providers and prevent a full PAN or malformed last-four value from being accepted as Card checkout metadata while retaining the current schema and checkout flow.

## 2026-10-04 — Secure payment references and Card verification

**What:** Added reversible encryption and keyed reference fingerprints with a unique reservation registry; migrated existing E-Wallet and payment references out of plaintext columns and checkout JSON; added a manager-reviewed Card queue with stock reservation, expiration, idempotency, audit details, and duplicate approval-code checks; masked references in review lists, receipts, reports, and audit output; added manager-only audited reveal controls that re-mask after 10 seconds; rejected card-number-shaped and CVV-shaped Card codes.

**Files touched:** `database/migrations/2026_10_04_000006_secure_payment_references_and_create_pending_card_verifications.php`, `app/Services/PaymentReferenceService.php`, `app/Services/CardVerificationService.php`, `app/Services/EwalletVerificationService.php`, `app/Services/CheckoutService.php`, `app/Models/Payment.php`, `app/Models/PendingEwalletVerification.php`, `app/Models/PendingCardVerification.php`, `app/Http/Controllers/PosController.php`, `app/Http/Controllers/AuditLogController.php`, `routes/web.php`, `resources/views/pos/index.blade.php`, `resources/views/pos/show.blade.php`, `resources/views/pos/pending-ewallet/index.blade.php`, `resources/views/pos/pending-ewallet/show.blade.php`, `resources/views/pos/pending-card/index.blade.php`, `resources/views/pos/pending-card/show.blade.php`, `resources/views/components/masked-value.blade.php`, `tests/Feature/PaymentReferenceMigrationTest.php`, `tests/Feature/CardVerificationTest.php`, `tests/Feature/EwalletVerificationTest.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/CheckoutIdempotencyTest.php`, `CHANGELOG.md`.

**Why:** Keep Card and E-Wallet review behavior consistent while protecting payment references at rest and in cashier-facing pages, logs, and exports without storing full PAN or CVV values.

## 2026-10-04 — POS transient reprint notice and manager authorization recovery

**What:** Applied the approved manager authorization audit-fields migration to the active preview SQLite database. Made the empty F10 reprint notice dismiss itself after three seconds, refined flash alerts to use a light outline, and styled the manager modal Cancel action with the shared secondary button treatment.

**Files touched:** `app/Http/Controllers/PosController.php`, `resources/views/layouts/app.blade.php`, `resources/views/components/manager-pin-modal.blade.php`, `CHANGELOG.md`.

**Why:** Keep the no-receipt message temporary, make the Cancel action visually consistent, and allow manager authorization auditing to work against the preview database schema.

## 2026-10-04 — Checkout schema recovery for preview POS

**What:** Applied the existing receipt/sale snapshot and checkout idempotency migrations to the active preview SQLite database so it matches the current checkout code. No migration files or existing records were edited.

**Files touched:** `CHANGELOG.md`.

**Why:** Complete Sale was returning HTTP 500 because the preview database lacked the `senior_pwd_type` and checkout idempotency columns expected by the application.

## 2026-10-04 — POS header action and shift status colors

**What:** Changed the POS register header action buttons to slate-teal with bold white labels and gave the ACTIVE status a padded strong-green pill with bold white text. NO SHIFT retains a muted status style.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Make key register controls and the active shift state easier to distinguish at a glance.

## 2026-10-04 — Customer directory action and status styling

**What:** Matched the customer directory Edit action to the slate-teal button style with bold white text, styled active customer status as a padded strong-green badge with bold white text, and made Delete visibly destructive.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Carry the clearer POS action and active-status color treatment into the customer directory.

## 2026-10-04 — Customer points and inactive status badges

**What:** Styled loyalty points with a slate-grav background and bold white text; changed inactive customer status to a padded solid-red badge with bold white text.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Improve contrast and make loyalty points and inactive accounts immediately recognizable.

## 2026-10-04 — Shared status and Edit action styling

**What:** Updated shared active badges to use a padded strong-green fill with bold white text and inactive badges to use a padded solid-red fill with bold white text. Styled management-page Edit links as slate-grav buttons with bold white labels and clear hover/focus states.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Apply the requested status and Edit-button treatment consistently across modules that use the shared UI classes.

## 2026-10-04 — POS receipt auto-print and store location presentation

**What:** Restored one-time automatic browser printing on newly completed receipt screens, labeled the configured store address as Location on receipts, and changed product Add controls to padded solid-green buttons with a clear focus state.

**Files touched:** `resources/views/pos/show.blade.php`, `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Restore the cashier's print workflow, make receipt location details easy to identify, and make product-add actions more visible.

## 2026-10-04 — POS product and stock badge emphasis

**What:** Made POS catalog product names bold and styled normal stock-count badges with a padded slate-grav fill and bold white text. Low-stock yellow and out-of-stock red states remain distinct.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Improve product-name readability and make stock counts easier to scan while preserving stock warnings.

## 2026-10-04 — POS product name readability

**What:** Slightly increased product-name text size in both standard and narrow catalog layouts while retaining bold weight and the existing line clamp.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Make product names easier for cashiers to spot.

## 2026-10-04 — POS reorder-level stock indicator

**What:** The POS stock badge now uses a solid-red background with white text when stock reaches the product reorder or critical-reorder level. Stock below the existing ten-unit watch threshold stays yellow until it reaches the configured reorder level; zero stock remains red.

**Files touched:** `app/Services/SaleService.php`, `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Surface configured reorder status clearly to the cashier without changing stock or checkout rules.

## 2026-10-04 — Audit log period filters

**What:** Added Daily, Weekly, Monthly, Yearly, and All time filters to the Audit Logs page. Filtering is performed against the log timestamp, the selected period stays in pagination links, and the current range is shown above results.

**Files touched:** `app/Http/Controllers/AuditLogController.php`, `resources/views/audit-logs/index.blade.php`, `CHANGELOG.md`.

**Why:** Let staff narrow the audit trail to a useful time window without manually scanning unrelated entries.

## 2026-10-04 — Customer table content spacing

**What:** Added consistent padded content surfaces for customer IDs, customer names, and contact/email values in the customer directory.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Improve separation and readability of customer details within the table rows.

## 2026-10-04 — Slate customer detail chips

**What:** Changed padded Customer ID, name, and contact/email content surfaces to slate grav with white text.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Match the stronger customer detail treatment to the loyalty-points badge styling.

## 2026-10-04 — Customer table white rows and slate header

**What:** Set the customer table surface and body rows to white. Styled the complete table header with slate-grav background, padded cells, and bold white labels while preserving the right alignment of Loyalty Points.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Improve contrast and structure in the customer directory table.

## 2026-10-04 — Employee table white rows and slate header

**What:** Applied the customer directory table treatment to Employees: white table/body rows and a padded slate-grav header with bold white labels.

**Files touched:** `resources/views/employees/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep the management tables visually consistent and easier to scan.

## 2026-10-04 — Audit Logs and Reports table styling

**What:** Applied a shared white-row table style with padded slate-grav headers and bold white column labels to Audit Logs and Reports.

**Files touched:** `resources/views/layouts/app.blade.php`, `resources/views/audit-logs/index.blade.php`, `resources/views/reports/index.blade.php`, `CHANGELOG.md`.

**Why:** Match the management-table treatment already used in Customers and Employees.

## 2026-10-04 — Dashboard table styling

**What:** Applied the shared white-row table style with padded slate-grav headers and bold white text to the Dashboard transaction and low-stock tables.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Keep Dashboard tables consistent with Customers, Employees, Audit Logs, and Reports.

## 2026-10-04 — Dashboard payment method badge

**What:** Styled payment method values in the Dashboard transactions table as padded slate-grav badges with bold white text.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Make payment types easier to scan and align them with the shared slate-grav UI treatment.

## 2026-10-04 — Dashboard Reprint action styling

**What:** Styled the Dashboard Reprint action as a padded solid-blue button with bold white text and a darker hover/focus state.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Give receipt reprinting a clear, easy-to-find action treatment.

## 2026-10-04 — Discount table and Edit action styling

**What:** Applied the shared white-row and padded slate-grav header treatment to the Discounts table, and styled each Edit action as a padded solid-blue button with bold white text.

**Files touched:** `resources/views/discounts/index.blade.php`, `CHANGELOG.md`.

**Why:** Carry the recent report action and table styling improvements into Discounts.

## 2026-10-04 — Discount Edit button color correction

**What:** Changed the Discounts Edit button from blue to padded slate grav with bold white text and a darker slate hover/focus treatment.

**Files touched:** `resources/views/discounts/index.blade.php`, `CHANGELOG.md`.

**Why:** Match the requested Edit action color consistently across management modules.

## 2026-10-04 — Shared table heading depth

**What:** Added subtle inset and drop shadows to table headings across modules, with a contrasting shadow treatment for slate-grav headers.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Give table headings clearer visual depth while preserving each table's existing palette and layout.

## 2026-10-04 — Navigation hover color refinement

**What:** Replaced the peach-fuzz hover background on sidebar navigation items with a restrained slate-grav tint and matching border/shadow.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Keep navigation hover states aligned with the selected slate-grav module styling.

## 2026-10-04 — Non-POS hover tint cleanup

**What:** Changed the shared surface-hover color to a light slate tint for all pages outside POS, using a route-aware body class to preserve the existing POS hover palette.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Remove peach-fuzz hover styling across the management modules while keeping POS visuals unchanged.

## 2026-10-04 — POS product card Add button overflow

**What:** Prevented product-card content from overflowing its border, constrained price/action flex sizing, and tightened Add button spacing in narrow catalog cards.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep the Add button inside product cards, including narrow POS catalog layouts.

## 2026-10-04 — POS product button clipping correction

**What:** Removed the product-card overflow clipping that was cutting off the Add button edge/shadow; retained the narrower button sizing rules.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep the Add button fully visible inside the product-card border.

## 2026-10-04 — POS product card button breathing room

**What:** Increased product-card minimum height and bottom padding so the Add button has a visible inset from the card border.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep the Add button from appearing clipped or pressed against the lower card edge.

## 2026-10-04 — Dashboard payment badge depth

**What:** Added a subtle inset highlight and drop shadow to the padded slate-grav Payment Method badges in the Dashboard transaction table.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Give the payment labels clearer visual depth while preserving their white text and slate treatment.

## 2026-10-04 — Dashboard payment method summary depth

**What:** Restyled the Payment Methods summary rows as padded slate-grav panels with bold white method/total text, muted-white transaction counts, and subtle inset/drop shadows.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Extend the slate-grav treatment and depth to the payment summary element on the Dashboard.

## 2026-10-04 — Dashboard payment summary soft depth

**What:** Refined the Payment Methods summary cards with a softer translucent edge, rounder corners, layered inset/drop shadows, and a restrained hover lift.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Add more polished depth and softer edges to the slate-grav payment summaries.

## 2026-10-04 — Green Dashboard payment summaries

**What:** Changed Dashboard Payment Methods summary cards to a solid green fill with white text, keeping their soft edge, layered depth, and darker green hover state.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Apply the requested stronger green accent to the payment summaries.

## 2026-10-04 — Softer Dashboard payment card depth

**What:** Reduced the Payment Methods cards to a light inset highlight and compact shadow, and removed the hover lift while keeping the green hover state.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Keep the cards dimensional without making their shadows feel heavy.

## 2026-10-04 — Green navigation hover depth

**What:** Changed non-selected module navigation hover states to solid green with white text, a subtle lift, and a green-tinted shadow. Selected modules retain their slate-grav styling and existing depth.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Make navigation feedback clearer and add consistent depth to module links.

## 2026-10-04 — Reverted green navigation hover

**What:** Restored the sidebar navigation hover to the prior light slate-grav tint with its subtle shadow; selected modules remain slate grav.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Revert the most recent green navigation hover change as requested.

## 2026-10-04 — Soft green navigation fill hover

**What:** Replaced the slate hover tint with a soft green fill animation that grows left-to-right, with a restrained shadow and reduced-motion support. The selected module remains slate grav.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Add a gentler green interaction state with clearer motion feedback.

## 2026-10-04 — Solid soft-green navigation hover

**What:** Replaced the left-to-right navigation fill animation with a solid soft-green hover background and a short color transition; the selected module remains slate grav.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Match the requested solid soft-green navigation hover.

## 2026-10-04 — Customer ID and name spacing

**What:** Removed the inner padding from Customer ID and Customer Name content chips while leaving Contact/Email spacing unchanged.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Reduce extra space around those two customer fields as requested.

## 2026-10-04 — Plain bold customer ID and name

**What:** Removed the slate chip backgrounds from Customer ID and Customer Name and rendered both as plain bold text.

**Files touched:** `resources/views/customers/index.blade.php`, `CHANGELOG.md`.

**Why:** Match the requested simpler customer table content style.

## 2026-10-04 — POS product action row alignment

**What:** Anchored each product card's price and Add button row to the card bottom so wrapped product names do not shift the button vertically.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep Add buttons aligned across cards in the same product row.

## 2026-10-04 — Remove Dashboard payment summary depth

**What:** Removed the shadows from Dashboard Payment Methods rows while retaining the solid green fill, soft border, and hover color.

**Files touched:** `resources/views/dashboard.blade.php`, `CHANGELOG.md`.

**Why:** Simplify the payment summary appearance as requested.

## 2026-10-04 — Cash Drawer table styling

**What:** Applied the shared white-row table treatment with padded slate-grav header, bold white labels, and header depth to Cash Drawer Sessions.

**Files touched:** `resources/views/cash-drawers/index.blade.php`, `CHANGELOG.md`.

**Why:** Match Cash Drawer with the other management tables.

## 2026-10-04 — Cash Drawer accountability status badges

**What:** Added padded, solid-color accountability badges with white bold text: green for Balanced, amber for Overage, and red for Shortage.

**Files touched:** `resources/views/cash-drawers/index.blade.php`, `CHANGELOG.md`.

**Why:** Make reconciliation status easy to scan while keeping discrepancies visibly distinct from balanced shifts.

## 2026-10-04 — Consistent module table row depth

**What:** Added a restrained shadow to shared table body rows across modules. Kept the POS cart and thermal receipt item rows flat for readability.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Extend consistent depth from table headings to module table content without crowding the POS ticket or print receipt.

## 2026-10-04 — Checkout database preflight

**What:** Added a read-only `pos:database-preflight` command that reports pending migrations and missing checkout schema; POS checkout and final sale creation now stop with a clear message when the deployed database is behind the application. The command reminds operators to back up the live database before applying migrations and does not run migrations or create a backup.

**Files touched:** `app/Console/Commands/PosDatabasePreflight.php`, `app/Services/DatabasePreflightService.php`, `app/Services/CheckoutService.php`, `app/Http/Controllers/PosController.php`, `CHANGELOG.md`.

**Why:** Prevent a schema mismatch from becoming a failed or partially processed sale, and provide a safe pre-deployment check.

## 2026-10-04 — POS customer search, checkout checks, and shared UI components

**What:** Moved register customer lookup to a bounded server-side search by customer ID, name, contact, or email; stopped embedding the full customer directory in the POS page; and retained held-cart customer restoration through the lookup endpoint. Added focused search, exact-cash, held-cart/close-shift, and cash-drawer reconciliation checks. Added reusable button, badge, and table Blade components and applied them to management tables, dashboard actions/payment badges, customer/employee controls, and cash-drawer accountability states.

**Files touched:** `app/Services/SaleService.php`, `app/Http/Controllers/PosController.php`, `routes/web.php`, `resources/views/pos/index.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/components/ui/button.blade.php`, `resources/views/components/ui/badge.blade.php`, `resources/views/components/ui/table.blade.php`, `resources/views/audit-logs/index.blade.php`, `resources/views/cash-drawers/index.blade.php`, `resources/views/categories/index.blade.php`, `resources/views/coupons/index.blade.php`, `resources/views/customers/index.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/discounts/index.blade.php`, `resources/views/employees/index.blade.php`, `resources/views/inventory/index.blade.php`, `resources/views/products/index.blade.php`, `resources/views/promotions/index.blade.php`, `resources/views/purchase-orders/create.blade.php`, `resources/views/purchase-orders/index.blade.php`, `resources/views/purchase-orders/show.blade.php`, `resources/views/reports/index.blade.php`, `resources/views/suppliers/index.blade.php`, `tests/Feature/PosCustomerSearchTest.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/CashDrawerFlowTest.php`, `CHANGELOG.md`.

**Why:** Keep large customer directories out of the register payload, validate cashier-critical checkout and shift paths, and make shared module controls consistent and maintainable.

## 2026-10-04 — Remove navigation link underline

**What:** Kept shared navigation links free of underlines in default, hover, focus, and active states.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Prevent navigation items from appearing underlined after being clicked.

## 2026-10-04 — Keep POS discount selector recoverable

**What:** Wrapped discount authorization in error-safe handling, restored the previous selection when authorization is canceled or fails, and always re-enabled the selector after the request settles.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Prevent a failed or interrupted manager authorization request from leaving the discount selector disabled.

## 2026-10-04 — Reset manager authorization controls after approval

**What:** Reset the PIN input, keypad, and Authorize button whenever the manager authorization modal closes; ignore overlapping authorization requests while one is pending.

**Files touched:** `resources/views/components/manager-pin-modal.blade.php`, `CHANGELOG.md`.

**Why:** Allow repeated discount changes and other protected actions after a successful authorization instead of leaving the next request blocked by a disabled submit button.

## 2026-10-04 — Refine POS discount dropdown styling

**What:** Improved the discount select surface, spacing, text hierarchy, chevron treatment, focus ring, disabled state, option colors, and selected-discount detail panel.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Make discount choices easier to scan and interact with while preserving the native select and its authorization flow.

## 2026-10-04 — Replace platform-colored discount popup

**What:** Added a branded, keyboard-operable discount listbox with code labels and policy details. Kept the native select synchronized as the existing authorization and checkout source of truth.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Avoid the operating-system blue selection popup and keep the dropdown visually consistent with the POS palette.

## 2026-10-04 — Prevent discount menu clipping

**What:** Positioned the discount listbox against the viewport, flipped it above the selector when needed, and recalculated placement during rail scrolling and window resizing.

**Files touched:** `resources/views/pos/index.blade.php`, `CHANGELOG.md`.

**Why:** Keep all available discount choices reachable when the customer rail has limited vertical space.

## 2026-10-04 — Restore base styling on colored shared buttons

**What:** Added the base `btn` class to slate and blue shared button variants so they receive the standard button layout, padding, and hover behavior.

**Files touched:** `resources/views/components/ui/button.blade.php`, `CHANGELOG.md`.

**Why:** Restore the intended appearance of Employee Edit and other slate/blue action buttons.

## 2026-10-04 — Fix link button style specificity

**What:** Added higher-specificity anchor rules for slate/blue variants and compact sizing so generic link defaults cannot override their colors, border, or padding.

**Files touched:** `resources/views/layouts/app.blade.php`, `CHANGELOG.md`.

**Why:** Ensure shared Edit links in Customer and Employee tables render in Slate Grav with the intended compact button shape.
