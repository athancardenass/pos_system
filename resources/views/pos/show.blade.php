@extends('layouts.app')

@section('title', 'Receipt '.$sale->receipt?->receipt_number)

@section('content')
    <div class="page-head">
        <h1>Receipt {{ $sale->receipt->receipt_number ?? '#' . $sale->transaction_id }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn" onclick="window.print()">🖨 Print Receipt</button>
            <a class="btn btn-secondary" href="{{ route('pos.show', $sale) }}">View</a>
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">New sale</a>
        </div>
    </div>

    {{-- On-screen receipt (centered card) --}}
    <div style="display: flex; justify-content: center;">
        <div class="card" id="receipt-screen" style="max-width: 420px; width: 100%; padding: 2rem;">

            {{-- Store Header --}}
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <div style="font-size: 1.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text);">POS</div>
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem;">Your Trusted Point of Sale</div>
                <div style="font-size: 0.7rem; color: var(--muted);">Cavite, Philippines</div>
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
                @if ($sale->discount)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; color: var(--success);">
                        <span>{{ $sale->discount->discount_name }} ({{ $sale->discount->discount_type === 'percentage' ? $sale->discount->discount_value . '%' : 'Fixed' }})</span>
                        <span>−₱{{ number_format($sale->subtotal - $sale->total_amount, 2) }}</span>
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
