@extends('layouts.app')
@section('title', 'Cash movements')
@push('styles')<link rel="stylesheet" href="{{ asset('css/shift.css') }}">@endpush
@section('content')
@include('cash-drawers.errors')
<div class="page-head"><h1>Cash movements</h1><a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a></div>
@if (!$drawer)
    <div class="card"><p>Open a shift in the POS before recording cash movements.</p></div>
@else
    <div class="card shift-overview"><strong>{{ $drawer->employee?->displayName() }} · Shift #{{ $drawer->drawer_id }}</strong><span>Expected cash: ₱{{ number_format($drawer->expected_cash,2) }}</span><a href="{{ route('cash-drawer.report',$drawer) }}">Review shift report</a></div>
    <div class="shift-report-columns">
        <section class="card"><h2>Cash In / Cash Out</h2><p class="muted">Sales and refunds are recorded automatically. Use this for other drawer cash.</p>
            <form method="POST" action="{{ route('cash-drawer.movements.store') }}" data-shift-action="cash_movement">
                @csrf<input type="hidden" name="operation_key" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01">
                <label for="cash-kind">Movement</label><select id="cash-kind" name="kind"><option value="manual_in">Cash In</option><option value="manual_out">Cash Out</option></select>
                <label for="cash-amount">Amount</label><input id="cash-amount" name="amount" type="number" min="0.01" max="999999999" step="0.01" required>
                <label for="cash-reason">Reason</label><textarea id="cash-reason" name="reason" class="input-lg bordered" rows="2" maxlength="255" required></textarea>
                <p class="shift-form-error" role="alert" hidden></p><button class="btn" type="submit">Record cash movement</button>
            </form>
        </section>
        <section class="card"><h2>Drawer Transfer</h2><p class="muted">Cash leaves your drawer now. The receiving cashier must confirm receipt.</p>
            @if ($destinations->isEmpty())<p>No other open cashier drawer. Ask the recipient to open their shift.</p>@else
            <form method="POST" action="{{ route('cash-drawer.transfers.store') }}" data-shift-action="cash_transfer">
                @csrf<input type="hidden" name="operation_key" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01">
                <label for="transfer-destination">Receiving drawer</label><select id="transfer-destination" name="destination_drawer_id" required>@foreach ($destinations as $d)<option value="{{ $d->drawer_id }}">{{ $d->employee?->displayName() }} · Shift #{{ $d->drawer_id }}</option>@endforeach</select>
                <label for="transfer-amount">Amount</label><input id="transfer-amount" name="amount" type="number" min="0.01" max="{{ max(0,(float)$drawer->expected_cash) }}" step="0.01" required>
                <label for="transfer-reason">Reason</label><input id="transfer-reason" name="reason" maxlength="255" required>
                <p class="shift-form-error" role="alert" hidden></p><button class="btn" type="submit">Send cash transfer</button>
            </form>@endif
        </section>
    </div>
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
@endsection
@push('scripts')<script src="{{ asset('js/shift-actions.js') }}" defer></script>@endpush
