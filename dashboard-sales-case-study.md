# Case Study: Sales Dashboard Module for a Point-of-Sale (POS) System

**Course:** BS in Information Technology
**Project:** POS System (Laravel)
**Stack:** Laravel 13 (PHP 8.3), MySQL, Blade templating
**Document type:** Feature case study — written for group submission

---

## 1. Background

A retail business needs a quick, at-a-glance view of how sales are performing without opening individual reports. The goal of this module was to give staff (cashiers, managers, and admins) an instant **sales overview** the moment they log in — showing today's performance, a rolling weekly trend, and how the current week compares to the previous one.

This case study documents the **Dashboard → Sales** section of our POS system: what it does, how it was built, and the design decisions behind it.

---

## 2. Objectives

- Give every authorized role a single-screen sales summary on login.
- Show **today's** numbers (sales count, revenue, average ticket) prominently.
- Visualize the **last 7 days** of revenue as a simple, readable bar chart.
- Compare **this week vs. last week** automatically using a daily-average baseline.
- Highlight good and bad days with color coding so problems are obvious at a glance.
- Keep the dashboard **role-aware** — only show sales data to roles that should see it.

---

## 3. System Overview

The POS system is built with Laravel. Sales are stored as `SaleTransaction` records (each with a `transaction_date` and `total_amount`) and `SaleDetail` line items (product, quantity, subtotal). The dashboard is rendered by a single invokable controller, `DashboardController`, which gathers all stats and passes them to the `dashboard.blade.php` view.

Access is controlled by a module-permission system (`allowedModules()`), so the sales block only appears for roles with the `pos.index` permission (Cashier, Manager, Admin).

---

## 4. What the Dashboard Sales Module Shows

### 4.1 Top Stat Cards
Four cards give the headline numbers:

| Card | Definition |
|------|-----------|
| **Today's Sales** | Number of transactions dated today |
| **Today's Revenue** | Sum of `total_amount` for today |
| **Avg Transaction** | Average `total_amount` per sale today |
| **Total Revenue** | All-time sum of every completed sale |

### 4.2 "Sales This Week" — Weekly Trend Chart ⭐
The centerpiece. It queries the **last 7 days** (today + the previous 6 days), groups sales by day, and draws one bar per day showing that day's revenue. Each bar also shows the day-of-week label (Mon, Tue, …) and the ₱ revenue figure.

### 4.3 This Week vs. Last Week (the key insight)
To make the chart meaningful, the controller computes a **baseline**: the average *daily* revenue of the **previous week** (days 13–7 days ago).

```
baseline = (revenue from days 13–7 ago) ÷ 7
```

Every bar is then color-coded against that baseline:
- 🟢 **Green** — day's revenue is **≥ 110%** of the baseline (a strong day)
- 🟠 **Orange** — day's revenue is within **±10%** of the baseline (average)
- 🔴 **Red** — day's revenue is **below 90%** of the baseline (a weak day)
- Accent color — used only when there isn't enough history for a baseline yet

A legend under the chart shows the exact ₱ thresholds for High / Average / Low, so the colors are self-explanatory.

> **Note on definition:** "This week" is implemented as a **rolling 7-day window** (trailing week), not a fixed Monday–Sunday calendar week. This keeps the chart always populated and the comparison always relevant.

### 4.4 Supporting Panels
- **Top Products** — top 5 products by total quantity sold (all-time), with revenue.
- **Payment Methods (Today)** — cash/card/etc. split for today, with transaction count and total.
- **Recent Transactions** — the last 5 sales (receipt #, customer, cashier, amount, method, time).
- **Top Categories** — top 5 categories ranked by revenue.

---

## 5. How It Was Built (Technical Summary)

**Controller (`DashboardController.php`)**
- Uses Laravel's query builder and Eloquent aggregates (`count()`, `sum()`, `avg()`, `groupBy()`).
- The weekly trend is a single grouped query:
  ```php
  SaleTransaction::select(
      DB::raw('DATE(transaction_date) as date'),
      DB::raw('COUNT(*) as count'),
      DB::raw('SUM(total_amount) as revenue')
  )
  ->where('transaction_date', '>=', Carbon::now()->subDays(6)->startOfDay())
  ->groupBy('date')
  ->orderBy('date')
  ->get();
  ```
- The previous-week baseline uses `whereBetween()` over days 13–7 ago, then divides the summed revenue by 7.
- All sales queries are wrapped in a `if (in_array('pos.index', $modules))` check so the block respects roles.

**View (`dashboard.blade.php`)**
- Pure Blade + inline CSS (no charting library needed) — bars are `<div>`s whose height is scaled to the max day's revenue.
- Color bands are computed in Blade using the baseline thresholds; a legend explains them.
- Fully responsive: the 4-card and 2-column grids collapse to fewer columns on small screens.

---

## 6. Design Decisions & Trade-offs

| Decision | Why |
|----------|-----|
| Rolling 7-day window (not calendar week) | Chart is always full and comparison stays current; simpler to reason about |
| Daily-average baseline for comparison | Smooths out single bad/good days; fair "are we up or down?" signal |
| 110% / 90% color thresholds | Clear, tunable bands; commented in code so they can be adjusted later |
| No JS chart library | Lighter page, no dependencies, easy to maintain |
| Role-gated stats | Staff only see data relevant to their permission level |

---

## 7. Results & Value

The dashboard gives any authorized user an immediate read on sales health:
- **Today's performance** is one glance away.
- The **weekly chart** shows momentum and flags weak days in red automatically.
- The **week-over-week baseline** turns a plain bar chart into an actual performance signal ("are we beating last week?").
- Because it is role-aware and dependency-free, it loads fast and stays secure.

---

## 8. Possible Future Enhancements
- Switch "this week" to a true calendar week (Mon–Sun) if management reporting requires it.
- Let users pick a date range instead of a fixed 7 days.
- Add a revenue goal line to the chart.
- Cache the dashboard queries to reduce database load at scale.

---

## 9. Conclusion

The Sales Dashboard module delivers a fast, clear, and secure sales overview for our POS system. By combining simple Laravel aggregates with a rolling weekly trend and an automatic week-over-week baseline, it turns raw transactions into an actionable daily picture — exactly what a retail team needs on login.

---

*Prepared by the POS System development team. Source: `app/Http/Controllers/DashboardController.php` and `resources/views/dashboard.blade.php`.*
