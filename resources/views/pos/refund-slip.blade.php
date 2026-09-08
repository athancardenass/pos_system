@extends('layouts.app')

@section('title', 'Credit Slip '.$refund->refund_id)

@section('content')
    <div class="page-head">
        <h1>Credit Slip #{{ $refund->refund_id }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn" onclick="window.print()">Print Slip</button>
            <a class="btn btn-secondary" href="{{ route('pos.show', $refund->transaction_id) }}">Back to Receipt</a>
        </div>
    </div>

    <div style="display: flex; justify-content: center;">
        <div class="card" id="slip-screen" style="max-width: 420px; width: 100%; padding: 2rem;">

            <div style="text-align: center; margin-bottom: 1.25rem;">
                <div style="font-size: 1.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--danger);">Credit Slip</div>
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem;">Proof of Refund</div>
                <div style="font-size: 0.7rem; color: var(--muted);">Old Nalsian Road, Calasial, Calasiao, 2418 Pangasinan</div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            <div style="font-size: 0.82rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Slip No.</span>
                    <span style="font-weight: 700;">CS-{{ str_pad((string) $refund->refund_id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Original Receipt</span>
                    <span>{{ $refund->sale->receipt?->receipt_number ?? '#'.$refund->transaction_id }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Refund Date</span>
                    <span>{{ $refund->refunded_at?->format('M j, Y g:i A') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Customer</span>
                    <span>{{ $refund->sale->customer?->fullName() ?? 'Walk-in' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Processed By</span>
                    <span>{{ $refund->employee?->username ?? '—' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Type</span>
                    <span>{{ $refund->is_full_refund ? 'Full refund' : 'Partial refund' }}@if ($refund->window_override) · window override @endif</span>
                </div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            <div style="font-size: 0.82rem; margin-bottom: 1rem;">
                @foreach ($refund->items as $item)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                        <div style="flex: 1; padding-right: 0.5rem;">
                            <div style="font-weight: 600;">{{ $item->saleDetail?->product?->product_name ?? 'Item' }}</div>
                            <div style="color: var(--muted); font-size: 0.75rem;">{{ $item->quantity }} refunded</div>
                        </div>
                        <div style="font-weight: 600; white-space: nowrap;">−₱{{ number_format((float) $item->amount, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div style="border-top: 1px dashed var(--rule); margin-bottom: 0.75rem;"></div>

            <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; padding: 0.5rem 0; border-top: 2px solid var(--rule); border-bottom: 2px solid var(--rule); margin: 0.5rem 0;">
                <span>AMOUNT REFUNDED</span>
                <span>₱{{ number_format((float) $refund->refund_amount, 2) }}</span>
            </div>

            <div style="font-size: 0.82rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Reason</span>
                    <span>{{ \App\Services\RefundService::REASONS[$refund->reason] ?? $refund->reason }}</span>
                </div>
                @if ($refund->notes)
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Notes</span>
                        <span>{{ $refund->notes }}</span>
                    </div>
                @endif
            </div>

            <div style="border-top: 2px dashed var(--rule); margin: 1.25rem 0;"></div>

            <div style="text-align: center; font-size: 0.75rem; color: var(--muted);">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">This slip confirms the refund of the items above.</div>
                <div>Present this slip for any returning-item inquiries.</div>
                <div style="margin-top: 0.5rem;">POS System · {{ now()->format('Y') }}</div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        #slip-screen, #slip-screen * { visibility: visible; }
        #slip-screen {
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
