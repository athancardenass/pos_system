<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::query()
            ->with('role')
            ->orderBy('last_name')
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('employees.create', [
            'roles' => Role::query()->orderBy('role_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $employee = Employee::query()->create($data);

        AuditLogger::record('create', 'employee', $employee->employee_id, 'Created employee '.$employee->username);

        return redirect()->route('employees.index')->with('status', 'Employee created.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'roles' => Role::query()->orderBy('role_name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validated($request, $employee);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $employee->update($data);

        AuditLogger::record('update', 'employee', $employee->employee_id, 'Updated employee '.$employee->username);

        return redirect()->route('employees.index')->with('status', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->employee_id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Reassign all sales/audit/PO records to another Manager, then delete.
        // This preserves financial/receipt integrity (no dangling FKs) instead of
        // hard-blocking deletion for employees with a sales history.
        DB::transaction(function () use ($employee) {
            $manager = Employee::query()
                ->where('status', 'active')
                ->whereHas('role', fn ($q) => $q->where('role_name', 'Manager'))
                ->where('employee_id', '!=', $employee->employee_id)
                ->orderBy('employee_id')
                ->first();

            if (! $manager) {
                throw ValidationException::withMessages([
                    'employee' => 'Cannot delete this employee: no active Manager exists to reassign their records.',
                ]);
            }

            $targetId = $manager->employee_id;
            SaleTransaction::where('employee_id', $employee->employee_id)->update(['employee_id' => $targetId]);
            SaleRefund::where('employee_id', $employee->employee_id)->update(['employee_id' => $targetId]);
            AuditLog::where('employee_id', $employee->employee_id)->update(['employee_id' => $targetId]);
            PurchaseOrder::where('employee_id', $employee->employee_id)->update(['employee_id' => $targetId]);

            $employee->delete();
        });

        AuditLogger::record('delete', 'employee', $employee->employee_id, 'Deleted employee '.$employee->username.' (records reassigned)');

        return redirect()->route('employees.index')->with('status', 'Employee deleted; their records were reassigned to an active manager.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $passwordRule = $employee ? 'nullable|string|min:8' : 'required|string|min:8';

        return $request->validate([
            'role_id' => 'required|exists:role,role_id',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'username' => 'required|string|max:50|unique:employee,username,'.($employee?->employee_id ?? 'NULL').',employee_id',
            'password' => $passwordRule,
            'contact_number' => 'nullable|string|max:20',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
