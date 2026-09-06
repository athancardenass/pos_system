<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\SaleDetail;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public const REASONS = [
        'damaged' => 'Damaged item',
        'wrong_item' => 'Wrong item',
        'changed_mind' => 'Customer changed mind',
        'defective' => 'Defective',
        'other' => 'Other',
    ];

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

        // Role gate: Cashiers may only refund their own sales; Manager/Admin any sale.
        if ($employee->hasRole('Cashier') && ! $employee->hasRole('Manager', 'Admin')
            && (int) $sale->employee_id !== (int) $employee->employee_id) {
            throw ValidationException::withMessages([
                'refund' => 'Cashiers can only refund their own sales. Ask a manager.',
            ]);
        }

        if (! array_key_exists($reason, self::REASONS)) {
            throw ValidationException::withMessages(['reason' => 'Invalid refund reason.']);
        }

        return DB::transaction(function () use ($sale, $items, $reason, $notes, $employee): SaleRefund {
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

                // Restore inventory.
                $inventory = Inventory::query()->firstOrCreate(
                    ['product_id' => $detail->product_id],
                    ['stock_quantity' => 0],
                );
                $inventory->stock_quantity += $qty;
                $inventory->save();
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
                'refunded_at' => now(),
            ]);

            foreach ($lines as $line) {
                $refund->items()->create([
                    'sale_detail_id' => $line['detail']->sale_detail_id,
                    'quantity' => $line['qty'],
                    'amount' => $line['amount'],
                ]);
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
