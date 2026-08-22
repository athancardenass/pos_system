@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="page-head">
        <h1>Products</h1>
        <a class="btn" href="{{ route('products.create') }}">New product</a>
    </div>
    <div class="card">
        @if ($products->isEmpty())
            <p class="empty">No products yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Barcode</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->product_name }}</td>
                            <td>{{ $product->barcode }}</td>
                            <td class="muted">{{ $product->category->category_name ?? '—' }}</td>
                            <td>{{ number_format($product->unit_price, 2) }}</td>
                            <td class="{{ $product->stockQuantity() <= $product->reorder_level ? 'warn' : '' }}">
                                {{ $product->stockQuantity() }}
                            </td>
                            <td class="actions">
                                <a href="{{ route('products.edit', $product) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
