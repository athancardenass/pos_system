@extends('layouts.app')

@section('title', 'Reports')

@php
    $columns = [
        'sales' => ['Transaction', 'Date', 'Cashier', 'Customer', 'Payment', 'Subtotal', 'Discount', 'Total', 'Status'],
        'products' => ['Product', 'Category', 'Units Sold', 'Revenue'],
        'inventory' => ['Product', 'Category', 'Price', 'Cost', 'Stock', 'Reorder Level', 'Status'],
        'refunds' => ['Refund', 'Sale', 'Date', 'Processed By', 'Amount', 'Type', 'Reason', 'Notes'],
    ];
@endphp

@section('content')
    <div class="page-head">
        <h1>Reports</h1>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="form-grid">
                <div>
                    <label for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}">
                </div>
                <div>
                    <label for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Apply date range</button>
            </div>
        </form>
    </div>

    @foreach ($types as $key => $label)
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <h2>{{ $label }}</h2>
                @if ($key === 'refunds' && ($refund_breakdown ?? []) !== [])
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        @foreach ($refund_breakdown as $reason => $b)
                            @php($barW = 8 + ($b['count'] * 6))
                            <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
                                <div style="width: {{ $barW }}px; height: 14px; background: var(--accent); border-radius: 3px;"></div>
                                <span style="color: var(--muted);">{{ $b['label'] }}:</span>
                                <span style="font-weight: 700;">{{ $b['count'] }}</span>
                                <span style="color: var(--muted);">₱{{ number_format($b['total'], 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <a class="btn" href="{{ route('reports.export', ['type' => $key, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">
                    Export CSV
                </a>
            </div>
            @php($rows = $previews[$key])
            @if (count($rows) === 0)
                <p class="empty">No data for this period.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>@foreach ($columns[$key] as $col)<th>{{ $col }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($rows, 0, 5) as $row)
                                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($rows) > 5)
                    <p class="muted" style="margin-top: 0.75rem; font-size: 0.8rem;">Showing 5 of {{ count($rows) }} rows — export the CSV for the full report.</p>
                @endif
            @endif
        </div>
    @endforeach
@endsection
