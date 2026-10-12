<?php

namespace App\Http\Controllers;

use App\Models\SaleTransaction;
use App\Services\ExchangeService;
use App\Services\ManagerAuthorizationService;
use App\Services\RefundService;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    public function preview(Request $request, SaleTransaction $saleTransaction, ExchangeService $exchanges)
    {
        return response()->json($exchanges->preview($saleTransaction, $request->validate($this->rules(false))));
    }

    public function store(Request $request, SaleTransaction $saleTransaction, ExchangeService $exchanges, ManagerAuthorizationService $authorizations)
    {
        $data = $request->validate($this->rules(true));
        $details = $this->approvalDetails($saleTransaction, $data);
        $approval = $authorizations->requireGrant($request, 'sale_exchange', $details, $data['manager_authorization_token'] ?? null, registerId: $data['register_id'] ?? null);
        $exchange = $exchanges->exchange($saleTransaction, $data, $approval);
        $request->session()->put('pos.last_receipt_transaction_id', $exchange->replacement_sale_id);

        return redirect()->route('pos.show', $exchange->replacement_sale_id)->with('status', 'Exchange completed. Net payment difference recorded.');
    }

    public function approvalDetails(SaleTransaction $sale, array $data): array
    {
        $items = array_map(fn ($q) => round((float) $q, 3), $data['items']);
        ksort($items, SORT_NUMERIC);

        return ['sale_id' => (int) $sale->transaction_id, 'items' => $items, 'replacements' => array_map(fn ($r) => ['product_id' => (int) $r['product_id'], 'quantity' => round((float) $r['quantity'], 3)], $data['replacements']),
            'condition' => $data['condition'], 'operation_key' => $data['operation_key'], 'reason' => $data['reason'], 'settlement_reference' => $data['settlement_reference'] ?? null];
    }

    private function rules(bool $commit): array
    {
        $rules = ['items' => 'required|array|min:1|max:100', 'items.*' => 'required|numeric|min:0|decimal:0,3',
            'replacements' => 'required|array|min:1|max:100', 'replacements.*.product_id' => 'required|integer|exists:product,product_id',
            'replacements.*.quantity' => 'required|numeric|min:0.001|max:999999|decimal:0,3'];
        if ($commit) {
            $rules += ['condition' => 'required|in:resellable,damaged', 'reason' => 'required|in:'.implode(',', array_keys(RefundService::REASONS)),
                'operation_key' => 'required|uuid', 'settlement_confirmed' => 'required|accepted', 'expected_difference' => 'required|numeric|decimal:0,2',
                'settlement_reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:255', 'manager_authorization_token' => 'nullable|string|max:100', 'register_id' => 'nullable|string|max:50'];
        }

        return $rules;
    }
}
