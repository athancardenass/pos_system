@extends('layouts.app')

@section('title', 'Point of sale')

@section('content')
    <div class="page-head">
        <h1>Point of sale</h1>
    </div>
    <div class="card">
        @include('partials.errors')
        @if ($products->isEmpty())
            <p class="empty">No products available. A manager needs to add products first.</p>
        @else
            <form method="POST" action="{{ route('pos.store') }}" id="pos-form">
                @csrf
                <div class="form-grid">
                    <div>
                        <label for="customer_id">Customer (optional)</label>
                        <select id="customer_id" name="customer_id">
                            <option value="">Walk-in</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->customer_id }}">{{ $customer->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="discount_id">Discount</label>
                        <select id="discount_id" name="discount_id">
                            <option value="" data-type="" data-value="0">None</option>
                            @foreach ($discounts as $discount)
                                <option value="{{ $discount->discount_id }}" data-type="{{ $discount->discount_type }}" data-value="{{ $discount->discount_value }}">
                                    {{ $discount->discount_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="payment_method">Payment method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="e-wallet">E-wallet</option>
                        </select>
                    </div>
                    <div>
                        <label for="amount_paid">Amount paid</label>
                        <input id="amount_paid" type="number" min="0" step="0.01" name="amount_paid" value="0" required>
                    </div>
                </div>

                <p class="muted">Add products to the cart. Stock shown in parentheses.</p>
                <div class="form-grid">
                    <div>
                        <label for="product_pick">Product</label>
                        <select id="product_pick">
                            @foreach ($products as $product)
                                <option value="{{ $product->product_id }}" data-price="{{ $product->unit_price }}" data-stock="{{ $product->stockQuantity() }}" data-name="{{ $product->product_name }}">
                                    {{ $product->product_name }} ({{ $product->stockQuantity() }}) — {{ number_format($product->unit_price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="qty_pick">Qty</label>
                        <input id="qty_pick" type="number" min="1" value="1">
                    </div>
                </div>
                <p><button class="btn btn-secondary" type="button" id="add-item">Add to cart</button></p>

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cart"></tbody>
                </table>
                <p>Subtotal: <span id="subtotal">0.00</span></p>
                <p>Total: <strong id="total">0.00</strong></p>
                <p>Change: <span id="change">0.00</span></p>
                <button type="submit">Complete sale</button>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    const cart = [];
    const cartEl = document.getElementById('cart');
    if (!cartEl) {} else {
        function money(n) { return Number(n).toFixed(2); }
        function discountTotal(subtotal) {
            const opt = document.getElementById('discount_id').selectedOptions[0];
            const type = opt.dataset.type;
            const value = Number(opt.dataset.value || 0);
            if (type === 'percentage') return Math.max(0, subtotal - (subtotal * value / 100));
            if (type === 'fixed') return Math.max(0, subtotal - value);
            return subtotal;
        }
        function render() {
            cartEl.innerHTML = '';
            let subtotal = 0;
            cart.forEach((line, i) => {
                const lineTotal = line.price * line.qty;
                subtotal += lineTotal;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${line.name}<input type="hidden" name="items[${i}][product_id]" value="${line.id}"></td>
                    <td>${line.qty}<input type="hidden" name="items[${i}][quantity]" value="${line.qty}"></td>
                    <td>${money(line.price)}</td>
                    <td>${money(lineTotal)}</td>
                    <td><button class="btn-ghost btn-danger" type="button" data-i="${i}">Remove</button></td>
                `;
                cartEl.appendChild(tr);
            });
            const total = discountTotal(subtotal);
            document.getElementById('subtotal').textContent = money(subtotal);
            document.getElementById('total').textContent = money(total);
            const paid = Number(document.getElementById('amount_paid').value || 0);
            document.getElementById('change').textContent = money(Math.max(0, paid - total));
            cartEl.querySelectorAll('button[data-i]').forEach(btn => {
                btn.addEventListener('click', () => {
                    cart.splice(Number(btn.dataset.i), 1);
                    render();
                });
            });
        }
        document.getElementById('add-item').addEventListener('click', () => {
            const pick = document.getElementById('product_pick');
            const opt = pick.selectedOptions[0];
            const qty = Number(document.getElementById('qty_pick').value || 1);
            const stock = Number(opt.dataset.stock);
            const existing = cart.find(l => l.id === Number(opt.value));
            const nextQty = (existing ? existing.qty : 0) + qty;
            if (nextQty > stock) {
                alert('Not enough stock.');
                return;
            }
            if (existing) existing.qty = nextQty;
            else cart.push({ id: Number(opt.value), name: opt.dataset.name, price: Number(opt.dataset.price), qty });
            render();
        });
        document.getElementById('discount_id').addEventListener('change', render);
        document.getElementById('amount_paid').addEventListener('input', render);
        document.getElementById('pos-form').addEventListener('submit', (e) => {
            if (!cart.length) {
                e.preventDefault();
                alert('Add at least one item.');
            }
        });
    }
</script>
@endpush
