<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->latest('purchase_id')
            ->paginate(15);

        return view('purchase-orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('purchase-orders.create', [
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
            'products' => Product::query()->orderBy('product_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['unit_cost'];
            }

            $order = PurchaseOrder::query()->create([
                'supplier_id' => $data['supplier_id'],
                'employee_id' => auth()->id(),
                'order_date' => $data['order_date'],
                'status' => 'pending',
                'total_amount' => round($total, 2),
            ]);

            foreach ($data['items'] as $item) {
                $order->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            return $order;
        });

        AuditLogger::record('create', 'purchase_order', $order->purchase_id, 'Created purchase order #'.$order->purchase_id);

        return redirect()->route('purchase-orders.show', $order)->with('status', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchase_order): View
    {
        $purchase_order->load(['supplier', 'employee', 'details.product']);

        return view('purchase-orders.show', ['order' => $purchase_order]);
    }

    public function receive(PurchaseOrder $purchase_order): RedirectResponse
    {
        if (! $purchase_order->isPending()) {
            return back()->with('error', 'Only pending orders can be received.');
        }

        DB::transaction(function () use ($purchase_order): void {
            $purchase_order->load('details');

            foreach ($purchase_order->details as $line) {
                $inventory = Inventory::query()->firstOrCreate(
                    ['product_id' => $line->product_id],
                    ['stock_quantity' => 0],
                );

                $inventory->stock_quantity += $line->quantity;
                $inventory->last_restocked = now();
                $inventory->save();
            }

            $purchase_order->update(['status' => 'received']);
        });

        AuditLogger::record('receive', 'purchase_order', $purchase_order->purchase_id, 'Received purchase order #'.$purchase_order->purchase_id);

        return redirect()->route('purchase-orders.show', $purchase_order)->with('status', 'Stock received.');
    }

    public function cancel(PurchaseOrder $purchase_order): RedirectResponse
    {
        if (! $purchase_order->isPending()) {
            return back()->with('error', 'Only pending orders can be cancelled.');
        }

        $purchase_order->update(['status' => 'cancelled']);

        AuditLogger::record('cancel', 'purchase_order', $purchase_order->purchase_id, 'Cancelled purchase order #'.$purchase_order->purchase_id);

        return redirect()->route('purchase-orders.show', $purchase_order)->with('status', 'Purchase order cancelled.');
    }
}
