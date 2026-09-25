# Supermarket POS System — Technical & Architectural Documentation

**Project Name:** Enterprise Supermarket Point of Sale (POS) System  
**Course/Domain:** Systems Integration and Architecture (SIA) / Enterprise Application Development  
**Technology Stack:** Laravel 10/11, PHP 8.3, MariaDB / MySQL (XAMPP), Blade, Vanilla JavaScript, Web Audio API  
**Last Updated:** September 2026  

---

## 1. Executive Summary & System Overview

The **Enterprise Supermarket Point of Sale (POS) System** is a robust, transactional retail terminal solution built to handle high-frequency supermarket operations. It combines frontline cashier efficiency with back-office financial accountability, multi-tier promotion mechanics, and strict inventory governance.

### Key Objectives
* **Sub-second Checkout Flow:** Rapid item scanning with hardware barcode buffering, audio cues, category filters, and hotkeys.
* **Pessimistic Concurrency & Data Integrity:** Strict database-level row locking (`lockForUpdate`) during checkout and refunds to prevent race conditions (TOCTOU) and stock overselling.
* **Cash Drawer Accountability:** Shift-based register sessions tracking opening floats, cash sales, paid ins/outs, and exact overage/shortage calculations.
* **Multi-Method Payment Governance:** Comprehensive verification for Cash, Card (terminal authorization codes), and E-Wallets (GCash/Maya transaction references).
* **Enterprise Integration Readiness:** Modular service-layer design prepared for future integration with Customer Relationship Management (CRM), Human Resource Management Systems (HRMS), and Procurement/Supply Chain modules.

---

## 2. Technology Stack & Environment

| Layer | Technology | Details |
|---|---|---|
| **Backend Framework** | Laravel 10/11 | Clean MVC + Dedicated Service Layer Architecture |
| **Language Runtime** | PHP 8.3 | Strict typing, typed properties, constructor property promotion |
| **Primary Database** | MariaDB 10.4 / MySQL | InnoDB engine with foreign key constraints and transactional row locks |
| **Testing Database** | SQLite (in-memory) | High-speed unit & feature test execution (`php artisan test`) |
| **Frontend Rendering** | Laravel Blade | Server-rendered components with zero bloat |
| **Client-Side Scripting** | Vanilla JavaScript (ES6+) | Hardware barcode buffer, dynamic cart state, keyboard shortcuts |
| **Audio Synthesizer** | Web Audio API | Procedural sound generation (no external MP3/WAV dependencies) |
| **UI Design System** | Neo-Brutalist SaaS | DM Sans typography, `#FEDAB8` canvas, `#203C3D` teal ink, `#C4504A` coral focus, `#2D8A4E` emerald |

---

## 3. System Architecture & Design Patterns

The application follows the **Thin Controller / Heavy Service** pattern. Business logic, mathematical calculations, and multi-table mutations are strictly encapsulated in dedicated domain services rather than controllers or models.

```
                    +-----------------------------+
                    |      HTTP Request / POS     |
                    +--------------+--------------+
                                   |
                                   v
                    +--------------+--------------+
                    |    Thin Controllers (HTTP)   |
                    | (Validation & JSON/View Out)|
                    +--------------+--------------+
                                   |
    +------------------------------+------------------------------+
    |                              |                              |
    v                              v                              v
+---+------------------+  +--------+-----------+  +---------------+-----+
|   CheckoutService    |  |  PromotionService  |  |  CashDrawerService  |
| - Stock locking      |  | - Order discounts  |  | - Opening float     |
| - Inventory mutate   |  | - Coupon threshold |  | - Sales tally       |
| - Payment record     |  | - Dynamic checks   |  | - Discrepancy math  |
+----------------------+  +--------------------+  +---------------------+
    |                              |                              |
    +------------------------------+------------------------------+
                                   |
                                   v
                    +--------------+--------------+
                    |   DB::transaction (Pessimistic Row Locking) |
                    |      MariaDB / InnoDB Engine                |
                    +---------------------------------------------+
```

### Core Domain Services

1. **`CheckoutService` (`app/Services/CheckoutService.php`)**
   * Encapsulates the entire sale transaction inside an atomic `DB::transaction`.
   * Enforces pessimistic locking (`lockForUpdate()`) on all purchased product rows.
   * Aggregates line items by product ID before checking stock availability to guard against multi-entry overselling.
   * Automatically adjusts inventory levels and registers stock movement entries.
   * Links payment records (including reference numbers and card/wallet providers).
   * Automatically credits active cash drawers for cash transactions.

2. **`PromotionService` (`app/Services/PromotionService.php`)**
   * Computes promotions and coupon discounts in a single authoritative pipeline.
   * Validates coupon codes against cart subtotals, active date ranges, and customer restrictions.
   * Powers the live AJAX endpoint (`POST /pos/check-coupon`) for instant cashier previews.

3. **`RefundService` (`app/Services/RefundService.php`)**
   * Processes partial and full sale refunds within an immutable audit trail.
   * Enforces the 7-day supermarket refund policy window (allows Manager role override).
   * Restores inventory quantities to stock upon item refund.
   * Generates printable Credit Slips (`/pos/refund/{refund}/slip`).

4. **`CashDrawerService` (`app/Services/CashDrawerService.php`)**
   * Manages register shift lifecycles: Open, Active, and Closed.
   * Calculates real-time expected cash balance (`Starting Float + Cash Sales + Cash In - Cash Out`).
   * Evaluates reconciliation discrepancies:
     * **Balanced:** Discrepancy = 0
     * **Shortage:** Actual < Expected (flagged red)
     * **Overage:** Actual > Expected (flagged yellow)

---

## 4. Key Functional Modules

### 4.1. Frontline POS Terminal (`/pos`)
* **Hardware Barcode Scanner Support:** Global keystroke buffer captures rapid scanner input (<120ms burst window) from anywhere on the page without requiring manual click into the search input.
* **Supermarket Audio Feedback (Web Audio API):**
  * `1760Hz` Sine Beep: Item scanned / added to cart.
  * `220Hz` Sawtooth Double Buzz: Out-of-stock warning, coupon error, or invalid payment.
  * `523-784Hz` 3-Tone Chime: Successful sale completion.
* **Quick Category Filter Chips:** One-click filtering (`All`, `Beverages`, `Bakery`, `Snacks`, `Household`) for unbarcoded or loose items.
* **Cashier Keyboard Shortcuts:**
  * `F2`: Jump focus directly to Product Search / Barcode input.
  * `F4`: Jump focus directly to Cash Received input.
  * `F8`: Cycle payment methods (`Cash` &harr; `Card` &harr; `E-Wallet`).
  * `Enter`: Complete sale (when inside payment fields).
  * `Esc`: Close open modal windows or clear search.
* **Customer Loyalty Lookup:** Instant search and match by Customer ID or phone number; displays member name and accumulated loyalty points.
* **Auto-Receipt Print & Continuous Queue Flow:** Automatically triggers browser thermal print dialog upon checkout completion (`?auto_print=1`) and allows returning to the next transaction instantly via `Spacebar` or `Enter`.

### 4.2. Multi-Method Payment Processing
* **Cash:** Dynamic calculation of change due; validation prevents completing sales if cash received is less than total.
* **Card (Credit / Debit):** Mandatory input of Terminal Authorization / Approval Code and masked card number (last 4 digits); stored in `payment` record.
* **E-Wallet (GCash / Maya / GrabPay):** Mandatory input of provider transaction reference number to ensure auditability against merchant terminal settlements.

### 4.3. Promotion & Coupon Engine
* Supports fixed-amount discounts and percentage discounts.
* Minimum purchase threshold verification (`min_purchase`).
* Expiration date windows (`starts_at` to `ends_at`) and activation status toggles.
* Real-time coupon preview (`POST /pos/check-coupon`) allowing cashier to confirm discounts before asking for customer payment.

### 4.4. Cash Drawer Accountability (`/cash-drawers`)
* Cash drawer opening with preset quick-float selections (₱1,000, ₱2,000, ₱3,000, ₱5,000).
* Live shift tracking showing running cash totals.
* End-of-shift register closure modal requiring physical cash count entry with automatic variance calculation.
* Historical audit log with status badges (`Balanced`, `Shortage`, `Overage`).

### 4.5. Reporting & Financial Analytics (`/reports`)
* Role-protected: Financial and revenue metrics restricted exclusively to the `Manager` role.
* Flexible date filtering: Daily, Weekly, Monthly, Yearly, and Custom Date Ranges.
* Metrics: Gross Sales, Net Sales, Refunds, VAT Collected, Top Selling Products, and Low Stock Alerts.
* One-click CSV export for external spreadsheet auditing.

---

## 5. Security & Role-Based Access Control (RBAC)

Authentication is managed via the **`employee`** database table and enforced by the `EnsureRole` middleware. The system uses a streamlined two-tier security model:

| Role | Access Scope |
|---|---|
| **Cashier** | POS Terminal, Cash Drawer Open/Close, Customer Directory, Product Lookup, Basic Receipt Lookup |
| **Manager** | Full POS Terminal Access, Financial Reports & Revenue Analytics, Cash Drawer Override, Refund Policy Overrides, Employee Management, Product & Inventory Management, Audit Log Review |

### Security Measures
* **No Hardcoded Superusers:** All logins verify bcrypt hashes in the `employee` table.
* **Pessimistic Concurrency Guards:** DB row locking prevents double-spend or oversell vulnerabilities.
* **Stored-XSS Guard:** Dynamic DOM updates strictly utilize `textContent` and `createElement` rather than direct `innerHTML` string interpolation.
* **Input Validation:** Strict server-side Form Requests and controller validation on all endpoints.

---

## 6. Database Schema & Data Dictionary

```
+---------------+       +------------------+       +-------------------+
|   employee    |       |   cash_drawers   |       |     customer      |
+---------------+       +------------------+       +-------------------+
| employee_id   |<---+  | drawer_id        |       | customer_id       |
| username      |    |  | employee_id (FK) |       | name, phone, etc. |
| role (enum)   |    |  | starting_cash    |       | loyalty_points    |
| password_hash |    |  | actual_cash      |       +---------+---------+
+---------------+    |  | status           |                 |
                     |  +------------------+                 |
                     |                                       |
                     +---------------------+                 |
                                           |                 |
+---------------+       +------------------+-------+         |
|    product    |       |     sale_transaction     |         |
+---------------+       +--------------------------+         |
| product_id    |<---+  | transaction_id           |         |
| name          |    |  | transaction_date         |         |
| barcode (EAN) |    |  | total_amount             |         |
| price, stock  |    |  | status (completed/refund)|         |
+---------------+    |  | customer_id (FK) --------+---------+
                     |  | employee_id (FK)         |
+---------------+    |  +------------+-------------+
|  sale_detail  |    |               |
+---------------+    |               v
| detail_id     |    |  +--------------------------+
| transaction_id+----+  |         payment          |
| product_id (FK)+---+  +--------------------------+
| quantity      |       | payment_id               |
| unit_price    |       | transaction_id (FK)      |
| subtotal      |       | payment_method (enum)    |
+---------------+       | payment_provider         |
                        | reference_number         |
                        | amount_paid              |
                        | change_amount            |
                        +--------------------------+
```

### Key Tables
1. **`employee`:** System users with assigned roles (`manager`, `cashier`) and login credentials.
2. **`products`:** Inventory catalogue containing unique EAN-13 barcodes, categories, cost, price, and current stock.
3. **`sale_transaction`:** Master sales record linking cashier, customer, date, total amount, and status.
4. **`sale_detail`:** Line items associated with each sale transaction.
5. **`payment`:** Payment execution record tracking method, provider, reference number, cash tendered, and change.
6. **`cash_drawers`:** Register shift records capturing floats, closing counts, and discrepancy metrics.
7. **`sale_refund` & `sale_refund_item`:** Itemized refund log with reasons, manager approvals, and amounts.
8. **`coupons` & `promotions`:** Promotion definitions, discount values, and usage thresholds.
9. **`audit_logs`:** System-wide transactional audit log capturing actions, models touched, and employee IDs.

---

## 7. Systems Integration & Architecture (SIA) Horizon

In compliance with the semester project roadmap, this POS system is decoupled and structured for upcoming integration with sibling enterprise modules:

1. **Customer Relationship Management (CRM):**
   * Customer schema maintains loose coupling via `customer_id`.
   * Ready for REST API synchronization of loyalty points, member tiers, and purchase history.
2. **Human Resource Management System (HRMS):**
   * Employee records are normalized; cashier shift hours and sales volume performance metrics are queryable via standard API endpoints.
3. **Procurement & Supply Chain Management (SCM):**
   * Real-time stock decrements and inventory movements provide purchase requisition triggers for low-stock products.

---

## 8. Verification & Quality Assurance

* **Automated Test Suite:** 94 unit and feature tests covering checkout calculations, inventory locking, promotion stacking, refund policies, cash drawer reconciliation, and payment reference integrity.
* **Test Command:** `php artisan test`
* **Test Status:** 94 passed, 325 assertions.
* **Code Integrity:** Verified zero syntax errors via `php -l` across all application files.
