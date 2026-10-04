@extends('layouts.app')

@section('title', 'Pending Card payments')

@push('styles')
<style>
    .card-review-page { max-width: 1240px; margin: 0 auto; }
    .card-review-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 104px; margin-bottom: 14px; padding: 18px 22px; border-radius: 14px; background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12); }
    .card-review-heading h1 { margin: 0; color: #fff; font-size: 1.35rem; font-weight: 800; letter-spacing: -.025em; }
    .card-review-heading p { margin: 5px 0 0; color: rgba(255,255,255,.78); font-size: .86rem; }
    .card-review-eyebrow { margin: 0 0 3px !important; color: #B8D9C4 !important; font-size: .66rem !important; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .card-review-heading-actions { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 9px; }
    .card-review-count { display: inline-flex; align-items: center; min-height: 34px; padding: 0 11px; border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: rgba(255,255,255,.1); color: #fff; font-size: .76rem; font-weight: 800; white-space: nowrap; }
    .card-review-heading .btn-secondary { min-height: 36px; border-color: rgba(255,255,255,.42); background: #fff; color: #203C3D; font-weight: 800; }
    .card-review-panel { overflow: hidden; border: 1px solid var(--rule-faint); border-radius: 14px; background: var(--surface); box-shadow: var(--shadow-card); }
    .card-review-note { padding: 11px 16px; border-bottom: 1px solid rgba(24,118,94,.12); background: #E7F2EB; color: #244A3A; font-size: .83rem; font-weight: 600; }
    .card-review-table-wrap { overflow-x: auto; }
    .card-review-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .card-review-table th, .card-review-table td { padding: 11px 14px; border-bottom: 1px solid var(--rule-faint); text-align: left; vertical-align: middle; }
    .card-review-table th { padding-top: 13px; padding-bottom: 13px; border-bottom: 0; background: #203C3D; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 3px 7px rgba(32,60,61,.18); }
    .card-review-table td { color: var(--text); font-size: .85rem; }
    .card-review-table tbody tr { transition: background-color .14s ease; }
    .card-review-table tbody tr:hover { background: #F3F7F4; }
    .card-review-table tr:last-child td { border-bottom: 0; }
    .card-review-empty { display: grid; justify-items: center; gap: 6px; padding: 52px 18px; color: var(--muted); text-align: center; }
    .card-review-empty strong { color: #203C3D; font-size: .98rem; }
    .card-review-reference { font-weight: 750; font-variant-numeric: tabular-nums; }
    .card-review-subtext { display: block; margin-top: 3px; color: var(--muted); font-size: .76rem; }
    .card-review-status { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; background: #FFF0CF; color: #755317; font-size: .7rem; font-weight: 800; text-transform: capitalize; }
    .card-review-row-action { min-height: 34px; padding: 7px 11px; border-color: #203C3D !important; background: #203C3D !important; color: #fff !important; font-size: .76rem; font-weight: 800; white-space: nowrap; }
    @media (max-width: 720px) {
        .card-review-heading { align-items: flex-start; flex-direction: column; }
        .card-review-heading-actions { width: 100%; justify-content: space-between; }
        .card-review-table th, .card-review-table td { padding: 10px; }
    }
    @media (max-width: 480px) { .card-review-heading { padding: 16px; } .card-review-heading-actions { align-items: flex-start; flex-direction: column; } }
</style>
@endpush

@section('content')
<main class="card-review-page">
    <header class="card-review-heading">
        <div>
            <p class="card-review-eyebrow">Payment review</p>
            <h1>Pending Card payments</h1>
            <p>Compare the approval code and amount against the Card terminal record before completing a sale.</p>
        </div>
        <div class="card-review-heading-actions">
            <span class="card-review-count">{{ $requests->count() }} waiting</span>
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
        </div>
    </header>

    <section class="card-review-panel" aria-label="Pending Card requests">
        <div class="card-review-note">Approval completes the sale and issues the receipt. Rejection releases reserved stock.</div>
        @if ($requests->isEmpty())
            <div class="card-review-empty" role="status"><strong>No pending Card payments</strong><span>Submitted terminal records awaiting manager review will appear here.</span></div>
        @else
            <div class="card-review-table-wrap">
                <table class="card-review-table">
                    <thead><tr><th>Payment</th><th>Cashier</th><th>Submitted</th><th>Reservation expires</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($requests as $pending)
                            <tr>
                                <td>
                                    <span class="card-review-reference">{{ $pending->payment_provider }} · <x-masked-value :value="$pending->maskedReference()" /></span>
                                    <span class="card-review-subtext"><span class="card-review-status">{{ $pending->status }}</span></span>
                                </td>
                                <td>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</td>
                                <td>
                                    <strong>₱{{ number_format((float) $pending->submitted_amount, 2) }}</strong>
                                    <span class="card-review-subtext">{{ $pending->created_at?->format('M j, Y · g:i A') }}</span>
                                </td>
                                <td>{{ $pending->expires_at?->format('M j, Y · g:i A') }}</td>
                                <td><a class="btn btn-secondary card-review-row-action" href="{{ route('pos.pending-card.show', $pending) }}">Review payment</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
