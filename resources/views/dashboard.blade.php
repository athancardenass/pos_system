@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-payment-method-list { display: flex; flex-direction: column; gap: .6rem; }
        .dashboard-payment-method-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: .7rem .85rem;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px;
            background: #16803c;
            color: #fff;
            box-shadow: none;
            transition: background-color .16s ease, border-color .16s ease;
        }
        .dashboard-payment-method-row:hover { border-color: rgba(255,255,255,.26); background: #126b32; }
        .dashboard-payment-method-name { color: #fff; font-size: .8rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .dashboard-payment-method-summary { text-align: right; }
        .dashboard-payment-method-total { color: #fff; font-size: .92rem; font-weight: 800; }
        .dashboard-payment-method-count { color: rgba(255,255,255,.78); font-size: .72rem; }

        /* Modern Dashboard Hero Header */
        .page-head.dashboard-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.25rem;
            min-height: 88px;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.6rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--r-lg);
            background: rgb(56, 43, 45);
            background: linear-gradient(135deg, rgb(56, 43, 45) 0%, rgb(40, 29, 31) 100%);
            color: #FFFFFF;
            box-shadow: 0 10px 25px -5px rgba(56, 43, 45, 0.28), 0 4px 10px -2px rgba(56, 43, 45, 0.16);
            position: relative;
            overflow: hidden;
        }

        .page-head.dashboard-head::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 190, 152, 0.14) 0%, transparent 70%);
            pointer-events: none;
            border-radius: 50%;
        }

        .dashboard-head-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            background: rgba(255, 190, 152, 0.14);
            border: 1px solid rgba(255, 190, 152, 0.28);
            color: #FFBE98;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .live-pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ADE80;
            box-shadow: 0 0 6px #4ADE80;
            animation: pulse-live 2s infinite ease-in-out;
            flex-shrink: 0;
        }

        @keyframes pulse-live {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.85); }
        }

        .dashboard-title {
            color: #FFFFFF !important;
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1.15;
        }

        .dashboard-welcome {
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .dashboard-welcome strong {
            color: #FFFFFF;
            font-weight: 800;
        }

        .welcome-sep {
            opacity: 0.45;
            color: #FFBE98;
        }

        .welcome-date {
            color: #FFD4BC;
            font-weight: 600;
        }

        .dashboard-head-actions {
            position: relative;
            z-index: 1;
        }

        #btn-new-pos-sale.btn-pos-hero,
        a#btn-new-pos-sale.btn {
            --btn-bg: #FFBE98;
            --btn-bg-hover: #FFD4BC;
            min-height: 44px;
            padding: 0.65rem 1.15rem;
            background: #FFBE98 !important;
            color: #0F172A !important;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            text-transform: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.16s var(--ease), background-color 0.16s var(--ease), box-shadow 0.16s var(--ease);
        }

        #btn-new-pos-sale.btn-pos-hero:hover,
        a#btn-new-pos-sale.btn:hover {
            background: #FFD4BC !important;
            color: #0F172A !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 190, 152, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        #btn-new-pos-sale.btn-pos-hero:active,
        a#btn-new-pos-sale.btn:active {
            transform: translateY(0);
        }

        .btn-pos-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: rgba(15, 23, 42, 0.12);
            color: #0F172A;
            flex-shrink: 0;
        }

        .btn-pos-label {
            font-weight: 800;
            color: #0F172A !important;
        }

        .btn-pos-kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.1rem 0.4rem;
            font-family: inherit;
            font-size: 0.68rem;
            font-weight: 800;
            color: rgba(15, 23, 42, 0.75);
            background: rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 4px;
            line-height: 1;
        }

        @media (max-width: 640px) {
            .page-head.dashboard-head {
                flex-direction: column;
                align-items: stretch;
                padding: 1.15rem;
            }
            .dashboard-head-actions {
                width: 100%;
            }
            #btn-new-pos-sale.btn-pos-hero,
            a#btn-new-pos-sale.btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head dashboard-head">
        <div class="dashboard-head-info">
            <div class="dashboard-eyebrow">
                <span class="live-pulse-dot" aria-hidden="true"></span>
                <span>Live Operations &bull; Asia/Manila (PHT)</span>
            </div>
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-welcome">
                <span>Welcome, <strong>{{ $employee->displayName() }}</strong></span>
                <span class="welcome-sep" aria-hidden="true">&bull;</span>
                <span class="welcome-date">{{ now()->format('l, F j, Y') }}</span>
            </p>
        </div>
        @if (in_array('pos.index', $modules))
            <div class="dashboard-head-actions">
                <a href="{{ route('pos.index') }}" class="btn btn-pos-hero" id="btn-new-pos-sale">
                    <span class="btn-pos-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </span>
                    <span class="btn-pos-label">New POS Sale</span>
                    <span class="btn-pos-kbd" aria-hidden="true">F1</span>
                </a>
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
                    <div class="dashboard-payment-method-list">
                        @foreach ($stats['payment_methods'] as $pm)
                            <div class="dashboard-payment-method-row">
                                <span class="dashboard-payment-method-name">{{ $pm->payment_method }}</span>
                                <div class="dashboard-payment-method-summary">
                                    <div class="dashboard-payment-method-total">₱{{ number_format($pm->total, 2) }}</div>
                                    <div class="dashboard-payment-method-count">{{ $pm->count }} txn(s)</div>
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
                        <x-ui.button variant="secondary" size="compact" :href="route('reports.index')">View Full Report &rarr;</x-ui.button>
                    @endif
                </div>
                <div class="table-wrap">
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>Receipt No.</th>
                                <th>Customer</th>
                                <th>Cashier</th>
                                <th style="text-align: right;">Total Amount</th>
                                <th>Payment Method</th>
                                <th>Receipt</th>
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
                                        <x-ui.badge variant="slate">{{ strtoupper($sale->payment_method) }}</x-ui.badge>
                                    </td>
                                    <td><x-ui.button variant="blue" size="compact" :href="route('pos.reprint', $sale)">Reprint</x-ui.button></td>
                                    <td style="color: var(--muted); font-size: 0.85rem;">{{ \Carbon\Carbon::parse($sale->transaction_date)->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
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
                    <x-ui.button variant="danger" size="compact" :href="route('inventory.index')">Manage Inventory &rarr;</x-ui.button>
                @endif
            </div>
            <div class="table-wrap">
                <x-ui.table>
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
                </x-ui.table>
            </div>
        </div>
    @endif
@endsection
