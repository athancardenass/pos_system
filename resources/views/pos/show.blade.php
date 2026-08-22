@extends('layouts.app')

@section('title', 'Receipt '.$sale->receipt?->receipt_number)

@section('content')
    <div class="page-head">
        <h1>Receipt {{ $sale->receipt->receipt_number ?? $sale->transaction_id }}</h1>
        <a class="btn btn-secondary" href="{{ route('pos.index') }}">New sale</a>
    </div>
    <div class="card">
        <p>Date: {{ optional($sale->transaction_date)->format('Y-m-d H:i') }}</p>
        <p>Cashier: {{ $sale->employee->username ?? '—' }}</p>
        <p>Customer: {{ $sale->customer?->fullName() ?? 'Walk-in' }}</p>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->saleDetails as $line)
                    <tr>
                        <td>{{ $line->product->product_name ?? '—' }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ number_format($line->unit_price, 2) }}</td>
                        <td>{{ number_format($line->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p>Subtotal: {{ number_format($sale->subtotal, 2) }}</p>
        @if ($sale->discount)
            <p>Discount: {{ $sale->discount->discount_name }}</p>
        @endif
        <p><strong>Total: {{ number_format($sale->total_amount, 2) }}</strong></p>
        <p>Paid ({{ $sale->payment_method }}): {{ number_format($sale->payment->amount_paid ?? 0, 2) }}</p>
        <p>Change: {{ number_format($sale->payment->change_amount ?? 0, 2) }}</p>
    </div>
@endsection
