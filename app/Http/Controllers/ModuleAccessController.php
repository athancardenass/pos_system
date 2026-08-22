<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleAccessController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('modules.ok', [
            'routeName' => $request->route()->getName(),
            'employee' => $request->user()->loadMissing('role'),
        ]);
    }
}
