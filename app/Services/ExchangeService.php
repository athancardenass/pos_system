<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleExchange;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExchangeService
{
    public function __construct(private readonly RefundService $refunds, private readonly CheckoutService $checkout,
        private readonly CashDrawerService $drawers, private readonly CustomerOutboxService $loyalty) {}

    public function preview(SaleTransaction $sale, array $data, bool $lock = false): array
    {
        if (! array_filter($data['items'], fn ($q) => (float) $q > 0)) {
            throw ValidationException::withMessages(['items' => 'Select a returned quantity.']);
        }
        $credit = app(ReturnPricingService::class)->quote($sale, $data['items'], $lock)['amount'];
        $replacement = 0;
        foreach ($data['replacements'] as $line) {
            $query = Product::where('is_active', true);
            if ($lock) {
                $query->lockForUpdate();
            }
            $product = $query->findOrFail($line['product_id']);
            $replacement += round((float) $product->unit_price * (float) $line['quantity'], 2);
        }

        return ['returned_value' => $credit, 'replacement_value' => round($replacement, 2), 'difference' => round($replacement - $credit, 2), 'payment_method' => $sale->payment_method];
    }

    public function exchange(SaleTransaction $sale, array $data, array $approval): SaleExchange
    {
        $employeeId = (int) auth()->id();
        $items = $data['items'];
        ksort($items, SORT_NUMERIC);
        $fingerprint = hash('sha256', json_encode([$sale->transaction_id, $employeeId, $items, $data['replacements'], $data['reason'], $data['condition'], $data['settlement_reference'] ?? null]));

        return DB::transaction(function () use ($sale, $data, $approval, $employeeId, $fingerprint) {
            $drawer = $this->drawers->lockOpenDrawer($employeeId);
            $sale = SaleTransaction::whereKey($sale->transaction_id)->lockForUpdate()->firstOrFail();
            if ($existing = SaleExchange::where('operation_key', $data['operation_key'])->lockForUpdate()->first()) {
                if ($existing->request_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages(['operation_key' => 'This exchange request ID has already been used with different data.']);
                }

                return $existing;
            }
            if (empty(array_filter($data['items'], fn ($q) => (float) $q > 0))) {
                throw ValidationException::withMessages(['items' => 'Select a returned quantity.']);
            }
            $productIds = array_merge($sale->saleDetails()->pluck('product_id')->all(), array_column($data['replacements'], 'product_id'));
            Product::whereIn('product_id', $productIds)->orderBy('product_id')->lockForUpdate()->get();
            $quote = $this->preview($sale, $data, true);
            if ($sale->payment_method === 'cash' && -$quote['difference'] > max(0, (float) $drawer->expected_cash)) {
                throw ValidationException::withMessages(['drawer' => 'Not enough expected cash for the exchange refund. Add Cash In or receive a drawer transfer first.']);
            }
            if (isset($data['expected_difference']) && abs((float) $data['expected_difference'] - $quote['difference']) > .001) {
                throw ValidationException::withMessages(['expected_difference' => 'Prices or return quantities changed. Review the exchange again.']);
            }
            if (! ($data['settlement_confirmed'] ?? false)) {
                throw ValidationException::withMessages(['settlement_confirmed' => 'Confirm the price difference has been settled.']);
            }
            if ($sale->payment_method !== 'cash' && abs($quote['difference']) > .001 && blank($data['settlement_reference'] ?? null)) {
                throw ValidationException::withMessages(['settlement_reference' => 'Enter the original payment app/terminal settlement reference.']);
            }
            $refund = $this->refunds->refund($sale, $data['items'], $data['reason'], $data['notes'] ?? null, $approval, [
                'condition' => $data['condition'], 'operation_key' => $data['operation_key'], 'settlement_reference' => $data['settlement_reference'] ?? null,
                'record_movement' => false, 'record_loyalty' => false,
            ]);
            $replacement = $this->checkout->checkout([
                'items' => $data['replacements'], 'customer_id' => $sale->customer_id, 'payment_method' => $sale->payment_method,
                'amount_paid' => $quote['replacement_value'],
                'register_id' => $approval['register_id'] ?? null,
            ], $employeeId, false, false);
            $difference = round((float) $replacement->total_amount - (float) $refund->refund_amount, 2);
            if (abs($difference - $quote['difference']) > .001) {
                throw ValidationException::withMessages(['expected_difference' => 'Settlement changed during checkout. Review and settle the new difference.']);
            }
            $exchange = SaleExchange::create(['operation_key' => $data['operation_key'], 'request_fingerprint' => $fingerprint,
                'original_sale_id' => $sale->transaction_id, 'replacement_sale_id' => $replacement->transaction_id, 'refund_id' => $refund->refund_id,
                'drawer_id' => $drawer->drawer_id, 'difference' => $difference, 'payment_method' => $sale->payment_method,
                'settlement_reference' => $data['settlement_reference'] ?? null]);
            // A return credit is not physical tender. The linked exchange documents that credit.
            $replacement->payment()->update(['amount_paid' => max(0, $difference), 'change_amount' => 0]);
            $this->drawers->record($drawer, ['kind' => 'exchange', 'amount' => $difference, 'payment_method' => $sale->payment_method,
                'sale_id' => $replacement->transaction_id, 'refund_id' => $refund->refund_id, 'reason' => $data['reason'],
                'approved_by_employee_id' => $approval['approved_by_employee_id'], 'operation_key' => 'exchange:'.$exchange->id]);
            app(LoyaltySettlementService::class)->exchange($sale, $replacement, $refund);
            AuditLogger::recordSensitive('sale_exchange', $employeeId, $approval['approved_by_employee_id'], $approval['register_id'] ?? null,
                ['exchange_id' => $exchange->id, 'difference' => $difference], 'sale_transaction', $sale->transaction_id, 'Exchanged items; recorded net settlement once.');

            return $exchange;
        });
    }
}
