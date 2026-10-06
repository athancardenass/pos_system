<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagerAuthorizationService
{
    public const ACTIONS = [
        'cart_item_void',
        'held_transaction_delete',
        'discount_apply',
        'sale_refund',
        'cash_drawer_open',
        'pending_card_verify',
        'pending_card_reject',
        'pending_ewallet_verify',
        'pending_ewallet_reject',
    ];

    private const GRANT_ACTIONS = [
        'discount_apply',
        'sale_refund',
        'cash_drawer_open',
        'pending_card_verify',
        'pending_card_reject',
        'pending_ewallet_verify',
        'pending_ewallet_reject',
    ];

    /**
     * @param array<string, mixed> $details
     * @return array{ok: bool, token?: string|null, approved_by?: string, attempts_remaining?: int, locked_for?: int, message?: string, status?: int}
     */
    public function authorize(
        Request $request,
        Employee $requester,
        string $action,
        array $details,
        ?string $pin,
        ?string $reason,
        ?string $notes,
        ?string $registerId,
    ): array {
        $approver = $requester->hasRole('Manager')
            ? $requester
            : $this->verifyManagerPin($request, $requester, $pin, $action, $registerId);

        if (is_array($approver)) {
            return $approver;
        }

        $token = null;
        if (in_array($action, self::GRANT_ACTIONS, true)) {
            $token = Str::random(64);
            $request->session()->put('manager_action_grants.'.$token, [
                'action' => $action,
                'requester_id' => (int) $requester->employee_id,
                'approver_id' => (int) $approver->employee_id,
                'register_id' => $registerId,
                'scope_hash' => $this->scopeHash($action, $details, $reason, $notes),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
        }

        $auditDetails = [
            'requested_action' => $action,
            'details' => $details,
        ];
        if ($reason !== null) {
            $auditDetails['reason'] = $reason;
        }
        if ($notes !== null && trim($notes) !== '') {
            $auditDetails['notes'] = trim($notes);
        }

        AuditLogger::recordSensitive(
            $action,
            (int) $requester->employee_id,
            (int) $approver->employee_id,
            $registerId,
            $auditDetails,
            description: 'Manager authorization approved for '.$action.'.',
        );

        return [
            'ok' => true,
            'token' => $token,
            'approved_by' => $approver->displayName(),
        ];
    }

    /**
     * Require a one-use authorization grant for a cashier, or allow managers to act directly.
     *
     * @param array<string, mixed> $details
     * @return array{requested_by_employee_id: int, approved_by_employee_id: int, register_id: ?string}
     */
    public function requireGrant(
        Request $request,
        string $action,
        array $details,
        ?string $token,
        ?string $reason = null,
        ?string $notes = null,
        ?string $registerId = null,
    ): array {
        $employee = $request->user();
        abort_unless($employee instanceof Employee, 403);

        if (blank($token) && $employee->hasRole('Manager')) {
            return [
                'requested_by_employee_id' => (int) $employee->employee_id,
                'approved_by_employee_id' => (int) $employee->employee_id,
                'register_id' => $registerId,
            ];
        }

        $grant = filled($token) ? $request->session()->get('manager_action_grants.'.$token) : null;
        if (! is_array($grant)
            || ($grant['action'] ?? null) !== $action
            || (int) ($grant['requester_id'] ?? 0) !== (int) $employee->employee_id
            || ($registerId !== null && ($grant['register_id'] ?? null) !== $registerId)
            || (int) ($grant['expires_at'] ?? 0) < now()->timestamp
            || ! hash_equals((string) ($grant['scope_hash'] ?? ''), $this->scopeHash($action, $details, $reason, $notes))) {
            throw ValidationException::withMessages([
                'manager_authorization_token' => 'Manager authorization is missing, expired, or does not match this action.',
            ]);
        }

        $request->session()->forget('manager_action_grants.'.$token);

        return [
            'requested_by_employee_id' => (int) $grant['requester_id'],
            'approved_by_employee_id' => (int) $grant['approver_id'],
            'register_id' => $grant['register_id'] ?? null,
        ];
    }

    private function verifyManagerPin(
        Request $request,
        Employee $requester,
        ?string $pin,
        string $action,
        ?string $registerId,
    ): Employee|array {
        if (! $requester->hasRole('Cashier')) {
            abort(403);
        }

        $stateKey = 'manager_pin_lockout.'.$requester->employee_id;
        $state = $request->session()->get($stateKey, []);
        $lockedUntil = (int) ($state['locked_until'] ?? 0);
        if ($lockedUntil > now()->timestamp) {
            $remaining = $lockedUntil - now()->timestamp;
            AuditLogger::recordSensitive(
                'manager_pin_locked_out',
                (int) $requester->employee_id,
                null,
                $registerId,
                ['requested_action' => $action, 'retry_after_seconds' => $remaining],
                description: 'Manager PIN prompt was locked after repeated failed attempts.',
            );

            return [
                'ok' => false,
                'status' => 429,
                'locked_for' => $remaining,
                'attempts_remaining' => 0,
                'message' => 'Too many incorrect PINs. Try again in '.$remaining.' seconds.',
            ];
        }

        $managers = Employee::query()
            ->where('status', 'active')
            ->whereNotNull('manager_pin_hash')
            ->whereHas('role', fn ($query) => $query->whereRaw('LOWER(role_name) = ?', ['manager']))
            ->get();
        $manager = $managers->first(fn (Employee $candidate) => Hash::check((string) $pin, (string) $candidate->manager_pin_hash));

        if ($manager) {
            $request->session()->forget($stateKey);

            return $manager;
        }

        $attempts = (int) ($state['attempts'] ?? 0) + 1;
        $lockedFor = $attempts >= 3 ? 60 : 0;
        $request->session()->put($stateKey, $lockedFor > 0
            ? ['attempts' => 0, 'locked_until' => now()->addSeconds($lockedFor)->timestamp]
            : ['attempts' => $attempts, 'locked_until' => 0]);

        AuditLogger::recordSensitive(
            'manager_pin_failed',
            (int) $requester->employee_id,
            null,
            $registerId,
            [
                'requested_action' => $action,
                'attempt' => $attempts,
                'reason' => $managers->isEmpty() ? 'no_active_manager_pin_configured' : 'invalid_pin',
                'locked_for_seconds' => $lockedFor,
            ],
            description: 'Manager PIN authorization failed for '.$action.'.',
        );

        return [
            'ok' => false,
            'status' => $lockedFor > 0 ? 429 : 422,
            'attempts_remaining' => max(0, 3 - $attempts),
            'locked_for' => $lockedFor,
            'message' => $lockedFor > 0
                ? 'Three incorrect PIN attempts. The prompt is locked for 60 seconds.'
                : ($managers->isEmpty()
                    ? 'No active manager PIN is configured. A manager must set one in Employees.'
                    : 'Incorrect manager PIN. '.$attempts.' of 3 attempts used.'),
        ];
    }

    /** @param array<string, mixed> $details */
    private function scopeHash(string $action, array $details, ?string $reason, ?string $notes): string
    {
        $scope = match ($action) {
            'discount_apply' => [
                'discount_id' => (int) ($details['discount_id'] ?? 0),
            ],
            'sale_refund' => [
                'sale_id' => (int) ($details['sale_id'] ?? 0),
                'items' => $this->normalizeRefundItems((array) ($details['items'] ?? [])),
                'reason' => $reason,
                'notes' => filled($notes) ? trim((string) $notes) : null,
            ],
            'cash_drawer_open' => [
                'opening_cash' => round((float) ($details['opening_cash'] ?? 0), 2),
            ],
            'pending_card_verify', 'pending_card_reject', 'pending_ewallet_verify', 'pending_ewallet_reject' => [
                'pending_id' => (int) ($details['pending_id'] ?? 0),
            ],
            default => [],
        };

        return hash('sha256', json_encode($scope, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
    }

    /** @param array<int|string, mixed> $items @return array<string, float> */
    private function normalizeRefundItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $detailId => $quantity) {
            if (! is_numeric($quantity)) {
                continue;
            }

            $quantity = round((float) $quantity, 3);
            if ($quantity > 0) {
                $normalized[(string) (int) $detailId] = $quantity;
            }
        }
        ksort($normalized, SORT_NUMERIC);

        return $normalized;
    }
}
