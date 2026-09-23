@extends('layouts.app')

@section('title', 'Cash Drawer Accountability')

@section('content')
<div class="page-head">
    <div>
        <h1>Cash Drawer Sessions</h1>
        <p class="muted">Review cashier opening floats, sales collected, and shift closure reconciliation.</p>
    </div>
</div>

<div class="card">
    @if ($drawers->isEmpty())
        <p class="empty">No closed cash drawer sessions recorded yet.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Responsible Cashier</th>
                        <th>Opened Shift</th>
                        <th>Closed Shift</th>
                        <th style="text-align: right;">Opening Float</th>
                        <th style="text-align: right;">Expected in Drawer</th>
                        <th style="text-align: right;">Actual Counted</th>
                        <th style="text-align: center;">Accountability Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drawers as $drawer)
                        @php($diff = (float) $drawer->difference)
                        @php($isBalanced = abs($diff) < 0.01)
                        @php($isOverage = $diff > 0)
                        <tr>
                            <td>
                                <strong>{{ $drawer->employee?->fullName() ?? $drawer->employee?->username ?? 'Unknown Cashier' }}</strong>
                            </td>
                            <td style="color: var(--muted); font-size: 0.85rem;">
                                {{ $drawer->opened_at?->format('M j, Y g:i A') ?? '—' }}
                            </td>
                            <td style="color: var(--muted); font-size: 0.85rem;">
                                {{ $drawer->closed_at?->format('M j, Y g:i A') ?? '—' }}
                            </td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                ₱{{ number_format($drawer->opening_cash, 2) }}
                            </td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">
                                ₱{{ number_format($drawer->expected_cash, 2) }}
                            </td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 800;">
                                ₱{{ number_format($drawer->actual_cash, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if ($isBalanced)
                                    <span class="badge badge-balanced">Balanced (₱0.00)</span>
                                @elseif ($isOverage)
                                    <span class="badge badge-overage">+₱{{ number_format($diff, 2) }} Overage</span>
                                @else
                                    <span class="badge badge-shortage">-₱{{ number_format(abs($diff), 2) }} Shortage</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
