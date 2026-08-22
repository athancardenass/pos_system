<?php

return [
    /*
    | Allowed roles per named route. Cashier = sales floor.
    | Manager = catalog, stock, purchasing. Admin = everything including employees.
    */
    'modules' => [
        'dashboard' => ['Admin', 'Manager', 'Cashier'],
        'pos.index' => ['Admin', 'Manager', 'Cashier'],
        'customers.index' => ['Admin', 'Manager', 'Cashier'],
        'categories.index' => ['Admin', 'Manager'],
        'products.index' => ['Admin', 'Manager'],
        'inventory.index' => ['Admin', 'Manager'],
        'suppliers.index' => ['Admin', 'Manager'],
        'purchase-orders.index' => ['Admin', 'Manager'],
        'discounts.index' => ['Admin', 'Manager'],
        'employees.index' => ['Admin'],
        'audit-logs.index' => ['Admin'],
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
        'employees.index' => 'Employees',
        'audit-logs.index' => 'Audit Logs',
    ],
];
