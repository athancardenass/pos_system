<?php

namespace App\Http\Controllers;

use App\Models\PendingCardVerification;
use App\Models\PendingEwalletVerification;
use App\Models\Product;
use App\Models\SaleExchange;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use App\Services\AuditLogger;
use App\Services\CardVerificationService;
use App\Services\CheckoutService;
use App\Services\EwalletVerificationService;
use App\Services\Integration\CRM\CustomerService as CrmCustomerService;
use App\Services\Integration\HR\EmployeeService as HrmsEmployeeService;
use App\Services\Integration\Inventory\ProductLookupService as InventoryProductLookupService;
use App\Services\ManagerAuthorizationService;
use App\Services\ReceiptSettingsService;
use App\Services\RefundService;
use App\Services\SaleService;
use App\Services\VatSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly EwalletVerificationService $ewalletVerifications,
        private readonly SaleService $saleService,
        private readonly ManagerAuthorizationService $authorizations,
        private readonly CardVerificationService $cardVerifications,
    ) {}

    public function index(VatSettingsService $vatSettings): View
    {
        $data = $this->saleService->getPosIndexData();
        $data['vatRate'] = $vatSettings->currentRate();

        $employee = auth()->user();
        $isManager = $employee->hasRole('Manager');
        $this->ewalletVerifications->expireStale();
        $data['pendingEwalletCount'] = PendingEwalletVerification::query()
            ->where('status', PendingEwalletVerification::STATUS_PENDING)
            ->when(! $isManager, fn ($query) => $query->where('employee_id', $employee->employee_id))
            ->count();
        $data['pendingCardCount'] = $this->cardVerifications->pendingCount(
            $isManager ? null : (int) $employee->employee_id,
        );

        return view('pos.index', $data);
    }

    /** Current stock snapshot for restoring held carts after product stock changes. */
    public function productStock(): JsonResponse
    {
        return response()->json([
            'stocks' => $this->saleService->getProductStockSnapshot(),
        ])->header('Cache-Control', 'no-store');
    }

    public function findReceipt(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $query = trim((string) ($data['q'] ?? ''));

        return view('pos.receipt-search', ['query' => $query, 'sale' => $this->saleService->findReceipt($query)]);
    }

    /** Search CRM customers when the audited CRM integration is configured. */
    public function searchCustomers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        return response()->json([
            'customers' => $this->saleService->searchPosCustomers($data['q']),
        ]);
    }

    /** Search the CRM directory for display only; CRM IDs are not POS checkout IDs. */
    public function searchCrmCustomers(Request $request, CrmCustomerService $crm): JsonResponse
    {
        $data = $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $result = $crm->searchReadOnly($data['q']);

        if ($result['status'] !== 200) {
            [$code, $message] = match ($result['status']) {
                503 => ['crm_not_configured', 'CRM customer lookup is not configured.'],
                504 => ['crm_timeout', 'CRM customer lookup timed out.'],
                default => ['crm_unavailable', 'CRM customer lookup is unavailable.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $result['status']);
        }

        return response()->json([
            'customers' => $result['customers'],
            'meta' => ['total' => $result['total']],
        ]);
    }

    /** Synchronize one exact CRM customer into the local FK-backed customer table. */
    public function syncCrmCustomer(Request $request, CrmCustomerService $crm): JsonResponse
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:120', 'regex:/^crm:customer:[A-Za-z0-9][A-Za-z0-9._:-]*$/'],
        ]);

        $result = $crm->syncFromCrm($data['external_id']);
        if ($result['status'] !== 200 && $result['status'] !== 201) {
            [$code, $message] = match ($result['status']) {
                404 => ['crm_customer_not_found', 'CRM customer was not found or is inactive.'],
                409 => ['crm_customer_conflict', 'CRM customer conflicts with an existing POS customer email.'],
                422 => ['invalid_crm_customer_id', 'A canonical CRM customer ID is required.'],
                503 => ['crm_not_configured', 'CRM customer lookup is not configured.'],
                504 => ['crm_timeout', 'CRM customer lookup timed out.'],
                default => ['crm_unavailable', 'CRM customer lookup is unavailable.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $result['status']);
        }

        return response()->json(['customer' => $result['customer']], $result['status']);
    }

    /** Read one HRMS employee by the external employee number. */
    public function lookupHrmsEmployee(Request $request, HrmsEmployeeService $hrms): JsonResponse
    {
        $data = $request->validate([
            'employee_no' => 'required|string|max:100',
        ]);

        $result = $hrms->lookupByEmployeeNo($data['employee_no']);
        if ($result['status'] !== 200) {
            [$code, $message] = match ($result['status']) {
                503 => ['hrms_not_configured', 'HRMS employee lookup is not configured.'],
                504 => ['hrms_timeout', 'HRMS employee lookup timed out.'],
                default => ['hrms_unavailable', 'HRMS employee lookup is unavailable.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $result['status']);
        }

        return response()->json([
            'employee' => $result['employee'],
            'meta' => ['total' => $result['total']],
        ]);
    }

    /** Read one Inventory product by exact SKU without mapping it to a POS product. */
    public function lookupInventoryProduct(Request $request, InventoryProductLookupService $inventory): JsonResponse
    {
        $data = $request->validate([
            'sku' => 'required|string|max:100',
        ]);

        $result = $inventory->lookupBySku($data['sku']);
        if ($result['status'] !== 200) {
            [$code, $message] = match ($result['status']) {
                503 => ['inventory_not_configured', 'Inventory product lookup is not configured.'],
                504 => ['inventory_timeout', 'Inventory product lookup timed out.'],
                default => ['inventory_unavailable', 'Inventory product lookup is unavailable.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $result['status']);
        }

        return response()->json([
            'product' => $result['product'],
            'meta' => ['total' => $result['total']],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkoutService->assertDatabaseReady();

        $paymentMethod = $request->input('payment_method');
        $paymentProviderRules = match ($paymentMethod) {
            'card' => 'required|string|in:Visa,Mastercard,BancNet,JCB,Other|max:50',
            'e-wallet' => 'required|string|in:GCash,Maya,ShopeePay,GrabPay,Other|max:50',
            default => 'nullable|string|max:50',
        };
        $referenceNumberRules = match ($paymentMethod) {
            'card' => [
                'required',
                'string',
                'max:50',
                'regex:/\A(?=[A-Za-z0-9-]*[A-Za-z0-9])[A-Za-z0-9-]+\z/',
                'not_regex:/^(?:\d[\s-]*){13,}$/',
                'not_regex:/^\d{3,4}$/',
            ],
            'e-wallet' => ['required', 'string', 'max:100', 'regex:/\A(?=[A-Za-z0-9-]*[A-Za-z0-9])[A-Za-z0-9-]+\z/'],
            default => 'nullable|string|max:100',
        };

        $data = $request->validate([
            'customer_id' => 'nullable|integer|min:1',
            'idempotency_key' => 'nullable|uuid',
            'discount_id' => 'nullable|exists:discount,discount_id',
            'payment_method' => 'required|in:cash,card,e-wallet',
            'reference_number' => $referenceNumberRules,
            'payment_provider' => $paymentProviderRules,
            'card_last4' => 'exclude_unless:payment_method,card|nullable|digits:4',
            'amount_paid' => 'required|numeric|min:0|decimal:0,2',
            'manager_authorization_token' => 'nullable|string|max:100',
            'register_id' => 'nullable|string|max:50',
            'senior_pwd_type' => 'nullable|in:senior_citizen,pwd',
            'senior_pwd_name' => 'nullable|string|max:150',
            'senior_pwd_id_number' => 'nullable|string|max:80',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,product_id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ], [
            'reference_number.regex' => 'Use only letters, numbers, and dashes for the payment reference, with at least one letter or number. Spaces and other symbols are not allowed.',
        ]);

        $idempotencyKey = strtolower((string) ($data['idempotency_key'] ?? Str::uuid()));
        $data['idempotency_key'] = $idempotencyKey;

        if ($sale = $this->checkoutService->findExistingSale($idempotencyKey, (int) auth()->id())) {
            return $this->redirectToCompletedSale($request, $sale, $idempotencyKey, 'This checkout was already completed. Showing its original receipt.');
        }

        if ($pending = $this->ewalletVerifications->findPendingByIdempotencyKey($idempotencyKey)) {
            abort_unless((int) $pending->employee_id === (int) auth()->id(), 403);

            if ($pending->sale) {
                return $this->redirectToCompletedSale($request, $pending->sale, $idempotencyKey, 'This checkout was already completed. Showing its original receipt.');
            }

            return $this->redirectToPendingEwallet($request, $pending, $idempotencyKey, 'This checkout is already awaiting e-wallet verification.');
        }

        if ($pending = $this->cardVerifications->findPendingByIdempotencyKey($idempotencyKey)) {
            abort_unless((int) $pending->employee_id === (int) auth()->id(), 403);

            if ($pending->sale) {
                return $this->redirectToCompletedSale($request, $pending->sale, $idempotencyKey, 'This checkout was already completed. Showing its original receipt.');
            }

            return $this->redirectToPendingCard($request, $pending, $idempotencyKey, 'This Card payment is already awaiting manager verification.');
        }

        $discountApproval = null;
        if (! empty($data['discount_id'])) {
            $discountApproval = $this->authorizations->requireGrant(
                $request,
                'discount_apply',
                ['discount_id' => (int) $data['discount_id']],
                $data['manager_authorization_token'] ?? null,
                registerId: $data['register_id'] ?? null,
            );
        }
        unset($data['manager_authorization_token']);

        if ($data['payment_method'] === 'e-wallet') {
            $pending = $this->ewalletVerifications->submit($data, (int) auth()->id());

            return $this->redirectToPendingEwallet(
                $request,
                $pending,
                $idempotencyKey,
                'E-wallet payment submitted. A manager must verify it in the merchant app before the sale is completed.',
            );
        }

        if ($data['payment_method'] === 'card') {
            $pending = $this->cardVerifications->submit($data, (int) auth()->id());

            return $this->redirectToPendingCard(
                $request,
                $pending,
                $idempotencyKey,
                'Card payment submitted. A manager must confirm the approval code and amount against the terminal record before the sale is completed.',
            );
        }

        $sale = $this->checkoutService->checkout($data, (int) auth()->id());
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);
        if ($discountApproval && $sale->discount_id) {
            $sale->loadMissing('discount');
            AuditLogger::recordSensitive(
                'discount_applied_to_sale',
                $discountApproval['requested_by_employee_id'],
                $discountApproval['approved_by_employee_id'],
                $discountApproval['register_id'],
                [
                    'discount_id' => (int) $sale->discount_id,
                    'discount_name' => $sale->discount?->discount_name,
                    'discount_amount' => $sale->manualDiscountAmount(),
                ],
                'sale_transaction',
                (int) $sale->transaction_id,
                'Applied a manager-approved discount to sale #'.$sale->transaction_id.'.',
            );
        }

        return $this->redirectToCompletedSale($request, $sale, $idempotencyKey, 'Sale completed.');
    }

    private function redirectToCompletedSale(Request $request, SaleTransaction $sale, string $idempotencyKey, string $status): RedirectResponse
    {
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);
        $request->session()->flash('pos.checkout_key_to_clear', $idempotencyKey);

        return redirect()
            ->route('pos.show', ['saleTransaction' => $sale, 'completed' => 1])
            ->with('status', $status);
    }

    private function redirectToPendingEwallet(Request $request, PendingEwalletVerification $pending, string $idempotencyKey, string $status): RedirectResponse
    {
        $request->session()->flash('pos.checkout_key_to_clear', $idempotencyKey);

        return redirect()
            ->route('pos.pending-ewallet.show', $pending)
            ->with('status', $status);
    }

    private function redirectToPendingCard(Request $request, PendingCardVerification $pending, string $idempotencyKey, string $status): RedirectResponse
    {
        $request->session()->flash('pos.checkout_key_to_clear', $idempotencyKey);

        return redirect()
            ->route('pos.pending-card.show', $pending)
            ->with('status', $status);
    }

    public function pendingEwalletIndex(): View
    {
        $this->ewalletVerifications->expireStale();
        $requests = PendingEwalletVerification::query()
            ->with('requester')
            ->where('status', PendingEwalletVerification::STATUS_PENDING)
            ->when(! auth()->user()->hasRole('Manager'), fn ($query) => $query->where('employee_id', auth()->id()))
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return view('pos.pending-ewallet.index', ['requests' => $requests]);
    }

    public function showPendingEwallet(PendingEwalletVerification $pendingEwalletVerification): View
    {
        $this->ewalletVerifications->expireStale();
        $pendingEwalletVerification->refresh()->load(['requester', 'verifier', 'rejecter', 'sale.receipt']);

        $employee = auth()->user();
        abort_unless(
            $employee->hasRole('Manager') || (int) $pendingEwalletVerification->employee_id === (int) $employee->employee_id,
            403,
        );

        $customerId = data_get($pendingEwalletVerification->checkout_payload, 'customer_id');

        return view('pos.pending-ewallet.show', [
            'pending' => $pendingEwalletVerification,
            'itemSnapshots' => $pendingEwalletVerification->checkout_payload['item_snapshot'] ?? [],
            'customer' => null,
            'canReview' => $pendingEwalletVerification->status === PendingEwalletVerification::STATUS_PENDING,
            'canReveal' => $employee->hasRole('Manager'),
        ]);
    }

    public function pendingCardIndex(): View
    {
        $this->cardVerifications->expireStale();
        $employee = auth()->user();
        $requests = PendingCardVerification::query()
            ->with('requester')
            ->where('status', PendingCardVerification::STATUS_PENDING)
            ->when(! $employee->hasRole('Manager'), fn ($query) => $query->where('employee_id', $employee->employee_id))
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return view('pos.pending-card.index', ['requests' => $requests]);
    }

    public function showPendingCard(PendingCardVerification $pendingCardVerification): View
    {
        $this->cardVerifications->expireStale();
        $pendingCardVerification->refresh()->load(['requester', 'verifier', 'rejecter', 'sale.receipt']);

        $employee = auth()->user();
        abort_unless(
            $employee->hasRole('Manager') || (int) $pendingCardVerification->employee_id === (int) $employee->employee_id,
            403,
        );
        $customerId = data_get($pendingCardVerification->checkout_payload, 'customer_id');

        return view('pos.pending-card.show', [
            'pending' => $pendingCardVerification,
            'itemSnapshots' => $pendingCardVerification->checkout_payload['item_snapshot'] ?? [],
            'customer' => null,
            'canReview' => $pendingCardVerification->status === PendingCardVerification::STATUS_PENDING,
            'canReveal' => $employee->hasRole('Manager'),
        ]);
    }

    public function verifyPendingEwallet(Request $request, PendingEwalletVerification $pendingEwalletVerification): RedirectResponse
    {
        $employee = $request->user();
        if ($employee->hasRole('Cashier') && blank($request->input('manager_authorization_token'))) {
            abort(403, 'Manager approval is required to verify e-wallet payment.');
        }

        $data = $request->validate([
            'merchant_checked' => 'required|accepted',
            'manager_authorization_token' => 'nullable|string|max:100',
        ]);

        $approval = $this->authorizations->requireGrant(
            $request,
            'pending_ewallet_verify',
            ['pending_id' => (int) $pendingEwalletVerification->id],
            $data['manager_authorization_token'] ?? null,
        );

        $approverId = (int) ($approval['approved_by_employee_id'] ?? auth()->id());
        $sale = $this->ewalletVerifications->verify($pendingEwalletVerification, $approverId);
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);

        return redirect()
            ->route('pos.show', ['saleTransaction' => $sale, 'completed' => 1])
            ->with('status', 'Payment verified in the merchant app. The sale is complete and the receipt is ready.');
    }

    public function rejectPendingEwallet(Request $request, PendingEwalletVerification $pendingEwalletVerification): RedirectResponse
    {
        $employee = $request->user();
        if ($employee->hasRole('Cashier') && blank($request->input('manager_authorization_token'))) {
            abort(403, 'Manager approval is required to reject e-wallet payment.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'manager_authorization_token' => 'nullable|string|max:100',
        ]);

        $approval = $this->authorizations->requireGrant(
            $request,
            'pending_ewallet_reject',
            ['pending_id' => (int) $pendingEwalletVerification->id],
            $data['manager_authorization_token'] ?? null,
            $data['reason'],
        );

        $approverId = (int) ($approval['approved_by_employee_id'] ?? auth()->id());
        $this->ewalletVerifications->reject($pendingEwalletVerification, $approverId, $data['reason']);

        return redirect()
            ->route('pos.pending-ewallet.show', $pendingEwalletVerification)
            ->with('status', 'Payment request rejected and reserved inventory released. Handle any customer refund separately in the merchant app.');
    }

    public function verifyPendingCard(Request $request, PendingCardVerification $pendingCardVerification): RedirectResponse
    {
        $employee = $request->user();
        if ($employee->hasRole('Cashier') && blank($request->input('manager_authorization_token'))) {
            abort(403, 'Manager approval is required to verify card payment.');
        }

        $data = $request->validate([
            'terminal_checked' => 'required|accepted',
            'manager_authorization_token' => 'nullable|string|max:100',
        ]);

        $approval = $this->authorizations->requireGrant(
            $request,
            'pending_card_verify',
            ['pending_id' => (int) $pendingCardVerification->id],
            $data['manager_authorization_token'] ?? null,
        );

        $approverId = (int) ($approval['approved_by_employee_id'] ?? auth()->id());
        $sale = $this->cardVerifications->verify($pendingCardVerification, $approverId);
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);

        return redirect()
            ->route('pos.show', ['saleTransaction' => $sale, 'completed' => 1])
            ->with('status', 'Card approval and amount were confirmed against the terminal record. The sale is complete and the receipt is ready.');
    }

    public function rejectPendingCard(Request $request, PendingCardVerification $pendingCardVerification): RedirectResponse
    {
        $employee = $request->user();
        if ($employee->hasRole('Cashier') && blank($request->input('manager_authorization_token'))) {
            abort(403, 'Manager approval is required to reject card payment.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'manager_authorization_token' => 'nullable|string|max:100',
        ]);

        $approval = $this->authorizations->requireGrant(
            $request,
            'pending_card_reject',
            ['pending_id' => (int) $pendingCardVerification->id],
            $data['manager_authorization_token'] ?? null,
            $data['reason'],
        );

        $approverId = (int) ($approval['approved_by_employee_id'] ?? auth()->id());
        $this->cardVerifications->reject($pendingCardVerification, $approverId, $data['reason']);

        return redirect()
            ->route('pos.pending-card.show', $pendingCardVerification)
            ->with('status', 'Card payment rejected and reserved inventory released.');
    }

    public function revealPendingEwalletReference(PendingEwalletVerification $pendingEwalletVerification): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('Manager'), 403);
        $reference = $pendingEwalletVerification->revealedReference();
        abort_if($reference === null, 404);

        AuditLogger::recordSensitive(
            'payment_reference_revealed',
            (int) auth()->id(),
            (int) auth()->id(),
            (string) data_get($pendingEwalletVerification->checkout_payload, 'register_id', ''),
            ['payment_method' => 'e-wallet', 'provider' => $pendingEwalletVerification->payment_provider],
            'pending_ewallet_verifications',
            (int) $pendingEwalletVerification->id,
            'Manager revealed an E-Wallet reference for payment verification.',
        );

        return response()->json(['reference' => $reference]);
    }

    public function revealPendingCardReference(PendingCardVerification $pendingCardVerification): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('Manager'), 403);
        $reference = $pendingCardVerification->revealedReference();
        abort_if($reference === null, 404);

        AuditLogger::recordSensitive(
            'payment_reference_revealed',
            (int) auth()->id(),
            (int) auth()->id(),
            (string) data_get($pendingCardVerification->checkout_payload, 'register_id', ''),
            ['payment_method' => 'card', 'provider' => $pendingCardVerification->payment_provider],
            'pending_card_verifications',
            (int) $pendingCardVerification->id,
            'Manager revealed a Card approval code for payment verification.',
        );

        return response()->json(['reference' => $reference]);
    }

    public function reprintLast(Request $request): RedirectResponse
    {
        $transactionId = (int) $request->session()->get('pos.last_receipt_transaction_id', 0);
        $saleQuery = SaleTransaction::query()
            ->whereKey($transactionId)
            ->whereHas('receipt');
        if (! auth()->user()->hasRole('Manager')) {
            $saleQuery->where('employee_id', auth()->id());
        }

        $sale = $saleQuery->first();
        if (! $sale) {
            return redirect()->route('pos.index')
                ->with('error', 'There is no completed receipt available to reprint for this register yet.')
                ->with('pos_reprint_no_receipt', true);
        }

        return redirect()->route('pos.reprint', $sale);
    }

    public function reprint(SaleTransaction $saleTransaction): RedirectResponse
    {
        return redirect()->route('pos.show', ['saleTransaction' => $saleTransaction, 'reprint' => 1]);
    }

    public function show(
        Request $request,
        SaleTransaction $saleTransaction,
        ReceiptSettingsService $receiptSettings,
    ): View {
        $sale = $this->saleService->loadSaleForReceipt($saleTransaction);
        $daysOld = $sale->transaction_date
            ? (int) $sale->transaction_date->diffInDays(now())
            : 0;
        $windowDays = RefundService::WINDOW_DAYS;
        $daysRemaining = max(0, $windowDays - $daysOld);
        $isOutsideWindow = $daysOld > $windowDays;

        return view('pos.show', [
            'sale' => $sale,
            'windowDays' => $windowDays,
            'daysOld' => $daysOld,
            'daysRemaining' => $daysRemaining,
            'isOutsideWindow' => $isOutsideWindow,
            'receiptSettings' => $receiptSettings->forReceipt($sale->receipt),
            'isReprint' => $request->boolean('reprint'),
            'saleCompleted' => $request->boolean('completed') && ! $request->boolean('reprint'),
            'exchangeProducts' => Product::where('is_active', true)->orderBy('product_name')->get(['product_id', 'product_name', 'unit_price']),
            'replacementExchange' => SaleExchange::with(['original.receipt', 'refund'])->where('replacement_sale_id', $sale->transaction_id)->first(),
            'originalExchanges' => SaleExchange::with('replacement.receipt')->where('original_sale_id', $sale->transaction_id)->get(),
        ]);
    }

    public function slip(SaleRefund $refund): View
    {
        $refund->load(['sale.receipt', 'sale.employee', 'items.saleDetail.product', 'employee']);
        $sale = $this->saleService->loadSaleForReceipt($refund->sale);

        return view('pos.refund-slip', ['refund' => $refund, 'sale' => $sale]);
    }

    public function refund(Request $request, SaleTransaction $saleTransaction, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(RefundService::REASONS)),
            'notes' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*' => 'nullable|numeric|min:0|decimal:0,3',
            'condition' => 'nullable|in:resellable,damaged',
            'operation_key' => 'required|uuid',
            'settlement_reference' => 'nullable|string|max:100',
            'manager_authorization_token' => 'nullable|string|max:100',
            'register_id' => 'nullable|string|max:50',
        ]);

        // Keep only lines with qty > 0; empty => service treats as full refund.
        $items = array_filter(
            array_map(static fn ($quantity) => round((float) $quantity, 3), (array) ($data['items'] ?? [])),
            static fn (float $quantity) => $quantity > 0,
        );

        if (isset($data['items']) && ! count($items)) {
            throw ValidationException::withMessages(['items' => 'Select at least one returned quantity.']);
        }

        $approval = $this->authorizations->requireGrant(
            $request,
            'sale_refund',
            ['sale_id' => (int) $saleTransaction->transaction_id, 'items' => $items,
                'condition' => $data['condition'] ?? null, 'operation_key' => $data['operation_key'] ?? null, 'settlement_reference' => $data['settlement_reference'] ?? null],
            $data['manager_authorization_token'] ?? null,
            $data['reason'],
            $data['notes'] ?? null,
            $data['register_id'] ?? null,
        );

        try {
            $refund = $refunds->refund($saleTransaction, $items, $data['reason'], $data['notes'] ?? null, $approval,
                array_intersect_key($data, array_flip(['condition', 'operation_key', 'settlement_reference'])));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $msg = $refund->is_full_refund
            ? 'Sale fully refunded. Return condition and settlement recorded.'
            : 'Partial refund of ₱'.number_format((float) $refund->refund_amount, 2).' processed.';

        return redirect()->route('pos.show', $saleTransaction)->with('status', $msg);
    }
}
