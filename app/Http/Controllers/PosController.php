<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\PromotionService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PromotionService $promotions,
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

        return view('pos.index', compact('products', 'customers', 'discounts', 'productsJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customer,customer_id',
            'discount_id' => 'nullable|exists:discount,discount_id',
            'coupon_code' => 'nullable|string|max:40',
            'payment_method' => 'required|in:cash,card,e-wallet',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,product_id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $sale = DB::transaction(function () use ($data) {
            $subtotal = 0;
            $lines = [];
            // Promotion-engine view of the cart (see PromotionService::$lines contract).
            $promoLines = [];

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
                $promoLines[] = [
                    'product_id' => (int) $product->product_id,
                    'category_id' => $product->category_id ? (int) $product->category_id : null,
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) $product->unit_price,
                ];
            }

            $subtotal = round($subtotal, 2);

            /*
             | Discount stack — one service owns all of it, in this fixed order:
             |   1. promotions (auto-applied by rule)   -> promo_discount column
             |   2. manual discount (cashier's picker)  -> not stored, reconstructed on read
             |   3. coupon (cashier-entered code)       -> coupon_discount column
             | Each step works on the remainder left by the previous one, and the final
             | total floors at 0. Unit prices in sale_details are never rewritten.
             */

            // 1. Auto-promotions.
            $promoResult = $this->promotions->applyPromotions($promoLines, $subtotal);
            $promoDiscount = $promoResult['total_discount'];
            $running = round($subtotal - $promoDiscount, 2);

            // 2. Legacy manual discount (unchanged flow, now applied to the remainder).
            $discount = null;
            if (! empty($data['discount_id'])) {
                $discount = Discount::query()->findOrFail($data['discount_id']);
                if (! $discount->isActive()) {
                    throw ValidationException::withMessages([
                        'discount_id' => 'That discount is not active today.',
                    ]);
                }
            }

            if ($discount) {
                // Only the resulting total is stored. The manual STEP is not a column —
                // SaleTransaction::manualDiscountAmount() reconstructs it for the receipt.
                $running = round($discount->applyTo($running), 2);
            }

            // 3. Coupon.
            $coupon = null;
            $couponDiscount = 0.0;
            $couponCode = trim((string) ($data['coupon_code'] ?? ''));

            if ($couponCode !== '') {
                $coupon = $this->promotions->validateCoupon(
                    $couponCode,
                    $subtotal,
                    $data['customer_id'] ?? null,
                );
                $couponDiscount = $this->promotions->couponDiscount($coupon, $running);
                $running = round($running - $couponDiscount, 2);
            }

            $total = max(0.0, round($running, 2));

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
                'promo_discount' => $promoDiscount,
                'coupon_discount' => $couponDiscount,
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($lines as $line) {
                $sale->saleDetails()->create([
                    'product_id' => $line['product']->product_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $this->inventory->adjustStock(
                    $line['product']->product_id,
                    -$line['quantity'],
                    'sale',
                    'sale_transaction',
                    $sale->transaction_id,
                );
            }

            $sale->payment()->create([
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'change_amount' => round((float) $data['amount_paid'] - $total, 2),
                'payment_date' => now(),
            ]);

            // Promotion engine audit rows + coupon spend — inside the same transaction, so
            // a failed checkout can never leave a redeemed coupon or an inflated used_count.
            foreach ($promoResult['applied'] as $applied) {
                $sale->appliedPromotions()->create([
                    'promotion_id' => $applied['promotion_id'],
                    'amount_discounted' => $applied['amount'],
                    'snapshot' => $applied['snapshot'],
                ]);
            }

            // A coupon that has nothing left to take (cart already at zero) is not spent.
            if ($coupon && $couponDiscount > 0) {
                $this->promotions->redeemCoupon($coupon, $sale, $sale->customer, $couponDiscount);
            }

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

            // Audit entry INSIDE the transaction so a committed sale can never
            // lack its audit trail (matches RefundService pattern).
            AuditLogger::record('sale', 'sale_transaction', $sale->transaction_id, 'Completed sale #'.$sale->transaction_id);

            return $sale;
        });

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
