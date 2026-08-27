# Case Study: Point-of-Sale (POS) System

**Course:** BS in Information Technology
**Project:** POS System
**Tech Stack:** Laravel 13 (PHP 8.3), MySQL, Blade templating
**Document Type:** Overall project case study — for group submission

---

## 1. Introduction

This case study documents a **Point-of-Sale (POS) System** built as a web application using the Laravel framework. The system lets a retail business record sales, manage products and inventory, track customers and suppliers, handle purchase orders, apply discounts, and monitor activity through role-based dashboards and audit logs.

The goal was to build a complete, working retail backend — not just a single screen — covering the full sales lifecycle from "add to cart" to receipt, inventory deduction, and reporting.

---

## 2. Objectives

- Record sales quickly with support for **cash, card, and e-wallet** payments.
- Maintain **accurate inventory** that decrements automatically on each sale.
- Manage the core business entities: products, categories, customers, suppliers.
- Support **purchase orders** (create → receive → cancel) to restock inventory.
- Apply **date-based discounts** at checkout.
- Enforce **role-based access** (Cashier, Manager, Admin) so staff only see what they should.
- Provide a **dashboard** with today's sales, a weekly trend, and low-stock alerts.
- Keep an **audit trail** of important actions for accountability.

---

## 3. System Architecture

The application follows the standard **Laravel MVC** pattern:

- **Models** (Eloquent) represent each database table and its relationships.
- **Controllers** handle requests and business logic.
- **Blade views** render the UI.
- **Migrations** define the database schema and foreign keys.
- **Routes** (`routes/web.php`) wire URLs to controllers and apply auth + role middleware.

### 3.1 Roles & Access Control
Authentication is built on the `Employee` model (which implements Laravel's `Authenticatable`). Each employee has a `Role` (Cashier, Manager, or Admin). Access is enforced two ways:

1. **Route middleware** — e.g. `role:Cashier,Manager,Admin` gates the POS and customer screens; `role:Manager,Admin` gates products, inventory, suppliers, purchase orders, discounts; `role:Admin` gates employees and audit logs.
2. **Module permissions** — `Employee::allowedModules()` reads a `roles.modules` config map, and the dashboard only renders panels the user's role is allowed to see.

### 3.2 Modules / Features
| Module | Who can use | What it does |
|--------|-------------|--------------|
| POS (sales) | Cashier, Manager, Admin | Ring up sales, print receipt |
| Customers | Cashier, Manager, Admin | Manage customer records, loyalty points |
| Products | Manager, Admin | CRUD products, pricing, barcodes |
| Categories | Manager, Admin | Organize products |
| Inventory | Manager, Admin | Stock levels, reorder alerts, restock |
| Suppliers | Manager, Admin | Manage suppliers |
| Purchase Orders | Manager, Admin | Create / receive / cancel restock orders |
| Discounts | Manager, Admin | Date-based promotional discounts |
| Employees | Admin | Staff accounts and roles |
| Audit Logs | Admin | View recorded system activity |
| Dashboard | (role-aware) | Sales summary, trends, alerts |

---

## 4. Data Model

The database has **16 Eloquent models**. Key tables:

- **employee / role / user** — staff accounts, roles, and auth.
- **product** — `product_id`, `category_id`, `supplier_id`, `product_name`, `barcode` (unique), `unit_price`, `cost_price`, `reorder_level`.
- **category / supplier** — classification and sourcing.
- **inventory** — `stock_quantity` per product, compared against `reorder_level`.
- **customer** — name, status, `total_purchases`, `loyalty_points`.
- **sale_transaction** — the sale header: `customer_id`, `employee_id`, `discount_id`, `transaction_date`, `subtotal`, `total_amount`, `payment_method`.
- **sale_details** — line items: `product_id`, `quantity`, `unit_price`, `subtotal`.
- **payment** — `payment_method`, `amount_paid`, `change_amount`, `payment_date`.
- **receipt** — `receipt_number` (format `R + YYYYMMDD + zero-padded transaction id`), `issued_date`.
- **discount** — name, value, active date range.
- **purchase_order / purchase_order_details** — restocking workflow.
- **audit_log** — action, description, timestamp, employee.

> **Schema note:** tables use descriptive names (`product`, `employee`, `sale_transaction`) with integer primary keys and foreign keys added in a separate migration step.

---

## 5. Core Process: How a Sale Works

The checkout flow lives in `PosController::store()` and is wrapped in a **database transaction** so it's all-or-nothing. Steps:

1. **Validate input** — items, quantities, payment method (cash/card/e-wallet), amount paid, optional customer/discount.
2. **Reserve & check stock** — each product row is locked (`lockForUpdate()`) and stock is verified; if any item is short, the whole sale is rejected with a clear message.
3. **Compute totals** — line subtotals are summed; an active discount (if provided) is applied.
4. **Verify payment** — rejects the sale if `amount_paid < total`.
5. **Persist atomically:**
   - Create the `sale_transaction` (tied to the logged-in employee).
   - Create each `sale_details` line.
   - **Decrement inventory** for each product sold.
   - Create the `payment` record (including change due).
   - Create the `receipt` with a generated receipt number.
   - If a customer is attached, update `total_purchases` and award **loyalty points** (`floor(total / 100)`).
6. **Audit log** — record the completed sale.
7. Redirect to the receipt/"show" page.

This design guarantees inventory can never go negative from a race condition and that a failed sale leaves no half-written records.

---

## 6. Dashboard & Reporting

On login, the dashboard (`DashboardController`) shows a role-aware summary (see the dedicated *Dashboard Sales* case study). Highlights:

- **Top cards:** Today's sales count, today's revenue, average transaction, all-time total revenue.
- **"Sales This Week" chart:** a rolling 7-day revenue bar chart. Each bar is color-coded against the **previous week's average daily revenue** baseline — 🟢 ≥110% (high), 🟠 within ±10% (average), 🔴 <90% (low) — so weak days are obvious at a glance.
- **Supporting panels:** Top products, payment-method split (today), recent transactions, top categories.
- **Inventory alerts:** out-of-stock and low-stock (below reorder level) warnings with a quick "Restock" link.
- **Recent activity:** last admin/audit actions (Admin only).

---

## 7. Other Key Features

- **Discounts** are date-scoped — a discount only applies if active "today," enforced both when listing options and at checkout.
- **Purchase Orders** model the restock cycle: create an order, *receive* it (which adds stock back to inventory), or *cancel* it.
- **Inventory sync** endpoint (`/inventory/sync`) creates inventory rows for any product missing one.
- **Audit logging** (`AuditLogger` service) records significant actions (e.g., sales) with who/when/what for traceability.
- **Customers** earn loyalty points automatically on paid sales.

---

## 8. Design Decisions & Trade-offs

| Decision | Rationale |
|----------|-----------|
| Single `DB::transaction` for sales | Atomicity — no partial sales, no negative stock |
| `lockForUpdate()` on products | Prevents race conditions when two cashiers sell the same item |
| Receipt number = `R + date + txn id` | Human-readable, sortable, unique without extra sequence tables |
| Rolling 7-day window for "this week" | Chart is always full; comparison always current |
| Discounts validated server-side | Client can't apply an expired/inactive discount |
| Role + module dual gating | Defense in depth: routes *and* dashboard respect permissions |
| Plain Blade + CSS (no JS chart lib) | Lightweight, dependency-free dashboard |

---

## 9. Results & Value

The system delivers an end-to-end retail workflow:
- Cashiers can complete a sale in a few clicks with automatic receipts and correct change.
- Inventory stays accurate because it updates inside the same transaction as the sale.
- Managers get products, suppliers, purchase orders, and discounts under one roof.
- Admins get staff management and a full audit trail.
- Everyone gets a relevant, secure dashboard on login.

---

## 10. Possible Future Enhancements

- Returns / voids and refund handling.
- Reports export (CSV/PDF) and date-range filtering.
- Barcode-scanning hardware integration at the POS.
- Calendar-week (Mon–Sun) reporting option alongside the rolling window.
- Caching dashboard queries for performance at scale.
- Email/SMS receipts.

---

## 11. Conclusion

The POS System is a complete, working Laravel application covering the full retail sales lifecycle — from checkout and inventory deduction to purchasing, discounts, role-based dashboards, and audit logging. Its transactional, race-safe checkout and clear role separation make it a solid foundation that could be extended into a production store system.

---

*Prepared by the POS System development team. Source files: `routes/web.php`, `app/Http/Controllers/*`, `app/Models/*`, `database/migrations/*`, `resources/views/*`.*
