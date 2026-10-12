<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Models\DrawerMovement;
use App\Models\DrawerTransfer;
use App\Services\CashDrawerService;
use App\Services\DrawerTransferService;
use App\Services\ManagerAuthorizationService;
use App\Services\ShiftReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DrawerMovementController extends Controller
{
    public function __construct(private readonly CashDrawerService $drawers, private readonly DrawerTransferService $transfers,
        private readonly ManagerAuthorizationService $authorizations, private readonly ShiftReportService $reports) {}

    public function index(Request $request)
    {
        $drawer = $this->drawers->getOpenDrawer((int) $request->user()->employee_id);
        $destinations = CashDrawer::with('employee')->where('status', 'open')->where('employee_id', '!=', $request->user()->employee_id)->get();
        $transfers = $drawer ? DrawerTransfer::with(['source.employee', 'destination.employee'])->where(fn ($q) => $q->where('source_drawer_id', $drawer->drawer_id)->orWhere('destination_drawer_id', $drawer->drawer_id))->orderByDesc('id')->get() : collect();

        return view('cash-drawers.workspace', ['drawer' => $drawer, 'destinations' => $destinations, 'transfers' => $transfers, 'report' => $drawer ? $this->reports->summary($drawer) : null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->commonRules() + ['kind' => 'required|in:manual_in,manual_out', 'amount' => 'required|numeric|min:0.01|max:999999999|decimal:0,2', 'reason' => 'required|string|max:255', 'operation_key' => 'required|uuid']);
        $details = ['kind' => $data['kind'], 'amount' => round((float) $data['amount'], 2), 'reason' => trim($data['reason']), 'operation_key' => $data['operation_key']];
        $movement = DB::transaction(function () use ($request, $data, $details) {
            $drawer = $this->drawers->lockOpenDrawer((int) $request->user()->employee_id);
            $existing = DrawerMovement::where('operation_key', 'manual:'.$data['operation_key'])->first();
            if ($existing && $existing->drawer_id != $drawer->drawer_id) {
                abort(403);
            }
            $approval = $existing ? ['approved_by_employee_id' => $existing->approved_by_employee_id] : $this->authorizations->requireGrant($request, 'cash_movement', $details, $data['manager_authorization_token'] ?? null, registerId: $data['register_id'] ?? null);
            if (! $existing && $details['kind'] === 'manual_out' && $details['amount'] > (float) $drawer->expected_cash) {
                throw ValidationException::withMessages(['amount' => 'Cash Out cannot exceed expected drawer cash.']);
            }

            return $this->drawers->record($drawer, ['kind' => $details['kind'], 'amount' => $details['amount'] * ($details['kind'] === 'manual_out' ? -1 : 1),
                'payment_method' => 'cash', 'reason' => $details['reason'], 'approved_by_employee_id' => $approval['approved_by_employee_id'], 'operation_key' => 'manual:'.$data['operation_key']]);
        });

        return $request->wantsJson() ? response()->json(['status' => 'ok', 'movement' => $movement]) : back()->with('status', 'Cash movement recorded.');
    }

    public function transfer(Request $request)
    {
        $data = $request->validate($this->commonRules() + ['destination_drawer_id' => 'required|integer|exists:cash_drawers,drawer_id', 'amount' => 'required|numeric|min:0.01|max:999999999|decimal:0,2', 'reason' => 'required|string|max:255', 'operation_key' => 'required|uuid']);
        $details = ['destination_drawer_id' => (int) $data['destination_drawer_id'], 'amount' => round((float) $data['amount'], 2), 'reason' => trim($data['reason']), 'operation_key' => $data['operation_key']];
        $approval = $this->authorizations->requireGrant($request, 'cash_transfer', $details, $data['manager_authorization_token'] ?? null, registerId: $data['register_id'] ?? null);
        $transfer = $this->transfers->initiate((int) $request->user()->employee_id, $details, $approval);

        return $request->wantsJson() ? response()->json(['status' => 'ok', 'transfer' => $transfer]) : back()->with('status', 'Transfer sent. Recipient must confirm receipt.');
    }

    public function accept(Request $request, DrawerTransfer $transfer)
    {
        $this->transfers->accept((int) $request->user()->employee_id, $transfer);

        return back()->with('status', 'Cash receipt confirmed.');
    }

    public function cancel(Request $request, DrawerTransfer $transfer)
    {
        $data = $request->validate($this->commonRules());
        $approval = $this->authorizations->requireGrant($request, 'cash_transfer_cancel', ['transfer_id' => $transfer->id], $data['manager_authorization_token'] ?? null, registerId: $data['register_id'] ?? null);
        $this->transfers->cancel((int) $request->user()->employee_id, $transfer, $approval);

        return back()->with('status', 'Transfer cancelled; source cash restored.');
    }

    private function commonRules(): array
    {
        return ['manager_authorization_token' => 'nullable|string|max:100', 'register_id' => 'nullable|string|max:50'];
    }
}
