<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::query()
            ->with('employee')
            ->latest('action_timestamp')
            ->paginate(25);

        return view('audit-logs.index', compact('logs'));
    }
}
