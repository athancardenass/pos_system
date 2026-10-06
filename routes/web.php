<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashDrawerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ManagerAuthorizationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceiptSettingsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\VatSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->hasRole('Cashier')
        ? redirect()->route('pos.index')
        : redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->middleware('role:Manager')->name('dashboard');

    Route::middleware('role:Cashier,Manager')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/customers/search', [PosController::class, 'searchCustomers'])->name('pos.customers.search');
        Route::get('/pos/stock', [PosController::class, 'productStock'])->name('pos.stock');
        Route::get('/pos/reprint-last', [PosController::class, 'reprintLast'])->name('pos.reprint-last');
        Route::get('/pos/{saleTransaction}/reprint', [PosController::class, 'reprint'])->name('pos.reprint');
        Route::post('/manager-authorization', [ManagerAuthorizationController::class, 'authorizeAction'])
            ->middleware('throttle:30,1')->name('manager-authorization.authorize');
        Route::get('/pos/pending-ewallet', [PosController::class, 'pendingEwalletIndex'])->name('pos.pending-ewallet.index');
        Route::get('/pos/pending-ewallet/{pendingEwalletVerification}', [PosController::class, 'showPendingEwallet'])->name('pos.pending-ewallet.show');
        Route::get('/pos/pending-card', [PosController::class, 'pendingCardIndex'])->name('pos.pending-card.index');
        Route::get('/pos/pending-card/{pendingCardVerification}', [PosController::class, 'showPendingCard'])->name('pos.pending-card.show');
        Route::post('/pos/pending-ewallet/{pendingEwalletVerification}/verify', [PosController::class, 'verifyPendingEwallet'])->name('pos.pending-ewallet.verify');
        Route::post('/pos/pending-ewallet/{pendingEwalletVerification}/reject', [PosController::class, 'rejectPendingEwallet'])->name('pos.pending-ewallet.reject');
        Route::post('/pos/pending-card/{pendingCardVerification}/verify', [PosController::class, 'verifyPendingCard'])->name('pos.pending-card.verify');
        Route::post('/pos/pending-card/{pendingCardVerification}/reject', [PosController::class, 'rejectPendingCard'])->name('pos.pending-card.reject');
        Route::middleware('role:Manager')->group(function () {
            Route::post('/pos/pending-ewallet/{pendingEwalletVerification}/reveal', [PosController::class, 'revealPendingEwalletReference'])->middleware('throttle:30,1')->name('pos.pending-ewallet.reveal');
            Route::post('/pos/pending-card/{pendingCardVerification}/reveal', [PosController::class, 'revealPendingCardReference'])->middleware('throttle:30,1')->name('pos.pending-card.reveal');
        });
        Route::post('/pos/check-coupon', [PosController::class, 'checkCoupon'])->name('pos.check-coupon');
        Route::get('/pos/{saleTransaction}', [PosController::class, 'show'])->name('pos.show');
        Route::post('/pos/{saleTransaction}/refund', [PosController::class, 'refund'])->name('pos.refund');
        Route::get('/pos/refund/{refund}/slip', [PosController::class, 'slip'])->name('pos.refund.slip');
        Route::post('/cash-drawer/open', [CashDrawerController::class, 'open'])->name('cash-drawer.open');
        Route::post('/cash-drawer/close', [CashDrawerController::class, 'close'])->name('cash-drawer.close');
        Route::get('/cash-drawer/status', [CashDrawerController::class, 'status'])->name('cash-drawer.status');
        Route::resource('customers', CustomerController::class)->except('show');
    });

    // Manager-only: the two previous `role:Manager` groups are merged into one so the
    // gate is declared once. Static routes stay ABOVE the resource catch-alls (e.g.
    // products/generate-barcode above Route::resource('products')).
    Route::middleware('role:Manager')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        // Static routes MUST stay above the products resource (see comment above).
        Route::get('/products/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generate-barcode');
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
        Route::get('/settings/vat', [VatSettingsController::class, 'edit'])->name('vat-settings.edit');
        Route::put('/settings/vat', [VatSettingsController::class, 'update'])->name('vat-settings.update');
        Route::get('/settings/receipt', [ReceiptSettingsController::class, 'edit'])->name('receipt-settings.edit');
        Route::put('/settings/receipt', [ReceiptSettingsController::class, 'update'])->name('receipt-settings.update');
        // Cash drawer review (manager-only)
        Route::get('/cash-drawers', [CashDrawerController::class, 'index'])->name('cash-drawers.index');
        // Promotion engine (manager-only, mirrors the discounts CRUD gate). No static
        // routes here yet; if any are added they must go ABOVE these resource catch-alls.
        Route::resource('promotions', PromotionController::class)->except('show');
        Route::resource('coupons', CouponController::class)->except('show');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
