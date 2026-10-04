<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\PendingEwalletVerification;
use App\Models\PendingCardVerification;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\CheckoutService;
use App\Services\CardVerificationService;
use App\Services\AuditLogger;
use App\Services\EwalletVerificationService;
use App\Services\ManagerAuthorizationService;
use App\Services\PromotionService;
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
    ) {
    }

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

    public function productStock(): JsonResponse
    {
        return response()->json([
            'stocks' => $this->saleService->getProductStockSnapshot(),
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        return response()->json([
            'customers' => $this->saleService->searchPosCustomers($data['q']),
        ]);
    }

    public function checkCoupon(Request $request, PromotionService $promotionService): JsonResponse
    {
        $data = $request->validate([
            'coupon_code' => 'required|string|max:40',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer',
        ]);

        try {
            $coupon = $promotionService->validateCoupon(
                $data['coupon_code'],
                (float) $data['subtotal'],
                $data['customer_id'] ?? null
            );

            $discountAmount = $promotionService->couponDiscount($coupon, (float) $data['subtotal']);

            return response()->json([
                'valid' => true,
                'code' => $coupon->code,
                'coupon_type' => $coupon->type,
                'discount_amount' => round($discountAmount, 2),
                'message' => "Coupon {$coupon->code} applied (-₱" . number_format($discountAmount, 2) . ")",
            ]);
        } catch (ValidationException $e) {
            $error = $e->errors()['coupon_code'][0] ?? 'Coupon is not valid.';
            return response()->json(['valid' => false, 'message' => $error], 422);
        } catch (\Throwable $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 422);
        }
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
                'not_regex:/^(?:\d[\s-]*){13,19}$/',
                'not_regex:/^\d{3,4}$/',
            ],
            'e-wallet' => 'required|string|max:100',
            default => 'nullable|string|max:100',
        };

        $data = $request->validate([
            'customer_id' => 'nullable|exists:customer,customer_id',
            'idempotency_key' => 'nullable|uuid',
            'discount_id' => 'nullable|exists:discount,discount_id',
            'coupon_code' => 'nullable|string|max:40',
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
            'customer' => $customerId ? Customer::query()->find($customerId) : null,
            'canReview' => $employee->hasRole('Manager') && $pendingEwalletVerification->status === PendingEwalletVerification::STATUS_PENDING,
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
            'customer' => $customerId ? Customer::query()->find($customerId) : null,
            'canReview' => $employee->hasRole('Manager') && $pendingCardVerification->status === PendingCardVerification::STATUS_PENDING,
            'canReveal' => $employee->hasRole('Manager'),
        ]);
    }

    public function verifyPendingEwallet(Request $request, PendingEwalletVerification $pendingEwalletVerification): RedirectResponse
    {
        $request->validate([
            'merchant_checked' => 'required|accepted',
        ]);

        $sale = $this->ewalletVerifications->verify($pendingEwalletVerification, (int) auth()->id());
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);

        return redirect()
            ->route('pos.show', ['saleTransaction' => $sale, 'completed' => 1])
            ->with('status', 'Payment verified in the merchant app. The sale is complete and the receipt is ready.');
    }

    public function rejectPendingEwallet(Request $request, PendingEwalletVerification $pendingEwalletVerification): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->ewalletVerifications->reject($pendingEwalletVerification, (int) auth()->id(), $data['reason']);

        return redirect()
            ->route('pos.pending-ewallet.show', $pendingEwalletVerification)
            ->with('status', 'Payment request rejected and reserved inventory released. Handle any customer refund separately in the merchant app.');
    }

    public function verifyPendingCard(Request $request, PendingCardVerification $pendingCardVerification): RedirectResponse
    {
        $request->validate(['terminal_checked' => 'required|accepted']);

        $sale = $this->cardVerifications->verify($pendingCardVerification, (int) auth()->id());
        $request->session()->put('pos.last_receipt_transaction_id', (int) $sale->transaction_id);

        return redirect()
            ->route('pos.show', ['saleTransaction' => $sale, 'completed' => 1])
            ->with('status', 'Card approval and amount were confirmed against the terminal record. The sale is complete and the receipt is ready.');
    }

    public function rejectPendingCard(Request $request, PendingCardVerification $pendingCardVerification): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $this->cardVerifications->reject($pendingCardVerification, (int) auth()->id(), $data['reason']);

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
    ): View
    {
        $sale = $this->saleService->loadSaleForReceipt($saleTransaction);

        return view('pos.show', [
            'sale' => $sale,
            'receiptSettings' => $receiptSettings->forReceipt($sale->receipt),
            'isReprint' => $request->boolean('reprint'),
            'saleCompleted' => $request->boolean('completed') && ! $request->boolean('reprint'),
        ]);
    }

    public function slip(\App\Models\SaleRefund $refund): View
    {
        $refund->load(['sale.receipt', 'sale.employee', 'sale.customer', 'items.saleDetail.product', 'employee']);

        return view('pos.refund-slip', ['refund' => $refund]);
    }

    public function refund(Request $request, SaleTransaction $saleTransaction, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(RefundService::REASONS)),
            'notes' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*' => 'nullable|numeric|min:0|decimal:0,3',
            'manager_authorization_token' => 'nullable|string|max:100',
            'register_id' => 'nullable|string|max:50',
        ]);

        // Keep only lines with qty > 0; empty => service treats as full refund.
        $items = array_filter(
            array_map(static fn ($quantity) => round((float) $quantity, 3), (array) ($data['items'] ?? [])),
            static fn (float $quantity) => $quantity > 0,
        );

        $approval = $this->authorizations->requireGrant(
            $request,
            'sale_refund',
            ['sale_id' => (int) $saleTransaction->transaction_id, 'items' => $items],
            $data['manager_authorization_token'] ?? null,
            $data['reason'],
            $data['notes'] ?? null,
            $data['register_id'] ?? null,
        );

        try {
            $refund = $refunds->refund($saleTransaction, $items, $data['reason'], $data['notes'] ?? null, $approval);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('refund') ?: $e->getMessage());
        }

        $msg = $refund->is_full_refund
            ? 'Sale fully refunded — inventory restored, points reversed.'
            : 'Partial refund of ₱'.number_format((float) $refund->refund_amount, 2).' processed.';

        return redirect()->route('pos.show', $saleTransaction)->with('status', $msg);
    }
}
