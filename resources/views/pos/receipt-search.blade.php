@extends('layouts.app')
@section('title','Find receipt')
@push('styles')<link rel="stylesheet" href="{{ asset('css/shift.css') }}">@endpush
@section('content')
<div class="pos-reference">
<header class="reference-header"><h1>Find receipt</h1><a class="reference-close" href="{{ route('pos.index') }}">Close (Esc)</a></header>
@include('cash-drawers.errors')
<div class="receipt-lookup-grid">
    <section aria-label="Receipt search">
    <form method="GET" action="{{ route('pos.receipts.search') }}">
        <label for="receipt-lookup">Receipt number or transaction ID</label>
        <div class="shift-actions"><input id="receipt-lookup" name="q" type="search" maxlength="100" value="{{ $query }}" required autofocus autocomplete="off"><button type="submit" class="btn">Find receipt</button></div>
    </form>
    <p class="shift-note">Enter the number printed on the original receipt to find a sale for return or exchange.</p>
    @if ($query !== '' && $sale)
        <a class="reference-found-receipt" href="{{ route('pos.receipts.search', ['q' => $query]) }}" aria-current="true">
            <div><strong>{{ $sale->receipt?->receipt_number ?? 'Transaction #'.$sale->transaction_id }}</strong><span>{{ $sale->transaction_date?->timezone('Asia/Manila')->format('g:i A') }} · {{ ucfirst($sale->payment_method) }}</span></div>
            <strong>₱{{ number_format($sale->total_amount,2) }}</strong>
        </a>
    @endif
    </section>
    <section class="receipt-lookup-preview" aria-label="Receipt summary" aria-live="polite">
    @if ($query!=='')
        @if ($sale)
            <div class="receipt-lookup-paper">
                <h2>{{ $sale->receipt?->receipt_number ?? 'Transaction #'.$sale->transaction_id }}</h2>
                <p>{{ $sale->transaction_date?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                <p>Cashier: {{ $sale->employee?->displayName() }}</p>
                <p>{{ ucfirst($sale->payment_method) }} · {{ ucfirst($sale->status) }}</p>
                <div class="reference-receipt-lines" aria-label="Purchased items">
                    @foreach ($sale->saleDetails as $line)
                        <div><span>{{ $line->product?->product_name ?? 'Item' }}<small>{{ rtrim(rtrim(number_format((float) $line->quantity, 3), '0'), '.') }} × {{ number_format((float) $line->unit_price, 2) }}</small></span><strong>{{ number_format((float) $line->subtotal, 2) }}</strong></div>
                    @endforeach
                </div>
                @php
                    $receiptDiscount = $sale->discountBreakdown();
                    $receiptTax = $sale->taxBreakdown();
                @endphp
                @if ($receiptDiscount || ($receiptTax['vat_exemption_amount'] ?? 0) > 0)
                    <div class="reference-receipt-adjustment"><span>Subtotal</span><strong>₱{{ number_format($sale->subtotal, 2) }}</strong></div>
                    @if (($receiptTax['vat_exemption_amount'] ?? 0) > 0)
                        <div class="reference-receipt-adjustment"><span>VAT removed</span><strong>−₱{{ number_format($receiptTax['vat_exemption_amount'], 2) }}</strong></div>
                    @endif
                    @if ($receiptDiscount)
                        <div class="reference-receipt-adjustment"><span>{{ $receiptDiscount['name'] ?? 'Discount' }}</span><strong>−₱{{ number_format($receiptDiscount['amount'] ?? 0, 2) }}</strong></div>
                    @endif
                @endif
                <div class="receipt-lookup-total"><span>Total</span><strong>₱{{ number_format($sale->total_amount,2) }}</strong></div>
            </div>
            <div class="reference-bottom-actions"><a class="btn" href="{{ route('pos.reprint',$sale) }}">Reprint</a><a class="btn btn-secondary" href="{{ route('pos.show',$sale) }}">Open receipt for return / exchange</a></div>
        @else
            <p class="receipt-lookup-empty" role="status">No receipt found. Check the number printed on the original receipt.</p>
        @endif
    @else
        <p class="receipt-lookup-empty">Find a receipt to see its details here.</p>
    @endif
    </section>
</div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos-reference.css') }}">
@endpush
