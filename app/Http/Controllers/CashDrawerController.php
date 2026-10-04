<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Services\AuditLogger;
use App\Services\CashDrawerService;
use App\Services\ManagerAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashDrawerController extends Controller
{
    public function __construct(
        private readonly CashDrawerService $service,
        private readonly ManagerAuthorizationService $authorizations,
    ) {
    }

    /**
     * Open a cash drawer session.
     */
    public function open(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'manager_authorization_token' => 'nullable|string|max:100',
            'register_id' => 'nullable|string|max:50',
        ]);

        $openingCash = round((float) $data['opening_cash'], 2);
        $approval = $this->authorizations->requireGrant(
            $request,
            'cash_drawer_open',
            ['opening_cash' => $openingCash],
            $data['manager_authorization_token'] ?? null,
            registerId: $data['register_id'] ?? null,
        );
        $drawer = $this->service->openDrawer((int) auth()->id(), $openingCash);
        AuditLogger::recordSensitive(
            'cash_drawer_opened',
            $approval['requested_by_employee_id'],
            $approval['approved_by_employee_id'],
            $approval['register_id'],
            ['opening_cash' => $openingCash, 'drawer_id' => $drawer->drawer_id],
            'cash_drawer',
            (int) $drawer->drawer_id,
            'Opened a cash drawer shift with ₱'.number_format($openingCash, 2).'.',
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'drawer' => $this->service->getOpenDrawer(auth()->id()),
            ]);
        }

        return back()->with('status', 'Cash drawer opened with ₱' . number_format($data['opening_cash'], 2));
    }

    /**
     * Close the cash drawer session.
     */
    public function close(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
        ]);

        $drawer = $this->service->closeDrawer(auth()->id(), (float) $data['actual_cash']);

        $diff = (float) $drawer->difference;
        $msg = 'Drawer closed. ';
        if ($diff > 0) {
            $msg .= 'Overage: ₱' . number_format($diff, 2);
        } elseif ($diff < 0) {
            $msg .= 'Shortage: ₱' . number_format(abs($diff), 2);
        } else {
            $msg .= 'Balanced.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'drawer' => $drawer,
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    /**
     * Get current open drawer status.
     */
    public function status(): JsonResponse
    {
        $employeeId = (int) auth()->id();
        $drawer = $this->service->getOpenDrawer($employeeId);
        $lastClosedDrawer = $drawer ? null : $this->service->getLastClosedDrawer($employeeId);

        return response()->json([
            'has_open_drawer' => $drawer !== null,
            'drawer' => $drawer,
            'last_closed_at' => $lastClosedDrawer?->closed_at,
        ]);
    }

    /**
     * Manager: list all closed drawers for review.
     */
    public function index(): View
    {
        $drawers = $this->service->getClosedDrawers();

        return view('cash-drawers.index', compact('drawers'));
    }
}
