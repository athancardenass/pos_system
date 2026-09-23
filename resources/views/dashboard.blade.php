@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Welcome, {{ $employee->displayName() }} &bull; {{ now()->format('l, F j, Y') }}</p>
        </div>
        @if (in_array('pos.index', $modules))
            <div>
                <a href="{{ route('pos.index') }}" class="btn">New POS Sale</a>
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if (!empty($alerts))
        @foreach ($alerts as $alert)
            <div class="flash flash-{{ $alert['type'] === 'danger' ? 'error' : ($alert['type'] === 'warning' ? 'warning' : 'info') }}">
                {{ $alert['message'] }}
            </div>
        @endforeach
    @endif

    {{-- Sales Stats --}}
    @if (in_array('pos.index', $modules))
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Today's Sales</span>
                    <span class="badge badge-active">Live</span>
                </div>
                <div class="stat-card-val">{{ $stats['today_sales'] }}</div>
                <div class="stat-card-sub">Transactions today</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Today's Revenue</span>
                    <span style="color:var(--success); font-weight:700;">PHP</span>
                </div>
                <div class="stat-card-val" style="color:var(--success);">₱{{ number_format($stats['today_revenue'], 2) }}</div>
                <div class="stat-card-sub">Gross sales today</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Avg Transaction</span>
                </div>
                <div class="stat-card-val">₱{{ number_format($stats['avg_transaction'], 2) }}</div>
                <div class="stat-card-sub">Per completed sale</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Total Revenue</span>
                </div>
                <div class="stat-card-val">₱{{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="stat-card-sub">All-time sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Refunds This Week</span>
                </div>
                <div class="stat-card-val" style="color:var(--danger);">₱{{ number_format($stats['refunds_week'] ?? 0, 2) }}</div>
                <div class="stat-card-sub">{{ $stats['refunds_count'] ?? 0 }} refund(s) all-time</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">
                    <span>Refund Rate (7d)</span>
                </div>
                <div class="stat-card-val">{{ number_format($stats['refund_rate'] ?? 0, 1) }}%</div>
                <div class="stat-card-sub">{{ round($stats['refund_rate_baseline'] ?? 0, 1) }}% vs prior week</div>
            </div>
        </div>

        {{-- Weekly Trend & Payment Breakdown --}}
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem;">
            @if ($stats['weekly_trend']->count())
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header-bar">
                        <h2>Sales This Week</h2>
                        <span class="muted" style="font-size:0.78rem;">Last 7 days daily performance</span>
                    </div>
                    <div style="display: flex; gap: 0.75rem; align-items: flex-end; height: 140px; padding-top: 1rem;">
                        @php($maxRevenue = max($stats['weekly_trend']->pluck('revenue')->max(), 1))
                        @php($baseline = $stats['daily_baseline'] ?? 0)
                        @php($greenAt = $baseline * 1.10)
                        @php($orangeFrom = $baseline * 0.90)
                        @foreach ($stats['weekly_trend'] as $day)
                            @php($h = max(round(($day->revenue / $maxRevenue) * 110), 6))
                            @php($isAbove = $baseline > 0 && $day->revenue >= $greenAt)
                            @php($isBelow = $baseline > 0 && $day->revenue < $orangeFrom)
                            @php($barColor = $isAbove ? 'var(--success)' : ($isBelow ? 'var(--danger)' : 'var(--accent)'))
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                                <span style="font-size: 0.68rem; font-weight: 700; color: var(--muted);">₱{{ number_format($day->revenue / 1000, 1) }}k</span>
                                <div style="width: 100%; max-width: 42px; height: {{ $h }}px; background: {{ $barColor }}; border-radius: var(--r-sm); transition: height 0.3s;"></div>
                                <span style="font-size: 0.72rem; font-weight: 600; color: var(--muted); text-transform: uppercase;">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($stats['payment_methods']->count())
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header-bar">
                        <h2>Payment Methods</h2>
                        <span class="muted" style="font-size:0.78rem;">Today</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        @foreach ($stats['payment_methods'] as $pm)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.75rem; background: var(--surface-soft); border-radius: var(--r-sm); border: 1px solid var(--rule-faint);">
                                <span style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.04em;">{{ $pm->payment_method }}</span>
                                <div style="text-align: right;">
                                    <div style="font-weight: 800; font-size: 0.92rem;">₱{{ number_format($pm->total, 2) }}</div>
                                    <div style="color: var(--muted); font-size: 0.72rem;">{{ $pm->count }} txn(s)</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Recent Transactions Table --}}
        @if ($stats['recent_sales']->count())
            <div class="card">
                <div class="card-header-bar">
                    <h2>Recent Completed Transactions</h2>
                    @if (in_array('reports.index', $modules))
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.4rem 0.75rem;">View Full Report &rarr;</a>
                    @endif
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Receipt No.</th>
                                <th>Customer</th>
                                <th>Cashier</th>
                                <th style="text-align: right;">Total Amount</th>
                                <th>Payment Method</th>
                                <th>Transaction Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['recent_sales'] as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('pos.show', $sale) }}" style="font-weight: 700; color: var(--text);">{{ $sale->receipt?->receipt_number ?? '#' . $sale->transaction_id }}</a>
                                    </td>
                                    <td>{{ $sale->customer?->fullName() ?? 'Walk-in' }}</td>
                                    <td>{{ $sale->employee?->fullName() ?? $sale->employee?->username ?? '—' }}</td>
                                    <td style="text-align: right; font-weight: 800; font-variant-numeric: tabular-nums;">₱{{ number_format($sale->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge" style="background: var(--bg-tint); color: var(--text); border: 1px solid var(--rule-faint);">{{ strtoupper($sale->payment_method) }}</span>
                                    </td>
                                    <td style="color: var(--muted); font-size: 0.85rem;">{{ \Carbon\Carbon::parse($sale->transaction_date)->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Low Stock Alerts for Managers --}}
    @if (in_array('products.index', $modules) && $stats['low_stock_count'] > 0)
        <div class="card" style="border-left: 4px solid var(--danger);">
            <div class="card-header-bar">
                <h2 style="color: var(--danger);">Low Stock Alert ({{ $stats['low_stock_count'] }} Products)</h2>
                @if (in_array('inventory.index', $modules))
                    <a href="{{ route('inventory.index') }}" class="btn btn-danger" style="font-size: 0.78rem; padding: 0.4rem 0.75rem;">Manage Inventory &rarr;</a>
                @endif
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th style="text-align: right;">Stock on Hand</th>
                            <th style="text-align: right;">Reorder Threshold</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['low_stock_items'] as $item)
                            <tr>
                                <td style="font-weight: 600;">{{ $item->product_name }}</td>
                                <td style="text-align: right; font-weight: 800; color: {{ $item->stock_quantity == 0 ? 'var(--danger)' : 'var(--accent)' }};">
                                    {{ $item->stock_quantity }}
                                </td>
                                <td style="text-align: right; color: var(--muted);">{{ $item->reorder_level }}</td>
                                <td>
                                    <span class="badge {{ $item->stock_quantity == 0 ? 'badge-inactive' : 'badge-pending' }}">
                                        {{ $item->stock_quantity == 0 ? 'Out of Stock' : 'Low Stock' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
