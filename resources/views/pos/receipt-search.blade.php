@extends('layouts.app')
@section('title','Find receipt')
@push('styles')<link rel="stylesheet" href="{{ asset('css/shift.css') }}">@endpush
@section('content')
<div class="page-head"><h1>Find receipt</h1><a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a></div>
@include('cash-drawers.errors')
<div class="card">
    <form method="GET" action="{{ route('pos.receipts.search') }}">
        <label for="receipt-lookup">Receipt number or transaction ID</label>
        <div class="shift-actions"><input id="receipt-lookup" name="q" type="search" maxlength="100" value="{{ $query }}" required autofocus autocomplete="off"><button type="submit" class="btn">Find receipt</button></div>
    </form>
    @if ($query!=='')
        @if ($sale)
            <h2>{{ $sale->receipt?->receipt_number ?? 'Transaction #'.$sale->transaction_id }}</h2>
            <p>{{ $sale->transaction_date?->timezone('Asia/Manila')->format('M j, Y g:i A') }} · {{ $sale->employee?->displayName() }} · {{ ucfirst($sale->payment_method) }} · ₱{{ number_format($sale->total_amount,2) }} · {{ ucfirst($sale->status) }}</p>
            <a class="btn" href="{{ route('pos.show',$sale) }}">Open receipt for return / exchange</a>
        @else
            <p role="status">No receipt found. Check the number printed on the original receipt.</p>
        @endif
    @endif
</div>
@endsection
