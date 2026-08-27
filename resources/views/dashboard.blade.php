@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Welcome, {{ $employee->username }} · {{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if (!empty($alerts))
        @foreach ($alerts as $alert)
            <div class="flash flash-{{ $alert['type'] === 'danger' ? 'error' : ($alert['type'] === 'warning' ? 'warning' : 'info') }}" style="margin-bottom: 0.75rem;">
                {{ $alert['message'] }}
            </div>
        @endforeach
    @endif

    {{-- Quick Actions --}}
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        @if (in_array('pos.index', $modules))
            <a href="{{ route('pos.index') }}" class="btn" style="font-size: 0.85rem;">⚡ New Sale</a>
        @endif
        @if (in_array('products.index', $modules))
            <a href="{{ route('products.create') }}" class="btn btn-secondary" style="font-size: 0.85rem;">+ Add Product</a>
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">📦 Inventory</a>
        @endif
        @if (in_array('customers.index', $modules))
            <a href="{{ route('customers.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">👤 Customers</a>
        @endif
        @if (in_array('purchase-orders.index', $modules))
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">📋 Orders</a>
        @endif
    </div>

    {{-- Sales Stats --}}
    @if (in_array('pos.index', $modules))
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
            <div style="background: var(--surface); border-bottom: 3px solid var(--accent); padding: 1rem 1.25rem;">
                <div style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 0.25rem;">Today's Sales</div>
                <div style="font-size: 1.75rem; font-weight: 700;">{{ $stats['today_sales'] }}</div>
            </div>
            <div style="background: var(--surface); border-bottom: 3px solid var(--success); padding: 1rem 1.25rem;">
                <div style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 0.25rem;">Today's Revenue</div>
                <div style="font-size: 1.75rem; font-weight: 700;">₱{{ number_format($stats['today_revenue'], 2) }}</div>
            </div>
            <div style="background: var(--surface); border-bottom: 3px solid var(--text); padding: 1rem 1.25rem;">
                <div style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 0.25rem;">Avg Transaction</div>
                <div style="font-size: 1.75rem; font-weight: 700;">₱{{ number_format($stats['avg_transaction'], 2) }}</div>
            </div>
            <div style="background: var(--surface); border-bottom: 3px solid var(--muted); padding: 1rem 1.25rem;">
                <div style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 0.25rem;">Total Revenue</div>
                <div style="font-size: 1.75rem; font-weight: 700;">₱{{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
        </div>

        {{-- Weekly Trend --}}
        @if ($stats['weekly_trend']->count())
            <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1rem; color: var(--muted);">Sales This Week</h2>
                <div style="display: flex; gap: 0.5rem; align-items: flex-end; height: 120px;">
                    @php($maxRevenue = max($stats['weekly_trend']->pluck('revenue')->max(), 1))
                    @php($baseline = $stats['daily_baseline'] ?? 0)
                    {{-- Color bands vs previous-week daily average: green >= 110%, orange within ±10%, red < 90% --}}
                    @php($greenAt = $baseline * 1.10)
                    @php($orangeFrom = $baseline * 0.90)
                    @foreach ($stats['weekly_trend'] as $day)
                        @php($barColor = $baseline > 0 && $day->revenue >= $greenAt ? 'var(--success)' : ($baseline > 0 && $day->revenue < $orangeFrom ? 'var(--danger)' : ($baseline > 0 ? '#C4956A' : 'var(--accent)')))
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                            <span style="font-size: 0.65rem; font-weight: 600;">₱{{ number_format($day->revenue, 0) }}</span>
                            <div style="width: 100%; max-width: 40px; background: {{ $barColor }}; min-height: 4px; height: {{ max(4, ($day->revenue / $maxRevenue) * 80) }}px;"></div>
                            <span style="font-size: 0.6rem; color: var(--muted);">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Chart legend (only when there is a baseline to compare against) --}}
            @if (($stats['daily_baseline'] ?? 0) > 0)
                <div style="display: flex; gap: 1.25rem; margin-top: -0.75rem; margin-bottom: 1.5rem; padding-left: 1.25rem; font-size: 0.7rem; color: var(--muted); flex-wrap: wrap;">
                    <span><span style="display:inline-block;width:10px;height:10px;background:var(--success);margin-right:0.3rem;"></span>High ≥ ₱{{ number_format($greenAt, 0) }}</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#C4956A;margin-right:0.3rem;"></span>Average ₱{{ number_format($orangeFrom, 0) }}–{{ number_format($greenAt, 0) }}</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:var(--danger);margin-right:0.3rem;"></span>Low &lt; ₱{{ number_format($orangeFrom, 0) }}</span>
                </div>
            @endif
        @endif

        {{-- Two-column layout: Top Products + Payment Methods --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            {{-- Top Products --}}
            @if ($stats['top_products']->count())
                <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem;">
                    <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; color: var(--muted);">Top Products</h2>
                    @foreach ($stats['top_products'] as $item)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; border-bottom: 1px solid #eee;">
                            <div>
                                <span style="font-weight: 600; font-size: 0.88rem;">{{ $item->product?->product_name ?? 'Unknown' }}</span>
                                <span style="color: var(--muted); font-size: 0.75rem; margin-left: 0.4rem;">×{{ $item->total_qty }}</span>
                            </div>
                            <span style="font-weight: 600; font-size: 0.85rem;">₱{{ number_format($item->total_revenue, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Payment Methods --}}
            @if ($stats['payment_methods']->count())
                <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem;">
                    <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; color: var(--muted);">Payment Methods (Today)</h2>
                    @foreach ($stats['payment_methods'] as $pm)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; border-bottom: 1px solid #eee;">
                            <span style="text-transform: uppercase; font-size: 0.82rem; font-weight: 600;">{{ $pm->payment_method }}</span>
                            <div style="text-align: right;">
                                <span style="font-weight: 600; font-size: 0.85rem;">₱{{ number_format($pm->total, 2) }}</span>
                                <span style="color: var(--muted); font-size: 0.72rem; margin-left: 0.4rem;">({{ $pm->count }} txns)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent Transactions --}}
        @if ($stats['recent_sales']->count())
            <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; color: var(--muted);">Recent Transactions</h2>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Receipt</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Customer</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Cashier</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em; text-align: right;">Amount</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Method</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['recent_sales'] as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('pos.show', $sale) }}" style="font-weight: 600;">{{ $sale->receipt?->receipt_number ?? '#' . $sale->transaction_id }}</a>
                                </td>
                                <td>{{ $sale->customer?->fullName() ?? 'Walk-in' }}</td>
                                <td>{{ $sale->employee?->username ?? '—' }}</td>
                                <td style="text-align: right; font-weight: 600;">₱{{ number_format($sale->total_amount, 2) }}</td>
                                <td style="text-transform: uppercase;">{{ $sale->payment_method }}</td>
                                <td style="color: var(--muted);">{{ \Carbon\Carbon::parse($sale->transaction_date)->format('M j, g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Inventory Alerts --}}
    @if (in_array('products.index', $modules) && $stats['low_stock_count'] > 0)
        <div style="background: var(--surface); border: 2px solid var(--rule); border-left: 4px solid var(--danger); padding: 1.25rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; color: var(--danger);">⚠ Low Stock Alert</h2>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Product</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em; text-align: right;">Stock</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em; text-align: right;">Reorder At</th>
                            <th style="font-size: 0.7rem; letter-spacing: 0.08em;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['low_stock_items'] as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td style="text-align: right; font-weight: 700; color: {{ $item->stock_quantity == 0 ? 'var(--danger)' : 'var(--accent)' }};">{{ $item->stock_quantity }}</td>
                                <td style="text-align: right; color: var(--muted);">{{ $item->reorder_level }}</td>
                                <td>
                                    <a href="{{ route('inventory.edit', $item->inventory_id) }}" style="font-weight: 600; text-decoration: underline;">Restock</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
        </div>
    @endif

    {{-- Top Categories --}}
    @if (in_array('pos.index', $modules) && $stats['top_categories']->count())
        <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; color: var(--muted);">Top Categories</h2>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @foreach ($stats['top_categories'] as $cat)
                    <div style="flex: 1; min-width: 140px; background: rgba(32,60,61,0.04); padding: 0.75rem; border-bottom: 3px solid var(--text);">
                        <div style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">{{ $cat->category_name }}</div>
                        <div style="font-size: 1.1rem; font-weight: 700;">₱{{ number_format($cat->total_revenue, 0) }}</div>
                        <div style="font-size: 0.7rem; color: var(--muted);">{{ $cat->total_qty }} items sold</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Activity (Admin) --}}
    @if (in_array('audit-logs.index', $modules) && isset($stats['recent_activity']) && $stats['recent_activity']->count())
        <div style="background: var(--surface); border: 2px solid var(--rule); padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h2 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted);">Recent Activity</h2>
                <a href="{{ route('audit-logs.index') }}" style="font-size: 0.78rem; font-weight: 600; text-decoration: underline;">View all →</a>
            </div>
            @foreach ($stats['recent_activity'] as $log)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.6rem; border-bottom: 1px solid #eee; font-size: 0.82rem;">
                    <div>
                        <span style="font-weight: 600; text-transform: uppercase; font-size: 0.72rem; padding: 0.1rem 0.3rem; background: rgba(32,60,61,0.06);">{{ $log->action }}</span>
                        <span style="color: var(--muted); margin-left: 0.3rem;">{{ $log->description }}</span>
                    </div>
                    <span style="color: var(--muted); font-size: 0.72rem; white-space: nowrap;">{{ $log->action_timestamp->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <style>
        @media (max-width: 768px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-template-columns: repeat(4"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        @media (max-width: 480px) {
            div[style*="grid-template-columns: repeat(4"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection
