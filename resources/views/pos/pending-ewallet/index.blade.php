@extends('layouts.app')

@section('title', 'E-wallet payment checks')

@push('styles')
<style>
    .ewallet-page { max-width: 1240px; margin: 0 auto; }
    .ewallet-heading, .ewallet-heading-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .ewallet-heading { min-height: 104px; margin-bottom: 14px; padding: 18px 22px; border-radius: 14px; background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12); }
    .ewallet-heading h1 { margin: 0; color: #fff; font-size: 1.35rem; font-weight: 800; letter-spacing: -.025em; }
    .ewallet-heading p { margin: 5px 0 0; color: rgba(255,255,255,.78); font-size: .86rem; }
    .ewallet-eyebrow { margin: 0 0 3px !important; color: #B8D9C4 !important; font-size: .66rem !important; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .ewallet-heading-actions { flex-wrap: wrap; justify-content: flex-end; }
    .ewallet-count { display: inline-flex; align-items: center; min-height: 34px; padding: 0 11px; border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: rgba(255,255,255,.1); color: #fff; font-size: .76rem; font-weight: 800; white-space: nowrap; }
    .ewallet-heading-actions .btn-secondary { min-height: 36px; border-color: rgba(255,255,255,.42); background: #fff; color: #203C3D; font-weight: 800; }
    .ewallet-panel { overflow: hidden; background: var(--surface); border: 1px solid var(--rule-faint); border-radius: 14px; box-shadow: var(--shadow-card); }
    .ewallet-panel-note { padding: 11px 16px; border-bottom: 1px solid rgba(24,118,94,.12); background: #E7F2EB; color: #244A3A; font-size: .83rem; font-weight: 600; }
    .ewallet-table-wrap { overflow-x: auto; }
    .ewallet-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ewallet-table th, .ewallet-table td { padding: 11px 14px; text-align: left; border-bottom: 1px solid var(--rule-faint); vertical-align: middle; }
    .ewallet-table th { padding-top: 12px; padding-bottom: 12px; border-bottom: 0; background: #203C3D; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 3px 7px rgba(32,60,61,.18); }
    .ewallet-table td { color: var(--text); font-size: .85rem; }
    .ewallet-table tbody tr { transition: background-color .14s ease; }
    .ewallet-table tbody tr:hover { background: #F3F7F4; }
    .ewallet-table tr:last-child td { border-bottom: 0; }
    .ewallet-reference { font-weight: 750; font-variant-numeric: tabular-nums; }
    .ewallet-subtext { display: block; margin-top: 3px; color: var(--muted); font-size: .77rem; }
    .ewallet-amount { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ewallet-empty { display: grid; justify-items: center; gap: 6px; padding: 52px 18px; text-align: center; color: var(--muted); }
    .ewallet-empty strong { color: #203C3D; font-size: .98rem; }
    .ewallet-status-pill { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; background: #FFF0CF; color: #755317; font-size: .7rem; font-weight: 800; text-transform: capitalize; }
    .ewallet-row-action { min-height: 34px; padding: 7px 11px; border-color: #203C3D !important; background: #203C3D !important; color: #fff !important; font-size: .76rem; font-weight: 800; white-space: nowrap; }
    @media (max-width: 720px) {
        .ewallet-heading { align-items: flex-start; flex-direction: column; }
        .ewallet-heading-actions { width: 100%; justify-content: space-between; }
        .ewallet-table th, .ewallet-table td { padding: 10px; }
    }
    @media (max-width: 480px) { .ewallet-heading { padding: 16px; } .ewallet-heading-actions { align-items: flex-start; flex-direction: column; } }
</style>
@endpush

@section('content')
<main class="ewallet-page">
    <header class="ewallet-heading">
        <div>
            <p class="ewallet-eyebrow">Payment review</p>
            <h1>Pending e-wallet payments</h1>
            <p>Check the completed payment in the receiving merchant app before approving a sale.</p>
        </div>
        <div class="ewallet-heading-actions">
            <span class="ewallet-count">{{ $requests->count() }} waiting</span>
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
        </div>
    </header>

    <section class="ewallet-panel" aria-label="Pending payment requests">
        <div class="ewallet-panel-note">Approval completes the sale and issues the receipt. Rejection releases reserved stock; any customer refund must be handled separately in the merchant app.</div>
        @if ($requests->isEmpty())
            <div class="ewallet-empty" role="status"><strong>No pending e-wallet payments</strong><span>New requests will appear here after a cashier submits a reference.</span></div>
        @else
            <div class="ewallet-table-wrap">
                <table class="ewallet-table">
                    <thead>
                        <tr><th>Payment</th><th>Cashier</th><th>Submitted</th><th>Reservation expires</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $pending)
                            <tr>
                                <td>
                                    <span class="ewallet-reference">{{ $pending->payment_provider }} · <x-masked-value :value="$pending->maskedReference()" /></span>
                                    <span class="ewallet-subtext"><span class="ewallet-status-pill">{{ $pending->status }}</span></span>
                                </td>
                                <td>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</td>
                                <td>
                                    <span class="ewallet-amount">₱{{ number_format((float) $pending->submitted_amount, 2) }}</span>
                                    <span class="ewallet-subtext">{{ $pending->created_at?->format('M j, Y · g:i A') }}</span>
                                </td>
                                <td>{{ $pending->expires_at?->format('M j, Y · g:i A') }}</td>
                                <td><a class="btn btn-secondary ewallet-row-action" href="{{ route('pos.pending-ewallet.show', $pending) }}">Review payment</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
