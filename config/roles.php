<?php

return [
    /*
    | Allowed roles per named route. Cashier = sales floor.
    | Manager = everything (catalog, stock, purchasing, employees, audit).
    | The old 'Admin' role has been merged into Manager (manager = everything).
    */
    'modules' => [
        'dashboard' => ['Manager'],
        'pos.index' => ['Manager', 'Cashier'],
        'customers.index' => ['Manager', 'Cashier'],
        'categories.index' => ['Manager'],
        'products.index' => ['Manager'],
        'inventory.index' => ['Manager'],
        'suppliers.index' => ['Manager'],
        'purchase-orders.index' => ['Manager'],
        'discounts.index' => ['Manager'],
        'promotions.index' => ['Manager'],
        'coupons.index' => ['Manager'],
        'cash-drawers.index' => ['Manager'],
        'reports.index' => ['Manager'],
        'employees.index' => ['Manager'],
        'audit-logs.index' => ['Manager'],
    ],

    /*
    | Primary Navigation displayed in the sidebar.
    | Refocused around the core POS workflow per professor requirements:
    | Products, Inventory, Purchase Orders, and Suppliers are hidden from
    | the primary navigation, but their underlying routes/controllers/models
    | remain fully intact and operational.
    */
    'primary_navigation' => [
        'pos.index' => ['Manager', 'Cashier'],
        'customers.index' => ['Manager', 'Cashier'],
        'cash-drawers.index' => ['Manager'],
        'promotions.index' => ['Manager'],
        'coupons.index' => ['Manager'],
        'discounts.index' => ['Manager'],
        'reports.index' => ['Manager'],
        'dashboard' => ['Manager'],
        'employees.index' => ['Manager'],
        'audit-logs.index' => ['Manager'],
    ],

    'labels' => [
        'dashboard' => 'Dashboard',
        'pos.index' => 'POS',
        'customers.index' => 'Customers',
        'categories.index' => 'Categories',
        'products.index' => 'Products',
        'inventory.index' => 'Inventory',
        'suppliers.index' => 'Suppliers',
        'purchase-orders.index' => 'Purchase Orders',
        'discounts.index' => 'Discounts',
        'promotions.index' => 'Promotions',
        'coupons.index' => 'Coupons',
        'cash-drawers.index' => 'Cash Drawers',
        'reports.index' => 'Reports',
        'employees.index' => 'Employees',
        'audit-logs.index' => 'Audit Logs',
    ],
];
