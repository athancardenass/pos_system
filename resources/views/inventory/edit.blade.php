@extends('layouts.app')

@section('title', 'Adjust stock')

@section('content')
    <div class="page-head">
        <h1>Adjust stock</h1>
        <a class="btn btn-secondary" href="{{ route('inventory.index') }}">Back</a>
    </div>
    <div class="card">
        <p>{{ $inventory->product->product_name ?? 'Product' }}</p>
        @include('partials.errors')
        <form method="POST" action="{{ route('inventory.update', $inventory) }}">
            @csrf
            @method('PUT')
            <label for="stock_quantity">Stock quantity</label>
            <input id="stock_quantity" type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $inventory->stock_quantity) }}" required>
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
