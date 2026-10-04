@extends('layouts.app')

@section('title', 'Pending Card payments')

@push('styles')
<style>
    .card-review-page { max-width: 1180px; margin: 0 auto; }
    .card-review-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 18px; }
    .card-review-heading h1 { margin: 0; color: var(--text); font-size: 1.45rem; }
    .card-review-heading p { margin: 5px 0 0; color: var(--muted); }
    .card-review-panel { overflow: hidden; border: 1px solid var(--rule-faint); border-radius: 14px; background: var(--surface); box-shadow: var(--shadow-card); }
    .card-review-note { padding: 13px 16px; background: var(--surface-active); color: var(--text); font-size: .86rem; }
    .card-review-table-wrap { overflow-x: auto; }
    .card-review-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .card-review-table th, .card-review-table td { padding: 12px 14px; border-bottom: 1px solid var(--rule-faint); text-align: left; vertical-align: middle; }
    .card-review-table th { padding-top: 13px; padding-bottom: 13px; border-bottom: 0; background: #203C3D; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 3px 7px rgba(32,60,61,.18); }
    .card-review-table td { color: var(--text); font-size: .86rem; }
    .card-review-table tr:last-child td { border-bottom: 0; }
    .card-review-empty { padding: 48px 18px; color: var(--muted); text-align: center; }
    .card-review-reference { font-weight: 750; font-variant-numeric: tabular-nums; }
    .card-review-subtext { display: block; margin-top: 3px; color: var(--muted); font-size: .76rem; }
    @media (max-width: 720px) {
        .card-review-heading { align-items: flex-start; flex-direction: column; }
        .card-review-table th, .card-review-table td { padding: 10px; }
    }
</style>
@endpush

@section('content')
<main class="card-review-page">
    <header class="card-review-heading">
        <div>
            <h1>Pending Card payments</h1>
            <p>Compare the approval code and amount against the Card terminal record before completing a sale.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
    </header>

    <section class="card-review-panel" aria-label="Pending Card requests">
        <div class="card-review-note">Approval completes the sale and issues the receipt. Rejection releases reserved stock.</div>
        @if ($requests->isEmpty())
            <div class="card-review-empty">No pending Card payments</div>
        @else
            <div class="card-review-table-wrap">
                <table class="card-review-table">
                    <thead><tr><th>Payment</th><th>Cashier</th><th>Submitted</th><th>Reservation expires</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($requests as $pending)
                            <tr>
                                <td>
                                    <span class="card-review-reference">{{ $pending->payment_provider }} · <x-masked-value :value="$pending->maskedReference()" /></span>
                                    <span class="card-review-subtext">{{ $pending->status }}</span>
                                </td>
                                <td>{{ $pending->requester?->displayName() ?? 'Unknown cashier' }}</td>
                                <td>
                                    <strong>₱{{ number_format((float) $pending->submitted_amount, 2) }}</strong>
                                    <span class="card-review-subtext">{{ $pending->created_at?->format('M j, Y · g:i A') }}</span>
                                </td>
                                <td>{{ $pending->expires_at?->format('M j, Y · g:i A') }}</td>
                                <td><a class="btn btn-secondary" href="{{ route('pos.pending-card.show', $pending) }}">Review payment</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
