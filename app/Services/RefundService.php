<?php

namespace App\Services;

use App\Models\SaleDetail;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {
    }
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
     * @param  array<int,int>  $items  sale_detail_id => quantity to refund (empty = full sale)
     *
     * @throws ValidationException on invalid input or permission denial
     */
    public function refund(SaleTransaction $sale, array $items, string $reason, ?string $notes = null): SaleRefund
    {
        $employee = auth()->user();

        // Role gate: Cashiers may only refund their own sales; Managers can refund any sale.
        // (Admin role merged into Manager — see CHANGELOG 2026-09-08.)
        $isManager = $employee->hasRole('Manager');
        if ($employee->hasRole('Cashier') && ! $isManager
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
            if (! $isManager) {
                throw ValidationException::withMessages([
                    'refund' => 'This sale is older than '.self::WINDOW_DAYS.' days — only a manager can refund it.',
                ]);
            }
            $windowOverride = true;
        }

        return DB::transaction(function () use ($sale, $items, $reason, $notes, $employee, $windowOverride): SaleRefund {
            // Lock + reload the sale so two concurrent refunds serialize (TOCTOU-safe).
            $sale = SaleTransaction::query()->lockForUpdate()->findOrFail($sale->transaction_id);

            if ($sale->isFullyRefunded()) {
                throw ValidationException::withMessages(['refund' => 'This sale is already fully refunded.']);
            }

            $details = $sale->saleDetails()->lockForUpdate()->get()->keyBy('sale_detail_id');

            // Empty items => full refund of everything still refundable.
            if (empty($items)) {
                $items = $details->map(fn (SaleDetail $d) => $d->refundableQuantity())->all();
            }
            $items = array_filter(array_map('intval', $items), fn ($q) => $q > 0);

            if (empty($items)) {
                throw ValidationException::withMessages(['items' => 'Nothing selected to refund.']);
            }

            // Discount ratio: what fraction of the subtotal was actually paid.
            $paidRatio = $sale->subtotal > 0 ? (float) $sale->total_amount / (float) $sale->subtotal : 1.0;

            $refundAmount = 0.0;
            $lines = [];

            foreach ($items as $detailId => $qty) {
                $detail = $details->get($detailId);
                if (! $detail) {
                    throw ValidationException::withMessages(['items' => 'Invalid refund line.']);
                }
                if ($qty > $detail->refundableQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => "Only {$detail->refundableQuantity()} of {$detail->product?->product_name} remaining to refund.",
                    ]);
                }

                // Pro-rate: line paid-share × refunded fraction of that line.
                $linePaid = round((float) $detail->subtotal * $paidRatio, 2);
                $amount = round($linePaid * ($qty / max(1, (int) $detail->quantity)), 2);
                $refundAmount += $amount;

                $lines[] = ['detail' => $detail, 'qty' => $qty, 'amount' => $amount];
            }

            $refundAmount = round($refundAmount, 2);

            // Full refund = this event consumes every remaining refundable quantity.
            $isFull = $details->every(
                fn (SaleDetail $d, $id) => ($items[$id] ?? 0) >= $d->refundableQuantity()
            );

            $refund = SaleRefund::query()->create([
                'transaction_id' => $sale->transaction_id,
                'employee_id' => $employee?->employee_id,
                'refund_amount' => $refundAmount,
                'reason' => $reason,
                'notes' => $notes,
                'is_full_refund' => $isFull,
                'window_override' => $windowOverride,
                'refunded_at' => now(),
            ]);

            foreach ($lines as $line) {
                $refund->items()->create([
                    'sale_detail_id' => $line['detail']->sale_detail_id,
                    'quantity' => $line['qty'],
                    'amount' => $line['amount'],
                ]);

                // Restore the refunded quantity to on-hand stock.
                $this->inventory->adjustStock(
                    $line['detail']->product_id,
                    $line['qty'],
                    'refund',
                    'sale_refund',
                    $refund->refund_id,
                    $reason,
                );
            }

            // Reverse loyalty + total purchases proportionally to the refunded amount.
            if ($sale->customer) {
                $customer = $sale->customer;
                $customer->total_purchases = max(0, (float) $customer->total_purchases - $refundAmount);
                $customer->loyalty_points = max(0, (int) $customer->loyalty_points - (int) floor($refundAmount / 100));
                $customer->save();
            }

            if ($isFull) {
                $sale->update(['status' => 'refunded', 'refunded_at' => now()]);
            }

            AuditLogger::record(
                $isFull ? 'refund' : 'partial_refund',
                'sale_transaction',
                $sale->transaction_id,
                ($isFull ? 'Refunded' : 'Partial refund of').' sale #'.$sale->transaction_id
                    .' (₱'.number_format($refundAmount, 2).', reason: '.$reason.')',
            );

            return $refund;
        });
    }
}
