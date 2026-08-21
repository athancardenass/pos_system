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
];
