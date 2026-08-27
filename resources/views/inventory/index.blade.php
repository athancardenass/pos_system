@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div class="page-head">
        <h1>Inventory</h1>
        <form method="POST" action="{{ route('inventory.sync') }}">
            @csrf
            <button class="btn btn-secondary" type="submit">Sync missing rows</button>
        </form>
    </div>
    <div class="card">
        @if ($items->isEmpty())
            <p class="empty">No inventory records. Create products first, then sync.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Reorder at</th>
                        <th>Last restocked</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td style="font-weight: 700;">{{ $item->product->product_name ?? 'Unknown' }}</td>
                            <td class="{{ optional($item->product)->reorder_level !== null && $item->stock_quantity <= $item->product->reorder_level ? 'warn' : '' }}">
                                {{ $item->stock_quantity }}
                            </td>
                            <td>{{ $item->product->reorder_level ?? '—' }}</td>
                            <td class="muted">{{ optional($item->last_restocked)->format('Y-m-d H:i') ?: '—' }}</td>
                            <td><a class="btn-ghost" href="{{ route('inventory.edit', $item) }}">Adjust</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $items->links('partials.pagination') }}
        @endif
    </div>
@endsection
