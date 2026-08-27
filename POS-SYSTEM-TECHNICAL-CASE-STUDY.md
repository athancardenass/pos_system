# POS System — Complete Technical Case Study & Learning Guide

> **How to use this document:** Every claim cites a real file in your repository
> (`C:/xampp/htdocs/pos_system`, which is the canonical copy of `github.com/athancardenass/pos_system`).
> Open the file in your editor and read along. The goal is not to memorize — it is to
> understand *how the pieces connect*, so you can explain, debug, and change the system yourself.
>
> Nothing in this repository was modified to produce this guide.

---

## Part I — System Overview

**What problem does it solve?**
A small retail business needs to record sales, track stock, manage products/customers/suppliers,
reorder goods, apply discounts, and watch activity — all from one web app. This POS system does exactly that.

**What type of application?**
A server-rendered **web application** (no separate frontend framework like React). The browser asks
Laravel for a page; Laravel runs PHP, talks to MySQL, and returns HTML. The only JavaScript is a small
cart script on the POS screen.

**Who uses it?** Three roles, seeded in `database/seeders/EmployeeSeeder.php`:
- **Admin** — everything, including employee accounts and audit logs.
- **Manager** — catalog, stock, purchasing, discounts, customers, sales.
- **Cashier** — ring up sales and manage customers.

**Main modules** (from `config/roles.php` → `modules`): dashboard, pos, customers, categories,
products, inventory, suppliers, purchase-orders, discounts, employees, audit-logs.

**Main workflows:** login → dashboard → (ring a sale | manage catalog | reorder stock | apply discount | review audit log).

**Technology stack:** see Part II. **Architecture:** see Part III and Part XIX.

---

## Part II — Technology Stack

| Layer | Technology | Evidence |
|-------|-----------|----------|
| Language | PHP **8.3** | `composer.json`: `"php": "^8.3"` |
| Framework | **Laravel 13.17** | `composer.json`: `"laravel/framework": "^13.17"` |
| Database | **MySQL** (via `mysql` driver) | `config/database.php` default connection; migrations use `Schema::create` |
| Templating | **Blade** | `resources/views/*.blade.php` |
| Styling | Plain **CSS** (inline + shared `app.blade.php`) | e.g. `resources/views/auth/login.blade.php` `<style>` block |
| Client JS | Vanilla JS + `JsBarcode.all.min.js` (barcode library) | `public/js/` |
| Auth | Laravel's built-in auth, but on the **Employee** model | `app/Models/Employee.php` uses `Authenticatable` |
| CLI | **Artisan** | `artisan` binary at repo root |
| Process | Web server (XAMPP on your machine) serving `public/` | `php artisan serve` for dev |

**Dependencies worth knowing** (`composer.json`):
- `laravel/framework` — the framework itself.
- `laravel/tinker` — interactive PHP shell (`php artisan tinker`) for poking at models.
- Dev: `phpunit/phpunit` (tests), `laravel/pint` (code style), `fakerphp/faker` (seed/factory fake data).
- `package.json` only has build tooling (`vite`), but the app ships compiled/inline CSS, so Vite is optional for running.

---

## Part III — Laravel Architecture

Laravel follows **MVC** (Model–View–Controller) and uses a **single entry point** plus a **service container**.

```
Browser (HTTP request)
        │
        ▼
public/index.php  ── the ONLY file the web server executes
        │  (bootstraps Laravel, builds the app)
        ▼
bootstrap/app.php  ── registers routes, middleware, exceptions
        │
        ▼
routes/web.php  ── matches URL → controller
        │
        ▼
Middleware (e.g. auth, role)  ── guards the request
        │
        ▼
Controller method  ── your business logic
        │
        ▼
Model / Eloquent  ── reads & writes MySQL
        │
        ▼
Controller returns a Blade view (or redirect)
        │
        ▼
Blade compiles to HTML
        │
        ▼
HTTP response → Browser renders the page
```

**Why this shape?** Each concern lives in one place: routes decide *which* code runs, controllers
decide *what* happens, models decide *how data is shaped*, views decide *how it looks*. That separation
is the whole reason the folder structure looks the way it does.

---

## Part IV — Repository Structure

```
pos_system/
├── app/                  ← Your PHP application code (the "M", "C", and helpers)
│   ├── Http/
│   │   ├── Controllers/  ← Controllers (one per feature area)
│   │   │   └── Auth/LoginController.php
│   │   └── Middleware/EnsureRole.php  ← role guard
│   ├── Models/           ← Eloquent models (one per table)
│   ├── Services/AuditLogger.php  ← reusable business logic
│   └── Providers/        ← bootstrapping hooks
├── bootstrap/app.php     ← application wiring (routes + middleware)
├── config/              ← configuration (roles.php, auth.php, database.php…)
├── database/
│   ├── migrations/       ← schema definitions (CREATE TABLE)
│   └── seeders/          ← initial data (roles, employees)
├── public/              ← web root (index.php, css, js) — ONLY this is web-exposed
├── resources/views/     ← Blade templates
├── routes/web.php        ← web route definitions
├── storage/             ← logs, compiled views, sessions (writable)
└── tests/               ← automated tests
```

**Key files and why they exist:**
- `public/index.php` — the front door. The web server points here; everything else is reached through it.
- `routes/web.php` — the "phone book" mapping URLs to controllers.
- `app/Models/Product.php` — the PHP representation of the `product` table.
- `app/Http/Controllers/ProductController.php` — logic for listing/creating/editing products.
- `resources/views/products/index.blade.php` — the HTML shown to the user.
- `database/migrations/2026_08_20_132950_create_product_table.php` — the SQL that built `product`.

---

## Part V — Database Architecture

The DB has **16 tables** (created by the 16 `create_*_table` migrations; FKs added in a second
migration batch). Tables use **descriptive names** and **integer primary keys** (not Laravel's default
`id`). Most disable timestamps (`public $timestamps = false`) except `inventory` and `audit_log`.

### Core tables and relationships

| Table | PK | Key columns | Relationships |
|-------|----|-------------|---------------|
| `role` | `role_id` | `role_name` | hasMany `employee` |
| `employee` | `employee_id` | `role_id`, `username`, `password`(hashed), `status` | belongsTo `role`; hasMany `sale_transaction`, `purchase_order`; hasMany `audit_log` |
| `category` | `category_id` | `category_name` | hasMany `product` |
| `supplier` | `supplier_id` | `supplier_name` | hasMany `product`, hasMany `purchase_order` |
| `product` | `product_id` | `category_id`, `supplier_id`, `barcode`(unique), `unit_price`, `cost_price`, `reorder_level` | belongsTo `category`, `supplier`; hasOne `inventory`; hasMany `sale_details`, `purchase_order_details` |
| `inventory` | `inventory_id` | `product_id`, `stock_quantity`, `last_restocked` | belongsTo `product` |
| `customer` | `customer_id` | name/contact/email, `loyalty_points`, `total_purchases`, `customer_status` | hasMany `sale_transaction` |
| `discount` | `discount_id` | `discount_type`(percentage\|fixed), `discount_value`, `start_date`, `end_date` | hasMany `sale_transaction` |
| `sale_transaction` | `transaction_id` | `customer_id`, `employee_id`, `discount_id`, `transaction_date`, `subtotal`, `total_amount`, `payment_method` | belongsTo `customer`, `employee`, `discount`; hasMany `sale_details`; hasOne `payment`, `receipt` |
| `sale_details` | `sale_detail_id` | `transaction_id`, `product_id`, `quantity`, `unit_price`, `subtotal` | belongsTo `sale_transaction`, `product` |
| `payment` | `payment_id` | `transaction_id`, `amount_paid`, `change_amount`, `payment_date` | belongsTo `sale_transaction` |
| `receipt` | `receipt_id` | `transaction_id`, `receipt_number`, `issued_date` | belongsTo `sale_transaction` |
| `purchase_order` | `purchase_id` | `supplier_id`, `employee_id`, `order_date`, `status`(pending/received/cancelled), `total_amount` | belongsTo `supplier`, `employee`; hasMany `purchase_order_details` |
| `purchase_order_details` | `po_detail_id` | `purchase_id`, `product_id`, `quantity`, `unit_cost` | belongsTo `purchase_order`, `product` |
| `audit_log` | `log_id` | `employee_id`, `action`, `table_affected`, `record_id`, `action_timestamp`, `description` | belongsTo `employee` |
| `users` | `id` | (Laravel default auth table) | **REFERENCED BUT UNUSED** — auth uses `employee`, not `users` (see Part XI) |

### Relationship types (real examples)
- **One-to-Many:** `Product` → many `SaleDetail` (`app/Models/Product.php::saleDetails()`).
- **One-to-One:** `SaleTransaction` → one `Payment` (`app/Models/SaleTransaction.php::payment()`).
- **Many-to-One (inverse):** `SaleDetail` belongsTo one `Product`.
- **Implicit Many-to-Many:** a sale touches many products *through* `sale_details`; a product is in many
  sales *through* `sale_details`. There is no pivot model name — it is two `hasMany` relations joined by
  the `sale_details` table.

### How a migration becomes a table
`database/migrations/2026_08_20_132950_create_product_table.php` calls `Schema::create('product', fn($t) => …)`
which Laravel translates to `CREATE TABLE product (…)`. The second batch
(`…_add_foreign_keys_to_product_table.php`) issues `ALTER TABLE` to add FK constraints. Running
`php artisan migrate` executes these in timestamp order.

---

## Part VI — Routes

All web routes live in `routes/web.php`. Verified map:

| Method | URL | Name | Controller@method | Middleware |
|--------|-----|------|-------------------|-----------|
| GET | `/` | — | closure | none (redirects to login/dashboard) |
| GET | `/login` | `login` | `LoginController@showLoginForm` | `guest` |
| POST | `/login` | `login` | `LoginController@login` | `guest` |
| POST | `/logout` | `logout` | `LoginController@logout` | `auth` |
| GET | `/dashboard` | `dashboard` | `DashboardController` (invokable) | `auth` |
| GET/POST | `/pos` | `pos.index` / `pos.store` | `PosController@index` / `@store` | `auth` + `role:Cashier,Manager,Admin` |
| GET | `/pos/{saleTransaction}` | `pos.show` | `PosController@show` | `auth` + `role:Cashier,Manager,Admin` |
| resource | `/customers` | `customers.*` | `CustomerController` | `auth` + `role:Cashier,Manager,Admin` |
| resource | `/categories` | `categories.*` | `CategoryController` | `auth` + `role:Manager,Admin` |
| resource | `/products` | `products.*` | `ProductController` | `auth` + `role:Manager,Admin` |
| GET/POST/GET-PUT | `/inventory`, `/inventory/sync`, `/inventory/{inventory}/edit`, `/inventory/{inventory}` | `inventory.*` | `InventoryController` | `auth` + `role:Manager,Admin` |
| resource | `/suppliers` | `suppliers.*` | `SupplierController` | `auth` + `role:Manager,Admin` |
| GET/POST/GET-POST | `/purchase-orders` (+create/show/receive/cancel) | `purchase-orders.*` | `PurchaseOrderController` | `auth` + `role:Manager,Admin` |
| resource | `/discounts` | `discounts.*` | `DiscountController` | `auth` + `role:Manager,Admin` |
| resource | `/employees` | `employees.*` | `EmployeeController` | `auth` + `role:Admin` |
| GET | `/audit-logs` | `audit-logs.index` | `AuditLogController@index` | `auth` + `role:Admin` |

**Concepts used in THIS project:**
- **`Route::middleware('auth')`** — wraps a group so only logged-in users pass.
- **`Role` middleware** — `role:Cashier,Manager,Admin` is a *custom* alias (registered in
  `bootstrap/app.php`) pointing to `App\Http\Middleware\EnsureRole`. It reads the extra `...$roles`
  argument.
- **Resource routes** — `Route::resource('products', ProductController::class)->except('show')`
  auto-creates `index/create/store/edit/update/destroy` (the `except('show')` drops the single-show route).
- **Route parameters** — `{saleTransaction}` in the URL is filled by **route-model binding**: Laravel
  automatically queries `SaleTransaction` by its key and injects the model into the controller
  (see `PosController@show(SaleTransaction $saleTransaction)`).
- **Named routes** — `route('products.index')` generates the URL; used in views and redirects so you
  never hardcode URLs.

---

## Part VII — Controllers

A controller receives an HTTP request and returns a response (a view or a redirect). All extend
`app/Http/Controllers/Controller.php`. Below are the most important, explained in beginner terms.

### LoginController (`app/Http/Controllers/Auth/LoginController.php`)
- `showLoginForm()` → returns `view('auth.login')`.
- `login(Request $request)` → validates `username`+`password`, adds `status='active'`, calls
  `Auth::attempt($credentials)`. On success: `session()->regenerate()` (anti session-fixation) and
  redirect to dashboard. On fail: `back()` with an error. **This is where password checking + session start happen.**
- `logout()` → `Auth::logout()`, invalidate session, regenerate CSRF token, redirect to login.

### PosController (`app/Http/Controllers/PosController.php`) — the heart of the system
- `index()` → loads products (with inventory), active customers, active discounts; builds a
  `productsJson` array; returns `pos.index` view. The view's JS consumes `productsJson`.
- `store(Request $request)` → **the checkout**. Wrapped in `DB::transaction(...)`. Validates items,
  locks each product row (`lockForUpdate()`), checks stock, computes subtotal, applies discount,
  verifies `amount_paid >= total`, then creates `sale_transaction` + `sale_details` + decrements
  `inventory` + creates `payment` + creates `receipt`, and (if customer) bumps `total_purchases` and
  `loyalty_points` (`floor(total/100)`). Finally `AuditLogger::record('sale', …)`.
- `show(SaleTransaction $sale)` → loads relations and shows the receipt page.

**Beginner decode of `public function store(Request $request): RedirectResponse`:**
- `public` — callable from anywhere (no visibility restriction).
- `function store` — a method named `store`; by Laravel convention `store` = "create a new record."
- `Request $request` — Laravel injects the incoming HTTP request object (contains form fields,
  session, etc.). The `: RedirectResponse` is a **type declaration** promising it returns a redirect.
- Inside, `$request->validate([…])` runs rules and throws a 422 if they fail (see Part XIII).
- It returns `redirect()->route('pos.show', $sale)` — sends the browser to the receipt.

### ProductController (`app/Http/Controllers/ProductController.php`)
- `index()` → `Product::with(['category','supplier','inventory'])->paginate(15)` then `view('products.index', compact('products'))`.
- `create()`/`store()` → show form / validate + `Product::create($data)` + create an `inventory` row
  (stock 0) + audit log.
- `edit(Product $product)`/`update()` → route-model-binding loads the product; `update($this->validated(…))`.
- `destroy(Product $product)` → refuses if `isInUse()` (has sales/purchase history); else deletes + logs.
- `validated()` (private) centralizes the validation rules — a small **DRY** win.

### Other controllers (same CRUD pattern)
- `CustomerController` — identical shape; `fullName()` used for display; delete blocked if sales exist.
- `DiscountController` — `discount_type` must be `percentage|fixed`; delete blocked if used on a sale.
- `InventoryController` — `index` lists stock; `update` sets `stock_quantity` + `last_restocked`;
  `storeMissing()` creates inventory rows for products that don't have one (`whereDoesntHave('inventory')`).
- `PurchaseOrderController` — `store()` builds the order + details in a transaction; `receive()` adds
  stock back to inventory and marks `received`; `cancel()` marks `cancelled`. Both `receive`/`cancel`
  guard on `isPending()`.
- `CategoryController`, `SupplierController`, `EmployeeController`, `AuditLogController` — standard CRUD/list.
- `DashboardController` (invokable, `__invoke`) — gathers all stats; see Part VIII/XIV.

---

## Part VIII — Models and Eloquent

A **Model** is a PHP class that represents one database table and lets you work with rows as objects.
Example: `app/Models/Product.php` represents the `product` table.

**Why Laravel knows the table:** the class sets `protected $table = 'product';`. If you omit it, Laravel
guesses the lowercase plural (`products`) — but this project overrides it because tables are singular.
PK is overridden too: `protected $primaryKey = 'product_id';` (Laravel's default is `id`).

**`fillable`** is the whitelist of columns you may mass-assign via `Product::create([…])` or
`$product->update([…])`. Fields not listed are ignored (a security feature).

**`casts`** tells Laravel how to convert DB values to PHP types, e.g.
`protected function casts(): array { return ['unit_price' => 'decimal:2']; }` — `unit_price` comes back
as a correctly-formatted decimal, not a raw string.

### Eloquent in action (real example)
```php
Product::query()
    ->with(['category', 'supplier', 'inventory'])
    ->orderBy('product_name')
    ->paginate(15);
```
What Laravel does for you (conceptually):
```sql
SELECT * FROM product ORDER BY product_name LIMIT 15 OFFSET 0;
-- then, because of with(): SELECT * FROM category WHERE category_id IN (…); etc.
```
Each row becomes a `Product` object. Accessing `$product->category` (the `belongsTo` relation) lazily
returns the related `Category` object — but `with()` **eager-loads** it up front to avoid the N+1 query problem.

### Relationships (from the code)
- `Product::category()` → `belongsTo(Category::class, 'category_id', 'category_id')`.
- `Product::inventory()` → `hasOne(Inventory::class, 'product_id', 'product_id')`.
- `SaleTransaction::saleDetails()` → `hasMany(SaleDetail::class, 'transaction_id', 'transaction_id')`.
- `SaleTransaction::payment()` / `receipt()` → `hasOne(...)`.
- `Employee::role()` → `belongsTo(Role::class)`.

### Custom methods on models (business logic that belongs with the data)
- `Product::stockQuantity()` → `(int)($this->inventory?->stock_quantity ?? 0)` — safe even if no inventory row.
- `Product::isInUse()` → true if it has sale or purchase details (used to block deletion).
- `Discount::isActive($on)` → checks `start_date`/`end_date` against today.
- `Discount::applyTo($subtotal)` → returns discounted total (percentage or fixed).
- `Employee::hasRole(...)` / `allowedModules()` → authorization helpers (Part XI).
- `Customer::fullName()` → concatenates names.

---

## Part IX — Migrations and Seeders

**Migrations** (`database/migrations/`) are version-controlled schema changes. Two batches:
1. `2026_08_20_132950_create_*_table.php` — defines each table.
2. `2026_08_20_132953_add_foreign_keys_to_*_table.php` — adds FK constraints after all tables exist.

Run them with `php artisan migrate`. `migrate:fresh` drops everything and re-runs (destructive).

**Seeders** (`database/seeders/`):
- `DatabaseSeeder` calls `RoleSeeder` then `EmployeeSeeder`.
- `RoleSeeder` creates the three roles (Admin/Manager/Cashier) via `firstOrCreate`.
- `EmployeeSeeder` creates three demo accounts (`admin`, `manager`, `cashier`) with password `password`
  (hashed by the model's `casts` → `'password' => 'hashed'`). The login view advertises these credentials.

> **Note:** There is **no product/category/supplier seeder**. After a fresh migrate, you must add those
> manually (or via `InventoryController@storeMissing` to backfill inventory).

---

## Part X — Blade / Frontend

Blade is Laravel's templating engine. `resources/views/layouts/app.blade.php` is the shared shell;
pages use `@extends('layouts.app')` and fill `@section('content')`.

**How `$variables` reach the view:** the controller passes them. Example from `ProductController@index`:
```php
$products = Product::query()->paginate(15);
return view('products.index', compact('products'));
```
`compact('products')` creates `['products' => $products]`. Blade can then write `{{ $products->links() }}`
and loop `@foreach ($products as $product)`.

**CSRF protection:** every form has `@csrf` (e.g. `resources/views/auth/login.blade.php:19`). That emits a
hidden token field; Laravel's `VerifyCsrfToken` middleware rejects POSTs without a valid token. This is
why a hand-written HTML form without `@csrf` returns **419**.

**The POS cart (the only real JavaScript):** `resources/views/pos/index.blade.php` loads
`const products = @json($productsJson);` — Laravel serializes the PHP array to JS. The script:
- maintains a `cart` array,
- `addToCart()` checks `product.stock` before adding (client-side guard),
- `render()` rebuilds the table and writes hidden inputs `items[i][product_id]` / `items[i][quantity]`,
- on submit, the form POSTs to `route('pos.store')` with the cart as `items[]`.

So the JS builds the request body; Laravel (not JS) is the source of truth for stock, totals, and persistence.

---

## Part XI — Authentication and Authorization

**Authentication = "who are you?"** **Authorization = "what are you allowed to do?"**

### Authentication (login)
- The login form posts to `LoginController@login`.
- `Auth::attempt(['username'=>…,'password'=>…,'status'=>'active'])` checks the `employee` table.
  Laravel hashes the submitted password with BCRYPT and compares it to the stored hash
  (`app/Models/Employee.php` casts `'password' => 'hashed'`).
- On success, Laravel stores the logged-in employee's ID in the **session** and `auth()->user()` returns that `Employee`.
- `config/auth.php` sets the provider to the `employee` model + `employees` table (so auth uses `employee`, NOT `users`).

### Authorization (roles)
Two layers, both verified in the repo:
1. **Route middleware** — `EnsureRole` (`app/Http/Middleware/EnsureRole.php`) reads
   `$request->user()` and calls `$employee->hasRole(...$roles)`; on failure `abort(403)`.
2. **Module gating in the dashboard** — `Employee::allowedModules()` (`app/Models/Employee.php`)
   filters `config/roles.php` `modules` by the employee's role, and `DashboardController` only renders
   panels whose key is in that list. So even if a panel existed, a Cashier never sees the Employees card.

> **Important correction to a common assumption:** the `users` table exists (from Laravel's default
> migration `0001_01_01_000000_create_users_table.php`) but is **REFERENCED BUT UNUSED** — all auth goes
> through `employee`. Leaving it is harmless but slightly confusing.

---

## Part XII — Middleware

Middleware sits between the request and the controller. `bootstrap/app.php` registers:
- `auth` and `guest` (Laravel built-ins) — `redirectUsersTo('/dashboard')`, `redirectGuestsTo('/login')`.
- `role` alias → `App\Http\Middleware\EnsureRole`.

**Flow for a protected page (e.g. `/products`):**
```
Browser GET /products
   → Laravel router matches route (auth + role:Manager,Admin)
   → auth middleware: is there a logged-in user? No → redirect /login (302)
   → (if logged in) EnsureRole: does user have Manager or Admin? No → abort(403)
   → passes → ProductController@index runs
```

`EnsureRole::handle(Request $request, Closure $next, string ...$roles)`:
- `$employee = $request->user();` (the authenticated employee, or null).
- `$employee?->loadMissing('role');` (make sure the role relation is loaded).
- `if (! $employee || ! $employee->hasRole(...$roles)) abort(403, …);`
- `return $next($request);` — hands the request to the next layer (the controller).

Middleware is useful because the check is centralized: you never repeat "is this user allowed?" inside every controller.

---

## Part XIII — Validation

Validation happens **inside controllers** via `$request->validate([…])`. Example (`ProductController::validated`):
```php
$request->validate([
    'product_name' => 'required|string|max:150',
    'barcode'      => 'required|string|max:50|unique:product,barcode',
    'unit_price'   => 'required|numeric|min:0',
]);
```
Rules mean: required, string, at most 150 chars, must be unique in `product.barcode`, numeric, ≥ 0.

**What happens on failure:** Laravel throws a `ValidationException`, which the framework turns into a
**422** response — it flashes the errors into the session and redirects `back()` to the form. Blade shows
them via `@include('partials.errors')` (present in `pos/index.blade.php` and `auth/login.blade.php`).
Old input is recalled with `{{ old('username') }}` so the user doesn't retype.

**Server-side is the real enforcement.** The POS JS also checks stock client-side, but the authoritative
check is `PosController@store` using `lockForUpdate()` + a stock comparison (see Part XIV) — because a
user can disable JS or hit the route directly.

---

## Part XIV — Complete Business Workflows

### A. Authentication
Login form → `POST /login` → `LoginController@login` → `Auth::attempt` → session regenerated →
redirect to `dashboard`. Logout → `POST /logout` → session invalidated.

### B. Product create
`products.create` (form) → `POST /products` → `ProductController@store` → validate →
`Product::create` → `Inventory::create` (stock 0) → `AuditLogger::record` → redirect `products.index` + status.

### C. Product update
`products.edit/{id}` → `ProductController@edit` (route-model-bound `$product`) → form prefilled →
`PUT /products/{id}` → `@update` → `$product->update(validated)` → audit → redirect.

### D. Product delete
`DELETE /products/{id}` → `@destroy` → if `isInUse()` block (error back), else `$product->delete()` + audit.

### E. POS / Checkout (most important)
```
User picks product + qty on POS form (JS builds cart)
   → form POSTs items[], customer_id, discount_id, payment_method, amount_paid
   → PosController@store
        → validate
        → DB::transaction:
             for each item: lock product row; if stock < qty → 422 error
             sum subtotal; apply discount if active
             if amount_paid < total → 422 error
             create sale_transaction (employee = auth()->id())
             for each item: create sale_details; inventory.stock_quantity -= qty
             create payment (change = paid - total)
             create receipt (number = 'R'+YYYYMMDD+zero-padded txn id)
             if customer: total_purchases += total; loyalty_points += floor(total/100)
        → AuditLogger::record('sale', …)
   → redirect pos.show (receipt)
```
**Why the transaction + lock:** two cashiers selling the last item simultaneously can't both succeed;
`lockForUpdate()` serializes them, and if anything fails the whole sale rolls back (no negative stock, no half-sale).

### F. Inventory
Created at product creation (stock 0) or via `inventory/sync`. Decremented on sale, incremented on PO
receive. `InventoryController@update` lets a manager set stock directly (records `last_restocked`).

### G. Customers
CRUD in `CustomerController`. During a sale, attaching a customer updates `total_purchases` and awards
loyalty points. Delete is blocked if the customer has sales (set `inactive` instead).

### H. Suppliers
Simple CRUD (`SupplierController`); referenced by products and purchase orders.

### I. Purchase Orders
`PurchaseOrderController@store` (transaction) creates order + details, status `pending`.
`receive()` adds each line's quantity back to inventory and sets `received`. `cancel()` sets `cancelled`.
Both refuse unless `isPending()`.

### J. Employees / Roles / Permissions
Admin-only CRUD (`EmployeeController`). Roles created by `RoleSeeder`. Permission enforced by `EnsureRole`
+ dashboard `allowedModules()`.

### K. Audit Logs
Every create/update/delete and every sale calls `AuditLogger::record(action, table, id, description)`
(`app/Services/AuditLogger.php`). It writes a row to `audit_log` with the current employee. Admin views
them at `/audit-logs`.

---

## Part XV — PHP Concepts Used

- **Class / Object / Method / Property** — `Product` is a class; `$product` is an object; `stockQuantity()` is a method.
- **Namespaces** — `namespace App\Models;` + `use` lets `Product` resolve without a full path.
- **Visibility** — `public` (callable anywhere), `private` (`validated()` in controllers, callable only inside the class).
- **Type declarations** — `store(Request $request): RedirectResponse` declares param and return types.
- **Constructor / `__invoke`** — `DashboardController` is invokable (single `index` logic), so the route points at the class, not a method.
- **Closures** — `DB::transaction(function () use ($data) { … })` is an anonymous function capturing `$data`.
- **Arrays / Collections** — query results are Laravel `Collection`s (have `->pluck()`, `->count()`); Blade uses `compact()`.
- **Null-safe operator** — `$this->inventory?->stock_quantity` (returns null safely if no inventory).
- **Traits** — `use HasFactory;` / `use AuthenticatableTrait;` mix in shared behavior.
- **Interfaces** — `Employee implements Authenticatable` (contract Laravel's auth requires).
- **Static method** — `AuditLogger::record(...)` is called on the class, not an instance.

---

## Part XVI — Laravel Concepts Used

MVC, routing, controllers, **Eloquent/ORM**, migrations, seeders, Blade, middleware, validation, sessions,
authentication, authorization, **CSRF** (`@csrf`), request/response, redirects (`redirect()->route()`),
**route-model binding** (`{saleTransaction}` → model), **dependency injection** (Laravel auto-injects
`Request` and bound models), **service container** (resolves classes/middleware), configuration
(`config/roles.php`, `config/auth.php`), environment (`.env` → `config/database.php`), and **Artisan**.
Concepts NOT used: API routes (`routes/api.php` absent from the map), queues, events/broadcasting,
API resources, policies (role checks are done in middleware, not Policy classes).

---

## Part XVII — Request Lifecycle (worked example: open /products as Manager)

```
1. Browser requests GET http://localhost/products
2. Web server (XAMPP) routes to public/index.php
3. index.php boots Laravel via bootstrap/app.php
4. Router loads routes/web.php; matches GET /products → products.index
5. Middleware stack runs:
     - auth: user? yes (session) → continue
     - role:Manager,Admin: user has Manager → continue
6. ProductController@index executes:
     - Product::with([...])->paginate(15)  → Eloquent builds SQL
     - Laravel opens a PDO connection to MySQL, runs the query, fetches rows
     - rows become Product objects (a Collection)
7. return view('products.index', compact('products'))
8. Blade loads layouts/app.blade.php + products/index.blade.php, injects $products, compiles to HTML
9. Laravel sends HTML as the HTTP response
10. Browser renders the Products page
```
If step 5's `role` check failed → `abort(403)`. If `/products` didn't exist → `abort(404)`.

---

## Part XVIII — Feature-to-Code Map (cheat sheet)

| Feature | Route | Controller | Model | DB Table | View |
|---------|-------|-----------|-------|----------|------|
| Login | `POST /login` | `LoginController@login` | `Employee` | `employee` | `auth/login` |
| Dashboard | `GET /dashboard` | `DashboardController` | many | many | `dashboard` |
| Sales (POS) | `GET/POST /pos` | `PosController` | `SaleTransaction`,`SaleDetail`,`Inventory`,`Payment`,`Receipt` | `sale_transaction`,`sale_details`,`inventory`,`payment`,`receipt` | `pos/index`,`pos/show` |
| Products | `GET/POST /products` (+edit) | `ProductController` | `Product`,`Inventory` | `product`,`inventory` | `products/index`,`products/create`,`products/edit` |
| Categories | `/categories` | `CategoryController` | `Category` | `category` | `categories/index` |
| Customers | `/customers` | `CustomerController` | `Customer` | `customer` | `customers/index` |
| Inventory | `/inventory` | `InventoryController` | `Inventory` | `inventory` | `inventory/index`,`inventory/edit` |
| Suppliers | `/suppliers` | `SupplierController` | `Supplier` | `supplier` | `suppliers/index` |
| Purchase Orders | `/purchase-orders` | `PurchaseOrderController` | `PurchaseOrder`,`PurchaseOrderDetail` | `purchase_order`,`purchase_order_details` | `purchase-orders/*` |
| Discounts | `/discounts` | `DiscountController` | `Discount` | `discount` | `discounts/*` |
| Employees | `/employees` | `EmployeeController` | `Employee`,`Role` | `employee`,`role` | `employees/*` |
| Audit Logs | `GET /audit-logs` | `AuditLogController` | `AuditLog` | `audit_log` | `audit-logs/index` |

---

## Part XIX — Architecture Diagram

```
                        POS SYSTEM (Laravel 13 / PHP 8.3 / MySQL)
                                   │
            ┌──────────────────────┼──────────────────────┐
            │                      │                      │
         Browser                Laravel                 MySQL
            │                      │                      │
       HTML / form           public/index.php            │
            │                      │                      │
            │  HTTP request        │ bootstrap/app.php     │
            ├─────────────────────▶│ routes/web.php        │
            │                      │ middleware            │
            │                      │  (auth, role)         │
            │                      │ controller            │
            │                      │      │                 │
            │                      │      │ Eloquent        │
            │                      │      ▼                 │
            │                      │   Model ───────────────┼──▶ tables
            │                      │      ▲                 │   (product, sale_*,
            │                      │      │ SQL             │    inventory, …)
            │                      │      └─────────────────┘
            │  HTTP response        │
            │◀─────────────────────│ Blade → HTML
            │                      │
       render page           AuditLogger (side-effect)
```

---

## Part XX — Technical Debt / Code Review

**CRITICAL**
- *Unused `users` table.* Default Laravel `users` migration remains but auth uses `employee`
  (`config/auth.php`, `EmployeeController` uses `employee`). Confusing for newcomers; safe to drop or document.

**HIGH**
- *No automated tests.* `tests/` exists but the repo shows no feature tests, so refactors are risky.
- *Hardcoded demo credentials in seeder* (`password` = `password`) — fine for local, dangerous if deployed.

**MEDIUM**
- *Stock check duplicated.* `PosController@store` (server) and `pos/index.blade.php` JS (client) both
  validate stock. They can drift; the JS is only UX, the server is authoritative — keep that boundary clear.
- *`public $timestamps = false` on most models* means no `created_at`/`updated_at` on sales, products, etc.
  Useful for audits but you lose automatic "when was this row last changed" columns (except `inventory`, `audit_log`).
- *Business logic in controllers.* Checkout, discounts, and loyalty all live in `PosController`. A
  `SaleService` would make it testable and thinner (the project does use a `Services/` folder for `AuditLogger`).

**LOW**
- *Inconsistent pagination vs `get()`* — dashboard uses `get()`, lists use `paginate(15)`; fine, just uneven.
- *Magic numbers in dashboard baseline* (110%/90% thresholds) are inline, not config — acceptable but could be tunable.

---

## Part XXI — Debugging Guide

| Symptom | Meaning | Where to look | Command |
|---------|---------|---------------|---------|
| **404** | No route matched | `routes/web.php`; run `php artisan route:list` | `php artisan route:list` |
| **403** | Authenticated but role denied | `EnsureRole.php`; `config/roles.php` | check your logged-in role |
| **419** | CSRF token missing/expired | form missing `@csrf`; stale page | add `@csrf`; refresh |
| **422** | Validation failed | controller `validate([…])`; Blade `@include('partials.errors')` | re-submit form, read errors |
| **500** | Server/code error | `storage/logs/laravel.log` | `tail storage/logs/laravel.log` |
| SQL error | bad query/column | the model/query; migration columns | check `database/migrations` |
| Undefined variable `$x` | view got no `$x` | the controller `view(...)` call | confirm `compact()`/passed array |
| Undefined method | called method doesn't exist on model | `app/Models/*.php` | check the model |
| Class not found | wrong `use`/namespace or not autoloaded | top of file `use App\…`; `composer.json` | `composer dump-autoload` |
| Route not found | `route('name')` typo / name missing | `routes/web.php` names | `php artisan route:list` |
| DB connection error | `.env` DB creds wrong / server down | `.env` `DB_*`; XAMPP MySQL running? | `php artisan tinker` → `DB::select('select 1')` |
| Migration error | already migrated / bad schema | `database/migrations`; `php artisan migrate:status` | `php artisan migrate:status` |

**Golden rule:** Laravel writes the real error to `storage/logs/laravel.log`. When the browser shows a
generic page, open that log — it names the file and line.

---

## Part XXII — Beginner Learning Path (tailored to this repo)

```
Level 1  PHP basics (variables, functions, classes, arrays, `use`)
Level 2  Repo layout — open app/, routes/, resources/views/ side by side
Level 3  Routes — read routes/web.php top to bottom; run `php artisan route:list`
Level 4  Controllers — start with ProductController (simplest CRUD)
Level 5  Blade — open products/index.blade.php; find where $products came from
Level 6  Models + Eloquent — Product.php; try `php artisan tinker` → `Product::first()`
Level 7  Relationships — Product→Inventory (hasOne), Sale→Details (hasMany)
Level 8  Database — read a migration; compare to the real table in phpMyAdmin
Level 9  Auth — LoginController + config/auth.php (employee, not users)
Level 10 Middleware — EnsureRole.php; how 403 happens
Level 11 Full flows — trace a sale end-to-end (Part XIV-E)
Level 12 Architecture — MVC, service container, transactions, technical debt
```

---

## Part XXIII — Practice Exercises

**Ex 1 — Find the route for the product list.**
- Hint: look in `routes/web.php` for `products`.
- Expected file: `routes/web.php`.
- Difficulty: Easy.

**Ex 2 — Which method creates a product?**
- Hint: resource routes map `store` to "create."
- Expected file: `app/Http/Controllers/ProductController.php::store`.
- Difficulty: Easy.

**Ex 3 — Which model is the `product` table?**
- Hint: search for `$table = 'product'`.
- Expected file: `app/Models/Product.php`.
- Difficulty: Easy.

**Ex 4 — Where is the product name stored in MySQL?**
- Hint: column `product_name` in the `product` table; migration `create_product_table.php`.
- Expected file: `database/migrations/2026_08_20_132950_create_product_table.php`.
- Difficulty: Easy.

**Ex 5 — Trace the source of `$products` in `products/index.blade.php`.**
- Hint: a controller calls `view('products.index', compact('products'))`.
- Expected file: `ProductController@index`.
- Difficulty: Medium.

**Ex 6 — Change the product name max length from 150 to 100.**
- Hint: validation rule is in the controller's `validated()`; also the migration defines the column size.
- Expected file: `ProductController.php` + `create_product_table.php`.
- Difficulty: Medium.

**Ex 7 — Add a new role "Supervisor" with Manager powers.**
- Hint: seeder + `config/roles.php` modules + `EnsureRole`.
- Expected file: `database/seeders/RoleSeeder.php`, `config/roles.php`.
- Difficulty: Hard.

**Ex 8 — Explain what `lockForUpdate()` prevents in `PosController@store`.**
- Hint: two cashiers, last item.
- Expected file: `app/Http/Controllers/PosController.php::store`.
- Difficulty: Hard.

### Solutions (attempt first!)
1. `Route::resource('products', ProductController::class)->except('show')` → `products.index` = GET `/products`.
2. `ProductController::store(Request $request)` — validates and `Product::create($data)`.
3. `app/Models/Product.php` (`protected $table = 'product';`).
4. Table `product`, column `product_name` (varchar 150) — see the migration.
5. `ProductController@index` does `Product::with([...])->paginate(15)` and passes `compact('products')`.
6. Change `'max:150'` → `'max:100'` in `validated()`; optionally `string('product_name', 100)` in migration (requires a new migration or `migrate:fresh`).
7. Add `'Supervisor'` to `RoleSeeder`, add it to the relevant `modules` arrays in `config/roles.php`, and include it in the `role:` middleware lists in `routes/web.php`.
8. It locks the product row in MySQL so concurrent sales can't both read-then-decrement the same stock,
   preventing negative inventory and double-selling.

---

## Part XXIV — "How I Should Think About This Project"

Stop seeing 40 files. See **one pipeline**:

> **URL → Route → Middleware → Controller → Model → SQL → MySQL → Model → Controller → Blade → HTML → Browser**

When something confuses you, walk that pipeline:
1. What URL/button? → find it in `routes/web.php`.
2. Which controller/method? → the route tells you.
3. What data? → the model + migration.
4. What's shown? → the Blade view named in the `return view(...)`.
5. Who's allowed? → `auth` + `role` middleware + `config/roles.php`.

A "feature" is never one file — it's the route + controller + model + migration + view working together.
That's the mental model that makes the system feel connected instead of random.

---

## Part XXV — Final Knowledge Checklist

You should now be able to answer:
- [ ] What happens when I click "Products"? (Route → `ProductController@index` → view)
- [ ] Which route handles a sale? (`POST /pos` → `pos.store`)
- [ ] Which controller receives a login? (`LoginController@login`)
- [ ] Where does product data come from? (`Product` model → `product` table)
- [ ] What SQL does `Product::with('inventory')` run? (SELECT product + SELECT inventory WHERE product_id IN)
- [ ] How does Laravel know which Blade to render? (the `return view('…')` in the controller)
- [ ] How does authentication work? (`Auth::attempt` on `employee`, session stored)
- [ ] How does role access work? (`EnsureRole` middleware + `config/roles.php` modules)
- [ ] How does a sale affect inventory? (`PosController@store` decrements `inventory.stock_quantity` in a transaction)
- [ ] How does data move browser → MySQL → back? (form POST → controller → Eloquent → SQL → objects → Blade → HTML)
- [ ] If it breaks, where do I look? (`storage/logs/laravel.log`, then the 5-step pipeline)
- [ ] To add a feature, which files? (route + controller + model/migration + view + (role in `config/roles.php`))

---

*Generated as a read-only analysis of the repository. No project files were modified. Every claim is
cited to a file you can open and verify.*
