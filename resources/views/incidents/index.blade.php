@extends('layouts.app')

@section('title', 'Incident reports')

@section('content')
    <div class="incident-register">
        <header class="page-head incident-register-head">
            <div class="incident-register-heading">
                <p class="eyebrow">Operations · Reports</p>
                <h1>Incident reports</h1>
                <p class="muted">Review product safety concerns and cashier shortages or overages.</p>
            </div>
            <div class="incident-register-actions">
                @if (auth()->user()->hasRole('Manager'))
                    <a class="btn btn-secondary" href="{{ route('reports.index') }}">Back to reports</a>
                @endif
                <a class="btn" href="{{ route('incidents.create') }}">Report safety concern</a>
            </div>
        </header>

        <aside class="incident-handoff-note" aria-label="Handoff information">
            <span class="incident-handoff-icon" aria-hidden="true">i</span>
            <div>
                <strong>Inventory and HRMS handoff is manual</strong>
                <p>Managers can download a case report and record the receiving team's acknowledgement reference.</p>
            </div>
        </aside>

        <section class="incident-register-panel" aria-labelledby="incident-register-title">
            <div class="incident-register-panel-head">
                <div>
                    <h2 id="incident-register-title">Case register</h2>
                    <p>Safety reports and shift cash variances, newest cases first.</p>
                </div>
                <div class="incident-case-count" aria-label="{{ $incidents->total() }} total cases">
                    <strong>{{ $incidents->total() }}</strong>
                    <span>Total cases</span>
                </div>
            </div>

            <div class="table-wrap incident-table-wrap">
                <table class="incident-table">
                    <caption class="incident-sr-only">Incident cases with their type, details, review status and handoff status.</caption>
                    <thead>
                        <tr>
                            <th scope="col">Case</th>
                            <th scope="col">Type</th>
                            <th scope="col">Details</th>
                            <th scope="col">Review status</th>
                            <th scope="col">Handoff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($incidents as $incident)
                            @php
                                $caseStatusClass = match ($incident->status) {
                                    'resolved' => 'is-resolved',
                                    'under_investigation' => 'is-review',
                                    default => 'is-open',
                                };
                                $handoffStatusClass = $incident->external_status === 'acknowledged_manually'
                                    ? 'is-acknowledged'
                                    : 'is-pending';
                            @endphp
                            <tr>
                                <td data-label="Case">
                                    <a class="incident-case-link" href="{{ route('incidents.show', $incident) }}">#{{ $incident->id }} <span aria-hidden="true">↗</span></a>
                                    <span class="incident-case-hint">Open case details</span>
                                </td>
                                <td data-label="Type">
                                    <span class="incident-type-pill {{ $incident->kind === 'product_safety' ? 'is-safety' : 'is-cash' }}">
                                        {{ $incident->kind === 'product_safety' ? 'Product safety' : 'Cash variance' }}
                                    </span>
                                </td>
                                <td class="incident-details-cell" data-label="Details">
                                    @if ($incident->kind === 'product_safety')
                                        <strong>{{ $incident->snapshot['product_name'] ?? 'Product safety report' }}</strong>
                                    @else
                                        <strong>Shift #{{ $incident->drawer_id }}</strong>
                                        <span class="incident-variance {{ $incident->difference < 0 ? 'is-shortage' : 'is-overage' }}">
                                            {{ $incident->difference < 0 ? 'Shortage' : 'Overage' }} · ₱{{ number_format(abs($incident->difference), 2) }}
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Review status">
                                    <span class="incident-status-pill {{ $caseStatusClass }}">{{ ucfirst(str_replace('_', ' ', $incident->status)) }}</span>
                                </td>
                                <td data-label="Handoff">
                                    <div class="incident-handoff-cell">
                                        <span class="incident-handoff-team">{{ $incident->kind === 'product_safety' ? 'Inventory' : 'HRMS' }}</span>
                                        <span class="incident-status-pill {{ $handoffStatusClass }}">{{ ucfirst(str_replace('_', ' ', $incident->external_status)) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="incident-empty-row">
                                <td class="incident-empty-cell" colspan="5">
                                    <div class="incident-empty-state">
                                        <span class="incident-empty-mark" aria-hidden="true">—</span>
                                        <h3>No incident reports yet</h3>
                                        <p>New product safety concerns and cash variances will appear here. Shift variances are recorded automatically when a drawer closes with a difference.</p>
                                        <a class="btn btn-secondary" href="{{ route('incidents.create') }}">Report a safety concern</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($incidents->hasPages())
                <div class="incident-pagination">{{ $incidents->links() }}</div>
            @endif
        </section>
    </div>
@endsection

@push('styles')
<style>
    .incident-register { max-width: 1320px; margin: 0 auto; }
    .incident-register-head { align-items: flex-start; }
    .incident-register-heading { min-width: min(100%, 28rem); position: relative; z-index: 1; }
    .incident-register-heading h1 { margin: 0; }
    .incident-register-heading .muted { max-width: 42rem; }
    .incident-register-actions { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; position: relative; z-index: 1; }
    .incident-handoff-note { display: flex; align-items: flex-start; gap: .8rem; margin: 0 0 1rem; padding: .9rem 1rem; border: 1px solid rgba(24, 118, 94, .18); border-radius: var(--r-lg); background: #F1F7F3; color: var(--text); }
    .incident-handoff-icon { display: grid; place-items: center; flex: 0 0 1.55rem; height: 1.55rem; border-radius: 50%; background: var(--accent-soft); color: var(--interactive-ink); font-weight: 800; }
    .incident-handoff-note strong { display: block; margin: .05rem 0 .2rem; font-size: .88rem; }
    .incident-handoff-note p { margin: 0; color: var(--muted); font-size: .82rem; line-height: 1.5; }
    .incident-register-panel { margin-bottom: 1rem; overflow: hidden; border: 1px solid var(--rule-faint); border-radius: var(--r-lg); background: var(--surface); box-shadow: var(--shadow-card); }
    .incident-register-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.05rem 1.25rem; border-bottom: 1px solid var(--rule-faint); }
    .incident-register-panel-head h2 { margin: 0; font-size: 1.05rem; }
    .incident-register-panel-head p { margin: .2rem 0 0; color: var(--muted); font-size: .82rem; }
    .incident-case-count { display: grid; min-width: 5.2rem; justify-items: center; padding: .35rem .65rem; border: 1px solid var(--rule-faint); border-radius: 10px; background: var(--surface-soft); }
    .incident-case-count strong { font-size: 1.05rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .incident-case-count span { margin-top: .15rem; color: var(--muted); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .incident-table-wrap { padding: 0 1rem; }
    .incident-table { min-width: 840px; }
    .incident-table thead th { padding: .8rem .7rem; background: var(--surface-soft); color: var(--muted); font-size: .7rem; text-transform: uppercase; letter-spacing: .045em; }
    .incident-table tbody td { padding: .9rem .7rem; }
    .incident-case-link { display: inline-flex; align-items: center; gap: .25rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .incident-case-link span { font-size: .8rem; }
    .incident-case-hint { display: block; margin-top: .18rem; color: var(--muted); font-size: .7rem; }
    .incident-type-pill, .incident-status-pill { display: inline-flex; align-items: center; width: fit-content; min-height: 1.55rem; padding: .22rem .55rem; border: 1px solid transparent; border-radius: 999px; font-size: .7rem; font-weight: 800; line-height: 1.2; }
    .incident-type-pill.is-safety { background: #FFF3E8; border-color: #F0D6C2; color: #85491F; }
    .incident-type-pill.is-cash { background: #EEF2F1; border-color: #D8E0DD; color: #405753; }
    .incident-status-pill.is-open, .incident-status-pill.is-pending { background: #FFF6E9; border-color: #F0DFC0; color: #785719; }
    .incident-status-pill.is-review { background: #EEF3FC; border-color: #D6E1F3; color: #36537C; }
    .incident-status-pill.is-resolved, .incident-status-pill.is-acknowledged { background: var(--success-soft); border-color: rgba(43, 128, 80, .22); color: var(--success-ink); }
    .incident-details-cell strong { display: block; max-width: 25rem; overflow-wrap: anywhere; }
    .incident-variance { display: inline-block; margin-top: .2rem; font-size: .76rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .incident-variance.is-shortage { color: var(--danger); }
    .incident-variance.is-overage { color: var(--muted); }
    .incident-handoff-cell { display: grid; justify-items: start; gap: .3rem; }
    .incident-handoff-team { color: var(--muted); font-size: .72rem; font-weight: 700; }
    .incident-empty-cell { padding: 0 !important; border-bottom: 0 !important; }
    .incident-empty-state { display: grid; justify-items: center; max-width: 34rem; margin: 0 auto; padding: 2.7rem 1.2rem; text-align: center; }
    .incident-empty-mark { display: grid; place-items: center; width: 2.3rem; height: 2.3rem; margin-bottom: .55rem; border-radius: 50%; background: var(--surface-soft); color: var(--muted); font-size: 1.1rem; font-weight: 800; }
    .incident-empty-state h3 { margin: 0; font-size: 1rem; }
    .incident-empty-state p { margin: .45rem 0 1rem; color: var(--muted); font-size: .84rem; line-height: 1.55; }
    .incident-pagination { padding: .8rem 1.25rem; border-top: 1px solid var(--rule-faint); }
    .incident-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

    @media (max-width: 760px) {
        .incident-register-head { align-items: flex-start; }
        .incident-register-actions { justify-content: flex-start; }
        .incident-register-panel-head { padding: .9rem; }
        .incident-table-wrap { padding: .75rem; }
        .incident-table { min-width: 0; }
        .incident-table thead { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .incident-table, .incident-table tbody, .incident-table tr, .incident-table td { display: block; width: 100%; }
        .incident-table tbody { display: grid; gap: .7rem; }
        .incident-table tbody tr:not(.incident-empty-row) { padding: .15rem .8rem; border: 1px solid var(--rule-faint); border-radius: 12px; background: #fff; }
        .incident-table tbody tr:not(.incident-empty-row):hover { background: #fff; }
        .incident-table tbody td { display: grid; grid-template-columns: minmax(6.2rem, 31%) minmax(0, 1fr); align-items: start; gap: .65rem; padding: .7rem 0; border-bottom: 1px solid var(--rule-faint); }
        .incident-table tbody td:last-child { border-bottom: 0; }
        .incident-table tbody td::before { content: attr(data-label); color: var(--muted); font-size: .67rem; font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
        .incident-table tbody td.incident-empty-cell { display: block; border-bottom: 0; }
        .incident-table tbody td.incident-empty-cell::before { content: none; }
    }

    @media (max-width: 420px) {
        .incident-register-panel-head { align-items: flex-start; }
        .incident-case-count { min-width: 4.6rem; }
        .incident-register-actions > .btn { justify-content: center; }
        .incident-table tbody td { grid-template-columns: 5.5rem minmax(0, 1fr); }
    }
</style>
@endpush
