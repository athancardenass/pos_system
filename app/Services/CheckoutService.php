<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PromotionService $promotions,
        private readonly CashDrawerService $cashDrawer,
    ) {
    }

    /**
     * Execute atomic, server-authoritative checkout transaction.
     *
     * @param  array{
     *     customer_id: ?int,
     *     discount_id: ?int,
     *     coupon_code: ?string,
     *     payment_method: string,
     *     amount_paid: float|int|string,
     *     items: list<array{product_id: int, quantity: float|int|string}>,
     *     idempotency_key?: ?string
     * }  $data
     * @throws ValidationException
     */
    public function checkout(array $data, int $employeeId): SaleTransaction
    {
        return DB::transaction(function () use ($data, $employeeId): SaleTransaction {
            $subtotal = 0;
            $lines = [];
            $promoLines = [];

            // Aggregate requested qty per product to prevent overselling on duplicate items[] lines
            $requested = [];
            foreach ($data['items'] as $item) {
                $pId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];
                $requested[$pId] = ($requested[$pId] ?? 0) + $qty;
            }

            foreach ($data['items'] as $item) {
                $pId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];

                $product = Product::query()->with('inventory')->lockForUpdate()->findOrFail($pId);
                $stock = $product->stockQuantity();

                if ($requested[$pId] > $stock) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->product_name} (available: {$stock}, requested: {$requested[$pId]}).",
                    ]);
                }

                $lineSubtotal = round((float) $product->unit_price * $qty, 2);
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $product->unit_price,
                    'subtotal' => $lineSubtotal,
                ];

                $promoLines[] = [
                    'product_id' => (int) $product->product_id,
                    'category_id' => $product->category_id ? (int) $product->category_id : null,
                    'quantity' => $qty,
                    'unit_price' => (float) $product->unit_price,
                ];
            }

            $subtotal = round($subtotal, 2);

            // 1. Auto-promotions
            $promoResult = $this->promotions->applyPromotions($promoLines, $subtotal);
            $promoDiscount = $promoResult['total_discount'];
            $running = round($subtotal - $promoDiscount, 2);

            // 2. Legacy manual discount
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
                $running = round($discount->applyTo($running), 2);
            }

            // 3. Coupon
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
            $amountPaid = (float) $data['amount_paid'];

            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid is less than the total due.',
                ]);
            }

            $sale = SaleTransaction::query()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'employee_id' => $employeeId,
                'discount_id' => $discount?->discount_id,
                'transaction_date' => now(),
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'promo_discount' => $promoDiscount,
                'coupon_discount' => $couponDiscount,
                'payment_method' => $data['payment_method'],
                'status' => 'completed',
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
                'reference_number' => $data['reference_number'] ?? null,
                'payment_provider' => $data['payment_provider'] ?? null,
                'amount_paid' => $amountPaid,
                'change_amount' => round($amountPaid - $total, 2),
                'payment_date' => now(),
            ]);

            foreach ($promoResult['applied'] as $applied) {
                $sale->appliedPromotions()->create([
                    'promotion_id' => $applied['promotion_id'],
                    'amount_discounted' => $applied['amount'],
                    'snapshot' => $applied['snapshot'],
                ]);
            }

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

            AuditLogger::record('sale', 'sale_transaction', $sale->transaction_id, 'Completed sale #'.$sale->transaction_id);

            // Update cash drawer for cash payments
            if ($data['payment_method'] === 'cash') {
                $this->cashDrawer->addCash($employeeId, $total);
            }

            return $sale;
        });
    }
}
