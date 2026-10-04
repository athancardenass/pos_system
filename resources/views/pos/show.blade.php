@extends('layouts.app')

@section('title', 'Receipt '.$sale->receipt?->receipt_number)

@section('content')
    @php
        $taxBreakdown = $sale->taxBreakdown();
        $discountBreakdown = $sale->discountBreakdown();
        $paperWidthMm = in_array((string) ($receiptSettings['paper_width'] ?? '58'), ['58', '80'], true) ? (string) $receiptSettings['paper_width'] : '58';
        $vatRateLabel = rtrim(rtrim(number_format($taxBreakdown['vat_rate'] * 100, 2, '.', ''), '0'), '.');
        $receiptNumber = $sale->receipt?->receipt_number ?? '#'.$sale->transaction_id;
        $isEwalletCompletion = $saleCompleted && $sale->payment_method === 'e-wallet';
        $checkoutKeyToClear = session('pos.checkout_key_to_clear');
        $checkoutKeyStorageKey = 'pos.checkout-idempotency.v1.'.(string) $sale->employee_id;
    @endphp

    @if ($saleCompleted)
        <section class="receipt-complete-banner" aria-label="Sale completed">
            <div>
                <p class="eyebrow">Payment recorded</p>
                <h1>Sale complete</h1>
                <p>Receipt {{ $receiptNumber }}</p>
            </div>
            <dl class="receipt-complete-summary">
                <div><dt>Total</dt><dd>₱{{ number_format($sale->total_amount, 2) }}</dd></div>
                <div><dt>{{ $sale->payment_method === 'cash' ? 'Cash received' : 'Amount received' }}</dt><dd>₱{{ number_format($sale->payment?->amount_paid ?? 0, 2) }}</dd></div>
                <div><dt>Change</dt><dd>₱{{ number_format($sale->payment?->change_amount ?? 0, 2) }}</dd></div>
            </dl>
            <div class="receipt-complete-actions">
                <button type="button" class="{{ $isEwalletCompletion ? 'btn btn-secondary' : 'btn' }}" onclick="window.print()">Print receipt</button>
                @if ($isEwalletCompletion)
                    <a class="btn" href="{{ route('pos.index') }}" data-ewallet-next-sale>Next sale <span class="receipt-key-hint">Enter</span></a>
                @else
                    <a class="btn btn-secondary" href="{{ route('pos.index') }}">New sale</a>
                @endif
            </div>
        </section>
    @else
    <div class="page-head">
        <h1>{{ $isReprint ? 'Reprint receipt' : 'Receipt' }} {{ $receiptNumber }}</h1>
        <div class="receipt-page-actions">
            <button type="button" class="btn" onclick="window.print()">{{ $isReprint ? 'Print reprint' : 'Print receipt' }}</button>
            @if (! $sale->isFullyRefunded())
                <button type="button" class="btn btn-danger" onclick="toggleRefundPanel()">Refund</button>
            @else
                <span class="btn btn-secondary receipt-refunded-chip">Fully refunded</span>
            @endif
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">New sale</a>
        </div>
    </div>
    @endif

    <div class="receipt-stage">
        <article class="receipt-paper" id="receipt-screen" aria-label="Sale receipt" data-paper-width="{{ $paperWidthMm }}">
            @if ($isReprint)
                <div class="receipt-reprint-stamp">REPRINT</div>
            @endif
            @if ($sale->isFullyRefunded())
                <div class="receipt-refunded">Refunded — Not Valid for Payment</div>
            @endif
            <header class="receipt-store">
                <div class="receipt-brand">{{ $receiptSettings['store_name'] ?? 'Your Store' }}</div>
                @if (filled($receiptSettings['store_address'] ?? null))
                    <div class="receipt-store-location"><span>Location</span><strong>{{ $receiptSettings['store_address'] }}</strong></div>
                @endif
                @if (filled($receiptSettings['tin'] ?? null))
                    <div>TIN: {{ $receiptSettings['tin'] }}</div>
                @endif
            </header>

            <div class="receipt-rule"></div>
            <section class="receipt-meta" aria-label="Transaction information">
                <div><span>Receipt / OR</span><strong>{{ $receiptNumber }}</strong></div>
                <div><span>Transaction</span><strong>#{{ $sale->transaction_id }}</strong></div>
                <div><span>Date / time</span><strong>{{ $sale->transaction_date?->format('M j, Y · g:i A') ?? '—' }}</strong></div>
                <div><span>Cashier</span><strong>{{ $sale->employee?->displayName() ?? '—' }} · ID {{ $sale->employee?->employee_id ?? '—' }}</strong></div>
                <div><span>Register</span><strong>{{ $sale->receipt?->register_id ?? 'REG 01' }}</strong></div>
                <div><span>Customer</span><strong>{{ $sale->customer?->fullName() ?? 'Walk-in' }}</strong></div>
                @if ($sale->customer)
                    <div><span>Customer ID</span><strong>{{ $sale->customer->customer_id }}</strong></div>
                @endif
                @if ($sale->senior_pwd_type)
                    <div><span>{{ $sale->senior_pwd_type === 'pwd' ? 'PWD holder' : 'Senior Citizen' }}</span><strong>{{ $sale->senior_pwd_name }}</strong></div>
                    <div><span>Discount ID</span><strong>{{ $sale->senior_pwd_id_number }}</strong></div>
                @endif
            </section>

            <div class="receipt-rule"></div>
            <table class="receipt-items">
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($sale->saleDetails as $line)
                        <tr>
                            <td><strong>{{ $line->product->product_name ?? '—' }}</strong></td>
                            <td>{{ $line->quantity }}</td>
                            <td>₱{{ number_format($line->unit_price, 2) }}</td>
                            <td>₱{{ number_format($line->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="receipt-rule receipt-rule-light"></div>
            <section class="receipt-totals" aria-label="Sale totals">
                <div><span>Subtotal</span><strong>₱{{ number_format($sale->subtotal, 2) }}</strong></div>
                @if (($taxBreakdown['vat_exemption_amount'] ?? 0) > 0)
                    <div><span>VAT removed</span><strong>−₱{{ number_format($taxBreakdown['vat_exemption_amount'], 2) }}</strong></div>
                @endif
                @if ($discountBreakdown)
                    <div class="receipt-discount">
                        <span><b>{{ $discountBreakdown['name'] ?? 'Discount' }}</b><small>Code: {{ $discountBreakdown['reference'] ?? 'DISC-'.str_pad((string) $sale->discount_id, 3, '0', STR_PAD_LEFT) }}</small></span>
                        <strong>−₱{{ number_format((float) ($discountBreakdown['amount'] ?? 0), 2) }}</strong>
                    </div>
                @endif
                <div><span>VATable sales</span><strong>₱{{ number_format($taxBreakdown['vatable_sales'], 2) }}</strong></div>
                <div><span>VAT ({{ $vatRateLabel }}%, included)</span><strong>₱{{ number_format($taxBreakdown['vat_amount'], 2) }}</strong></div>
                <div><span>VAT-exempt sales</span><strong>₱{{ number_format($taxBreakdown['vat_exempt_sales'], 2) }}</strong></div>
                <div class="receipt-total"><span>TOTAL</span><strong>₱{{ number_format($sale->total_amount, 2) }}</strong></div>
                <div><span>Payment method</span><strong>{{ strtoupper($sale->payment_method) }}{{ $sale->payment?->payment_provider ? ' — '.$sale->payment->payment_provider : '' }}</strong></div>
                <div><span>{{ $sale->payment_method === 'cash' ? 'Cash received' : 'Amount received' }}</span><strong>₱{{ number_format($sale->payment?->amount_paid ?? 0, 2) }}</strong></div>
                @if ($sale->payment?->reference_ciphertext || $sale->payment?->card_last4)
                    <div><span>Payment reference</span><strong class="receipt-mono"><x-masked-value :value="$sale->payment->maskedReference()" /></strong></div>
                @endif
                <div><span>Change</span><strong>₱{{ number_format($sale->payment?->change_amount ?? 0, 2) }}</strong></div>
            </section>

            <div class="receipt-rule"></div>

            {{-- Footer --}}
            @php
                $daysOld = $sale->transaction_date ? (int) $sale->transaction_date->diffInDays(now()) : 0;
                $windowDays = \App\Services\RefundService::WINDOW_DAYS;
                $daysRemaining = max(0, $windowDays - $daysOld);
                $isOutsideWindow = $daysOld > $windowDays;
            @endphp
            <footer class="receipt-footer">
                @if (filled($receiptSettings['footer_text'] ?? null))
                    <strong>{{ $receiptSettings['footer_text'] }}</strong>
                @endif
                <span>Exchange or refund allowed within {{ $windowDays }} days with this official receipt.</span>
                @if (! $sale->isFullyRefunded())
                    <span class="receipt-policy {{ $isOutsideWindow ? 'expired' : 'active' }}">
                        @if ($isOutsideWindow)
                            Return policy expired ({{ $daysOld }} days old) &bull; Manager override required
                        @else
                            Refund window active: {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }} remaining
                        @endif
                    </span>
                @endif
                <span>{{ $receiptNumber }}</span>
            </footer>
        </div>
    </div>
    {{-- Refund panel (hidden until Refund clicked) --}}
    @if (! $sale->isFullyRefunded())
        <div id="refund-panel" style="display: none; max-width: 560px; margin: 1.5rem auto 0;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h2 style="margin: 0;">Refund this sale</h2>
                    <span style="font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: var(--r-pill); background: {{ $isOutsideWindow ? 'var(--accent-soft)' : 'var(--success-soft)' }}; color: {{ $isOutsideWindow ? 'var(--accent)' : 'var(--success)' }}; border: 1px solid currentColor;">
                        {{ $isOutsideWindow ? 'Outside 7-Day Window (' . $daysOld . 'd old) — Manager Override' : $daysRemaining . ' days left to refund' }}
                    </span>
                </div>
                @if ($isOutsideWindow)
                    <div style="background: var(--accent-soft); border: 1px solid rgba(196,80,74,.24); padding: 0.75rem 1rem; border-radius: var(--r-sm); margin-bottom: 1rem; font-size: 0.85rem; color: var(--text);">
                        <strong>Policy Notice:</strong> This transaction is {{ $daysOld }} days old, which exceeds the standard {{ $windowDays }}-day return policy. Only a <strong>Manager</strong> can authorize this refund, and it will be recorded in the audit log as a policy override.
                    </div>
                @endif
                <form method="POST" action="{{ route('pos.refund', $sale) }}">
                    @csrf
                    <input type="hidden" name="manager_authorization_token" id="refund-manager-auth-token">
                    <input type="hidden" name="register_id" id="refund-register-id">
                    <div class="form-grid">
                        @foreach ($sale->saleDetails as $line)
                            <div>
                                <label for="refund_qty_{{ $line->sale_detail_id }}">
                                    {{ $line->product->product_name ?? 'Item' }} — refund qty ({{ $line->refundableQuantity() }} refundable)
                                </label>
                                <input id="refund_qty_{{ $line->sale_detail_id }}" type="number" min="0" step="0.001"
                                    max="{{ $line->refundableQuantity() }}" value="0" name="items[{{ $line->sale_detail_id }}]">
                            </div>
                        @endforeach
                        <div>
                            <label for="refund_reason">Reason</label>
                            <select id="refund_reason" name="reason" required>
                                <option value="">— Select reason —</option>
                                @foreach (\App\Services\RefundService::REASONS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="refund_notes">Notes (optional)</label>
                            <textarea id="refund_notes" name="notes" class="input-lg bordered" rows="2" maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">Process Refund</button>
                        <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('refund-panel').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- Refund history --}}
    @if ($sale->refunds->isNotEmpty())
        <div style="max-width: 560px; margin: 1.5rem auto 0;">
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Refund history</h2>
                @foreach ($sale->refunds as $refund)
                    <div style="padding: 0.6rem 0; border-bottom: 1px solid rgba(32,60,61,0.12); font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-weight: 700; color: var(--danger);">−₱{{ number_format((float) $refund->refund_amount, 2) }}</span>
                            <span style="display: inline-flex; gap: 0.75rem; align-items: center;">
                                <a class="btn-ghost" href="{{ route('pos.refund.slip', $refund) }}">Slip</a>
                                <span class="muted">{{ $refund->refunded_at?->format('M j, Y g:i A') }}</span>
                            </span>
                        </div>
                        <div class="muted">
                            {{ \App\Services\RefundService::REASONS[$refund->reason] ?? $refund->reason }}
                            · {{ $refund->is_full_refund ? 'Full refund' : 'Partial refund' }}
                            · by {{ $refund->employee?->username ?? '—' }}
                            @if ($refund->notes) · “{{ $refund->notes }}” @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <x-manager-pin-modal :is-manager="auth()->user()->hasRole('Manager')" />
@endsection

@push('styles')
<style>
    .receipt-complete-banner { display: grid; grid-template-columns: minmax(180px, 1fr) auto auto; align-items: center; gap: 18px; margin-bottom: 16px; padding: 18px 20px; border: 1px solid rgba(45,138,78,.16); border-radius: 16px; background: linear-gradient(115deg, #f0f7f1, #fff); box-shadow: 0 8px 24px rgba(32,60,61,.07); }
    .receipt-complete-banner h1 { margin: 2px 0; font-size: 1.35rem; }
    .receipt-complete-banner p { margin: 0; color: var(--muted); }
    .receipt-complete-summary { display: flex; gap: 15px; margin: 0; }
    .receipt-complete-summary div { display: grid; gap: 4px; }
    .receipt-complete-summary dt { color: var(--muted); font-size: .72rem; }
    .receipt-complete-summary dd { margin: 0; font-weight: 750; font-variant-numeric: tabular-nums; }
    .receipt-complete-actions, .receipt-page-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .receipt-key-hint { display: inline-flex; align-items: center; margin-left: 7px; padding: 2px 6px; border: 1px solid currentColor; border-radius: 5px; font-size: .65rem; font-weight: 750; line-height: 1.2; opacity: .8; }
    .receipt-refunded-chip { cursor: default; }
    .receipt-stage { display: flex; justify-content: center; padding: 18px 12px 30px; border-radius: 16px; background: #f3f5f3; }
    .receipt-paper { box-sizing: border-box; width: min(100%, 410px); padding: 22px 23px; border: 0; border-radius: 12px; background: #fff; color: #203C3D; box-shadow: 0 12px 32px rgba(32,60,61,.1); font: inherit; font-size: 11px; line-height: 1.45; font-variant-numeric: tabular-nums; }
    .receipt-refunded { margin-bottom: 12px; padding: 7px; border: 1px solid rgba(196,80,74,.28); border-radius: 7px; background: rgba(196,80,74,.07); color: #a83d38; font-size: 10px; font-weight: 750; text-align: center; }
    .receipt-reprint-stamp { margin-bottom: 8px; padding: 4px 6px; border: 1px dashed #555; color: #222; font-size: 9px; font-weight: 800; letter-spacing: .18em; text-align: center; }
    .receipt-store { display: grid; gap: 3px; color: #596663; font-size: 10px; text-align: center; }
    .receipt-brand { color: #203C3D; font-size: 25px; font-weight: 800; letter-spacing: .14em; line-height: 1.1; }
    .receipt-store-location { display: grid; gap: 2px; white-space: pre-line; }
    .receipt-store-location span { color: #5d6967; font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .receipt-store-location strong { color: #203C3D; font-size: 10px; font-weight: 650; white-space: pre-line; }
    .receipt-store-note { margin: 0 0 3px; color: #203C3D; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .receipt-rule { margin: 14px 0 10px; border-top: 1px dashed rgba(32,60,61,.36); }
    .receipt-rule-light { margin: 9px 0; border-color: rgba(32,60,61,.16); }
    .receipt-meta { display: grid; gap: 5px; font-size: 10px; }
    .receipt-meta > div, .receipt-totals > div { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
    .receipt-meta span, .receipt-totals > div > span:first-child { color: #5d6967; }
    .receipt-meta strong { max-width: 68%; color: #203C3D; text-align: right; overflow-wrap: anywhere; }
    .receipt-items { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10px; }
    .receipt-items thead th { padding: 3px 2px 7px; border: 0; border-bottom: 1px solid rgba(32,60,61,.16); border-radius: 0; background: #f1f4f2; color: #52605d; font-size: 9px; font-weight: 700; text-align: left; text-transform: none; }
    .receipt-items th:first-child, .receipt-items td:first-child { width: 42%; padding-left: 0; }
    .receipt-items th:nth-child(2), .receipt-items td:nth-child(2) { width: 10%; text-align: center; }
    .receipt-items th:nth-child(3), .receipt-items td:nth-child(3) { width: 23%; text-align: right; }
    .receipt-items th:last-child, .receipt-items td:last-child { width: 25%; text-align: right; white-space: nowrap; }
    .receipt-items td { padding: 7px 2px; border: 0; border-bottom: 1px solid rgba(32,60,61,.07); vertical-align: top; }
    .receipt-items td:first-child { padding-right: 5px; }
    .receipt-items td strong { display: block; overflow-wrap: anywhere; font-weight: 650; }
    .receipt-items td:nth-child(2), .receipt-items td:nth-child(3), .receipt-items td:last-child { font-variant-numeric: tabular-nums; }
    .receipt-items td:last-child { font-weight: 700; }
    .receipt-totals { display: grid; gap: 6px; font-size: 11px; }
    .receipt-discount { color: #236b43; }
    .receipt-discount > span { display: grid; gap: 2px; }
    .receipt-discount small { color: #5d6967; font-size: 9px; }
    .receipt-total { margin: 5px 0; padding: 10px 11px; border: 0; border-radius: 8px; background: #203C3D; color: #fff; font-size: 15px; font-weight: 800; }
    .receipt-total > span:first-child { color: rgba(255,255,255,.78) !important; }
    .receipt-total strong { color: #fff; font-size: 17px; }
    .receipt-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
    .receipt-footer { display: grid; gap: 5px; color: #5d6967; font-size: 9px; text-align: center; }
    .receipt-footer strong { color: #203C3D; font-size: 11px; }
    .receipt-policy { margin-top: 2px; font-weight: 700; }
    .receipt-policy.active { color: #2D8A4E; }
    .receipt-policy.expired { color: #C4504A; }
    @media (max-width: 900px) {
        .receipt-complete-banner { grid-template-columns: 1fr; }
        .receipt-complete-summary { justify-content: space-between; }
    }
    @media print {
        @page { size: {{ $paperWidthMm }}mm auto; margin: 0; }
        body * { visibility: hidden; }
        #receipt-screen, #receipt-screen * { visibility: visible; }
        .receipt-stage { display: block; padding: 0; background: #fff; }
        #receipt-screen {
            position: absolute;
            left: 0; top: 0;
            transform: none;
            max-width: {{ $paperWidthMm }}mm; width: {{ $paperWidthMm }}mm;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
            padding: 3mm !important;
            color: #000 !important;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace !important;
        }
        #receipt-screen *, #receipt-screen strong, #receipt-screen span { color: #000 !important; }
        .receipt-total { border: 1px solid #000 !important; background: #fff !important; color: #000 !important; }
        .receipt-items th, .receipt-items td { border-color: #777 !important; }
        .receipt-reprint-stamp { border-color: #000 !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    const checkoutKeyToClear = @json($checkoutKeyToClear);
    if (checkoutKeyToClear) {
        try {
            const checkoutKeyStorageKey = @json($checkoutKeyStorageKey);
            if (window.sessionStorage.getItem(checkoutKeyStorageKey) === checkoutKeyToClear) {
                window.sessionStorage.removeItem(checkoutKeyStorageKey);
            }
        } catch (error) {
            console.warn('Checkout retry key could not be cleared from this browser tab.', error);
        }
    }

    @if ($saleCompleted)
        const autoPrintReceiptKey = @json('pos.auto-print-receipt.'.$sale->transaction_id);
        let shouldAutoPrintReceipt = true;
        try {
            shouldAutoPrintReceipt = window.sessionStorage.getItem(autoPrintReceiptKey) !== 'printed';
            if (shouldAutoPrintReceipt) window.sessionStorage.setItem(autoPrintReceiptKey, 'printed');
        } catch (error) {
            console.warn('Automatic receipt print preference could not be saved for this tab.', error);
        }
        if (shouldAutoPrintReceipt) {
            const startReceiptPrint = () => window.setTimeout(() => window.print(), 450);
            if (document.readyState === 'complete') startReceiptPrint();
            else window.addEventListener('load', startReceiptPrint, { once: true });
        }
    @endif

    let returnToPosStarted = false;
    window.addEventListener('keydown', (e) => {
        if ((e.key === ' ' || e.key === 'Enter') && !e.repeat) {
            const active = document.activeElement;
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) {
                return;
            }
            e.preventDefault();
            if (returnToPosStarted) return;
            returnToPosStarted = true;
            window.location.assign("{{ route('pos.index') }}");
        }
    });

    document.querySelector('[data-ewallet-next-sale]')?.focus({ preventScroll: true });

    const refundForm = document.querySelector('#refund-panel form');
    if (refundForm) {
        refundForm.addEventListener('submit', async event => {
            if (refundForm.dataset.managerAuthorized === 'true') return;
            event.preventDefault();
            if (!refundForm.reportValidity()) return;
            if (!window.confirm('Process this refund?')) return;

            const items = {};
            refundForm.querySelectorAll('input[name^="items["]').forEach(input => {
                const match = input.name.match(/^items\[([^\]]+)\]$/);
                const quantity = Number(input.value || 0);
                if (match && quantity > 0) items[match[1]] = Math.round(quantity * 1000) / 1000;
            });
            if (Object.keys(items).length === 0) {
                window.alert('Enter a refund quantity for at least one item.');
                return;
            }

            const authorization = await window.requestManagerAuthorization({
                action: 'sale_refund',
                details: { sale_id: {{ (int) $sale->transaction_id }}, items },
                reason: document.getElementById('refund_reason').value,
                notes: document.getElementById('refund_notes').value.trim()
            });
            if (!authorization) return;

            document.getElementById('refund-manager-auth-token').value = authorization.token || '';
            document.getElementById('refund-register-id').value = document.querySelector('.pos-register-badge')?.textContent.trim() || 'REG 01';
            refundForm.dataset.managerAuthorized = 'true';
            refundForm.requestSubmit();
        });
    }

    function toggleRefundPanel() {
        const panel = document.getElementById('refund-panel');
        if (!panel) return;
        const hidden = panel.style.display === 'none';
        panel.style.display = hidden ? 'block' : 'none';
        if (hidden) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const first = panel.querySelector('input[type="number"]');
            if (first) setTimeout(() => first.focus(), 350);
        }
    }
</script>
@endpush
