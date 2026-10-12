<?php

namespace App\Services;

use App\Models\CashDrawer;
use App\Models\DrawerMovement;
use App\Models\DrawerTransfer;
use App\Models\Employee;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashDrawerService
{
    public function openDrawer(int $employeeId, float $openingCash): CashDrawer
    {
        return DB::transaction(function () use ($employeeId, $openingCash) {
            Employee::whereKey($employeeId)->lockForUpdate()->firstOrFail();
            if ($this->lockOpenDrawer($employeeId, false)) {
                $this->invalid('drawer', 'Close the existing shift before opening another.');
            }

            return CashDrawer::create(['employee_id' => $employeeId, 'opening_cash' => $openingCash, 'ledger_start_cash' => $openingCash,
                'expected_cash' => $openingCash, 'status' => 'open', 'opened_at' => now()]);
        });
    }

    public function getOpenDrawer(int $employeeId): ?CashDrawer
    {
        return CashDrawer::where('employee_id', $employeeId)->where('status', 'open')->first();
    }

    public function lockOpenDrawer(int $employeeId, bool $required = true): ?CashDrawer
    {
        Employee::whereKey($employeeId)->lockForUpdate()->firstOrFail();
        $drawer = CashDrawer::where('employee_id', $employeeId)->where('status', 'open')->lockForUpdate()->first();
        if (! $drawer && $required) {
            $this->invalid('drawer', 'Open a shift before processing this action.');
        }

        return $drawer;
    }

    public function getLastClosedDrawer(int $employeeId): ?CashDrawer
    {
        return CashDrawer::where('employee_id', $employeeId)->where('status', 'closed')->whereNotNull('closed_at')->orderByDesc('closed_at')->first();
    }

    public function lockForCheckout(int $employeeId): ?CashDrawer
    {
        $drawer = $this->lockOpenDrawer($employeeId, false);
        if (! $drawer && CashDrawer::where('employee_id', $employeeId)->exists()) {
            $this->invalid('drawer', 'Open a new shift before starting another payment.');
        }

        return $drawer;
    }

    public function record(CashDrawer $drawer, array $entry): DrawerMovement
    {
        return DB::transaction(function () use ($drawer, $entry) {
            $drawer = CashDrawer::whereKey($drawer->drawer_id)->lockForUpdate()->firstOrFail();
            $existing = DrawerMovement::where('operation_key', $entry['operation_key'])->lockForUpdate()->first();
            if ($existing) {
                foreach (['kind', 'payment_method', 'reason', 'sale_id', 'refund_id', 'transfer_id'] as $field) {
                    if ((string) $existing->$field !== (string) ($entry[$field] ?? null)) {
                        $this->invalid('operation_key', 'This request ID has already been used with different data.');
                    }
                }
                if ($existing->drawer_id != $drawer->drawer_id || abs((float) $existing->amount - round((float) $entry['amount'], 2)) > .001) {
                    $this->invalid('operation_key', 'This request ID has already been used with different data.');
                }

                return $existing;
            }
            if (! $drawer->isOpen()) {
                $this->invalid('drawer', 'This shift is closed.');
            }
            $amount = round((float) $entry['amount'], 2);
            if (! is_finite($amount)) {
                $this->invalid('amount', 'Enter a valid amount.');
            }
            $movement = DrawerMovement::create(array_merge($entry, ['drawer_id' => $drawer->drawer_id, 'amount' => $amount, 'created_at' => now()]));
            if ($movement->payment_method === 'cash') {
                $drawer->update(['expected_cash' => round((float) $drawer->expected_cash + $amount, 2)]);
            }

            return $movement;
        });
    }

    public function recordSale(SaleTransaction $sale, ?CashDrawer $drawer): void
    {
        if ($drawer) {
            $this->record($drawer, ['kind' => 'sale', 'amount' => (float) $sale->total_amount, 'payment_method' => $sale->payment_method,
                'sale_id' => $sale->transaction_id, 'operation_key' => 'sale:'.$sale->transaction_id]);
        }
    }

    public function addCash(int $employeeId, float $amount): void
    {
        DB::transaction(function () use ($employeeId, $amount) {
            if ($drawer = $this->lockOpenDrawer($employeeId, false)) {
                $this->record($drawer, ['kind' => 'manual_in', 'amount' => $amount,
                    'payment_method' => 'cash', 'reason' => 'Legacy cash adjustment', 'operation_key' => 'legacy:'.Str::uuid()]);
            }
        });
    }

    public function closeDrawer(int $employeeId, float $actualCash, ?string $notes = null, ?float $expected = null): CashDrawer
    {
        return DB::transaction(function () use ($employeeId, $actualCash, $notes, $expected) {
            $drawer = $this->lockOpenDrawer($employeeId);
            if (DrawerTransfer::where('status', 'pending')->where(fn ($q) => $q->where('source_drawer_id', $drawer->drawer_id)->orWhere('destination_drawer_id', $drawer->drawer_id))->exists()) {
                $this->invalid('drawer', 'Accept or cancel pending drawer transfers before closing.');
            }
            foreach (['pending_card_verifications', 'pending_ewallet_verifications'] as $table) {
                if (DB::table($table)->where('employee_id', $employeeId)->where('status', 'pending')->exists()) {
                    $this->invalid('drawer', 'Verify or reject pending payments before closing.');
                }
            }
            if ($expected !== null && abs($expected - (float) $drawer->expected_cash) > .001) {
                $this->invalid('expected_cash', 'Drawer balance changed. Review and count again.');
            }
            $diff = round($actualCash - (float) $drawer->expected_cash, 2);
            if (abs($diff) > .001 && blank($notes)) {
                $this->invalid('reconciliation_notes', 'Explain the shortage or overage before closing.');
            }
            $drawer->update(['actual_cash' => $actualCash, 'difference' => $diff, 'reconciliation_notes' => $notes, 'closed_at' => now(), 'status' => 'closed']);
            $snapshot = app(ShiftReportService::class)->summary($drawer->fresh());
            $snapshot['legacy'] = false;
            $drawer->update(['report_snapshot' => $snapshot]);
            AuditLogger::record('shift_closed', 'cash_drawers', $drawer->drawer_id, 'Closed shift with difference '.number_format($diff, 2));

            return $drawer->fresh();
        });
    }

    public function getClosedDrawers()
    {
        return CashDrawer::with('employee')->where('status', 'closed')->orderByDesc('closed_at')->get();
    }

    private function invalid(string $field,string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
