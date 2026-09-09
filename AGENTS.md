# pos_system — Agent Rules (READ BEFORE EDITING ANYTHING)

This Laravel 13 / PHP 8.3 POS is a **group project** (XAMPP, MariaDB :3306).
AI agents (pi, Hermes, Cursor, ...): follow every rule below. If a rule conflicts with the task, follow the rule and flag the conflict — do not silently "improve" things.

## Hard boundaries
1. **DO NOT run `git commit`, `git push`, `git checkout`, `git reset`, or any git write command.** Humans of the group control version control. Work happens in the working tree only.
2. **DO NOT touch the customer/supplier address schema** (columns, migrations, forms for address fields). The CRM group owns that domain — it will be integrated at end of semester.
3. **DO NOT delete or rewrite files outside your assigned task.** Only edit what the task names, plus `CHANGELOG.md`.
4. **DO NOT modify `composer.json`, `package.json`, `.env`, or config/ files** unless the task explicitly says so.
5. Every change MUST be appended to `CHANGELOG.md`: date, what changed, files touched, why.

## Design system (violations have been rejected 3+ times — treat as law)
- Neo-brutalist, font DM Sans. CSS variables live in `resources/views/layouts/app.blade.php`: bg `#FEDAB8`, ink/teal `#203C3D`, coral focus `#C4504A`, green hover `#2D8A4E`.
- **NEVER use inline `style="..."` on form controls.** Reuse/add a class in `layouts/app.blade.php`.
- Inputs are **boxed flat**: white fill, full 2px border, `border-radius: 6px`, coral focus glow, NO resting shadow, unified sizing (`padding: .85rem 1rem; font-size: 1rem`).
- Prominent text fields get `.input-lg bordered`. Groups: `.form-actions` (submit/cancel row), `.field-stack` (field + action button below), `.field-pair` (fixed 300px flex row).
- Inside forms, EVERY input is `<div><label>…</label><input></div>` inside `.form-grid`. No bespoke tables for user input. (Read-only data MAY use tables.)
- Notes/Address/Description = `textarea` with `.input-lg bordered` rows 2–3.
- **NO EMOJI** anywhere in UI text, code comments, or output.
- Any click-revealed panel MUST `scrollIntoView({behavior:'smooth'})` and focus its first input.
- Buttons: `.btn` (filled teal), `.btn-secondary`, `.btn-ghost`, `.btn-danger`. Tables: `border-collapse: separate`; never `width:1%` on an actions `<td>`.
- Barcodes are EAN-13: generate ONLY via `Product::generateBarcode()` (12 digits + valid checksum).

## Backend rules
- Auth uses the **`employee`** table + `config/roles.php` (`manager`/`cashier`). The **`users`** table is unused — never build on it. (The `admin` role has been merged into `manager`; see CHANGELOG 2026-09-08.)
- Money/stock mutations: `->lockForUpdate()` + re-check status guards **inside** `DB::transaction` (TOCTOU). Aggregate requested qty per product BEFORE comparing to stock (oversell guard).
- Dashboard revenue/top-product queries always filter `status = 'completed'` (refunded sales must not inflate stats).
- Never interpolate DB/user strings into `innerHTML` — use `createElement`/`textContent` (stored-XSS guard).
- Static routes BEFORE resource catch-alls (`products/generate-barcode` above `Route::resource('products')`).
- Controllers stay thin: business logic in `app/Services/*` (see RefundService, SaleService pattern).

## Environment pitfalls
- MariaDB may be stopped — `[2002] connection refused` means start mysqld, not "the code is broken".
- Shared DB between repo copies: check `Schema::hasTable()` before adding a migration for something that may already exist.
- Multiple stale `php artisan serve` on :8000 serve old CSS — kill all listeners, start ONE, verify served HTML before believing a UI bug.
- Verify with `php -l`, `php artisan route:list`, and tinker (render views via `View::make(...)->render()` + fake `ViewErrorBag` to grep markup — Manager/Admin routes 302 on curl).

## Integration horizon (end of semester)
POS will integrate with sibling systems: CRM, HRMS, procurement. Keep: API-friendly controllers (JSON responses where natural), role checks centralized in services, no hard-coded cross-domain data (addresses, employees beyond auth). When adding features, prefer extension points over edits to shared models.

## Task format for agents
A well-formed task names: the goal, the exact files/areas allowed, and the acceptance check (e.g. "run php artisan test", "page 127.0.0.1:8000/products shows column X"). If the task doesn't say which files, ask before editing broadly.
