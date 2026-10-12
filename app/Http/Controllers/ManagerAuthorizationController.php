<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ManagerAuthorizationService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagerAuthorizationController extends Controller
{
    public function authorizeAction(Request $request, ManagerAuthorizationService $authorizations): JsonResponse
    {
        $requester = $request->user();
        abort_unless($requester instanceof Employee && $requester->hasRole('Cashier', 'Manager', 'Supervisor'), 403);

        $isManager = $requester->hasRole('Manager');
        $isSupervisor = $requester->hasRole('Supervisor');
        $rules = [
            'action' => ['required', 'string', Rule::in(ManagerAuthorizationService::ACTIONS)],
            'pin' => [$isManager || $isSupervisor ? 'nullable' : 'required', 'string', 'regex:/^\d{4,6}$/'],
            'register_id' => 'required|string|max:50',
            'details' => 'present|array|max:15',
            'reason' => [Rule::requiredIf(fn () => in_array($request->input('action'), ['cart_item_void', 'held_transaction_delete', 'sale_refund'], true)), 'nullable', 'string', Rule::when(fn () => in_array($request->input('action'), ['cart_item_void', 'held_transaction_delete', 'sale_refund'], true), Rule::in(array_keys(RefundService::REASONS)))],
            'notes' => 'nullable|string|max:255',
        ];
        $rules += match ($request->input('action')) {
            'cash_movement' => [
                'details.kind' => 'required|in:manual_in,manual_out', 'details.amount' => 'required|numeric|min:0.01|decimal:0,2',
                'details.reason' => 'required|string|max:255', 'details.operation_key' => 'required|uuid',
            ],
            'cash_transfer' => [
                'details.destination_drawer_id' => 'required|integer|min:1', 'details.amount' => 'required|numeric|min:0.01|decimal:0,2',
                'details.reason' => 'required|string|max:255', 'details.operation_key' => 'required|uuid',
            ],
            'cash_transfer_cancel' => ['details.transfer_id' => 'required|integer|min:1'],
            'sale_exchange' => [
                'details.sale_id' => 'required|integer|min:1', 'details.items' => 'required|array|min:1|max:100',
                'details.items.*' => 'numeric|min:0|decimal:0,3', 'details.replacements' => 'required|array|min:1|max:100',
                'details.replacements.*.product_id' => 'required|integer|min:1', 'details.replacements.*.quantity' => 'required|numeric|min:0.001|decimal:0,3',
                'details.condition' => 'required|in:resellable,damaged', 'details.operation_key' => 'required|uuid',
                'details.reason' => 'required|string|max:50', 'details.settlement_reference' => 'nullable|string|max:100',
            ],
            'cart_item_void' => [
                'details.product_id' => 'required|integer|min:1',
                'details.product_name' => 'required|string|max:100',
                'details.quantity' => 'required|numeric|min:0.001|decimal:0,3',
                'details.line_total' => 'required|numeric|min:0|decimal:0,2',
            ],
            'held_transaction_delete' => [
                'details.hold_number' => 'required|string|max:30',
                'details.item_count' => 'required|integer|min:0',
                'details.total' => 'required|numeric|min:0|decimal:0,2',
            ],
            'discount_apply' => [
                'details.discount_id' => 'required|integer|min:1',
                'details.discount_name' => 'required|string|max:100',
                'details.discount_amount' => 'required|numeric|min:0|decimal:0,2',
            ],
            'sale_refund' => [
                'details.sale_id' => 'required|integer|min:1',
                'details.items' => 'present|array|max:100',
                'details.items.*' => 'nullable|numeric|min:0|decimal:0,3',
                'details.condition' => 'nullable|in:resellable,damaged',
                'details.operation_key' => 'nullable|uuid',
                'details.settlement_reference' => 'nullable|string|max:100',
            ],
            'cash_drawer_open' => [
                'details.opening_cash' => 'required|numeric|min:0|decimal:0,2',
            ],
            'pending_card_verify', 'pending_card_reject', 'pending_ewallet_verify', 'pending_ewallet_reject' => [
                'details.pending_id' => 'required|integer|min:1',
            ],
            default => [],
        };
        $data = $request->validate($rules);

        $details = $this->sanitizeDetails($data['action'], $data['details']);
        $result = $authorizations->authorize(
            $request,
            $requester,
            $data['action'],
            $details,
            $isManager ? null : ($data['pin'] ?? null),
            $data['reason'] ?? null,
            $data['notes'] ?? null,
            $data['register_id'] ?? null,
        );

        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }

    /** @param array<string, mixed> $details @return array<string, mixed> */
    private function sanitizeDetails(string $action, array $details): array
    {
        return match ($action) {
            'cash_movement', 'cash_transfer', 'cash_transfer_cancel', 'sale_exchange' => $details,
            'cart_item_void' => [
                'product_id' => (int) ($details['product_id'] ?? 0),
                'product_name' => mb_substr((string) ($details['product_name'] ?? 'Item'), 0, 100),
                'quantity' => round((float) ($details['quantity'] ?? 0), 3),
                'line_total' => round((float) ($details['line_total'] ?? 0), 2),
            ],
            'held_transaction_delete' => [
                'hold_number' => mb_substr((string) ($details['hold_number'] ?? 'Held cart'), 0, 30),
                'item_count' => max(0, (int) ($details['item_count'] ?? 0)),
                'total' => round((float) ($details['total'] ?? 0), 2),
            ],
            'discount_apply' => [
                'discount_id' => (int) ($details['discount_id'] ?? 0),
                'discount_name' => mb_substr((string) ($details['discount_name'] ?? ''), 0, 100),
                'discount_amount' => round((float) ($details['discount_amount'] ?? 0), 2),
            ],
            'sale_refund' => [
                'sale_id' => (int) ($details['sale_id'] ?? 0),
                'items' => (array) ($details['items'] ?? []),
                'condition' => $details['condition'] ?? null,
                'operation_key' => $details['operation_key'] ?? null,
                'settlement_reference' => $details['settlement_reference'] ?? null,
            ],
            'cash_drawer_open' => [
                'opening_cash' => round((float) ($details['opening_cash'] ?? 0), 2),
            ],
            'pending_card_verify', 'pending_card_reject', 'pending_ewallet_verify', 'pending_ewallet_reject' => [
                'pending_id' => (int) ($details['pending_id'] ?? 0),
            ],
            default => [],
        };
    }
}
