@extends('layouts.app')

@section('title', 'Adjust stock')

@section('content')
    <div class="page-head form-page-head">
        <h1>Adjust stock</h1>
        <a class="btn btn-secondary" href="{{ route('inventory.index') }}">Back</a>
    </div>
    <div class="card form-page-card">
        <p class="form-page-record">{{ $inventory->product->product_name ?? 'Product' }}</p>
        @include('partials.errors')
        <form method="POST" action="{{ route('inventory.update', $inventory) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div>
                    <label for="stock_quantity">Stock quantity</label>
                    <input class="input-lg bordered" id="stock_quantity" type="number" min="0" step="0.001" name="stock_quantity" value="{{ old('stock_quantity', $inventory->stock_quantity) }}" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Save stock adjustment</button>
            </div>
        </form>
    </div>
@endsection
