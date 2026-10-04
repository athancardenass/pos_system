@extends('layouts.app')

@section('title', 'E-wallet payment checks')

@push('styles')
<style>
    .ewallet-page { max-width: 1180px; margin: 0 auto; }
    .ewallet-heading, .ewallet-heading-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .ewallet-heading { margin-bottom: 18px; }
    .ewallet-heading h1 { margin: 0; color: var(--text); font-size: 1.45rem; }
    .ewallet-heading p { margin: 5px 0 0; color: var(--muted); }
    .ewallet-panel { overflow: hidden; background: var(--surface); border: 1px solid var(--rule-faint); border-radius: 14px; box-shadow: var(--shadow-card); }
    .ewallet-panel-note { padding: 13px 16px; background: var(--surface-active); color: var(--text); font-size: .88rem; }
    .ewallet-table-wrap { overflow-x: auto; }
    .ewallet-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ewallet-table th, .ewallet-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--rule-faint); vertical-align: middle; }
    .ewallet-table th { padding-top: 13px; padding-bottom: 13px; border-bottom: 0; background: #203C3D; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 3px 7px rgba(32,60,61,.18); }
    .ewallet-table td { color: var(--text); font-size: .88rem; }
    .ewallet-table tr:last-child td { border-bottom: 0; }
    .ewallet-reference { font-weight: 750; font-variant-numeric: tabular-nums; }
    .ewallet-subtext { display: block; margin-top: 3px; color: var(--muted); font-size: .77rem; }
    .ewallet-amount { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ewallet-empty { padding: 48px 18px; text-align: center; color: var(--muted); }
    @media (max-width: 720px) {
        .ewallet-heading { align-items: flex-start; flex-direction: column; }
        .ewallet-table th, .ewallet-table td { padding: 10px; }
    }
</style>
@endpush

@section('content')
<main class="ewallet-page">
    <header class="ewallet-heading">
        <div>
            <h1>Pending e-wallet payments</h1>
            <p>Check the completed payment in the receiving merchant app before approving a sale.</p>
        </div>
        <div class="ewallet-heading-actions">
            <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
        </div>
    </header>

    <section class="ewallet-panel" aria-label="Pending payment requests">
        <div class="ewallet-panel-note">Approval completes the sale and issues the receipt. Rejection releases reserved stock; any customer refund must be handled separately in the merchant app.</div>
        @if ($requests->isEmpty())
            <div class="ewallet-empty">No pending e-wallet payments</div>
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
                                    <span class="ewallet-subtext">{{ $pending->status }}</span>
                                </td>
                                <td>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</td>
                                <td>
                                    <span class="ewallet-amount">₱{{ number_format((float) $pending->submitted_amount, 2) }}</span>
                                    <span class="ewallet-subtext">{{ $pending->created_at?->format('M j, Y · g:i A') }}</span>
                                </td>
                                <td>{{ $pending->expires_at?->format('M j, Y · g:i A') }}</td>
                                <td><a class="btn btn-secondary" href="{{ route('pos.pending-ewallet.show', $pending) }}">Review payment</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
