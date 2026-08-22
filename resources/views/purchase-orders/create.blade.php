@extends('layouts.app')

@section('title', 'New purchase order')

@section('content')
    <div class="page-head">
        <h1>New purchase order</h1>
        <a class="btn btn-secondary" href="{{ route('purchase-orders.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('purchase-orders.store') }}">
            @csrf
            <div class="form-grid">
                <div>
                    <label for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="order_date">Order date</label>
                    <input id="order_date" type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required>
                </div>
            </div>
            <h2>Line items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit cost</th>
                    </tr>
                </thead>
                <tbody id="lines">
                    <tr>
                        <td>
                            <select name="items[0][product_id]" required>
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" min="1" name="items[0][quantity]" value="1" required></td>
                        <td><input type="number" min="0" step="0.01" name="items[0][unit_cost]" value="0" required></td>
                    </tr>
                </tbody>
            </table>
            <p><button class="btn btn-secondary" type="button" id="add-line">Add line</button></p>
            <button type="submit">Create order</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const products = @json($products->map(fn ($p) => ['id' => $p->product_id, 'name' => $p->product_name, 'cost' => $p->cost_price]));
    let index = 1;
    document.getElementById('add-line').addEventListener('click', () => {
        const row = document.createElement('tr');
        const options = products.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        row.innerHTML = `
            <td><select name="items[${index}][product_id]" required><option value="">Select product</option>${options}</select></td>
            <td><input type="number" min="1" name="items[${index}][quantity]" value="1" required></td>
            <td><input type="number" min="0" step="0.01" name="items[${index}][unit_cost]" value="0" required></td>
        `;
        document.getElementById('lines').appendChild(row);
        index++;
    });
</script>
@endpush
