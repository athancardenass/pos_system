<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:Cashier,Manager,Admin')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{saleTransaction}', [PosController::class, 'show'])->name('pos.show');
        Route::resource('customers', CustomerController::class)->except('show');
    });

    Route::middleware('role:Manager,Admin')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/sync', [InventoryController::class, 'storeMissing'])->name('inventory.sync');
        Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::resource('discounts', DiscountController::class)->except('show');
    });

    Route::middleware('role:Admin')->group(function () {
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
