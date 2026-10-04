@extends('layouts.app')

@section('title', 'Audit logs')

@push('styles')
    <style>
        .audit-period-filter { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem; }
        .audit-period-button {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            padding: 7px 13px;
            border: 1px solid rgba(32,60,61,.15);
            border-radius: 9px;
            background: #fff;
            color: var(--text) !important;
            font-size: .8rem;
            font-weight: 750;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }
        .audit-period-button:hover, .audit-period-button:focus-visible { border-color: var(--text); background: #f2f5f3; text-decoration: none; }
        .audit-period-button.is-active { border-color: var(--text); background: var(--text); color: #fff !important; font-weight: 800; }
        .audit-period-button.is-active:hover, .audit-period-button.is-active:focus-visible { background: #2b4d4e; }
        .audit-period-button:focus-visible { outline: 3px solid rgba(32,60,61,.2); outline-offset: 2px; }
        .audit-period-caption { margin: 0 0 1rem; color: var(--muted); font-size: .82rem; }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <h1>Audit logs</h1>
    </div>
    <div class="card">
        <nav class="audit-period-filter" aria-label="Filter audit logs by time period">
            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'all' => 'All time'] as $key => $label)
                <a class="audit-period-button {{ $period === $key ? 'is-active' : '' }}" href="{{ route('audit-logs.index', $key === 'all' ? [] : ['period' => $key]) }}" @if ($period === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        @if ($from && $to)
            <p class="audit-period-caption">Showing activity from {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}.</p>
        @endif
        @if ($logs->isEmpty())
            <p class="empty">No audit activity found for this period.</p>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Requested by</th>
                        <th>Approved by</th>
                        <th>Register</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record</th>
                        <th>Details</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ optional($log->action_timestamp)->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->requester?->username ?? $log->employee?->username ?? '—' }}</td>
                            <td>{{ $log->approver?->username ?? '—' }}</td>
                            <td>{{ $log->register_id ?? '—' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->table_affected }}</td>
                            <td>{{ $log->record_id }}</td>
                            <td class="muted">{{ $log->details ? json_encode($log->details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—' }}</td>
                            <td class="muted">{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            {{ $logs->links('partials.pagination') }}
        @endif
    </div>
@endsection
