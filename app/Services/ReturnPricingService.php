<?php

namespace App\Services;

use App\Models\SaleDetail;
use App\Models\SaleTransaction;
use Illuminate\Validation\ValidationException;

class ReturnPricingService
{
    /** Allocate paid cents deterministically, then refund cumulative entitlement minus earlier returns. */
    public function quote(SaleTransaction $sale, array $items, bool $lock = false): array
    {
        $query = $sale->saleDetails()->with('product')->orderBy('sale_detail_id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $details = $query->get()->keyBy('sale_detail_id');
        if (! $items) {
            $items = $details->map(fn ($d) => $d->refundableQuantity())->all();
        }
        $totalCents = (int) round((float) $sale->total_amount * 100);
        $runningSubtotal = 0;
        $allocated = 0;
        $paid = [];
        foreach ($details as $id => $detail) {
            $runningSubtotal += (float) $detail->subtotal;
            $through = (float) $sale->subtotal > 0 ? (int) round($totalCents * $runningSubtotal / (float) $sale->subtotal) : 0;
            $paid[$id] = $through - $allocated;
            $allocated = $through;
        }
        $lines = [];
        $selected = [];
        $sum = 0;
        foreach ($items as $id => $quantity) {
            if (! is_numeric($quantity) || ! is_finite((float) $quantity) || abs((float) $quantity - round((float) $quantity, 3)) > .0000001) {
                throw ValidationException::withMessages(['items' => 'Return quantities must be numbers with at most three decimal places.']);
            }
            $qty = round((float) $quantity, 3);
            if ($qty <= 0) {
                continue;
            }
            $detail = $details->get($id);
            if (! $detail || $qty > $detail->refundableQuantity() + .0000001) {
                throw ValidationException::withMessages(['items' => 'Returned quantity exceeds the remaining original purchase.']);
            }
            $priorQty = (float) $detail->refundItems()->sum('quantity');
            $priorCents = (int) round((float) $detail->refundItems()->sum('amount') * 100);
            $cents = max(0, (int) round($paid[$id] * ($priorQty + $qty) / max(.001, (float) $detail->quantity)) - $priorCents);
            $lines[] = ['detail' => $detail, 'qty' => $qty, 'amount' => $cents / 100];
            $selected[$id] = $qty;
            $sum += $cents;
        }
        if (! $lines) {
            throw ValidationException::withMessages(['items' => 'Select at least one returned quantity.']);
        }
        $full = $details->every(fn (SaleDetail $d, $id) => ($selected[$id] ?? 0) + .0000001 >= $d->refundableQuantity());
        $remaining = max(0, $totalCents - (int) round((float) $sale->refunds()->sum('refund_amount') * 100));
        $target = $full ? $remaining : min($remaining, $sum);
        $adjust = $target - $sum;
        for ($i = count($lines) - 1; $i >= 0 && $adjust !== 0; $i--) {
            $cents = (int) round($lines[$i]['amount'] * 100);
            $delta = $adjust > 0 ? $adjust : max(-$cents, $adjust);
            $lines[$i]['amount'] = ($cents + $delta) / 100;
            $adjust -= $delta;
        }

        return ['lines' => $lines, 'amount' => $target / 100, 'is_full' => $full];
    }
}
