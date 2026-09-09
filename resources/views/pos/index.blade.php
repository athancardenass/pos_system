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
                        <label for="coupon_code">Coupon code (optional)</label>
                        <input id="coupon_code" type="text" name="coupon_code" maxlength="40" autocomplete="off"
                            value="{{ old('coupon_code') }}" placeholder="Enter code">
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

                {{-- Barcode Search --}}
                <div style="margin-bottom: 1rem;">
                    <label for="barcode_search">Barcode / SKU Search</label>
                    <input id="barcode_search" type="text" placeholder="Scan or type barcode..." class="barcode-input" autofocus>
                    <div id="barcode-result" style="font-size: 0.82rem; margin-top: 0.25rem; min-height: 1.2em;"></div>
                </div>

                <p class="muted">Add products to the cart. Stock shown in parentheses.</p>
                <div class="form-grid">
                    <div>
                        <label for="product_pick">Product</label>
                        <select id="product_pick">
                            @foreach ($products as $product)
                                <option value="{{ $product->product_id }}" data-price="{{ $product->unit_price }}" data-stock="{{ $product->stockQuantity() }}" data-name="{{ $product->product_name }}" data-barcode="{{ $product->barcode }}">
                                    {{ $product->product_name }} ({{ $product->stockQuantity() }}) — ₱{{ number_format($product->unit_price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="qty_pick">Qty</label>
                        <input id="qty_pick" type="number" min="1" value="1">
                    </div>
                </div>
                <p style="margin-bottom: 1.25rem;"><button class="btn btn-secondary" type="button" id="add-item">Add to cart</button></p>

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
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--rule);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                        <span class="muted">Subtotal:</span>
                        <span id="subtotal">0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; font-size: 1.1rem; font-weight: 700;">
                        <span>Total:</span>
                        <span id="total">0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="muted">Change:</span>
                        <span id="change">0.00</span>
                    </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" class="btn btn-ghost" id="clear-cart">Clear</button>
                    <button type="submit">Complete sale</button>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    const products = @json($productsJson);
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

                // Build with DOM methods so product names can't inject HTML (XSS).
                const tdName = document.createElement('td');
                const nameText = document.createTextNode(line.name);
                tdName.appendChild(nameText);
                const hiddenId = document.createElement('input');
                hiddenId.type = 'hidden';
                hiddenId.name = `items[${i}][product_id]`;
                hiddenId.value = line.id;
                tdName.appendChild(hiddenId);

                const tdQty = document.createElement('td');
                const qtyWrap = document.createElement('div');
                qtyWrap.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem;';
                const btnDec = document.createElement('button');
                btnDec.type = 'button'; btnDec.className = 'qty-btn'; btnDec.dataset.act = 'dec'; btnDec.dataset.i = i;
                btnDec.setAttribute('aria-label', 'Decrease quantity'); btnDec.textContent = '−';
                const qtySpan = document.createElement('span');
                qtySpan.style.cssText = 'min-width: 1.5rem; text-align: center; font-weight: 700;';
                qtySpan.textContent = line.qty;
                const btnInc = document.createElement('button');
                btnInc.type = 'button'; btnInc.className = 'qty-btn'; btnInc.dataset.act = 'inc'; btnInc.dataset.i = i;
                btnInc.setAttribute('aria-label', 'Increase quantity'); btnInc.textContent = '+';
                const hiddenQty = document.createElement('input');
                hiddenQty.type = 'hidden';
                hiddenQty.name = `items[${i}][quantity]`;
                hiddenQty.value = line.qty;
                qtyWrap.append(btnDec, qtySpan, btnInc, hiddenQty);
                tdQty.appendChild(qtyWrap);

                const tdPrice = document.createElement('td');
                tdPrice.textContent = money(line.price);
                const tdLine = document.createElement('td');
                tdLine.textContent = money(lineTotal);
                const tdRm = document.createElement('td');
                const rmBtn = document.createElement('button');
                rmBtn.className = 'btn-ghost'; rmBtn.type = 'button'; rmBtn.dataset.i = i;
                rmBtn.style.cssText = 'color: var(--danger);';
                rmBtn.textContent = 'Remove';
                tdRm.appendChild(rmBtn);

                tr.append(tdName, tdQty, tdPrice, tdLine, tdRm);
                cartEl.appendChild(tr);
            });
            const total = discountTotal(subtotal);
            document.getElementById('subtotal').textContent = money(subtotal);
            document.getElementById('total').textContent = money(total);
            const paid = Number(document.getElementById('amount_paid').value || 0);
            document.getElementById('change').textContent = money(Math.max(0, paid - total));
            // Qty steppers
            cartEl.querySelectorAll('button.qty-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const idx = Number(btn.dataset.i);
                    const line = cart[idx];
                    if (!line) return;
                    if (btn.dataset.act === 'inc') {
                        const product = products.find(p => p.id === line.id);
                        if (line.qty + 1 > product.stock) { alert('Not enough stock for ' + product.name + '.'); return; }
                        line.qty += 1;
                    } else {
                        line.qty -= 1;
                        if (line.qty <= 0) cart.splice(idx, 1);
                    }
                    render();
                });
            });
            cartEl.querySelectorAll('button[data-i]:not(.qty-btn)').forEach(btn => {
                btn.addEventListener('click', () => {
                    cart.splice(Number(btn.dataset.i), 1);
                    render();
                });
            });
        }
        function addToCart(productId, qty) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            qty = parseInt(qty, 10) || 1;
            const existing = cart.find(l => l.id === productId);
            const currentQty = existing ? existing.qty : 0;
            const newQty = currentQty + qty;
            if (newQty > product.stock) {
                alert('Not enough stock for ' + product.name + ' (available: ' + product.stock + ', in cart: ' + currentQty + ').');
                return;
            }
            if (existing) existing.qty = newQty;
            else cart.push({ id: product.id, name: product.name, price: product.price, qty: newQty });
            render();
        }
        // Add to cart button
        document.getElementById('add-item').addEventListener('click', () => {
            const pick = document.getElementById('product_pick');
            const opt = pick.selectedOptions[0];
            const qty = Number(document.getElementById('qty_pick').value || 1);
            addToCart(Number(opt.value), qty);
        });
        // Barcode search
        const barcodeInput = document.getElementById('barcode_search');
        const barcodeResult = document.getElementById('barcode-result');
        let barcodeTimer;
        barcodeInput.addEventListener('input', function() {
            clearTimeout(barcodeTimer);
            const val = this.value.trim();
            if (!val) { barcodeResult.textContent = ''; return; }
            barcodeTimer = setTimeout(() => {
                const match = products.find(p => p.barcode === val);
                if (match) {
                    // textContent — product names are DB/user data (XSS-safe).
                    barcodeResult.textContent = `${match.name} — ₱${money(match.price)} (Stock: ${match.stock})`;
                    addToCart(match.id, 1);
                    this.value = '';
                    barcodeResult.textContent = 'Added to cart!';
                    setTimeout(() => { barcodeResult.textContent = ''; }, 1500);
                } else {
                    barcodeResult.textContent = `No product found for "${val}"`;
                }
            }, 150);
        });
        // Enter key on barcode
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.dispatchEvent(new Event('input'));
            }
        });
        document.getElementById('discount_id').addEventListener('change', render);
        document.getElementById('amount_paid').addEventListener('input', render);
        document.getElementById('pos-form').addEventListener('submit', (e) => {
            if (!cart.length) {
                e.preventDefault();
                alert('Add at least one item.');
                return;
            }
            // A coupon code (and any auto-promotion) is priced server-side, and the
            // server is authoritative. The pre-submit guard below only understands the
            // manual discount picker, so with a code entered it would block a payment
            // that is actually sufficient — skip it and let PromotionService decide.
            const couponCode = (document.getElementById('coupon_code').value || '').trim();
            if (couponCode) return;
            const total = discountTotal(cart.reduce((s, l) => s + l.price * l.qty, 0));
            const paid = Number(document.getElementById('amount_paid').value || 0);
            if (paid < total - 0.001) {
                e.preventDefault();
                alert('Amount paid (₱' + money(paid) + ') is less than the total due (₱' + money(total) + ').');
            }
        });
        document.getElementById('clear-cart').addEventListener('click', () => {
            if (cart.length && !confirm('Remove all items from the cart?')) return;
            cart.length = 0;
            render();
        });
    }
</script>
@endpush
