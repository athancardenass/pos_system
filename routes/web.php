<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:Cashier,Manager,Admin')->group(function () {
        Route::get('/pos', ModuleAccessController::class)->name('pos.index');
        Route::get('/customers', ModuleAccessController::class)->name('customers.index');
    });

    Route::middleware('role:Manager,Admin')->group(function () {
        Route::get('/categories', ModuleAccessController::class)->name('categories.index');
        Route::get('/products', ModuleAccessController::class)->name('products.index');
        Route::get('/inventory', ModuleAccessController::class)->name('inventory.index');
        Route::get('/suppliers', ModuleAccessController::class)->name('suppliers.index');
        Route::get('/purchase-orders', ModuleAccessController::class)->name('purchase-orders.index');
        Route::get('/discounts', ModuleAccessController::class)->name('discounts.index');
    });

    Route::middleware('role:Admin')->group(function () {
        Route::get('/employees', ModuleAccessController::class)->name('employees.index');
        Route::get('/audit-logs', ModuleAccessController::class)->name('audit-logs.index');
    });
});
