<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\PendingCardVerification;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardVerificationService
{
    private const RESERVATION_MINUTES = 60;

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CheckoutService $checkout,
        private readonly PaymentReferenceService $paymentReferences,
    ) {
    }

    public function submit(array $checkoutPayload, int $employeeId): PendingCardVerification
    {
        $this->expireStale();

        $provider = trim((string) ($checkoutPayload['payment_provider'] ?? ''));
        $reference = trim((string) ($checkoutPayload['reference_number'] ?? ''));
        $cardLast4 = $checkoutPayload['card_last4'] ?? null;
        if (! $cardLast4 && preg_match('/\s+\(\*{4}\s+(\d{4})\)$/', $reference, $matches)) {
            $cardLast4 = $matches[1];
        }
        $idempotencyKey = isset($checkoutPayload['idempotency_key']) ? (string) $checkoutPayload['idempotency_key'] : null;
        $fingerprint = $this->paymentReferences->fingerprint('card', $provider, $reference);
        $ciphertext = $this->paymentReferences->encrypt($reference);
        unset($checkoutPayload['reference_number'], $checkoutPayload['card_last4'], $checkoutPayload['idempotency_key']);
        $checkoutPayload['payment_provider'] = $provider;
        $quantities = $this->aggregateQuantities($checkoutPayload['items'] ?? []);

        try {
            return DB::transaction(function () use (
                $checkoutPayload,
                $employeeId,
                $provider,
                $cardLast4,
                $fingerprint,
                $ciphertext,
                $quantities,
                $idempotencyKey,
            ): PendingCardVerification {
                if ($idempotencyKey !== null && ($existing = $this->findPendingByIdempotencyKey($idempotencyKey))) {
                    $this->ensureSameCashier($existing, $employeeId);

                    return $existing;
                }

                if ($this->paymentReferences->alreadyUsed('card', $provider, $fingerprint)) {
                    throw ValidationException::withMessages([
                        'reference_number' => 'This Card approval code has already been used for this card network.',
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
                $pending = PendingCardVerification::query()->create([
                    'employee_id' => $employeeId,
                    'checkout_idempotency_key' => $idempotencyKey,
                    'payment_provider' => $provider,
                    'reference_ciphertext' => $ciphertext,
                    'reference_fingerprint' => $fingerprint,
                    'card_last4' => $cardLast4,
                    'submitted_amount' => $checkoutPayload['amount_paid'],
                    'checkout_payload' => $checkoutPayload,
                    'status' => PendingCardVerification::STATUS_PENDING,
                    'expires_at' => now()->addMinutes(self::RESERVATION_MINUTES),
                ]);

                $this->paymentReferences->reserve(
                    'card',
                    $provider,
                    $fingerprint,
                    'pending_card_verifications',
                    (int) $pending->id,
                );

                foreach ($quantities as $productId => $quantity) {
                    $this->inventory->adjustStock(
                        (int) $productId,
                        -$quantity,
                        'adjustment',
                        'pending_card_verification',
                        (int) $pending->id,
                        'Reserved while awaiting manual Card terminal verification',
                    );
                }

                AuditLogger::recordSensitive(
                    'card_payment_pending',
                    $employeeId,
                    null,
                    (string) ($checkoutPayload['register_id'] ?? ''),
                    ['payment_method' => 'card', 'provider' => $provider, 'amount' => $checkoutPayload['amount_paid']],
                    'pending_card_verifications',
                    (int) $pending->id,
                    "Card payment from {$provider} submitted for manager verification.",
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
                && $this->paymentReferences->alreadyUsed('card', $provider, $fingerprint)) {
                throw ValidationException::withMessages([
                    'reference_number' => 'This Card approval code has already been used for this card network.',
                ]);
            }

            throw $exception;
        }
    }

    public function findPendingByIdempotencyKey(string $idempotencyKey): ?PendingCardVerification
    {
        return PendingCardVerification::query()
            ->with('sale')
            ->where('checkout_idempotency_key', $idempotencyKey)
            ->first();
    }

    public function pendingCount(?int $employeeId = null): int
    {
        $this->expireStale();

        return PendingCardVerification::query()
            ->where('status', PendingCardVerification::STATUS_PENDING)
            ->when($employeeId !== null, fn ($query) => $query->where('employee_id', $employeeId))
            ->count();
    }

    public function expireStale(): void
    {
        DB::transaction(function (): void {
            $expired = PendingCardVerification::query()
                ->where('status', PendingCardVerification::STATUS_PENDING)
                ->where('expires_at', '<=', now())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($expired as $pending) {
                $this->releaseReservations($pending, 'Released after Card terminal verification request expired');
                $pending->status = PendingCardVerification::STATUS_EXPIRED;
                $pending->resolution_note = 'Verification window expired; inventory reservation released.';
                $pending->save();

                AuditLogger::recordSensitive(
                    'card_payment_expired',
                    (int) $pending->employee_id,
                    null,
                    (string) data_get($pending->checkout_payload, 'register_id', ''),
                    ['payment_method' => 'card', 'provider' => $pending->payment_provider, 'amount' => $pending->submitted_amount],
                    'pending_card_verifications',
                    (int) $pending->id,
                    'Card verification request expired; reserved inventory was released.',
                );
            }
        });
    }

    public function verify(PendingCardVerification $pending, int $managerId): SaleTransaction
    {
        $this->expireStale();

        return DB::transaction(function () use ($pending, $managerId): SaleTransaction {
            $locked = PendingCardVerification::query()->lockForUpdate()->findOrFail($pending->id);
            if ($locked->status === PendingCardVerification::STATUS_VERIFIED && $locked->sale_transaction_id) {
                return $locked->sale()->firstOrFail();
            }
            $this->ensurePending($locked);

            $this->releaseReservations($locked, 'Released reservation before completing verified Card sale');
            $checkoutPayload = $locked->checkout_payload;
            $checkoutPayload['reference_number'] = $locked->revealedReference();
            $checkoutPayload['payment_provider'] = $locked->payment_provider;
            $checkoutPayload['card_last4'] = $locked->card_last4;
            if ($locked->checkout_idempotency_key) {
                $checkoutPayload['idempotency_key'] = $locked->checkout_idempotency_key;
            }
            $sale = $this->checkout->checkout($checkoutPayload, (int) $locked->employee_id);

            $locked->status = PendingCardVerification::STATUS_VERIFIED;
            $locked->verified_by_employee_id = $managerId;
            $locked->verified_at = now();
            $locked->sale_transaction_id = $sale->transaction_id;
            $locked->resolution_note = 'Manager confirmed the approval code and amount against the Card terminal record.';
            $locked->save();

            AuditLogger::recordSensitive(
                'card_payment_verified',
                (int) $locked->employee_id,
                $managerId,
                (string) data_get($locked->checkout_payload, 'register_id', ''),
                ['payment_method' => 'card', 'provider' => $locked->payment_provider, 'amount' => $locked->submitted_amount, 'sale_id' => (int) $sale->transaction_id],
                'pending_card_verifications',
                (int) $locked->id,
                "Verified a {$locked->payment_provider} Card payment and completed sale #{$sale->transaction_id}.",
            );

            return $sale;
        });
    }

    public function reject(PendingCardVerification $pending, int $managerId, string $reason): void
    {
        $this->expireStale();

        DB::transaction(function () use ($pending, $managerId, $reason): void {
            $locked = PendingCardVerification::query()->lockForUpdate()->findOrFail($pending->id);
            $this->ensurePending($locked);

            $this->releaseReservations($locked, 'Released because Card terminal payment was not verified');
            $reference = $locked->revealedReference();
            $safeReason = $this->paymentReferences->redactText(
                trim($reason),
                $reference,
                $this->paymentReferences->maskedApprovalCode($reference),
            );
            $locked->status = PendingCardVerification::STATUS_REJECTED;
            $locked->rejected_by_employee_id = $managerId;
            $locked->rejected_at = now();
            $locked->resolution_note = $safeReason;
            $locked->save();

            AuditLogger::recordSensitive(
                'card_payment_rejected',
                (int) $locked->employee_id,
                $managerId,
                (string) data_get($locked->checkout_payload, 'register_id', ''),
                ['payment_method' => 'card', 'provider' => $locked->payment_provider, 'amount' => $locked->submitted_amount, 'reason' => $locked->resolution_note],
                'pending_card_verifications',
                (int) $locked->id,
                "Rejected a {$locked->payment_provider} Card payment: {$locked->resolution_note}",
            );
        });
    }

    private function releaseReservations(PendingCardVerification $pending, string $reason): void
    {
        foreach ($this->aggregateQuantities($pending->checkout_payload['items'] ?? []) as $productId => $quantity) {
            $this->inventory->adjustStock(
                (int) $productId,
                $quantity,
                'adjustment',
                'pending_card_verification',
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

    private function ensureSameCashier(PendingCardVerification $pending, int $employeeId): void
    {
        if ((int) $pending->employee_id !== $employeeId) {
            throw new AuthorizationException('This checkout attempt belongs to another cashier.');
        }
    }

    private function ensurePending(PendingCardVerification $pending): void
    {
        if ($pending->status !== PendingCardVerification::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'verification' => 'This Card request is no longer awaiting manager verification.',
            ]);
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key');
    }
}
