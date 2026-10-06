<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    private bool $checkoutSchemaChecked = false;

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PromotionService $promotions,
        private readonly CashDrawerService $cashDrawer,
        private readonly VatSettingsService $vatSettings,
        private readonly ReceiptNumberService $receiptNumbers,
        private readonly PaymentReferenceService $paymentReferences,
        private readonly DatabasePreflightService $databasePreflight,
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
        $this->assertDatabaseReady();

        $idempotencyKey = isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null;

        try {
            return DB::transaction(function () use ($data, $employeeId, $idempotencyKey): SaleTransaction {
            if ($idempotencyKey !== null && ($existing = $this->findExistingSale($idempotencyKey, $employeeId))) {
                return $existing;
            }

            $vatRate = $this->vatSettings->currentRate();
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
                if ($idempotencyKey !== null && ($existing = $this->findExistingSale($idempotencyKey, $employeeId))) {
                    return $existing;
                }
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
            $specialDiscountType = null;
            $specialDiscountBase = 0.0;
            $specialDiscountAmount = 0.0;
            $vatExemptionAmount = 0.0;
            $discountSnapshot = null;
            if (! empty($data['discount_id'])) {
                $discount = Discount::query()->findOrFail($data['discount_id']);
                if (! $discount->isActive()) {
                    throw ValidationException::withMessages([
                        'discount_id' => 'That discount is not active today.',
                    ]);
                }
            }

            if ($discount) {
                $specialDiscountType = $discount->specialPolicyType();
                $submittedType = $data['senior_pwd_type'] ?? null;
                if ($specialDiscountType !== $submittedType) {
                    throw ValidationException::withMessages([
                        'senior_pwd_type' => $specialDiscountType
                            ? 'Choose the correct Senior Citizen or PWD discount and provide its required name and ID.'
                            : 'Senior/PWD proof can only be used with a Senior Citizen or PWD discount.',
                    ]);
                }

                if ($specialDiscountType) {
                    $proofName = trim((string) ($data['senior_pwd_name'] ?? ''));
                    $proofId = trim((string) ($data['senior_pwd_id_number'] ?? ''));
                    if ($proofName === '') {
                        throw ValidationException::withMessages(['senior_pwd_name' => 'Enter the full name shown on the Senior Citizen/PWD ID.']);
                    }
                    if ($proofId === '') {
                        throw ValidationException::withMessages(['senior_pwd_id_number' => 'Enter the Senior Citizen/PWD ID number.']);
                    }

                    // SIMPLIFIED SENIOR/PWD POLICY: apply 20% to the VAT-exempt base
                    // across all products. Replace this calculation when the project
                    // adopts item eligibility and statutory validation rules.
                    $specialGross = $running;
                    $specialDiscountBase = config('vat.enabled', true)
                        ? round($specialGross / (1 + $vatRate), 2)
                        : round($specialGross, 2);
                    $vatExemptionAmount = round($specialGross - $specialDiscountBase, 2);
                    $specialDiscountAmount = round($specialDiscountBase * 0.20, 2);
                    $running = max(0.0, round($specialDiscountBase - $specialDiscountAmount, 2));

                    $discountSnapshot = [
                        'type' => $specialDiscountType,
                        'name' => $discount->policyDisplayName(),
                        'reference' => 'DISC-'.str_pad((string) $discount->discount_id, 3, '0', STR_PAD_LEFT),
                        'rate' => 20,
                        'base_amount' => $specialDiscountBase,
                        'amount' => $specialDiscountAmount,
                    ];
                } else {
                    $discountBase = $running;
                    $running = round($discount->applyTo($running), 2);
                    $discountSnapshot = [
                        'type' => 'standard',
                        'name' => (string) $discount->discount_name,
                        'reference' => 'DISC-'.str_pad((string) $discount->discount_id, 3, '0', STR_PAD_LEFT),
                        'rate' => $discount->discount_type === 'percentage' ? (float) $discount->discount_value : null,
                        'base_amount' => round($discountBase, 2),
                        'amount' => round($discountBase - $running, 2),
                    ];
                }
            } elseif (filled($data['senior_pwd_type'] ?? null) || filled($data['senior_pwd_name'] ?? null) || filled($data['senior_pwd_id_number'] ?? null)) {
                throw ValidationException::withMessages([
                    'discount_id' => 'Select the matching Senior Citizen or PWD discount before entering proof details.',
                ]);
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
            $taxable = config('vat.enabled', true);
            $taxSnapshot = [
                'vat_rate' => $vatRate,
                'vatable_sales' => $specialDiscountType || ! $taxable ? 0.0 : round($total / (1 + $vatRate), 2),
                'vat_amount' => $specialDiscountType || ! $taxable ? 0.0 : round($total * $vatRate / (1 + $vatRate), 2),
                'vat_exempt_sales' => $specialDiscountType ? $specialDiscountBase : 0.0,
                'vat_exemption_amount' => $specialDiscountType ? $vatExemptionAmount : 0.0,
                'vat_enabled' => $taxable,
            ];
            $amountPaid = (float) $data['amount_paid'];

            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid is less than the total due.',
                ]);
            }

            if (in_array($data['payment_method'], ['card', 'e-wallet'], true) && $amountPaid > $total) {
                // If tendered before backend auto-promotions/discounts reduced the total,
                // cap the card/e-wallet charged amount to the final transaction total.
                $amountPaid = $total;
            }

            $sale = SaleTransaction::query()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'employee_id' => $employeeId,
                'discount_id' => $discount?->discount_id,
                'transaction_date' => now(),
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'vat_rate' => $vatRate,
                'promo_discount' => $promoDiscount,
                'coupon_discount' => $couponDiscount,
                'senior_pwd_type' => $specialDiscountType,
                'senior_pwd_name' => $specialDiscountType ? trim((string) $data['senior_pwd_name']) : null,
                'senior_pwd_id_number' => $specialDiscountType ? trim((string) $data['senior_pwd_id_number']) : null,
                'discount_snapshot' => $discountSnapshot,
                'tax_snapshot' => $taxSnapshot,
                'checkout_idempotency_key' => $idempotencyKey,
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

            $paymentReference = trim((string) ($data['reference_number'] ?? ''));
            $cardLast4 = $data['card_last4'] ?? null;
            if ($data['payment_method'] === 'card' && ! $cardLast4
                && preg_match('/\s+\(\*{4}\s+(\d{4})\)$/', $paymentReference, $matches)) {
                $cardLast4 = $matches[1];
            }

            $sale->payment()->create([
                'payment_method' => $data['payment_method'],
                'reference_number' => null,
                'reference_ciphertext' => $paymentReference !== '' ? $this->paymentReferences->encrypt($paymentReference) : null,
                'reference_fingerprint' => $paymentReference !== ''
                    ? $this->paymentReferences->fingerprint(
                        (string) $data['payment_method'],
                        (string) ($data['payment_provider'] ?? ''),
                        $paymentReference,
                    )
                    : null,
                'card_last4' => $cardLast4,
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

            $this->receiptNumbers->issue($sale, $data['register_id'] ?? null);

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
        } catch (QueryException $exception) {
            if ($idempotencyKey !== null && $this->isUniqueConstraintViolation($exception)) {
                $existing = $this->findExistingSale($idempotencyKey, $employeeId);
                if ($existing) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    /** Ensure the active database can safely process this register request. */
    public function assertDatabaseReady(): void
    {
        if ($this->checkoutSchemaChecked) {
            return;
        }

        $this->databasePreflight->assertCheckoutReady();
        $this->checkoutSchemaChecked = true;
    }

    public function findExistingSale(string $idempotencyKey, int $employeeId): ?SaleTransaction
    {
        $sale = SaleTransaction::query()
            ->where('checkout_idempotency_key', $idempotencyKey)
            ->first();

        if ($sale && (int) $sale->employee_id !== $employeeId) {
            throw new AuthorizationException('This checkout attempt belongs to another cashier.');
        }

        return $sale;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key');
    }
}
