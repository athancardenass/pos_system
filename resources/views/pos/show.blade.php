@extends('layouts.app')

@section('title', 'Receipt '.$sale->receipt?->receipt_number)

@section('content')
    <div class="page-head">
        <h1>Receipt {{ $sale->receipt->receipt_number ?? '#' . $sale->transaction_id }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn" onclick="window.print()">Print Receipt</button>
            @if (! $sale->isFullyRefunded())
                <button type="button" class="btn btn-danger"
                    onclick="toggleRefundPanel()">
                    Refund
                </button>
            @else
                <span class="btn btn-secondary" style="cursor: default; background: rgba(196,80,74,0.12); color: var(--danger); border-color: var(--danger);">
                    Fully Refunded
                </span>
            @endif
            <a class="btn btn-secondary" href="{{ route('pos.show', $sale) }}">View</a>
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">New sale</a>
        </div>
    </div>

    {{-- On-screen receipt (centered card) --}}
    <div style="display: flex; justify-content: center;">
        <div class="card" id="receipt-screen" style="max-width: 420px; width: 100%; padding: 2rem;">

            @if ($sale->isFullyRefunded())
                <div style="text-align: center; background: rgba(196,80,74,0.12); border: 2px solid var(--danger); color: var(--danger); font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; padding: 0.5rem; margin-bottom: 1rem; border-radius: 6px;">
                    Refunded — Not Valid for Payment
                </div>
            @endif

            {{-- Store Header --}}
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <div style="font-size: 1.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text);">POS</div>
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem;">Your Trusted Point of Sale</div>
                <div style="font-size: 0.7rem; color: var(--muted);">Old Nalsian Road, Calasial, Calasiao, 2418 Pangasinan</div>
                <div style="font-size: 0.7rem; color: var(--muted);">VAT Reg: 123-456-789-000</div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            {{-- Transaction Info --}}
            <div style="font-size: 0.82rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Receipt No.</span>
                    <span style="font-weight: 700;">{{ $sale->receipt->receipt_number ?? '—' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Date</span>
                    <span>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('M j, Y') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Time</span>
                    <span>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('g:i A') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Cashier</span>
                    <span>{{ $sale->employee->username ?? '—' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Customer</span>
                    <span>{{ $sale->customer?->fullName() ?? 'Walk-in' }}</span>
                </div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            {{-- Items --}}
            <div style="font-size: 0.82rem; margin-bottom: 1rem;">
                @foreach ($sale->saleDetails as $line)
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.4rem;">
                        <div style="flex: 1; padding-right: 0.5rem;">
                            <div style="font-weight: 600;">{{ $line->product->product_name ?? '—' }}</div>
                            <div style="color: var(--muted); font-size: 0.75rem;">{{ $line->quantity }} × ₱{{ number_format($line->unit_price, 2) }}</div>
                        </div>
                        <div style="font-weight: 600; white-space: nowrap;">₱{{ number_format($line->subtotal, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div style="border-top: 1px dashed var(--rule); margin-bottom: 0.75rem;"></div>

            {{-- Totals --}}
            <div style="font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Subtotal</span>
                    <span>₱{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                {{-- Promotion engine savings, printed in the order they were applied. --}}
                @foreach ($sale->appliedPromotions as $row)
                    <div class="receipt-line receipt-save">
                        <span>{{ $row->label() }}</span>
                        <span>−₱{{ number_format((float) $row->amount_discounted, 2) }}</span>
                    </div>
                @endforeach
                @if ($sale->discount)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; color: var(--success);">
                        <span>{{ $sale->discount->discount_name }} ({{ $sale->discount->discount_type === 'percentage' ? $sale->discount->discount_value . '%' : 'Fixed' }})</span>
                        {{-- The manual step is not stored as an amount: it worked on the subtotal
                             LEFT by promotions, so it must be reconstructed, not re-derived from
                             subtotal - total (that would also swallow promo + coupon savings). --}}
                        <span>−₱{{ number_format($sale->manualDiscountAmount(), 2) }}</span>
                    </div>
                @endif
                @foreach ($sale->couponRedemptions as $redemption)
                    <div class="receipt-line receipt-save">
                        <span>Coupon {{ $redemption->coupon->code ?? $redemption->coupon_id }}</span>
                        <span>−₱{{ number_format((float) $redemption->amount_applied, 2) }}</span>
                    </div>
                @endforeach
                @if (config('vat.enabled', true))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; color: var(--muted);">
                        <span>VAT ({{ ($sale->vat_rate * 100) }}%, included)</span>
                        <span>₱{{ number_format($sale->vat_amount, 2) }}</span>
                    </div>
                @endif
                <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; padding: 0.5rem 0; border-top: 2px solid var(--rule); border-bottom: 2px solid var(--rule); margin: 0.5rem 0;">
                    <span>TOTAL</span>
                    <span>₱{{ number_format($sale->total_amount, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="color: var(--muted);">Payment ({{ strtoupper($sale->payment_method) }})</span>
                    <span>₱{{ number_format($sale->payment->amount_paid ?? 0, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: 600;">
                    <span style="color: var(--muted);">Change</span>
                    <span>₱{{ number_format($sale->payment->change_amount ?? 0, 2) }}</span>
                </div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin: 1.25rem 0;"></div>

            {{-- Footer --}}
            <div style="text-align: center; font-size: 0.75rem; color: var(--muted);">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">Thank you for shopping!</div>
                <div>This receipt serves as your proof of purchase.</div>
                <div style="margin-top: 0.5rem;">POS System · {{ now()->format('Y') }}</div>
            </div>
        </div>
    </div>
    {{-- Refund panel (hidden until Refund clicked) --}}
    @if (! $sale->isFullyRefunded())
        <div id="refund-panel" style="display: none; max-width: 560px; margin: 1.5rem auto 0;">
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Refund this sale</h2>
                <form method="POST" action="{{ route('pos.refund', $sale) }}">
                    @csrf
                    <div class="form-grid">
                        @foreach ($sale->saleDetails as $line)
                            <div>
                                <label for="refund_qty_{{ $line->sale_detail_id }}">
                                    {{ $line->product->product_name ?? 'Item' }} — refund qty ({{ $line->refundableQuantity() }} refundable)
                                </label>
                                <input id="refund_qty_{{ $line->sale_detail_id }}" type="number" min="0"
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
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Process this refund?')">Process Refund</button>
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
@endsection

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        #receipt-screen, #receipt-screen * { visibility: visible; }
        #receipt-screen {
            position: absolute;
            left: 50%; top: 0;
            transform: translateX(-50%);
            max-width: 400px; width: 100%;
            border: none !important;
            box-shadow: none !important;
            background: #fff !important;
            padding: 1rem !important;
        }
        .page-head, nav, .sidebar, .btn { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
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
