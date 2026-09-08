<?php

return [
    /*
    | Allowed roles per named route. Cashier = sales floor.
    | Manager = everything (catalog, stock, purchasing, employees, audit).
    | The old 'Admin' role has been merged into Manager (manager = everything).
    */
    'modules' => [
        'dashboard' => ['Manager', 'Cashier'],
        'pos.index' => ['Manager', 'Cashier'],
        'customers.index' => ['Manager', 'Cashier'],
        'categories.index' => ['Manager'],
        'products.index' => ['Manager'],
        'inventory.index' => ['Manager'],
        'suppliers.index' => ['Manager'],
        'purchase-orders.index' => ['Manager'],
        'discounts.index' => ['Manager'],
        'reports.index' => ['Manager'],
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
        'reports.index' => 'Reports',
        'employees.index' => 'Employees',
        'audit-logs.index' => 'Audit Logs',
    ],
];
