@extends('layouts.app')
@section('title', 'Closing Shift Report')
@push('styles')<link rel="stylesheet" href="{{ asset('css/shift.css') }}">@endpush
@section('content')
<div class="page-head shift-no-print">
    <h1>{{ $drawer->isOpen() ? 'Shift Preview' : 'Closing Shift Report' }}</h1>
    <div class="shift-actions"><a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a><button class="btn" type="button" id="print-shift-report">Print report</button></div>
</div>
<article class="card shift-report" id="shift-report">
    <section class="shift-print-summary" aria-label="Thermal shift report summary">
        <header class="shift-print-heading">
            <strong>{{ $drawer->isOpen() ? 'SHIFT PREVIEW' : 'CLOSING SHIFT REPORT' }}</strong>
            <span>SHIFT #{{ $report['drawer_id'] }}</span>
        </header>
        <dl class="shift-print-meta">
            <div><dt>Cashier</dt><dd>{{ $report['cashier'] }}</dd></div>
            <div><dt>Opened</dt><dd>{{ \Carbon\Carbon::parse($report['opened_at'])->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd></div>
            <div><dt>Closed</dt><dd>{{ $report['closed_at'] ? \Carbon\Carbon::parse($report['closed_at'])->timezone('Asia/Manila')->format('M j, Y g:i A') : 'Still open' }}</dd></div>
            <div><dt>Elapsed</dt><dd>{{ sprintf('%02d:%02d:%02d', intdiv($report['duration_seconds'],3600), intdiv($report['duration_seconds']%3600,60), $report['duration_seconds']%60) }}</dd></div>
        </dl>

        <h3>Sales and returns</h3>
        <dl class="shift-print-totals">
            @foreach (['cash'=>'Cash sales','card'=>'Card sales','e-wallet'=>'E-wallet sales'] as $method=>$label)
                <div><dt>{{ $label }}</dt><dd>₱{{ number_format($report['sales_by_method'][$method] ?? 0,2) }}</dd></div>
            @endforeach
            <div><dt>Total sales</dt><dd>₱{{ number_format($report['gross_sales'],2) }}</dd></div>
            <div><dt>Refunds / returns</dt><dd>−₱{{ number_format($report['refunds'],2) }}</dd></div>
            <div class="shift-print-emphasis"><dt>Net sales</dt><dd>₱{{ number_format($report['net_sales'],2) }}</dd></div>
        </dl>

        <h3>Cash reconciliation</h3>
        <dl class="shift-print-totals">
            <div><dt>Opening float</dt><dd>₱{{ number_format($report['opening_cash'],2) }}</dd></div>
            @foreach (['sale'=>'Cash sales','manual_in'=>'Other cash in','manual_out'=>'Other cash out','refund'=>'Cash refunds','exchange'=>'Exchange difference','transfer_in'=>'Transfer in','transfer_out'=>'Transfer out','transfer_reversal'=>'Transfer returned'] as $kind=>$label)
                @php($amount = (float) ($report['cash_by_kind'][$kind] ?? 0))
                @if (abs($amount) > .001)
                    <div><dt>{{ $label }}</dt><dd>{{ $amount < 0 ? '−₱'.number_format(abs($amount),2) : '₱'.number_format($amount,2) }}</dd></div>
                @endif
            @endforeach
            @if (abs($report['brought_forward'])>.001)<div><dt>Balance brought forward</dt><dd>₱{{ number_format($report['brought_forward'],2) }}</dd></div>@endif
            <div><dt>Total cash in</dt><dd>₱{{ number_format($report['cash_in'],2) }}</dd></div>
            <div><dt>Total cash out</dt><dd>−₱{{ number_format($report['cash_out'],2) }}</dd></div>
            <div class="shift-print-emphasis"><dt>Expected cash</dt><dd>₱{{ number_format($report['expected_cash'],2) }}</dd></div>
            <div><dt>Money on hand</dt><dd>{{ $report['actual_cash'] === null ? 'Not counted' : '₱'.number_format($report['actual_cash'],2) }}</dd></div>
            <div class="shift-print-emphasis">
                <dt>{{ $report['difference'] === null ? 'Difference' : (abs($report['difference'])<.01 ? 'Balanced' : ($report['difference']<0 ? 'Shortage' : 'Overage')) }}</dt>
                <dd>{{ $report['difference'] === null ? 'Pending closure' : (($report['difference'] < 0 ? '−₱' : '₱').number_format(abs($report['difference']),2)) }}</dd>
            </div>
        </dl>
        @if ($report['reconciliation_notes'])<p class="shift-print-notes"><strong>Notes:</strong> {{ $report['reconciliation_notes'] }}</p>@endif
        <footer>END OF REPORT</footer>
    </section>
    <div class="shift-report-detail">
    <h2>{{ $drawer->isOpen() ? 'Shift Preview' : 'Closing Shift Report' }} #{{ $report['drawer_id'] }}</h2>
    <dl class="shift-meta">
        <div><dt>Cashier</dt><dd>{{ $report['cashier'] }}</dd></div>
        <div><dt>Opened</dt><dd>{{ \Carbon\Carbon::parse($report['opened_at'])->timezone('Asia/Manila')->format('M j, Y g:i:s A') }}</dd></div>
        <div><dt>Closed</dt><dd>{{ $report['closed_at'] ? \Carbon\Carbon::parse($report['closed_at'])->timezone('Asia/Manila')->format('M j, Y g:i:s A') : 'Shift still open' }}</dd></div>
        <div><dt>Duration</dt><dd>{{ sprintf('%02d:%02d:%02d', intdiv($report['duration_seconds'],3600), intdiv($report['duration_seconds']%3600,60), $report['duration_seconds']%60) }}</dd></div>
    </dl>
    @if ($report['legacy'])<p class="muted">Legacy shift: detailed movement history was not recorded. Saved balances remain unchanged.</p>@endif
    <div class="shift-report-columns">
        <section><h3>Sales and returns</h3>
            <dl class="shift-totals">
                @foreach (['cash'=>'Cash sales','card'=>'Card sales','e-wallet'=>'E-wallet sales'] as $method=>$label)
                    <div><dt>{{ $label }}</dt><dd>₱{{ number_format($report['sales_by_method'][$method] ?? 0,2) }}</dd></div>
                @endforeach
                <div><dt>Sales before returns (after discounts)</dt><dd>₱{{ number_format($report['gross_sales'],2) }}</dd></div>
                <div><dt>Returns processed in this shift</dt><dd>−₱{{ number_format($report['refunds'],2) }}</dd></div>
                <div class="shift-total-line"><dt>Net sales</dt><dd>₱{{ number_format($report['net_sales'],2) }}</dd></div>
            </dl>
            <h3>Non-cash settlement</h3>
            <dl class="shift-totals"><div><dt>Card — net collected / refunded</dt><dd>₱{{ number_format($report['payment_totals']['card'],2) }}</dd></div><div><dt>E-wallet — net collected / refunded</dt><dd>₱{{ number_format($report['payment_totals']['e-wallet'],2) }}</dd></div></dl>
        </section>
        <section><h3>Cash reconciliation</h3>
            <dl class="shift-totals">
                <div><dt>Opening float</dt><dd>₱{{ number_format($report['opening_cash'],2) }}</dd></div>
                @if (abs($report['brought_forward'])>.001)<div><dt>Balance brought forward before ledger</dt><dd>₱{{ number_format($report['brought_forward'],2) }}</dd></div>@endif
                @foreach (['sale'=>'Cash sales collected','manual_in'=>'Other Cash In','manual_out'=>'Other Cash Out','refund'=>'Cash refunds','exchange'=>'Exchange cash difference','transfer_in'=>'Transfer In','transfer_out'=>'Transfer Out','transfer_reversal'=>'Cancelled transfer returned'] as $kind=>$label)
                    <div><dt>{{ $label }}</dt><dd>₱{{ number_format($report['cash_by_kind'][$kind] ?? 0,2) }}</dd></div>
                @endforeach
                <div><dt>Total Cash In</dt><dd>₱{{ number_format($report['cash_in'],2) }}</dd></div>
                <div><dt>Total Cash Out</dt><dd>−₱{{ number_format($report['cash_out'],2) }}</dd></div>
                <div class="shift-total-line"><dt>Expected cash</dt><dd>₱{{ number_format($report['expected_cash'],2) }}</dd></div>
                <div><dt>Money on hand — counted</dt><dd>{{ $report['actual_cash'] === null ? 'Not yet counted' : '₱'.number_format($report['actual_cash'],2) }}</dd></div>
                <div class="shift-total-line"><dt>{{ $report['difference'] === null ? 'Difference' : (abs($report['difference'])<.01 ? 'Balanced' : ($report['difference']<0 ? 'Shortage' : 'Overage')) }}</dt><dd>{{ $report['difference'] === null ? 'Pending closure' : '₱'.number_format($report['difference'],2) }}</dd></div>
            </dl>
            @if ($report['reconciliation_notes'])<p><strong>Reconciliation notes:</strong> {{ $report['reconciliation_notes'] }}</p>@endif
        </section>
    </div>
    <h3 class="shift-movement-heading">Movement history</h3>
    @if (empty($report['movements']))<p class="muted">No ledger movements for this shift.</p>@else
    <div class="table-wrap"><table class="shift-table shift-movement-table"><thead><tr><th>Time</th><th>Movement / reference</th><th>Method</th><th class="shift-money">Settled amount</th></tr></thead><tbody>
        @foreach ($report['movements'] as $m)<tr>
            <td>{{ \Carbon\Carbon::parse($m['created_at'])->timezone('Asia/Manila')->format('M j, g:i:s A') }}</td>
            <td>{{ ucwords(str_replace('_',' ',$m['kind'])) }}
                @if ($m['sale_id']) · Receipt {{ $m['receipt'] ?? '#'.$m['sale_id'] }} @endif
                @if ($m['refund_id']) · Return #{{ $m['refund_id'] }} from {{ $m['original_receipt'] ?? 'original receipt' }} @endif
                @if ($m['transfer_id']) · Transfer #{{ $m['transfer_id'] }} @endif
                @if ($m['reason'])<small>{{ $m['reason'] }}</small>@endif
                @foreach ($m['returned_items'] as $item)<small>{{ $item['name'] }} × {{ $item['quantity'] }} · {{ $item['condition'] }}</small>@endforeach
            </td><td>{{ ucfirst($m['payment_method']) }}</td><td class="shift-money">₱{{ number_format($m['amount'],2) }}</td>
        </tr>@endforeach
    </tbody></table></div>@endif
    </div>
</article>
@endsection
@push('scripts')<script>document.getElementById('print-shift-report').addEventListener('click', () => window.print());</script>@endpush
