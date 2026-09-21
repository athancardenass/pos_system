<?php

namespace App\Services;

use App\Models\CashDrawer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashDrawerService
{
    /**
     * Open a cash drawer session for the given employee.
     */
    public function openDrawer(int $employeeId, float $openingCash): CashDrawer
    {
        return DB::transaction(function () use ($employeeId, $openingCash) {
            // Close any existing open drawer for this employee
            CashDrawer::where('employee_id', $employeeId)
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

            return CashDrawer::create([
                'employee_id' => $employeeId,
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'status' => 'open',
                'opened_at' => now(),
            ]);
        });
    }

    /**
     * Get the currently open drawer for an employee.
     */
    public function getOpenDrawer(int $employeeId): ?CashDrawer
    {
        return CashDrawer::where('employee_id', $employeeId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Add cash to the expected amount (e.g., cash sale).
     */
    public function addCash(int $employeeId, float $amount): void
    {
        $drawer = $this->getOpenDrawer($employeeId);
        if (! $drawer) {
            return;
        }

        $drawer->expected_cash = round((float) $drawer->expected_cash + $amount, 2);
        $drawer->save();
    }

    /**
     * Close the drawer and calculate difference.
     *
     * @throws ValidationException
     */
    public function closeDrawer(int $employeeId, float $actualCash): CashDrawer
    {
        $drawer = $this->getOpenDrawer($employeeId);

        if (! $drawer) {
            throw ValidationException::withMessages([
                'drawer' => 'No open cash drawer found for this employee.',
            ]);
        }

        $difference = round($actualCash - (float) $drawer->expected_cash, 2);

        $drawer->update([
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $drawer->fresh();
    }

    /**
     * Get all closed drawers (for manager review).
     */
    public function getClosedDrawers()
    {
        return CashDrawer::with('employee')
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->get();
    }
}
