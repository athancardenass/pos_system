<?php

namespace App\Services;

use App\Models\CashDrawer;
use App\Models\DrawerMovement;

class ShiftReportService
{
    public function summary(CashDrawer $drawer): array
    {
        if (is_array($drawer->report_snapshot)) {
            return $drawer->report_snapshot;
        }
        $movements = DrawerMovement::with(['sale.receipt', 'refund.sale.receipt', 'refund.items.saleDetail.product'])
            ->where('drawer_id', $drawer->drawer_id)->orderBy('id')->get();
        $cashIn = 0;
        $cashOut = 0;
        $gross = 0;
        $refunds = 0;
        $payments = ['cash' => 0.0, 'card' => 0.0, 'e-wallet' => 0.0];
        $salesByMethod = $payments;
        $refundsByMethod = $payments;
        $byKind = [];
        $rows = [];
        foreach ($movements as $m) {
            $amount = (float) $m->amount;
            $payments[$m->payment_method] = round(($payments[$m->payment_method] ?? 0) + $amount, 2);
            if ($m->payment_method === 'cash') {
                $cashIn += max(0, $amount);
                $cashOut += max(0, -$amount);
                $byKind[$m->kind] = round(($byKind[$m->kind] ?? 0) + $amount, 2);
            }
            if ($m->kind === 'sale') {
                $gross += $m->sale ? (float) $m->sale->total_amount : $amount;
            }
            if ($m->kind === 'refund') {
                $refunds += $m->refund ? (float) $m->refund->refund_amount : -$amount;
            }
            if ($m->kind === 'exchange') {
                $gross += (float) $m->sale?->total_amount;
                $refunds += (float) $m->refund?->refund_amount;
            }
            if (in_array($m->kind, ['sale', 'exchange'], true)) {
                $salesByMethod[$m->payment_method] += ($m->sale ? (float) $m->sale->total_amount : $amount);
            }
            if (in_array($m->kind, ['refund', 'exchange'], true)) {
                $refundsByMethod[$m->payment_method] += ($m->refund ? (float) $m->refund->refund_amount : -$amount);
            }
            $rows[] = [
                'id' => $m->id, 'kind' => $m->kind, 'amount' => $amount, 'payment_method' => $m->payment_method,
                'reason' => $m->reason, 'approved_by_employee_id' => $m->approved_by_employee_id,
                'sale_id' => $m->sale_id, 'refund_id' => $m->refund_id, 'transfer_id' => $m->transfer_id,
                'receipt' => $m->sale?->receipt?->receipt_number, 'original_receipt' => $m->refund?->sale?->receipt?->receipt_number,
                'created_at' => $m->created_at?->toIso8601String(),
                'returned_items' => $m->refund?->items->map(fn ($i) => ['name' => $i->saleDetail?->product?->product_name, 'quantity' => $i->quantity, 'condition' => $i->condition])->all() ?? [],
            ];
        }
        $start = (float) ($drawer->ledger_start_cash ?? $drawer->opening_cash);

        return [
            'drawer_id' => $drawer->drawer_id, 'cashier' => $drawer->employee?->displayName() ?? 'Unknown cashier',
            'opened_at' => $drawer->opened_at?->toIso8601String(), 'closed_at' => $drawer->closed_at?->toIso8601String(),
            'duration_seconds' => max(0, (int) $drawer->opened_at?->diffInSeconds($drawer->closed_at ?? now())),
            'opening_cash' => (float) $drawer->opening_cash, 'brought_forward' => round($start - (float) $drawer->opening_cash, 2),
            'cash_in' => round($cashIn, 2), 'cash_out' => round($cashOut, 2),
            'expected_cash' => round($start + $cashIn - $cashOut, 2), 'actual_cash' => $drawer->actual_cash === null ? null : (float) $drawer->actual_cash,
            'difference' => $drawer->difference === null ? null : (float) $drawer->difference,
            'gross_sales' => round($gross, 2), 'refunds' => round($refunds, 2), 'net_sales' => round($gross - $refunds, 2),
            'payment_totals' => $payments, 'cash_by_kind' => $byKind, 'movements' => $rows,
            'sales_by_method' => $salesByMethod, 'refunds_by_method' => $refundsByMethod,
            'reconciliation_notes' => $drawer->reconciliation_notes, 'legacy' => ! $movements->count() && $drawer->status === 'closed' && ! $drawer->report_snapshot,
        ];
    }
}
