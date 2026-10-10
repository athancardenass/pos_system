<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly,all',
        ]);
        $period = $validated['period'] ?? 'all';
        $now = now();

        [$from, $to] = match ($period) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };

        $query = AuditLog::query()
            ->with(['employee', 'requester', 'approver'])
            ->when($from && $to, fn ($query) => $query->whereBetween('action_timestamp', [$from, $to]))
            ->latest('action_timestamp');

        $logs = $query->paginate(25)->withQueryString();

        $logs->getCollection()->transform(function (AuditLog $log): AuditLog {
            if (in_array($log->table_affected, ['pending_ewallet_verifications', 'pending_card_verifications'], true)) {
                $log->description = match ($log->action) {
                    'payment_pending', 'card_payment_pending' => 'Payment reference submitted for manager verification.',
                    'payment_verified', 'card_payment_verified' => 'Payment reference verified and sale completed.',
                    'payment_rejected', 'card_payment_rejected' => 'Payment request rejected; reserved inventory was released.',
                    'payment_expired', 'card_payment_expired' => 'Payment verification request expired; reserved inventory was released.',
                    default => $log->description,
                };
            }

            return $log;
        });

        return view('audit-logs.index', compact('logs', 'period', 'from', 'to'));
    }
}
