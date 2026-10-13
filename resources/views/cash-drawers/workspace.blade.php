@extends('layouts.app')
@section('title', 'Cash movements')
@push('styles')<link rel="stylesheet" href="{{ asset('css/shift.css') }}">@endpush
@section('content')
<div class="pos-reference">
@include('cash-drawers.errors')
<header class="reference-header"><h1>Cash movements @if ($drawer)<span class="reference-header-meta">Register 01 · Shift since {{ $drawer->opened_at?->timezone('Asia/Manila')->format('g:i A') }}</span>@endif</h1><a class="reference-close" href="{{ route('pos.index') }}">Close (Esc)</a></header>
@if (!$drawer)
    <div class="card"><p>Open a shift in the POS before recording cash movements.</p></div>
@else
    <dl class="reference-cash-totals" aria-label="This shift cash summary">
        <div><dt>Opening float</dt><dd>₱{{ number_format($report['opening_cash'],2) }}</dd></div>
        <div><dt>Cash sales</dt><dd>₱{{ number_format($report['cash_by_kind']['sale'] ?? 0,2) }}</dd></div>
        <div><dt>Other cash in</dt><dd class="reference-positive">+₱{{ number_format($report['cash_by_kind']['manual_in'] ?? 0,2) }}</dd></div>
        <div><dt>Other cash out</dt><dd class="reference-negative">−₱{{ number_format(abs($report['cash_by_kind']['manual_out'] ?? 0),2) }}</dd></div>
        <div class="reference-cash-expected"><dt>Expected in drawer</dt><dd>₱{{ number_format($report['expected_cash'],2) }}</dd></div>
    </dl>
    <div class="reference-cash-layout">
        <section class="reference-cash-history" aria-label="This shift movements">
            <h2 class="reference-section-bar">This shift</h2>
            <div class="reference-movement-row"><div><strong>Opening float</strong><time>{{ $drawer->opened_at?->timezone('Asia/Manila')->format('g:i A') }}</time><p>Shift start · {{ $drawer->employee?->displayName() }}</p></div><strong>₱{{ number_format($report['opening_cash'],2) }}</strong></div>
            @foreach ($report['movements'] as $movement)
                @continue($movement['payment_method'] !== 'cash')
                <div class="reference-movement-row">
                    <div><strong>{{ ['manual_in' => 'Cash in', 'manual_out' => 'Cash out', 'sale' => 'Cash sales', 'refund' => 'Cash refund'][$movement['kind']] ?? \Illuminate\Support\Str::headline($movement['kind']) }}</strong><time>{{ \Carbon\Carbon::parse($movement['created_at'])->timezone('Asia/Manila')->format('g:i A') }}</time><p>{{ $movement['reason'] ?: ucfirst($movement['payment_method']) }}@if ($movement['receipt']) · {{ $movement['receipt'] }}@endif</p></div>
                    <strong class="{{ $movement['amount'] < 0 ? 'reference-negative' : 'reference-positive' }}">{{ $movement['amount'] < 0 ? '−' : '+' }}₱{{ number_format(abs($movement['amount']),2) }}</strong>
                </div>
            @endforeach
            <a class="reference-history-link" href="{{ route('cash-drawer.report',$drawer) }}">Review shift report</a>
        </section>
        <section class="reference-movement-form"><h2>New movement</h2><p class="muted">Sales and refunds are recorded automatically. Use this for other drawer cash.</p>
            <form method="POST" action="{{ route('cash-drawer.movements.store') }}" data-shift-action="cash_movement">
                @csrf<input type="hidden" name="operation_key" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01">
                <label for="cash-kind">Movement</label><select id="cash-kind" name="kind" class="reference-native-select"><option value="manual_in">Cash In</option><option value="manual_out">Cash Out</option></select>
                <div class="reference-segmented" data-select-chips="cash-kind"><button type="button" data-choice="manual_in" aria-pressed="true">Cash In</button><button type="button" data-choice="manual_out" aria-pressed="false">Cash Out</button></div>
                <label for="cash-amount">Amount</label><input id="cash-amount" name="amount" type="number" min="0.01" max="999999999" step="0.01" required>
                <label for="cash-reason">Reason</label><div class="reference-reason-chips" data-reason-chips="cash-reason"><button type="button" data-choice="Change fund">Change fund</button><button type="button" data-choice="Supplier payment">Supplier payment</button><button type="button" data-choice="Bank deposit">Bank deposit</button><button type="button" data-choice="">Other</button></div><textarea id="cash-reason" name="reason" class="input-lg bordered" rows="2" maxlength="255" required placeholder="Add the reason or a movement note"></textarea>
                <p class="shift-form-error" role="alert" hidden></p><button class="btn" type="submit">Record cash movement</button>
            </form>
        </section>
    </div>
    <details class="reference-transfer-panel"><summary>Drawer Transfer</summary><section class="card"><p class="muted">Cash leaves your drawer now. The receiving cashier must confirm receipt.</p>
            @if ($destinations->isEmpty())<p>No other open cashier drawer. Ask the recipient to open their shift.</p>@else
            <form method="POST" action="{{ route('cash-drawer.transfers.store') }}" data-shift-action="cash_transfer">
                @csrf<input type="hidden" name="operation_key" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01">
                <label for="transfer-destination">Receiving drawer</label><select id="transfer-destination" name="destination_drawer_id" required>@foreach ($destinations as $d)<option value="{{ $d->drawer_id }}">{{ $d->employee?->displayName() }} · Shift #{{ $d->drawer_id }}</option>@endforeach</select>
                <label for="transfer-amount">Amount</label><input id="transfer-amount" name="amount" type="number" min="0.01" max="{{ max(0,(float)$drawer->expected_cash) }}" step="0.01" required>
                <label for="transfer-reason">Reason</label><input id="transfer-reason" name="reason" maxlength="255" required>
                <p class="shift-form-error" role="alert" hidden></p><button class="btn" type="submit">Send cash transfer</button>
            </form>@endif
        </section></details>
    <section class="card"><h2>Transfers</h2>
    @if ($transfers->isEmpty())<p class="muted">No drawer transfers this shift.</p>@else
    <div class="table-wrap"><table class="shift-table"><thead><tr><th>Reference</th><th>From → To</th><th>Amount / reason</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @foreach ($transfers as $transfer)<tr><td>#{{ $transfer->id }}</td><td>{{ $transfer->source?->employee?->displayName() }} → {{ $transfer->destination?->employee?->displayName() }}</td><td>₱{{ number_format($transfer->amount,2) }}<small>{{ $transfer->reason }}</small></td><td>{{ ucfirst($transfer->status) }}</td><td>
            @if ($transfer->status==='pending' && $transfer->destination_drawer_id===$drawer->drawer_id)
                <form method="POST" action="{{ route('cash-drawer.transfers.accept',$transfer) }}">@csrf<button class="btn btn-secondary" type="submit">Confirm cash received</button></form>
            @elseif ($transfer->status==='pending' && $transfer->source_drawer_id===$drawer->drawer_id)
                <form method="POST" action="{{ route('cash-drawer.transfers.cancel',$transfer) }}" data-shift-action="cash_transfer_cancel">@csrf<input type="hidden" name="transfer_id" value="{{ $transfer->id }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01"><p class="shift-form-error" role="alert" hidden></p><button class="btn btn-secondary" type="submit">Cancel and return cash</button></form>
            @endif
        </td></tr>@endforeach
    </tbody></table></div>@endif
    </section>
    <p class="muted">A transfer changes both drawer balances; it does not erase an existing shortage.</p>
    <x-manager-pin-modal :is-manager="auth()->user()->hasRole('Manager')" />
@endif
</div>
@endsection
@push('scripts')<script src="{{ asset('js/shift-actions.js') }}" defer></script><script src="{{ asset('js/pos-reference.js') }}" defer></script>@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos-reference.css') }}">
@endpush
