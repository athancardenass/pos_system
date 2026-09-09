<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\AuditLogger;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('inventory')
            ->orderBy('product_name')
            ->get();

        $customers = Customer::query()
            ->where('customer_status', 'active')
            ->orderBy('last_name')
            ->get();

        $discounts = Discount::query()
            ->orderBy('discount_name')
            ->get()
            ->filter(fn (Discount $discount) => $discount->isActive())
            ->values();

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->product_id,
            'name' => $p->product_name,
            'price' => (float) $p->unit_price,
            'stock' => $p->stockQuantity(),
            'barcode' => $p->barcode,
        ]);

        return view('pos.index', compact('products', 'customers', 'discounts', 'productsJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customer,customer_id',
            'discount_id' => 'nullable|exists:discount,discount_id',
            'payment_method' => 'required|in:cash,card,e-wallet',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,product_id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $sale = DB::transaction(function () use ($data) {
            $subtotal = 0;
            $lines = [];

            // Aggregate requested qty per product so duplicate items[] lines
            // can't bypass the stock check (each line was checking the same
            // full stock, then decrementing twice -> oversell).
            $requested = [];
            foreach ($data['items'] as $item) {
                $requested[$item['product_id']] = ($requested[$item['product_id']] ?? 0) + $item['quantity'];
            }

            foreach ($data['items'] as $item) {
                $product = Product::query()->with('inventory')->lockForUpdate()->findOrFail($item['product_id']);
                $stock = $product->stockQuantity();

                if ($requested[$item['product_id']] > $stock) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->product_name} (available: {$stock}, requested: {$requested[$item['product_id']]}).",
                    ]);
                }

                $lineSubtotal = round((float) $product->unit_price * $item['quantity'], 2);
                $subtotal += $lineSubtotal;
                $lines[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->unit_price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = null;
            if (! empty($data['discount_id'])) {
                $discount = Discount::query()->findOrFail($data['discount_id']);
                if (! $discount->isActive()) {
                    throw ValidationException::withMessages([
                        'discount_id' => 'That discount is not active today.',
                    ]);
                }
            }

            $total = $discount ? $discount->applyTo($subtotal) : round($subtotal, 2);

            if ((float) $data['amount_paid'] < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid is less than the total due.',
                ]);
            }

            $sale = SaleTransaction::query()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'employee_id' => auth()->id(),
                'discount_id' => $discount?->discount_id,
                'transaction_date' => now(),
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($lines as $line) {
                $sale->saleDetails()->create([
                    'product_id' => $line['product']->product_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $inventory = Inventory::query()->firstOrCreate(
                    ['product_id' => $line['product']->product_id],
                    ['stock_quantity' => 0],
                );
                $inventory->stock_quantity -= $line['quantity'];
                $inventory->save();
            }

            $sale->payment()->create([
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'change_amount' => round((float) $data['amount_paid'] - $total, 2),
                'payment_date' => now(),
            ]);

            $sale->receipt()->create([
                'receipt_number' => 'R'.now()->format('Ymd').'-'.str_pad((string) $sale->transaction_id, 6, '0', STR_PAD_LEFT),
                'issued_date' => now(),
            ]);

            if (! empty($data['customer_id'])) {
                $customer = Customer::query()->find($data['customer_id']);
                if ($customer) {
                    $customer->total_purchases = (float) $customer->total_purchases + $total;
                    $customer->loyalty_points = (int) $customer->loyalty_points + (int) floor($total / 100);
                    $customer->save();
                }
            }

            return $sale;
        });

        AuditLogger::record('sale', 'sale_transaction', $sale->transaction_id, 'Completed sale #'.$sale->transaction_id);

        return redirect()->route('pos.show', $sale)->with('status', 'Sale completed.');
    }

    public function show(SaleTransaction $saleTransaction): View
    {
        $saleTransaction->load(['customer', 'employee', 'discount', 'saleDetails.product', 'payment', 'receipt', 'refunds.employee', 'refunds.items']);

        return view('pos.show', ['sale' => $saleTransaction]);
    }

    public function slip(\App\Models\SaleRefund $refund): View
    {
        $refund->load(['sale.receipt', 'sale.employee', 'sale.customer', 'items.saleDetail.product', 'employee']);

        return view('pos.refund-slip', ['refund' => $refund]);
    }

    public function refund(Request $request, SaleTransaction $saleTransaction, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(RefundService::REASONS)),
            'notes' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*' => 'nullable|integer|min:0',
        ]);

        // Keep only lines with qty > 0; empty => service treats as full refund.
        $items = array_filter(array_map('intval', (array) ($data['items'] ?? [])), fn ($q) => $q > 0);

        try {
            $refund = $refunds->refund($saleTransaction, $items, $data['reason'], $data['notes'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('refund') ?: $e->getMessage());
        }

        $msg = $refund->is_full_refund
            ? 'Sale fully refunded — inventory restored, points reversed.'
            : 'Partial refund of ₱'.number_format((float) $refund->refund_amount, 2).' processed.';

        return redirect()->route('pos.show', $saleTransaction)->with('status', $msg);
    }
}
