<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\PendingEwalletVerification;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EwalletVerificationService
{
    private const RESERVATION_MINUTES = 60;

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CheckoutService $checkout,
        private readonly PaymentReferenceService $paymentReferences,
    ) {
    }

    public function submit(array $checkoutPayload, int $employeeId): PendingEwalletVerification
    {
        $this->expireStale();

        $provider = trim((string) ($checkoutPayload['payment_provider'] ?? ''));
        $reference = trim((string) ($checkoutPayload['reference_number'] ?? ''));
        $fingerprint = $this->paymentReferences->fingerprint('e-wallet', $provider, $reference);
        $ciphertext = $this->paymentReferences->encrypt($reference);
        $idempotencyKey = isset($checkoutPayload['idempotency_key']) ? (string) $checkoutPayload['idempotency_key'] : null;
        $checkoutPayload['payment_provider'] = $provider;
        unset($checkoutPayload['reference_number']);
        unset($checkoutPayload['idempotency_key']);
        $quantities = $this->aggregateQuantities($checkoutPayload['items'] ?? []);

        try {
            return DB::transaction(function () use ($checkoutPayload, $employeeId, $provider, $fingerprint, $ciphertext, $quantities, $idempotencyKey): PendingEwalletVerification {
            if ($idempotencyKey !== null && ($existing = $this->findPendingByIdempotencyKey($idempotencyKey))) {
                $this->ensureSameCashier($existing, $employeeId);

                return $existing;
            }

            if ($this->paymentReferences->alreadyUsed('e-wallet', $provider, $fingerprint)) {
                throw ValidationException::withMessages([
                    'reference_number' => 'This e-wallet reference has already been used.',
                ]);
            }

            $itemSnapshots = [];

            foreach ($quantities as $productId => $quantity) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                $inventory = Inventory::query()->where('product_id', $productId)->lockForUpdate()->first();
                if ($idempotencyKey !== null && ($existing = $this->findPendingByIdempotencyKey($idempotencyKey))) {
                    $this->ensureSameCashier($existing, $employeeId);

                    return $existing;
                }
                $available = (float) ($inventory?->stock_quantity ?? 0);

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->product_name} (available: {$available}, requested: {$quantity}).",
                    ]);
                }

                $itemSnapshots[] = [
                    'product_id' => (int) $product->product_id,
                    'product_name' => (string) $product->product_name,
                    'unit_price' => (float) $product->unit_price,
                    'quantity' => $quantity,
                ];
            }

            $checkoutPayload['item_snapshot'] = $itemSnapshots;

            $pending = PendingEwalletVerification::query()->create([
                'employee_id' => $employeeId,
                'checkout_idempotency_key' => $idempotencyKey,
                'payment_provider' => $provider,
                'reference_number' => '',
                'reference_ciphertext' => $ciphertext,
                'reference_fingerprint' => $fingerprint,
                'submitted_amount' => $checkoutPayload['amount_paid'],
                'checkout_payload' => $checkoutPayload,
                'status' => PendingEwalletVerification::STATUS_PENDING,
                'expires_at' => now()->addMinutes(self::RESERVATION_MINUTES),
            ]);

            $this->paymentReferences->reserve(
                'e-wallet',
                $provider,
                $fingerprint,
                'pending_ewallet_verifications',
                (int) $pending->id,
            );

            foreach ($quantities as $productId => $quantity) {
                $this->inventory->adjustStock(
                    (int) $productId,
                    -$quantity,
                    'adjustment',
                    'pending_ewallet_verification',
                    (int) $pending->id,
                    'Reserved while awaiting manual e-wallet verification',
                );
            }

            AuditLogger::recordSensitive(
                'payment_pending',
                $employeeId,
                null,
                (string) ($checkoutPayload['register_id'] ?? ''),
                ['payment_method' => 'e-wallet', 'provider' => $provider, 'amount' => $checkoutPayload['amount_paid']],
                'pending_ewallet_verifications',
                (int) $pending->id,
                "E-wallet payment from {$provider} submitted for manager verification.",
            );

            return $pending;
            });
        } catch (QueryException $exception) {
            if ($idempotencyKey !== null && $this->isUniqueConstraintViolation($exception)) {
                $existing = $this->findPendingByIdempotencyKey($idempotencyKey);
                if ($existing) {
                    $this->ensureSameCashier($existing, $employeeId);

                    return $existing;
                }
            }

            if ($this->isUniqueConstraintViolation($exception)
                && $this->paymentReferences->alreadyUsed('e-wallet', $provider, $fingerprint)) {
                throw ValidationException::withMessages([
                    'reference_number' => 'This e-wallet reference is already awaiting verification or was already reviewed.',
                ]);
            }

            throw $exception;
        }
    }

    public function findPendingByIdempotencyKey(string $idempotencyKey): ?PendingEwalletVerification
    {
        return PendingEwalletVerification::query()
            ->with('sale')
            ->where('checkout_idempotency_key', $idempotencyKey)
            ->first();
    }

    private function ensureSameCashier(PendingEwalletVerification $pending, int $employeeId): void
    {
        if ((int) $pending->employee_id !== $employeeId) {
            throw new AuthorizationException('This checkout attempt belongs to another cashier.');
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key');
    }

    public function pendingCount(): int
    {
        $this->expireStale();

        return PendingEwalletVerification::query()
            ->where('status', PendingEwalletVerification::STATUS_PENDING)
            ->count();
    }

    public function expireStale(): void
    {
        DB::transaction(function (): void {
            $expired = PendingEwalletVerification::query()
                ->where('status', PendingEwalletVerification::STATUS_PENDING)
                ->where('expires_at', '<=', now())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($expired as $pending) {
                $this->releaseReservations($pending, 'Released after e-wallet verification request expired');
                $pending->status = PendingEwalletVerification::STATUS_EXPIRED;
                $pending->resolution_note = 'Verification window expired; inventory reservation released.';
                $pending->save();

                AuditLogger::recordSensitive(
                    'payment_expired',
                    (int) $pending->employee_id,
                    null,
                    (string) data_get($pending->checkout_payload, 'register_id', ''),
                    ['payment_method' => 'e-wallet', 'provider' => $pending->payment_provider, 'amount' => $pending->submitted_amount],
                    'pending_ewallet_verifications',
                    (int) $pending->id,
                    'E-wallet verification request expired; reserved inventory was released.',
                );
            }
        });
    }

    public function verify(PendingEwalletVerification $pending, int $managerId): SaleTransaction
    {
        $this->expireStale();

        return DB::transaction(function () use ($pending, $managerId): SaleTransaction {
            $locked = PendingEwalletVerification::query()->lockForUpdate()->findOrFail($pending->id);
            if ($locked->status === PendingEwalletVerification::STATUS_VERIFIED && $locked->sale_transaction_id) {
                return $locked->sale()->firstOrFail();
            }
            $this->ensurePending($locked);

            $this->releaseReservations($locked, 'Released reservation before completing verified e-wallet sale');
            $checkoutPayload = $locked->checkout_payload;
            $checkoutPayload['reference_number'] = $locked->revealedReference();
            $checkoutPayload['payment_provider'] = $locked->payment_provider;
            if ($locked->checkout_idempotency_key) {
                $checkoutPayload['idempotency_key'] = $locked->checkout_idempotency_key;
            }
            $sale = $this->checkout->checkout($checkoutPayload, (int) $locked->employee_id);

            $locked->status = PendingEwalletVerification::STATUS_VERIFIED;
            $locked->verified_by_employee_id = $managerId;
            $locked->verified_at = now();
            $locked->sale_transaction_id = $sale->transaction_id;
            $locked->resolution_note = 'Manager confirmed the completed payment in the merchant app.';
            $locked->save();

            AuditLogger::recordSensitive(
                'payment_verified',
                (int) $locked->employee_id,
                $managerId,
                (string) data_get($locked->checkout_payload, 'register_id', ''),
                ['payment_method' => 'e-wallet', 'provider' => $locked->payment_provider, 'amount' => $locked->submitted_amount, 'sale_id' => (int) $sale->transaction_id],
                'pending_ewallet_verifications',
                (int) $locked->id,
                "Verified a {$locked->payment_provider} payment and completed sale #{$sale->transaction_id}.",
            );

            return $sale;
        });
    }

    public function reject(PendingEwalletVerification $pending, int $managerId, string $reason): void
    {
        $this->expireStale();

        DB::transaction(function () use ($pending, $managerId, $reason): void {
            $locked = PendingEwalletVerification::query()->lockForUpdate()->findOrFail($pending->id);
            $this->ensurePending($locked);

            $this->releaseReservations($locked, 'Released because e-wallet payment was not verified');
            $reference = $locked->revealedReference();
            $safeReason = $this->paymentReferences->redactText(
                trim($reason),
                $reference,
                $this->paymentReferences->mask('e-wallet', $reference),
            );
            $locked->status = PendingEwalletVerification::STATUS_REJECTED;
            $locked->rejected_by_employee_id = $managerId;
            $locked->rejected_at = now();
            $locked->resolution_note = $safeReason;
            $locked->save();

            AuditLogger::recordSensitive(
                'payment_rejected',
                (int) $locked->employee_id,
                $managerId,
                (string) data_get($locked->checkout_payload, 'register_id', ''),
                ['payment_method' => 'e-wallet', 'provider' => $locked->payment_provider, 'amount' => $locked->submitted_amount, 'reason' => $locked->resolution_note],
                'pending_ewallet_verifications',
                (int) $locked->id,
                "Rejected a {$locked->payment_provider} payment: {$locked->resolution_note}",
            );
        });
    }

    private function releaseReservations(PendingEwalletVerification $pending, string $reason): void
    {
        foreach ($this->aggregateQuantities($pending->checkout_payload['items'] ?? []) as $productId => $quantity) {
            $this->inventory->adjustStock(
                (int) $productId,
                $quantity,
                'adjustment',
                'pending_ewallet_verification',
                (int) $pending->id,
                $reason,
            );
        }
    }

    private function aggregateQuantities(array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantities[$productId] = round(($quantities[$productId] ?? 0) + (float) $item['quantity'], 3);
        }

        ksort($quantities);

        return $quantities;
    }

    private function ensurePending(PendingEwalletVerification $pending): void
    {
        if ($pending->status !== PendingEwalletVerification::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'verification' => 'This e-wallet request is no longer awaiting verification.',
            ]);
        }
    }
}
