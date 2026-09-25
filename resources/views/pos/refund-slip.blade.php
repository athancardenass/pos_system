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
                <div style="font-size: 1.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--danger);">CREDIT SLIP</div>
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem; font-weight: 600;">Official Proof of Return / Exchange</div>
                <div style="font-size: 0.7rem; color: var(--muted);">Old Nalsian Road, Calasiao, 2418 Pangasinan</div>
                <div style="font-size: 0.7rem; color: var(--muted);">VAT Reg TIN: 123-456-789-000</div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            <div style="font-size: 0.82rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Slip No.</span>
                    <span style="font-weight: 700; font-family: monospace;">CS-{{ str_pad((string) $refund->refund_id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Original Receipt</span>
                    <span style="font-family: monospace;">{{ $refund->sale->receipt?->receipt_number ?? '#'.$refund->transaction_id }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Refund Date & Time</span>
                    <span>{{ $refund->refunded_at?->format('M j, Y g:i A') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Customer</span>
                    <span>{{ $refund->sale->customer?->fullName() ?? 'Walk-in Customer' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                    <span style="color: var(--muted);">Processed By</span>
                    <span>{{ $refund->employee?->username ?? '—' }} ({{ $refund->employee?->role?->role_name ?? 'Staff' }})</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Refund Classification</span>
                    <span>
                        <span style="font-weight: 600;">{{ $refund->is_full_refund ? 'Full Refund' : 'Partial Refund' }}</span>
                        @if ($refund->window_override)
                            <span style="font-size: 0.72rem; color: var(--danger); font-weight: 700; background: rgba(196,80,74,0.1); padding: 0.1rem 0.4rem; border-radius: 4px;">Manager Override</span>
                        @endif
                    </span>
                </div>
            </div>

            <div style="border-top: 2px dashed var(--rule); margin-bottom: 1rem;"></div>

            <div style="font-size: 0.82rem; margin-bottom: 1rem;">
                <div style="font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.05em;">Returned Merchandise</div>
                @foreach ($refund->items as $item)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.45rem;">
                        <div style="flex: 1; padding-right: 0.5rem;">
                            <div style="font-weight: 600;">{{ $item->saleDetail?->product?->product_name ?? 'Item' }}</div>
                            <div style="color: var(--muted); font-size: 0.75rem;">Qty Returned: {{ $item->quantity }} unit(s)</div>
                        </div>
                        <div style="font-weight: 600; white-space: nowrap; color: var(--danger);">−₱{{ number_format((float) $item->amount, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div style="border-top: 1px dashed var(--rule); margin-bottom: 0.75rem;"></div>

            <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; padding: 0.5rem 0; border-top: 2px solid var(--rule); border-bottom: 2px solid var(--rule); margin: 0.5rem 0; color: var(--danger);">
                <span>TOTAL REFUNDED</span>
                <span>₱{{ number_format((float) $refund->refund_amount, 2) }}</span>
            </div>

            <div style="font-size: 0.82rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="color: var(--muted);">Return Reason</span>
                    <span style="font-weight: 600;">{{ \App\Services\RefundService::REASONS[$refund->reason] ?? $refund->reason }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="color: var(--muted);">Tender Method</span>
                    <span>Cash Reimbursed / Credit</span>
                </div>
                @if ($refund->notes)
                    <div style="display: flex; justify-content: space-between; margin-top: 0.25rem;">
                        <span style="color: var(--muted);">Staff Notes</span>
                        <span style="font-style: italic;">“{{ $refund->notes }}”</span>
                    </div>
                @endif
            </div>

            <div style="border-top: 2px dashed var(--rule); margin: 1.25rem 0 1rem;"></div>

            {{-- Signature Verification Block --}}
            <div style="font-size: 0.75rem; margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;">
                    <div style="flex: 1; text-align: center;">
                        <div style="border-bottom: 1px solid var(--rule); height: 28px;"></div>
                        <div style="font-size: 0.7rem; color: var(--muted); margin-top: 0.35rem;">Customer Signature</div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="border-bottom: 1px solid var(--rule); height: 28px;"></div>
                        <div style="font-size: 0.7rem; color: var(--muted); margin-top: 0.35rem;">Authorized Representative</div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; font-size: 0.72rem; color: var(--muted); line-height: 1.4;">
                <div style="font-weight: 600; margin-bottom: 0.2rem;">Official Proof of Refund Document</div>
                <div>Returned items have been restored to inventory. Non-transferable.</div>
                <div style="margin-top: 0.4rem; font-size: 0.68rem;">POS Supermarket System &bull; {{ now()->format('Y') }}</div>
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
