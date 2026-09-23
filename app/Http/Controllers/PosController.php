<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {
    }

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

        $customersJson = $customers->map(fn ($c) => [
            'id' => $c->customer_id,
            'name' => $c->fullName(),
            'contact' => $c->contact_number,
            'points' => (int) $c->loyalty_points,
        ]);

        return view('pos.index', compact('products', 'customers', 'discounts', 'productsJson', 'customersJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customer,customer_id',
            'discount_id' => 'nullable|exists:discount,discount_id',
            'coupon_code' => 'nullable|string|max:40',
            'payment_method' => 'required|in:cash,card,e-wallet',
            'reference_number' => 'nullable|string|max:100',
            'payment_provider' => 'nullable|string|max:50',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,product_id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $sale = $this->checkoutService->checkout($data, (int) auth()->id());

        return redirect()->route('pos.show', $sale)->with('status', 'Sale completed.');
    }

    public function show(SaleTransaction $saleTransaction): View
    {
        $saleTransaction->load([
            'customer', 'employee', 'discount', 'payment', 'receipt',
            'refunds.employee', 'refunds.items',
            'appliedPromotions.promotion', 'couponRedemptions.coupon',
            // Product AND the refunded-qty aggregate ride along on the lines, so the
            // receipt loop's refundableQuantity() calls cost zero extra queries (N+1).
            'saleDetails' => fn ($query) => $query
                ->with('product')
                ->withSum('refundItems as refunded_qty', 'quantity'),
        ]);

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
