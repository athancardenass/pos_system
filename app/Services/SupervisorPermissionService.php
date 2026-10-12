<?php

namespace App\Services;

use App\Models\DrawerTransfer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupervisorPermissionService
{
    public const ACTIONS = [
        'cash_drawer_open' => 'Opening float',
        'cash_movement' => 'Cash movements',
        'cash_transfer' => 'Drawer transfers',
        'cash_transfer_cancel' => 'Cancel a drawer transfer',
        'sale_refund' => 'Refunds within the standard return window',
        'sale_exchange' => 'Item exchanges within the standard return window',
    ];

    public function allows(Employee $employee, string $action, array $details): bool
    {
        if (! $employee->isActive() || ! $employee->hasRole('Supervisor') || ! array_key_exists($action, self::ACTIONS)) {
            return false;
        }

        $permission = $employee->supervisorActionPermissions()
            ->where('action', $action)
            ->first();
        if (! $permission) {
            return false;
        }

        if (in_array($action, ['sale_refund', 'sale_exchange'], true) && ! $this->saleIsWithinStandardWindow((int) ($details['sale_id'] ?? 0))) {
            return false;
        }

        $amount = $this->amountFor($action, $details);

        return $amount !== null && $amount >= 0 && $amount <= (float) $permission->max_amount + 0.000001;
    }

    /** @return array<string, float> */
    public function permissionLimits(Employee $employee): array
    {
        return $employee->supervisorActionPermissions()
            ->pluck('max_amount', 'action')
            ->map(fn ($amount) => (float) $amount)
            ->all();
    }

    /** @param array<string, mixed> $permissions */
    public function update(Employee $manager, Employee $supervisor, array $permissions): void
    {
        if (! $supervisor->hasRole('Supervisor')) {
            throw ValidationException::withMessages(['employee' => 'Permissions can only be assigned to a Supervisor.']);
        }

        DB::transaction(function () use ($manager, $supervisor, $permissions): void {
            $locked = Employee::query()->lockForUpdate()->findOrFail($supervisor->employee_id);
            if (! $locked->hasRole('Supervisor')) {
                throw ValidationException::withMessages(['employee' => 'Permissions can only be assigned to a Supervisor.']);
            }

            $normalized = [];
            foreach ($permissions as $action => $amount) {
                if (! array_key_exists($action, self::ACTIONS) || blank($amount)) {
                    continue;
                }
                $normalized[$action] = round((float) $amount, 2);
            }

            $locked->supervisorActionPermissions()->delete();
            foreach ($normalized as $action => $amount) {
                $locked->supervisorActionPermissions()->create(['action' => $action, 'max_amount' => $amount]);
            }

            AuditLogger::recordSensitive(
                'supervisor_permissions_updated',
                (int) $manager->employee_id,
                (int) $manager->employee_id,
                null,
                ['supervisor_employee_id' => (int) $locked->employee_id, 'permissions' => $normalized],
                'supervisor_action_permission',
                (int) $locked->employee_id,
                'Manager updated Supervisor task permissions and amount limits.',
            );
        });
    }

    public function revokeAll(Employee $employee): void
    {
        $employee->supervisorActionPermissions()->delete();
    }

    private function saleIsWithinStandardWindow(int $saleId): bool
    {
        $sale = SaleTransaction::query()->find($saleId);
        if (! $sale || ! in_array($sale->status, ['completed', 'refunded'], true)) {
            return false;
        }

        return ! $sale->transaction_date || $sale->transaction_date->diffInDays(now()) <= RefundService::WINDOW_DAYS;
    }

    private function amountFor(string $action, array $details): ?float
    {
        return match ($action) {
            'cash_drawer_open' => is_numeric($details['opening_cash'] ?? null) ? (float) $details['opening_cash'] : null,
            'cash_movement', 'cash_transfer' => is_numeric($details['amount'] ?? null) ? (float) $details['amount'] : null,
            'cash_transfer_cancel' => $this->transferAmount((int) ($details['transfer_id'] ?? 0)),
            'sale_refund' => $this->returnAmount((int) ($details['sale_id'] ?? 0), (array) ($details['items'] ?? [])),
            'sale_exchange' => $this->exchangeAmount($details),
            default => null,
        };
    }

    private function transferAmount(int $transferId): ?float
    {
        $amount = DrawerTransfer::query()->whereKey($transferId)->value('amount');

        return $amount === null ? null : (float) $amount;
    }

    private function returnAmount(int $saleId, array $items): ?float
    {
        $sale = SaleTransaction::query()->find($saleId);
        if (! $sale) {
            return null;
        }

        return (float) app(ReturnPricingService::class)->quote($sale, $items)['amount'];
    }

    private function exchangeAmount(array $details): ?float
    {
        $returnedValue = $this->returnAmount((int) ($details['sale_id'] ?? 0), (array) ($details['items'] ?? []));
        if ($returnedValue === null || empty($details['replacements'])) {
            return null;
        }

        $products = Product::query()
            ->whereIn('product_id', array_column($details['replacements'], 'product_id'))
            ->get()
            ->keyBy('product_id');
        $replacementValue = 0;
        foreach ($details['replacements'] as $line) {
            $product = $products->get((int) ($line['product_id'] ?? 0));
            $quantity = $line['quantity'] ?? null;
            if (! $product || ! is_numeric($quantity) || (float) $quantity <= 0) {
                return null;
            }
            $replacementValue += round((float) $product->unit_price * (float) $quantity, 2);
        }

        return max($returnedValue, round($replacementValue, 2));
    }
}
