@extends('layouts.app')

@section('title', 'Reports')

@php
    $columns = [
        'sales' => ['Transaction', 'Receipt No.', 'Date', 'Cashier', 'Customer', 'Payment', 'Subtotal', 'Discount', 'Total', 'Status'],
        'products' => ['Product', 'Category', 'Units Sold', 'Revenue'],
        'inventory' => ['Product', 'Category', 'Price', 'Cost', 'Stock', 'Reorder Level', 'Status'],
        'refunds' => ['Refund', 'Sale', 'Date', 'Processed By', 'Amount', 'Type', 'Reason', 'Notes'],
    ];
@endphp

@section('content')
    <div class="page-head">
        <h1>Reports</h1>
    </div>

    <section class="card" aria-labelledby="incident-reports-heading">
        <h2 id="incident-reports-heading">Incident reports</h2>
        <p>Review product safety concerns and cashier shortages or overages, record manager findings, and export cases for Inventory or HRMS handoff. Incident cases have their own register and are not limited by the sales date filter below.</p>
        <a class="btn" href="{{ route('incidents.index') }}">View incident reports</a>
        <a class="btn btn-secondary" href="{{ route('incidents.create') }}">Report safety concern</a>
    </section>

    <div class="card">
        <form method="GET" action="{{ route('reports.index') }}" id="report-filter-form">
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
            <div class="pos-quick-filters">
                <button type="button" class="pos-quick-btn" data-range="daily">Daily</button>
                <button type="button" class="pos-quick-btn" data-range="weekly">Weekly</button>
                <button type="button" class="pos-quick-btn" data-range="monthly">Monthly</button>
                <button type="button" class="pos-quick-btn" data-range="yearly">Yearly</button>
                <x-ui.button type="submit">Apply</x-ui.button>
            </div>
        </form>
    </div>

    @push('styles')
    <style>
        .pos-quick-filters { display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; }
        .pos-quick-btn {
            padding: 0.5rem 1rem;
            background: var(--surface); border: 2px solid var(--rule);
            border-radius: 6px; cursor: pointer;
            font-family: inherit; font-size: 0.78rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.03em;
            color: var(--text); transition: all 0.15s;
        }
        .pos-quick-btn:hover { border-color: var(--accent); }
        .pos-quick-btn.active {
            background: var(--text);
            color: #fff;
            border-color: var(--text);
        }
        .report-results-summary { margin-top: 0.75rem; color: var(--muted); font-size: 0.8rem; }
        .report-receipt-link { font-weight: 700; text-decoration: underline; text-underline-offset: 0.12em; white-space: nowrap; }
        .report-receipt-link:hover { color: var(--interactive-ink); }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.querySelectorAll('.pos-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.pos-quick-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const today = new Date();
                let from = new Date();
                const to = new Date();
                switch (btn.dataset.range) {
                    case 'daily':
                        from = new Date(today);
                        break;
                    case 'weekly':
                        from = new Date(today);
                        from.setDate(today.getDate() - 6);
                        break;
                    case 'monthly':
                        from = new Date(today);
                        from.setMonth(today.getMonth() - 1);
                        break;
                    case 'yearly':
                        from = new Date(today);
                        from.setFullYear(today.getFullYear() - 1);
                        break;
                }
                const fmt = d => d.toISOString().split('T')[0];
                document.getElementById('from').value = fmt(from);
                document.getElementById('to').value = fmt(to);
            });
        });
    </script>
    @endpush

    @foreach ($types as $key => $label)
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <h2>{{ $label }}</h2>
                @if ($key === 'refunds' && ($refund_breakdown ?? []) !== [])
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        @foreach ($refund_breakdown as $reason => $b)
                            @php
                                $barW = 8 + ($b['count'] * 6);
                            @endphp
                            <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
                                <div style="width: {{ $barW }}px; height: 14px; background: var(--accent); border-radius: 3px;"></div>
                                <span style="color: var(--muted);">{{ $b['label'] }}:</span>
                                <span style="font-weight: 700;">{{ $b['count'] }}</span>
                                <span style="color: var(--muted);">₱{{ number_format($b['total'], 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <x-ui.button :href="route('reports.export', ['type' => $key, 'from' => $from->toDateString(), 'to' => $to->toDateString()])">Export CSV</x-ui.button>
            </div>
            @php
                $isSales = $key === 'sales';
                $rows = $isSales ? $transactions->items() : $previews[$key];
            @endphp
            @if (count($rows) === 0)
                <p class="empty">{{ $isSales ? 'No completed transactions for this period.' : 'No data for this period.' }}</p>
            @else
                <div class="table-wrap">
                    <x-ui.table>
                        <thead>
                            <tr>@foreach ($columns[$key] as $col)<th>{{ $col }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                            @foreach ($isSales ? $rows : array_slice($rows, 0, 5) as $row)
                                <tr>
                                    @foreach ($row as $columnIndex => $cell)
                                        <td>
                                            @if ($isSales && $columnIndex === 1 && $cell !== '—')
                                                <a class="report-receipt-link" href="{{ route('pos.show', $row[0]) }}" aria-label="Open receipt {{ $cell }}">{{ $cell }}</a>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                </div>
                @if ($isSales)
                    @if ($transactions->hasPages())
                        {{ $transactions->links('partials.pagination') }}
                    @else
                        <p class="report-results-summary">Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }} completed transactions in the selected period.</p>
                    @endif
                @elseif (count($rows) > 5)
                    <p class="muted" style="margin-top: 0.75rem; font-size: 0.8rem;">Showing 5 of {{ count($rows) }} rows — export the CSV for the full report.</p>
                @endif
            @endif
        </div>
    @endforeach
@endsection
