<?php

namespace App\Services;

use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Tracks paid value through exchanged receipts so later refunds address real CRM awards. */
class LoyaltySettlementService
{
    public function __construct(private readonly CustomerOutboxService $outbox) {}

    public function prepare(SaleTransaction $sale): void
    {
        if (! $sale->customer_id || DB::table('loyalty_credit_allocations')->where('sale_id', $sale->transaction_id)->exists()) {
            return;
        }
        DB::table('loyalty_credit_allocations')->insert(['sale_id' => $sale->transaction_id, 'origin_sale_id' => $sale->transaction_id,
            'remaining_amount' => max(0, round((float) $sale->total_amount - (float) $sale->refunds()->sum('refund_amount'), 2))]);
    }

    public function refund(SaleTransaction $sale, SaleRefund $refund): void
    {
        if (! $sale->customer_id) {
            return;
        }
        foreach ($this->take($sale, (float) $refund->refund_amount) as $origin => $amount) {
            $this->outbox->recordRefundEvent($origin, $refund->refund_id, $sale->customer_id, $amount);
        }
    }

    public function exchange(SaleTransaction $original, SaleTransaction $replacement, SaleRefund $refund): void
    {
        if (! $original->customer_id) {
            return;
        }
        $credits = $this->take($original, (float) $refund->refund_amount);
        $remaining = (int) round((float) $replacement->total_amount * 100);
        foreach ($credits as $origin => $amount) {
            $cents = (int) round($amount * 100);
            $moved = min($remaining, $cents);
            $remaining -= $moved;
            DB::table('loyalty_credit_allocations')->insert(['sale_id' => $replacement->transaction_id, 'origin_sale_id' => $origin, 'remaining_amount' => $moved / 100]);
            if ($cents > $moved) {
                $this->outbox->recordRefundEvent($origin, $refund->refund_id, $original->customer_id, ($cents - $moved) / 100);
            }
        }
        if ($remaining > 0) {
            DB::table('loyalty_credit_allocations')->insert(['sale_id' => $replacement->transaction_id, 'origin_sale_id' => $replacement->transaction_id, 'remaining_amount' => $remaining / 100]);
            $this->outbox->recordSaleEvent($replacement->transaction_id, $original->customer_id, $remaining / 100);
        }
    }

    private function take(SaleTransaction $sale, float $amount): array
    {
        $remaining = (int) round($amount * 100);
        $taken = [];
        foreach (DB::table('loyalty_credit_allocations')->where('sale_id', $sale->transaction_id)->orderBy('id')->lockForUpdate()->get() as $credit) {
            $available = (int) round((float) $credit->remaining_amount * 100);
            $used = min($available, $remaining);
            if ($used > 0) {
                $taken[(int) $credit->origin_sale_id] = $used / 100;
                DB::table('loyalty_credit_allocations')->where('id', $credit->id)->update(['remaining_amount' => ($available - $used) / 100]);
                $remaining -= $used;
            }
        }
        if ($remaining > 0) {
            throw ValidationException::withMessages(['refund' => 'Loyalty credit reconciliation requires review before this return.']);
        }

        return $taken;
    }
}
