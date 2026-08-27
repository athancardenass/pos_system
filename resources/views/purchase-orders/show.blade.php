@extends('layouts.app')

@section('title', 'Purchase order #'.$order->purchase_id)

@section('content')
    <div class="page-head">
        <h1>Purchase order #{{ $order->purchase_id }}</h1>
        <a class="btn btn-secondary" href="{{ route('purchase-orders.index') }}">Back</a>
    </div>
    <div class="card">
        <p>Supplier: {{ $order->supplier->supplier_name ?? '—' }}</p>
        <p>Ordered by: {{ $order->employee->username ?? '—' }} on {{ optional($order->order_date)->format('Y-m-d') }}</p>
        <p>Status: <span class="badge {{ $order->status === 'pending' ? 'badge-pending' : ($order->status === 'received' ? 'badge-active' : 'badge-inactive') }}">{{ $order->status }}</span></p>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit cost</th>
                    <th>Line total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->details as $line)
                    <tr>
                        <td>{{ $line->product->product_name ?? '—' }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ number_format($line->unit_cost, 2) }}</td>
                        <td>{{ number_format($line->quantity * $line->unit_cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Total: {{ number_format($order->total_amount, 2) }}</strong></p>
        @if ($order->isPending())
            <div class="actions">
                <form method="POST" action="{{ route('purchase-orders.receive', $order) }}">
                    @csrf
                    <button type="submit">Receive stock</button>
                </form>
                <form method="POST" action="{{ route('purchase-orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    <button class="btn btn-danger" type="submit">Cancel</button>
                </form>
            </div>
        @endif
    </div>
@endsection
