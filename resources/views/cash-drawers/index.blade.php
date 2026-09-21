@extends('layouts.app')

@section('title', 'Cash Drawer History')

@section('content')
<div class="page-head">
    <h1>Cash Drawer History</h1>
</div>

<div class="card">
    @if ($drawers->isEmpty())
        <p class="empty">No closed cash drawers yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Cashier</th>
                    <th>Opened</th>
                    <th>Closed</th>
                    <th>Opening Cash</th>
                    <th>Expected Cash</th>
                    <th>Actual Cash</th>
                    <th>Difference</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($drawers as $drawer)
                    <tr>
                        <td>{{ $drawer->employee?->fullName() ?? 'Unknown' }}</td>
                        <td>{{ $drawer->opened_at?->format('M d, Y H:i') }}</td>
                        <td>{{ $drawer->closed_at?->format('M d, Y H:i') }}</td>
                        <td>₱{{ number_format($drawer->opening_cash, 2) }}</td>
                        <td>₱{{ number_format($drawer->expected_cash, 2) }}</td>
                        <td>₱{{ number_format($drawer->actual_cash, 2) }}</td>
                        <td style="font-weight: 700; color: {{ $drawer->difference > 0 ? 'var(--success)' : ($drawer->difference < 0 ? 'var(--danger)' : 'var(--text)') }};">
                            {{ $drawer->difference > 0 ? '+' : '' }}₱{{ number_format($drawer->difference, 2) }}
                            @if($drawer->difference > 0)
                                (Overage)
                            @elseif($drawer->difference < 0)
                                (Shortage)
                            @else
                                (Balanced)
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
