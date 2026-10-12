<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly InventoryOutboxService $inventoryOutbox,
        private readonly CustomerOutboxService $customerOutbox,
    ) {}

    public const REASONS = [
        'damaged' => 'Damaged item',
        'wrong_item' => 'Wrong item',
        'changed_mind' => 'Customer changed mind',
        'defective' => 'Defective',
        'other' => 'Other',
    ];

    /** Standard refund window in days. Outside it, only a Manager may refund (logged as an override). */
    public const WINDOW_DAYS = 7;

    /** Roles allowed to override the refund window or refund any sale. */
    public const MANAGER_ROLES = ['Manager'];

    /**
     * Refund a sale — full (no $items) or partial ($items = [sale_detail_id => qty]).
     *
     * Runs in a transaction with row locks (same discipline as PosController::store):
     * - locks the sale row so concurrent refunds serialize
     * - restores inventory per refunded quantity
     * - refunds the ACTUAL PAID amount (discount pro-rated across lines)
     * - reverses loyalty points + total purchases proportionally
     * - writes a sale_refund record (+ per-line sale_refund_item rows)
     * - flips sale status to 'refunded' only when every line is fully refunded
     *
     * @param  array<int,float|int|string>  $items  sale_detail_id => quantity to refund (empty = full sale)
     *
     * @throws ValidationException on invalid input or permission denial
     */
    public function refund(SaleTransaction $sale, array $items, string $reason, ?string $notes = null, ?array $authorization = null, array $options = []): SaleRefund
    {
        $employee = auth()->user();

        // Role gate: Cashiers may only refund their own sales; Managers can refund any sale.
        // (Admin role merged into Manager — see CHANGELOG 2026-09-08.)
        $isManager = $employee->hasRole('Manager');
        if (! $authorization && $employee->hasRole('Cashier') && ! $isManager
            && (int) $sale->employee_id !== (int) $employee->employee_id) {
            throw ValidationException::withMessages([
                'refund' => 'Cashiers can only refund their own sales. Ask a manager.',
            ]);
        }

        if (! array_key_exists($reason, self::REASONS)) {
            throw ValidationException::withMessages(['reason' => 'Invalid refund reason.']);
        }

        // Refund window: within WINDOW_DAYS anyone eligible may refund.
        // Beyond it, only a Manager — and the event is flagged as an override.
        $daysOld = $sale->transaction_date
            ? (int) $sale->transaction_date->diffInDays(now())
            : 0;
        $outsideWindow = $daysOld > self::WINDOW_DAYS;
        $windowOverride = false;

        if ($outsideWindow) {
            $isManager = $employee && $employee->hasRole('Manager');
            if (! $isManager && ! $authorization) {
                throw ValidationException::withMessages([
                    'refund' => 'This sale is older than '.self::WINDOW_DAYS.' days — only a manager can refund it.',
                ]);
            }
            $windowOverride = true;
        }

        ksort($items, SORT_NUMERIC);
        $condition = $options['condition'] ?? (in_array($reason, ['damaged', 'defective'], true) ? 'damaged' : 'resellable');
        if (! in_array($condition, ['damaged', 'resellable'], true)) {
            throw ValidationException::withMessages(['condition' => 'Choose the returned item condition.']);
        }
        $fingerprint = hash('sha256', json_encode([$sale->transaction_id, $employee->employee_id, $items, $reason, $notes, $condition, $options['settlement_reference'] ?? null]));

        return DB::transaction(function () use ($sale, $items, $reason, $notes, $employee, $windowOverride, $authorization, $options, $condition, $fingerprint): SaleRefund {
            $drawer = app(CashDrawerService::class)->lockOpenDrawer((int) $employee->employee_id);
            // Lock + reload the sale so two concurrent refunds serialize (TOCTOU-safe).
            $sale = SaleTransaction::query()->lockForUpdate()->findOrFail($sale->transaction_id);

            if (! empty($options['operation_key']) && $previous = SaleRefund::where('operation_key', $options['operation_key'])->lockForUpdate()->first()) {
                if ($previous->request_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages(['operation_key' => 'This refund ID has already been used with different data.']);
                }

                return $previous;
            }
            if (! in_array($sale->status, ['completed', 'refunded'], true)) {
                throw ValidationException::withMessages(['refund' => 'Only completed sales can be returned.']);
            }
            if ($sale->payment_method !== 'cash' && blank($options['settlement_reference'] ?? null) && ($options['record_movement'] ?? true)) {
                throw ValidationException::withMessages(['settlement_reference' => 'Confirm the refund in the original payment app/terminal and enter its reference.']);
            }

            if ($sale->isFullyRefunded()) {
                throw ValidationException::withMessages(['refund' => 'This sale is already fully refunded.']);
            }
            app(LoyaltySettlementService::class)->prepare($sale);

            $quote = app(ReturnPricingService::class)->quote($sale, $items, true);
            $lines = $quote['lines'];
            $refundAmount = $quote['amount'];
            $isFull = $quote['is_full'];
            if (($options['record_movement'] ?? true) && $sale->payment_method === 'cash'
                && $refundAmount > max(0, (float) $drawer->expected_cash)) {
                throw ValidationException::withMessages(['drawer' => 'Not enough expected cash for this refund. Add Cash In or receive a drawer transfer before paying out.']);
            }
            Product::whereIn('product_id', array_map(fn ($line) => $line['detail']->product_id, $lines))->orderBy('product_id')->lockForUpdate()->get();
            usort($lines, fn ($a, $b) => $a['detail']->product_id <=> $b['detail']->product_id);
            $refund = SaleRefund::query()->create([
                'transaction_id' => $sale->transaction_id,
                'employee_id' => $employee?->employee_id,
                'refund_amount' => $refundAmount,
                'reason' => $reason,
                'notes' => $notes,
                'is_full_refund' => $isFull,
                'window_override' => $windowOverride,
                'refunded_at' => now(),
                'drawer_id' => $drawer->drawer_id,
                'payment_method' => $sale->payment_method,
                'settlement_reference' => $options['settlement_reference'] ?? null,
                'operation_key' => $options['operation_key'] ?? null,
                'request_fingerprint' => $fingerprint,
            ]);

            $restockedItems = [];
            foreach ($lines as $line) {
                $refund->items()->create([
                    'sale_detail_id' => $line['detail']->sale_detail_id,
                    'quantity' => $line['qty'],
                    'amount' => $line['amount'],
                    'condition' => $condition,
                ]);

                // Restore the refunded quantity to on-hand stock.
                if ($condition === 'resellable') {
                    $this->inventory->adjustStock(
                        $line['detail']->product_id,
                        $line['qty'],
                        'refund',
                        'sale_refund',
                        $refund->refund_id,
                        $reason,
                    );
                }
                if ($condition === 'resellable') {
                    $restockedItems[] = [
                        'product_id' => (int) $line['detail']->product_id,
                        'quantity' => (float) $line['qty'],
                    ];
                }
            }

            $this->inventoryOutbox->recordRefundEvent(
                (int) $sale->transaction_id,
                (int) $refund->refund_id,
                $restockedItems,
            );

            if ($options['record_loyalty'] ?? true) {
                app(LoyaltySettlementService::class)->refund($sale, $refund);
            }

            if ($options['record_movement'] ?? true) {
                app(CashDrawerService::class)->record($drawer, [
                    'kind' => 'refund', 'amount' => -$refundAmount, 'payment_method' => $sale->payment_method,
                    'refund_id' => $refund->refund_id, 'reason' => $reason, 'approved_by_employee_id' => $authorization['approved_by_employee_id'] ?? $employee->employee_id,
                    'operation_key' => 'refund:'.$refund->refund_id,
                ]);
            }

            if ($isFull) {
                $sale->update(['status' => 'refunded', 'refunded_at' => now()]);
            }

            $auditDescription = ($isFull ? 'Refunded' : 'Partially refunded').' sale #'.$sale->transaction_id
                .' (₱'.number_format($refundAmount, 2).', reason: '.$reason.').';
            if ($authorization) {
                AuditLogger::recordSensitive(
                    $isFull ? 'refund' : 'partial_refund',
                    (int) $authorization['requested_by_employee_id'],
                    (int) $authorization['approved_by_employee_id'],
                    $authorization['register_id'] ?? null,
                    [
                        'refund_id' => (int) $refund->refund_id,
                        'refund_amount' => $refundAmount,
                        'reason' => $reason,
                        'notes' => filled($notes) ? $notes : null,
                        'items' => array_map(fn (array $line) => [
                            'sale_detail_id' => (int) $line['detail']->sale_detail_id,
                            'quantity' => (float) $line['qty'],
                            'amount' => (float) $line['amount'],
                        ], $lines),
                    ],
                    'sale_transaction',
                    (int) $sale->transaction_id,
                    $auditDescription,
                );
            } else {
                AuditLogger::record(
                    $isFull ? 'refund' : 'partial_refund',
                    'sale_transaction',
                    $sale->transaction_id,
                    $auditDescription,
                );
            }

            return $refund;
        });
    }
}
