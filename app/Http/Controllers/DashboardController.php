<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleDetail;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $employee = auth()->user()->loadMissing('role');
        $modules = $employee->allowedModules();

        $stats = [];

        // === Sales Stats (Cashier, Manager, Admin) ===
        if (in_array('pos.index', $modules)) {
            $today = Carbon::today();

            $stats['today_sales'] = SaleTransaction::whereDate('transaction_date', $today)->count();
            $stats['today_revenue'] = SaleTransaction::whereDate('transaction_date', $today)->sum('total_amount');
            $stats['avg_transaction'] = SaleTransaction::whereDate('transaction_date', $today)->avg('total_amount') ?? 0;

            $stats['total_sales'] = SaleTransaction::count();
            $stats['total_revenue'] = SaleTransaction::sum('total_amount');

            // Last 7 days trend
            $stats['weekly_trend'] = SaleTransaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
                ->where('transaction_date', '>=', Carbon::now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Payment method breakdown (today)
            $stats['payment_methods'] = SaleTransaction::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->whereDate('transaction_date', $today)
                ->groupBy('payment_method')
                ->get();

            // Top selling products (all time)
            $stats['top_products'] = SaleDetail::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
                ->groupBy('product_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->with('product')
                ->get();

            // Top categories
            $stats['top_categories'] = SaleDetail::join('product', 'sale_details.product_id', '=', 'product.product_id')
                ->join('category', 'product.category_id', '=', 'category.category_id')
                ->select('category.category_name', DB::raw('SUM(sale_details.quantity) as total_qty'), DB::raw('SUM(sale_details.subtotal) as total_revenue'))
                ->groupBy('category.category_name')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get();

            // Recent transactions
            $stats['recent_sales'] = SaleTransaction::with(['customer', 'employee'])
                ->latest('transaction_date')
                ->limit(5)
                ->get();
        }

        // === Inventory Stats (Manager, Admin) ===
        if (in_array('products.index', $modules)) {
            $stats['products'] = Product::count();
            $stats['categories'] = Category::count();
            $stats['suppliers'] = Supplier::count();

            // Low stock (below reorder level)
            $stats['low_stock_items'] = DB::table('inventory')
                ->join('product', 'inventory.product_id', '=', 'product.product_id')
                ->whereColumn('inventory.stock_quantity', '<=', 'product.reorder_level')
                ->whereNotNull('product.reorder_level')
                ->select('inventory.*', 'product.product_name', 'product.reorder_level')
                ->get();

            $stats['low_stock_count'] = count($stats['low_stock_items']);

            // Out of stock
            $stats['out_of_stock'] = DB::table('inventory')
                ->join('product', 'inventory.product_id', '=', 'product.product_id')
                ->where('inventory.stock_quantity', 0)
                ->select('inventory.*', 'product.product_name')
                ->get();

            $stats['out_of_stock_count'] = count($stats['out_of_stock']);
        }

        // === Customer Stats (Cashier, Manager, Admin) ===
        if (in_array('customers.index', $modules)) {
            $stats['customers'] = Customer::count();
            $stats['active_customers'] = Customer::where('customer_status', 'active')->count();
        }

        // === Employee Stats (Admin) ===
        if (in_array('employees.index', $modules)) {
            $stats['employees'] = Employee::count();
            $stats['active_employees'] = Employee::where('status', 'active')->count();
        }

        // === Purchase Orders (Manager, Admin) ===
        if (in_array('purchase-orders.index', $modules)) {
            $stats['pending_orders'] = PurchaseOrder::where('status', 'pending')->count();
            $stats['received_orders'] = PurchaseOrder::where('status', 'received')->count();
        }

        // === Audit Log (Admin) ===
        if (in_array('audit-logs.index', $modules)) {
            $stats['recent_activity'] = AuditLog::with('employee')
                ->latest('action_timestamp')
                ->limit(8)
                ->get();
        }

        // === Alerts ===
        $alerts = [];
        if (($stats['out_of_stock_count'] ?? 0) > 0) {
            $alerts[] = ['type' => 'danger', 'message' => $stats['out_of_stock_count'] . ' product(s) are out of stock'];
        }
        if (($stats['low_stock_count'] ?? 0) > 0) {
            $alerts[] = ['type' => 'warning', 'message' => $stats['low_stock_count'] . ' product(s) are below reorder level'];
        }
        if (($stats['pending_orders'] ?? 0) > 0) {
            $alerts[] = ['type' => 'info', 'message' => $stats['pending_orders'] . ' purchase order(s) awaiting delivery'];
        }

        return view('dashboard', compact('employee', 'modules', 'stats', 'alerts'));
    }
}
