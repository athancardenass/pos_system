<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($employee->saleTransactions()->exists()) {
            return back()->with('error', 'Cannot delete an employee with sales history. Set them inactive instead.');
        }

        $id = $employee->employee_id;
        $username = $employee->username;
        $employee->delete();

        AuditLogger::record('delete', 'employee', $id, 'Deleted employee '.$username);

        return redirect()->route('employees.index')->with('status', 'Employee deleted.');
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
