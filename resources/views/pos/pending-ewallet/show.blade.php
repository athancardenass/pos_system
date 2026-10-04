@extends('layouts.app')

@section('title', 'Review e-wallet payment')

@push('styles')
<style>
    .ewallet-review { display: grid; max-width: 1240px; gap: 12px; margin: 0 auto; }
    .ewallet-review-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 88px; padding: 16px 20px; border-radius: 14px; background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12); }
    .ewallet-review-heading h1 { margin: 0; color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: -.025em; }
    .ewallet-review-heading p { margin: 5px 0 0; color: rgba(255,255,255,.78); font-size: .84rem; }
    .ewallet-review-eyebrow { margin: 0 0 3px !important; color: #B8D9C4 !important; font-size: .66rem !important; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .ewallet-review-heading .btn-secondary { min-height: 36px; border-color: rgba(255,255,255,.42); background: #fff; color: #203C3D; font-weight: 800; }
    .ewallet-payment-summary { display: grid; grid-template-columns: minmax(170px, .6fr) minmax(0, 1.4fr); align-items: center; gap: 14px 20px; padding: 16px 18px; border: 1px solid rgba(32,60,61,.08); border-radius: 14px; background: #203C3D; box-shadow: 0 4px 12px rgba(32,60,61,.1); }
    .ewallet-payment-amount { display: grid; gap: 3px; }
    .ewallet-payment-amount span { color: #B8D9C4; font-size: .76rem; font-weight: 750; }
    .ewallet-payment-amount strong { color: #fff; font-size: clamp(1.65rem, 4vw, 2.1rem); font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.035em; line-height: 1.1; }
    .ewallet-payment-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 0; }
    .ewallet-payment-facts div { min-width: 0; display: grid; gap: 4px; padding: 9px 10px; border: 1px solid rgba(255,255,255,.15); border-radius: 9px; background: rgba(255,255,255,.08); }
    .ewallet-payment-facts dt { color: #B8D9C4; font-size: .67rem; font-weight: 750; }
    .ewallet-payment-facts dd { min-width: 0; margin: 0; color: #fff; font-size: .79rem; font-weight: 750; overflow-wrap: anywhere; }
    .ewallet-review-grid { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 14px; align-items: start; }
    .ewallet-review-panel { min-width: 0; padding: 16px; border: 1px solid var(--rule-faint); border-radius: 14px; background: var(--surface); box-shadow: var(--shadow-card); }
    .ewallet-review-panel h2 { margin: 0 0 12px; color: #203C3D; font-size: .96rem; font-weight: 800; }
    .ewallet-details { display: grid; grid-template-columns: minmax(108px, .7fr) minmax(0, 1.3fr); gap: 9px 14px; margin: 0; }
    .ewallet-details dt { color: var(--muted); font-size: .78rem; }
    .ewallet-details dd { min-width: 0; margin: 0; color: var(--text); font-size: .82rem; font-weight: 650; overflow-wrap: anywhere; }
    .ewallet-review-table-wrap { overflow-x: auto; border: 1px solid var(--rule-faint); border-radius: 10px; }
    .ewallet-review-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ewallet-review-table th, .ewallet-review-table td { padding: 9px 7px; border-bottom: 1px solid var(--rule-faint); text-align: left; font-size: .8rem; }
    .ewallet-review-table th { background: #203C3D; color: #fff; font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .045em; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 2px 5px rgba(32,60,61,.13); }
    .ewallet-review-table td:last-child, .ewallet-review-table th:last-child { text-align: right; white-space: nowrap; }
    .ewallet-review-table tfoot td { border-bottom: 0; font-weight: 800; }
    .ewallet-status-pill { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #FFF0CF; color: #755317; font-size: .7rem; font-weight: 800; text-transform: capitalize; }
    .ewallet-status-note { margin: 0; padding: 10px 12px; border: 1px solid rgba(24,118,94,.1); border-radius: 9px; background: #E7F2EB; color: #244A3A; font-size: .79rem; font-weight: 600; line-height: 1.5; }
    .ewallet-action-stack { display: grid; gap: 11px; margin-top: 14px; }
    .ewallet-action-stack form { display: grid; gap: 10px; }
    .ewallet-action-stack label { color: var(--text); font-size: .82rem; }
    .ewallet-action-stack textarea { width: 100%; min-height: 70px; resize: vertical; }
    .ewallet-verify-check { display: flex; align-items: flex-start; gap: 9px; }
    .ewallet-verify-check input { width: 17px; height: 17px; flex: 0 0 auto; margin-top: 2px; accent-color: var(--accent); }
    .ewallet-action-divider { height: 1px; margin: 2px 0; background: var(--rule-faint); }
    .ewallet-key-hint { display: inline-flex; align-items: center; margin-left: 7px; padding: 2px 6px; border: 1px solid currentColor; border-radius: 5px; font-size: .65rem; font-weight: 750; line-height: 1.2; opacity: .8; }
    .ewallet-result { display: grid; gap: 11px; }
    .ewallet-result h2 { margin: 0; font-size: 1.12rem; }
    .ewallet-result p { margin: 0; color: var(--muted); font-size: .84rem; line-height: 1.5; }
    .ewallet-result-summary { display: grid; gap: 7px; padding: 12px; border-radius: 9px; background: var(--surface-soft); }
    .ewallet-result-summary div { display: flex; justify-content: space-between; gap: 12px; font-size: .79rem; }
    .ewallet-result-summary span { color: var(--muted); }
    .ewallet-result-summary strong { color: var(--text); text-align: right; overflow-wrap: anywhere; }
    .ewallet-result-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .payment-reference-value { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
    .payment-reference-reveal { min-height: 28px; padding: 4px 8px; font-size: .68rem; }
    .ewallet-action-stack [data-ewallet-primary-action] { width: 100%; justify-content: center; min-height: 42px; font-weight: 800; }
    .ewallet-result { padding: 12px; border: 1px solid rgba(24,118,94,.12); border-radius: 11px; background: #F4F8F5; }
    @media (max-width: 760px) {
        .ewallet-review-grid { grid-template-columns: 1fr; }
        .ewallet-payment-summary { grid-template-columns: 1fr; }
        .ewallet-payment-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .ewallet-review-heading { align-items: stretch; flex-direction: column; }
        .ewallet-review-heading .btn-secondary { align-self: flex-start; }
        .ewallet-payment-summary, .ewallet-review-panel { padding: 14px; }
        .ewallet-payment-facts { grid-template-columns: 1fr 1fr; gap: 10px; }
        .ewallet-details { grid-template-columns: minmax(95px, .65fr) minmax(0, 1.35fr); gap: 8px; }
        .ewallet-result-actions > a { flex: 1 1 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
@php
    $customerId = data_get($pending->checkout_payload, 'customer_id');
    $customerLabel = $customer?->fullName() ?? ($customerId ? 'Customer #'.$customerId : 'Walk-in customer');
    $receiptNumber = $pending->sale?->receipt?->receipt_number ?? '#'.$pending->sale?->transaction_id;
@endphp
<section class="ewallet-review" aria-label="E-wallet payment review">
    <header class="ewallet-review-heading">
        <div>
            <p class="ewallet-review-eyebrow">Payment review</p>
            <h1>Review e-wallet payment</h1>
            <p>Match the reference and amount against the completed transaction in the merchant app.</p>
        </div>
        <a class="btn btn-secondary" href="{{ auth()->user()->hasRole('Manager') ? route('pos.pending-ewallet.index') : route('pos.index') }}">Back</a>
    </header>

    <section class="ewallet-payment-summary" aria-label="Payment summary">
        <div class="ewallet-payment-amount">
            <span>{{ $canReview ? 'Amount to verify' : 'Submitted amount' }}</span>
            <strong>₱{{ number_format((float) $pending->submitted_amount, 2) }}</strong>
        </div>
        <dl class="ewallet-payment-facts">
            <div><dt>Provider</dt><dd>{{ $pending->payment_provider }}</dd></div>
            <div>
                <dt>Reference number</dt>
                <dd class="payment-reference-value">
                    <span data-reference-value>{{ $pending->maskedReference() }}</span>
                    @if ($canReveal)
                        <button type="button" class="btn btn-secondary payment-reference-reveal" data-reference-reveal data-url="{{ route('pos.pending-ewallet.reveal', $pending) }}" data-masked-value="{{ $pending->maskedReference() }}">Reveal</button>
                    @endif
                </dd>
            </div>
            <div><dt>Customer</dt><dd>{{ $customerLabel }}@if ($customerId)<br><span>ID {{ $customerId }}</span>@endif</dd></div>
            <div><dt>Cart</dt><dd>{{ count($itemSnapshots) }} {{ \Illuminate\Support\Str::plural('item', count($itemSnapshots)) }}</dd></div>
        </dl>
    </section>

    <div class="ewallet-review-grid">
        <section class="ewallet-review-panel" aria-labelledby="ewallet-request-title">
            <h2 id="ewallet-request-title">Request details</h2>
            <dl class="ewallet-details">
                <dt>Status</dt><dd><span class="ewallet-status-pill">{{ $pending->status }}</span></dd>
                <dt>Cashier</dt><dd>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</dd>
                <dt>Submitted</dt><dd>{{ $pending->created_at?->format('M j, Y · g:i A') }}</dd>
                @if ($pending->expires_at && $pending->status === \App\Models\PendingEwalletVerification::STATUS_PENDING)
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
        </section>

        <section class="ewallet-review-panel" aria-labelledby="ewallet-cart-title">
            <h2 id="ewallet-cart-title">Cart summary</h2>
            <div class="ewallet-review-table-wrap"><table class="ewallet-review-table">
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
                <div class="ewallet-action-stack">
                    <p class="ewallet-status-note">Check the recipient account, completed status, reference number, and exact amount in the merchant app before approving.</p>
                    <form class="ewallet-review-form" data-ewallet-review-form method="POST" action="{{ route('pos.pending-ewallet.verify', $pending) }}">
                        @csrf
                        <div class="ewallet-verify-check">
                            <input id="merchant-checked" type="checkbox" name="merchant_checked" value="1" required>
                            <label for="merchant-checked">I confirmed the completed payment, reference, and amount in the merchant app.</label>
                        </div>
                        <button class="btn" type="submit" data-ewallet-primary-action>Verify and complete sale <span class="ewallet-key-hint">Enter</span></button>
                    </form>
                    <div class="ewallet-action-divider" aria-hidden="true"></div>
                    <form class="ewallet-review-form" data-ewallet-review-form method="POST" action="{{ route('pos.pending-ewallet.reject', $pending) }}">
                        @csrf
                        <div>
                            <label for="ewallet-reject-reason">Reason for rejection</label>
                            <textarea id="ewallet-reject-reason" class="input-lg bordered" name="reason" rows="2" maxlength="255" required placeholder="For example: reference not found or amount does not match"></textarea>
                        </div>
                        <button class="btn btn-danger" type="submit">Reject payment</button>
                    </form>
                    <p class="ewallet-status-note">Rejecting releases reserved stock. If money was received, process any customer refund separately in the merchant app.</p>
                </div>
            @elseif ($pending->sale)
                <div class="ewallet-result" aria-live="polite">
                    <h2>Payment verified</h2>
                    <p>The sale is complete and the receipt is ready.</p>
                    <div class="ewallet-result-summary">
                        <div><span>Receipt</span><strong>{{ $receiptNumber }}</strong></div>
                        <div><span>Total</span><strong>₱{{ number_format((float) $pending->sale->total_amount, 2) }}</strong></div>
                    </div>
                    <div class="ewallet-result-actions">
                        <a class="btn btn-secondary" href="{{ route('pos.show', $pending->sale) }}">Open receipt</a>
                        <a class="btn" href="{{ route('pos.index') }}" data-ewallet-return-to-pos>Next sale <span class="ewallet-key-hint">Enter</span></a>
                    </div>
                </div>
            @elseif ($pending->status === \App\Models\PendingEwalletVerification::STATUS_REJECTED)
                <div class="ewallet-result" aria-live="polite">
                    <h2>Payment rejected</h2>
                    <p>The payment was not verified and the reserved inventory has been released. Handle any customer refund separately in the merchant app.</p>
                    @if ($pending->resolution_note)
                        <div class="ewallet-result-summary"><div><span>Reason</span><strong>{{ $pending->resolution_note }}</strong></div></div>
                    @endif
                    <div class="ewallet-result-actions">
                        <a class="btn" href="{{ route('pos.index') }}" data-ewallet-return-to-pos>Back to POS <span class="ewallet-key-hint">Enter</span></a>
                    </div>
                </div>
            @elseif ($pending->status === \App\Models\PendingEwalletVerification::STATUS_PENDING)
                <p class="ewallet-status-note">Awaiting manager review. The requested stock stays reserved until it is reviewed or the reservation expires.</p>
            @else
                <p class="ewallet-status-note">This payment request is {{ $pending->status }} and can no longer be reviewed.</p>
                <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
            @endif
        </section>
    </div>
</section>
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
        const primaryAction = document.querySelector('[data-ewallet-primary-action]');
        const returnToPos = document.querySelector('[data-ewallet-return-to-pos]');
        let submissionStarted = false;
        let returnNavigationStarted = false;

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

        if (primaryAction) primaryAction.focus({ preventScroll: true });
        if (returnToPos) returnToPos.focus({ preventScroll: true });

        document.querySelectorAll('[data-ewallet-review-form]').forEach(form => {
            form.addEventListener('submit', event => {
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
