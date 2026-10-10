# POS System — Agent Rules

Laravel 13 / PHP 8.3 / MariaDB :3306 (XAMPP). Follow all rules strictly; log every change in `CHANGELOG.md`.

## Boundaries
- **Git:** Working tree edits only. Never run `git commit`, `push`, `checkout`, `reset`, etc.
- **Scope:** Never edit customer/supplier address schema (CRM domain).
- **Files:** Touch only assigned files + `CHANGELOG.md`. Do not modify `composer.json`, `package.json`, `.env`, or `config/`.

## Architecture & Backend
- **Auth:** `employee` table (`manager` / `cashier`). `users` table is unused.
- **Services:** Keep controllers thin; place business logic in `app/Services/*`.
- **Transactions:** Wrap money/stock mutations in `DB::transaction()` with `->lockForUpdate()`. Aggregate qty per item before stock check.
- **Reporting:** Revenue and top-product queries must filter `status = 'completed'`.
- **Routing:** Define static routes before resource routes (`products/generate-barcode` above `Route::resource('products')`).
- **Barcodes:** EAN-13 generated via `Product::generateBarcode()`.
- **Security:** Sanitize user input; avoid raw `innerHTML` interpolation (XSS guard). Avoid inline `style="..."` on tested routes.

## Testing & Verification
- Verify changes with `php artisan test`.
