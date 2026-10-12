<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\SupervisorPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorPermissionController extends Controller
{
    public function edit(Employee $employee, SupervisorPermissionService $permissions): View
    {
        abort_unless($employee->hasRole('Supervisor'), 404);

        return view('employees.supervisor-permissions', [
            'employee' => $employee->loadMissing('role'),
            'actions' => SupervisorPermissionService::ACTIONS,
            'permissionLimits' => $permissions->permissionLimits($employee),
        ]);
    }

    public function update(Request $request, Employee $employee, SupervisorPermissionService $permissions): RedirectResponse
    {
        abort_unless($employee->hasRole('Supervisor'), 404);

        $data = $request->validate([
            'permissions' => ['present', 'array:'.implode(',', array_keys(SupervisorPermissionService::ACTIONS))],
            'permissions.*' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
        ]);

        $manager = $request->user();
        abort_unless($manager instanceof Employee && $manager->hasRole('Manager'), 403);
        $permissions->update($manager, $employee, $data['permissions']);

        return redirect()->route('employees.supervisor-permissions.edit', $employee)
            ->with('status', 'Supervisor permissions updated.');
    }
}
