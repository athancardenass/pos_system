<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $employee = auth()->user()->loadMissing('role');

        return view('dashboard', [
            'employee' => $employee,
            'modules' => $employee->allowedModules(),
        ]);
    }
}
