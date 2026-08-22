@extends('layouts.app')

@section('title', 'Purchase orders')

@section('content')
    <div class="page-head">
        <h1>Purchase orders</h1>
        <a class="btn" href="{{ route('purchase-orders.create') }}">New order</a>
    </div>
    <div class="card">
        @if ($orders->isEmpty())
            <p class="empty">No purchase orders yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->purchase_id }}</a></td>
                            <td>{{ $order->supplier->supplier_name ?? '—' }}</td>
                            <td>{{ optional($order->order_date)->format('Y-m-d') }}</td>
                            <td><span class="badge">{{ $order->status }}</span></td>
                            <td>{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
