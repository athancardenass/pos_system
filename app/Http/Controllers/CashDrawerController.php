<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Services\CashDrawerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashDrawerController extends Controller
{
    public function __construct(
        private readonly CashDrawerService $service,
    ) {
    }

    /**
     * Open a cash drawer session.
     */
    public function open(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $this->service->openDrawer(auth()->id(), (float) $data['opening_cash']);

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
        $drawer = $this->service->getOpenDrawer(auth()->id());

        return response()->json([
            'has_open_drawer' => $drawer !== null,
            'drawer' => $drawer,
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
