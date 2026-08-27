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
                            <td style="font-weight: 700;">{{ $product->product_name }}</td>
                            <td><code style="font-size: 0.82rem; background: rgba(32,60,61,0.05); padding: 0.15rem 0.4rem;">{{ $product->barcode }}</code></td>
                            <td class="muted">{{ $product->category->category_name ?? '—' }}</td>
                            <td style="font-weight: 700;">₱{{ number_format($product->unit_price, 2) }}</td>
                            <td class="{{ $product->stockQuantity() <= $product->reorder_level ? 'warn' : '' }}">
                                {{ $product->stockQuantity() }}
                            </td>
                            <td class="actions">
                                <a class="btn-ghost" href="{{ route('products.edit', $product) }}">Edit</a>
                                <button type="button" class="btn-ghost print-barcode-btn" data-id="{{ $product->product_id }}" data-name="{{ $product->product_name }}" data-barcode="{{ $product->barcode }}" data-price="{{ number_format($product->unit_price, 2) }}">Print Label</button>
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
            {{ $products->links('partials.pagination') }}
        @endif
    </div>
@endsection

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        .barcode-label, .barcode-label * { visibility: visible; }
        .barcode-label { position: absolute; left: 0; top: 0; width: 58mm; padding: 3mm; border: 1px solid #000; font-family: monospace; }
        .barcode-label .bl-name { font-size: 7pt; font-weight: bold; margin-bottom: 1mm; }
        .barcode-label .bl-code { font-size: 6pt; color: #333; margin-bottom: 1mm; }
        .barcode-label .bl-price { font-size: 9pt; font-weight: bold; margin-top: 1mm; }
        .barcode-label svg { width: 100%; height: auto; }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
    document.querySelectorAll('.print-barcode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const name = this.dataset.name;
            const barcode = this.dataset.barcode;
            const price = this.dataset.price;
            const label = document.createElement('div');
            label.className = 'barcode-label';
            label.innerHTML = `<div class="bl-name">${name}</div><div class="bl-code">SKU: ${barcode}</div><svg class="bl-barcode"></svg><div class="bl-price">₱${price}</div>`;
            document.body.appendChild(label);
            JsBarcode(label.querySelector('.bl-barcode'), barcode, {
                format: 'EAN13',
                width: 1.5,
                height: 30,
                displayValue: false,
                margin: 0,
                valid: function(valid) {
                    if (!valid) {
                        label.querySelector('.bl-barcode').outerHTML = '<div class="bl-barcode" style="color:red;font-size:8pt;text-align:center;padding:2mm;">Invalid EAN-13<br>(' + barcode + ')</div>';
                        alert('⚠️ Barcode invalid — label printed with error notice');
                    }
                }
            });
            window.print();
            setTimeout(() => label.remove(), 100);
        });
    });
</script>
@endpush
