@extends('layouts.app')

@section('title', 'Review Card payment')

@push('styles')
<style>
    .card-review { display: grid; max-width: 1240px; gap: 12px; margin: 0 auto; }
    .card-review-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 88px; padding: 16px 20px; border-radius: 14px; background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12); }
    .card-review-heading h1 { margin: 0; color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: -.025em; }
    .card-review-heading p { margin: 5px 0 0; color: rgba(255,255,255,.78); font-size: .84rem; }
    .card-review-eyebrow { margin: 0 0 3px !important; color: #B8D9C4 !important; font-size: .66rem !important; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .card-review-heading .btn-secondary { min-height: 36px; border-color: rgba(255,255,255,.42); background: #fff; color: #203C3D; font-weight: 800; }
    .card-review-summary { display: grid; grid-template-columns: minmax(170px, .6fr) minmax(0, 1.4fr); align-items: center; gap: 14px 20px; padding: 16px 18px; border: 1px solid rgba(32,60,61,.08); border-radius: 14px; background: #203C3D; box-shadow: 0 4px 12px rgba(32,60,61,.1); }
    .card-review-amount { display: grid; gap: 3px; }
    .card-review-amount span { color: #B8D9C4; font-size: .76rem; font-weight: 750; }
    .card-review-amount strong { color: #fff; font-size: clamp(1.65rem, 4vw, 2.1rem); font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.035em; line-height: 1.1; }
    .card-review-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 0; }
    .card-review-facts div { min-width: 0; display: grid; gap: 4px; padding: 9px 10px; border: 1px solid rgba(255,255,255,.15); border-radius: 9px; background: rgba(255,255,255,.08); }
    .card-review-facts dt { color: #B8D9C4; font-size: .67rem; font-weight: 750; }
    .card-review-facts dd { min-width: 0; margin: 0; color: #fff; font-size: .79rem; font-weight: 750; overflow-wrap: anywhere; }
    .card-review-grid { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 14px; align-items: start; }
    .card-review-panel { min-width: 0; padding: 16px; border: 1px solid var(--rule-faint); border-radius: 14px; background: var(--surface); box-shadow: var(--shadow-card); }
    .card-review-panel h2 { margin: 0 0 12px; color: #203C3D; font-size: .96rem; font-weight: 800; }
    .card-review-details { display: grid; grid-template-columns: minmax(108px, .7fr) minmax(0, 1.3fr); gap: 9px 14px; margin: 0; }
    .card-review-details dt { color: var(--muted); font-size: .78rem; }
    .card-review-details dd { min-width: 0; margin: 0; color: var(--text); font-size: .82rem; font-weight: 650; overflow-wrap: anywhere; }
    .card-review-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .card-review-table th, .card-review-table td { padding: 9px 7px; border-bottom: 1px solid var(--rule-faint); text-align: left; font-size: .8rem; }
    .card-review-table th { background: #203C3D; color: #fff; font-size: .66rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 2px 5px rgba(32,60,61,.13); }
    .card-review-table td:last-child, .card-review-table th:last-child { text-align: right; white-space: nowrap; }
    .card-review-table tfoot td { border-bottom: 0; font-weight: 800; }
    .card-review-status { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #FFF0CF; color: #755317; font-size: .7rem; font-weight: 800; text-transform: capitalize; }
    .card-review-note { margin: 0; padding: 10px 12px; border: 1px solid rgba(24,118,94,.1); border-radius: 9px; background: #E7F2EB; color: #244A3A; font-size: .79rem; font-weight: 600; line-height: 1.5; }
    .card-review-actions { display: grid; gap: 11px; margin-top: 14px; }
    .card-review-actions form { display: grid; gap: 10px; }
    .card-review-actions label { color: var(--text); font-size: .82rem; }
    .card-review-actions textarea { width: 100%; min-height: 70px; resize: vertical; }
    .card-review-check { display: flex; align-items: flex-start; gap: 9px; }
    .card-review-check input { width: 17px; height: 17px; flex: 0 0 auto; margin-top: 2px; accent-color: var(--accent); }
    .card-review-result { display: grid; gap: 11px; padding: 12px; border: 1px solid rgba(24,118,94,.12); border-radius: 11px; background: #F4F8F5; }
    .card-review-result h2 { margin: 0; font-size: 1.12rem; }
    .card-review-result p { margin: 0; color: var(--muted); font-size: .84rem; line-height: 1.5; }
    .card-review-result-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .payment-reference-value { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
    .payment-reference-reveal { min-height: 28px; padding: 4px 8px; font-size: .68rem; }
    .card-approval-reference { margin-top: 12px; }
    .payment-reference-value [data-reference-value] { font-variant-numeric: tabular-nums; }
    .verification-cashier-notice { display: flex; align-items: center; gap: 8px; padding: 10px 14px; margin-bottom: 12px; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: #b45309; font-size: 0.85rem; }
    .card-review-actions [data-card-primary-action] { width: 100%; justify-content: center; min-height: 42px; font-weight: 800; }
    .card-review-table-wrap { overflow-x: auto; border: 1px solid var(--rule-faint); border-radius: 10px; }
    @media (max-width: 760px) {
        .card-review-grid, .card-review-summary { grid-template-columns: 1fr; }
        .card-review-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .card-review-heading { align-items: stretch; flex-direction: column; }
        .card-review-heading .btn-secondary { align-self: flex-start; }
        .card-review-summary, .card-review-panel { padding: 14px; }
        .card-review-result-actions > a { flex: 1 1 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
@php
    $customerId = data_get($pending->checkout_payload, 'customer_id');
    $customerLabel = $customer?->fullName() ?? ($customerId ? 'Customer #'.$customerId : 'Walk-in customer');
    $receiptNumber = $pending->sale?->receipt?->receipt_number ?? '#'.$pending->sale?->transaction_id;
@endphp
<section class="card-review" aria-label="Card payment review">
    <header class="card-review-heading">
        <div>
            <p class="card-review-eyebrow">Payment review</p>
            <h1>Review Card payment</h1>
            <p>Compare the approval code and amount against the Card terminal record.</p>
        </div>
        <a class="btn btn-secondary" href="{{ auth()->user()->hasRole('Manager') ? route('pos.pending-card.index') : route('pos.index') }}">Back</a>
    </header>

    <section class="card-review-summary" aria-label="Payment summary">
        <div class="card-review-amount">
            <span>{{ $canReview ? 'Amount to verify' : 'Submitted amount' }}</span>
            <strong>₱{{ number_format((float) $pending->submitted_amount, 2) }}</strong>
        </div>
        <dl class="card-review-facts">
            <div><dt>Card network</dt><dd>{{ $pending->payment_provider }}</dd></div>
            <div><dt>Card</dt><dd><x-masked-value :value="$pending->maskedReference()" /></dd></div>
            <div><dt>Customer</dt><dd>{{ $customerLabel }}@if ($customerId)<br><span>ID {{ $customerId }}</span>@endif</dd></div>
            <div><dt>Cart</dt><dd>{{ count($itemSnapshots) }} {{ \Illuminate\Support\Str::plural('item', count($itemSnapshots)) }}</dd></div>
        </dl>
    </section>

    <div class="card-review-grid">
        <section class="card-review-panel" aria-labelledby="card-request-title">
            <h2 id="card-request-title">Request details</h2>
            <dl class="card-review-details">
                <dt>Status</dt><dd><span class="card-review-status">{{ $pending->status }}</span></dd>
                <dt>Cashier</dt><dd>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</dd>
                <dt>Submitted</dt><dd>{{ $pending->created_at?->format('M j, Y · g:i A') }}</dd>
                @if ($pending->expires_at && $pending->status === \App\Models\PendingCardVerification::STATUS_PENDING)
                    <dt>Reserved until</dt><dd>{{ $pending->expires_at->format('M j, Y · g:i A') }}</dd>
                @endif
                @if ($pending->verifier)
                    <dt>Verified by</dt><dd>{{ $pending->verifier->displayName() }} · {{ $pending->verified_at?->format('M j, Y · g:i A') }}</dd>
                @elseif ($pending->rejecter)
                    <dt>Rejected by</dt><dd>{{ $pending->rejecter->displayName() }} · {{ $pending->rejected_at?->format('M j, Y · g:i A') }}</dd>
                @endif
                @if ($pending->resolution_note)
                    <dt>Review note</dt><dd>{{ $pending->resolution_note }}</dd>
                @endif
            </dl>
            @if ($canReveal)
                <div class="payment-reference-value card-approval-reference">
                    <strong>Terminal approval code</strong>
                    <span data-reference-value>{{ $pending->maskedApprovalCode() }}</span>
                    <button type="button" class="btn btn-secondary payment-reference-reveal" data-reference-reveal data-url="{{ route('pos.pending-card.reveal', $pending) }}" data-masked-value="{{ $pending->maskedApprovalCode() }}">Reveal</button>
                </div>
            @endif
        </section>

        <section class="card-review-panel" aria-labelledby="card-cart-title">
            <h2 id="card-cart-title">Cart summary</h2>
            <div class="card-review-table-wrap"><table class="card-review-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($itemSnapshots as $item)
                        <tr>
                            <td>{{ $item['product_name'] }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item['quantity'], 3), '0'), '.') }}</td>
                            <td>₱{{ number_format((float) $item['unit_price'], 2) }}</td>
                            <td>₱{{ number_format((float) $item['quantity'] * (float) $item['unit_price'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="3">Amount submitted</td><td>₱{{ number_format((float) $pending->submitted_amount, 2) }}</td></tr></tfoot>
            </table></div>

            @if ($canReview)
                <div class="card-review-actions">
                    @if (auth()->user()->hasRole('Cashier'))
                        <div class="verification-cashier-notice">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span><strong>Cashier Terminal:</strong> Requires Manager PIN authorization to verify or reject this card transaction.</span>
                        </div>
                    @endif
                    <p class="card-review-note">Check the Card terminal record or merchant receipt. A manager confirmation is a manual check; this system does not contact the card network.</p>
                    <form data-card-review-form data-action-type="verify" method="POST" action="{{ route('pos.pending-card.verify', $pending) }}">
                        @csrf
                        <div class="card-review-check">
                            <input id="terminal-checked" type="checkbox" name="terminal_checked" value="1" required>
                            <label for="terminal-checked">I confirmed the approval code and amount against the completed Card terminal record.</label>
                        </div>
                        <button class="btn" type="submit" data-card-primary-action>
                            @if (auth()->user()->hasRole('Cashier'))
                                Authorize with Manager PIN & Complete Sale <span class="ewallet-key-hint">Enter</span>
                            @else
                                Verify and complete sale <span class="ewallet-key-hint">Enter</span>
                            @endif
                        </button>
                    </form>
                    <div class="ewallet-action-divider" aria-hidden="true"></div>
                    <form data-card-review-form data-action-type="reject" method="POST" action="{{ route('pos.pending-card.reject', $pending) }}">
                        @csrf
                        <div>
                            <label for="card-reject-reason">Reason for rejection</label>
                            <textarea id="card-reject-reason" class="input-lg bordered" name="reason" rows="2" maxlength="255" required placeholder="For example: approval not found or amount does not match"></textarea>
                        </div>
                        <button class="btn btn-danger" type="submit">
                            @if (auth()->user()->hasRole('Cashier'))
                                Reject with Manager PIN
                            @else
                                Reject payment
                            @endif
                        </button>
                    </form>
                    <p class="card-review-note">Rejecting releases reserved stock. Handle any customer refund or reversal through the terminal provider as needed.</p>
                </div>
            @elseif ($pending->sale)
                <div class="card-review-result" aria-live="polite">
                    <h2>Payment verified</h2>
                    <p>The sale is complete and the receipt is ready.</p>
                    <div class="ewallet-result-summary">
                        <div><span>Receipt</span><strong>{{ $receiptNumber }}</strong></div>
                        <div><span>Total</span><strong>₱{{ number_format((float) $pending->sale->total_amount, 2) }}</strong></div>
                    </div>
                    <div class="card-review-result-actions">
                        <a class="btn btn-secondary" href="{{ route('pos.show', $pending->sale) }}">Open receipt</a>
                        <a class="btn" href="{{ route('pos.index') }}" data-card-return-to-pos>Next sale <span class="ewallet-key-hint">Enter</span></a>
                    </div>
                </div>
            @elseif ($pending->status === \App\Models\PendingCardVerification::STATUS_REJECTED)
                <div class="card-review-result" aria-live="polite">
                    <h2>Payment rejected</h2>
                    <p>The payment was not verified and reserved inventory has been released.</p>
                    @if ($pending->resolution_note)
                        <div class="ewallet-result-summary"><div><span>Reason</span><strong>{{ $pending->resolution_note }}</strong></div></div>
                    @endif
                    <div class="card-review-result-actions"><a class="btn" href="{{ route('pos.index') }}" data-card-return-to-pos>Back to POS <span class="ewallet-key-hint">Enter</span></a></div>
                </div>
            @elseif ($pending->status === \App\Models\PendingCardVerification::STATUS_PENDING)
                <p class="card-review-note">Awaiting manager review. Requested stock remains reserved until review or expiration.</p>
            @else
                <p class="card-review-note">This Card request is {{ $pending->status }} and can no longer be reviewed.</p>
                <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
            @endif
        </section>
    </div>
</section>
<x-manager-pin-modal :is-manager="auth()->user()->hasRole('Manager')" />
@endsection

@php
    $checkoutKeyToClear = session('pos.checkout_key_to_clear');
    $checkoutKeyStorageKey = 'pos.checkout-idempotency.v1.'.(string) $pending->employee_id;
@endphp
@if ($checkoutKeyToClear)
    @push('scripts')
    <script>
        try {
            const checkoutKeyStorageKey = @json($checkoutKeyStorageKey);
            const checkoutKeyToClear = @json($checkoutKeyToClear);
            if (window.sessionStorage.getItem(checkoutKeyStorageKey) === checkoutKeyToClear) {
                window.sessionStorage.removeItem(checkoutKeyStorageKey);
            }
        } catch (error) {
            console.warn('Checkout retry key could not be cleared from this browser tab.', error);
        }
    </script>
    @endpush
@endif

@push('scripts')
<script>
    (() => {
        const primaryAction = document.querySelector('[data-card-primary-action]');
        const returnToPos = document.querySelector('[data-card-return-to-pos]');
        let submissionStarted = false;
        let returnNavigationStarted = false;

        if (primaryAction) primaryAction.focus({ preventScroll: true });
        if (returnToPos) returnToPos.focus({ preventScroll: true });

        const isManager = @json(auth()->user()->hasRole('Manager'));

        document.querySelectorAll('[data-card-review-form]').forEach(form => {
            form.addEventListener('submit', async event => {
                if (form.querySelector('input[name="manager_authorization_token"]')) {
                    if (submissionStarted) {
                        event.preventDefault();
                        return;
                    }
                    submissionStarted = true;
                    form.querySelectorAll('button[type="submit"]').forEach(button => {
                        button.disabled = true;
                        button.setAttribute('aria-busy', 'true');
                    });
                    return;
                }

                if (!isManager) {
                    event.preventDefault();
                    const actionType = form.dataset.actionType || (form.action.includes('verify') ? 'verify' : 'reject');
                    const isVerify = actionType === 'verify';
                    const reasonInput = form.querySelector('textarea[name="reason"]');
                    const reason = reasonInput ? reasonInput.value.trim() : null;

                    if (!isVerify && (!reasonInput || !reasonInput.reportValidity())) {
                        return;
                    }

                    if (isVerify) {
                        const checkInput = form.querySelector('input[name="terminal_checked"]');
                        if (checkInput && !checkInput.reportValidity()) {
                            return;
                        }
                    }

                    if (typeof window.requestManagerAuthorization !== 'function') {
                        alert('Manager PIN authorization is not available.');
                        return;
                    }

                    const authorization = await window.requestManagerAuthorization({
                        action: isVerify ? 'pending_card_verify' : 'pending_card_reject',
                        details: { pending_id: @json($pending->id) },
                        requiresReason: false,
                        reason: reason,
                    });

                    if (!authorization || !authorization.token) {
                        return;
                    }

                    let tokenInput = form.querySelector('input[name="manager_authorization_token"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'manager_authorization_token';
                        form.appendChild(tokenInput);
                    }
                    tokenInput.value = authorization.token;

                    submissionStarted = true;
                    form.querySelectorAll('button[type="submit"]').forEach(button => {
                        button.disabled = true;
                        button.setAttribute('aria-busy', 'true');
                    });

                    form.submit();
                    return;
                }

                if (submissionStarted) {
                    event.preventDefault();
                    return;
                }
                submissionStarted = true;
                form.querySelectorAll('button[type="submit"]').forEach(button => {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                });
            });
        });

        document.querySelectorAll('[data-reference-reveal]').forEach(button => {
            let remaskTimer = null;
            button.addEventListener('click', async () => {
                const value = button.parentElement?.querySelector('[data-reference-value]');
                if (!value) return;
                if (remaskTimer !== null) {
                    window.clearTimeout(remaskTimer);
                    remaskTimer = null;
                    value.textContent = button.dataset.maskedValue || '';
                    button.textContent = 'Reveal';
                    return;
                }

                button.disabled = true;
                try {
                    const response = await fetch(button.dataset.url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    if (!response.ok) throw new Error('Reference reveal was not authorized.');
                    const result = await response.json();
                    value.textContent = result.reference || '';
                    button.textContent = 'Hide';
                    remaskTimer = window.setTimeout(() => {
                        value.textContent = button.dataset.maskedValue || '';
                        button.textContent = 'Reveal';
                        remaskTimer = null;
                    }, 10000);
                } catch (error) {
                    value.textContent = button.dataset.maskedValue || '';
                    button.textContent = 'Reveal';
                } finally {
                    button.disabled = false;
                }
            });
        });

        if (returnToPos) {
            returnToPos.addEventListener('click', event => {
                if (returnNavigationStarted) {
                    event.preventDefault();
                    return;
                }
                returnNavigationStarted = true;
            });
            window.addEventListener('keydown', event => {
                if (event.key !== 'Enter' || event.repeat || document.activeElement !== returnToPos) return;
                event.preventDefault();
                if (returnNavigationStarted) return;
                returnNavigationStarted = true;
                window.location.assign(returnToPos.href);
            });
        }
    })();
</script>
@endpush
