@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<style>
    /* ===== POS Screen — Modern Layout ===== */
    .pos-shell {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 1.5rem;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .pos-shell { grid-template-columns: 1fr; }
    }

    /* Header bar */
    .pos-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--rule);
        flex-wrap: wrap;
    }
    .pos-header-brand { display: flex; align-items: center; gap: 0.75rem; }
    .pos-header-brand h1 { font-size: 1.25rem; font-weight: 800; letter-spacing: -0.01em; }
    .pos-header-register { color: var(--muted); font-size: 0.85rem; }
    .pos-header-meta {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.82rem; font-weight: 600; color: var(--muted);
    }
    .pos-status-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--success);
        box-shadow: 0 0 0 3px rgba(45,138,78,0.2);
    }
    .pos-avatar {
        width: 32px; height: 32px; border-radius: 50%;
        background: var(--accent); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem;
    }
    .pos-status-active { color: var(--success); font-weight: 700; }

    /* Cards */
    .pos-card {
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    .pos-card-title {
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: var(--muted); margin-bottom: 0.75rem;
    }

    /* Receipt table */
    .pos-receipt { width: 100%; border-collapse: separate; border-spacing: 0; }
    .pos-receipt th {
        font-size: 0.68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--muted); padding: 0.5rem 0.6rem;
        border-bottom: 2px solid var(--rule);
        text-align: left;
    }
    .pos-receipt td {
        padding: 0.6rem;
        border-bottom: 1px solid rgba(32,60,61,0.1);
        font-size: 0.88rem;
    }
    .pos-receipt tr:last-child td { border-bottom: none; }
    .pos-receipt .num { text-align: right; font-variant-numeric: tabular-nums; }
    .pos-receipt .remove-btn { color: var(--danger); }
    .pos-receipt .remove-btn:hover { opacity: 0.7; }

    /* Totals */
    .pos-totals { margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--rule); }
    .pos-total-row {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 0.4rem; font-size: 0.88rem;
    }
    .pos-total-row.discount { color: var(--success); }
    .pos-total-row.grand {
        font-size: 1.35rem; font-weight: 800;
        margin-top: 0.5rem; padding-top: 0.5rem;
        border-top: 2px dashed var(--rule);
    }

    /* Search */
    .pos-search-wrap { position: relative; }
    .pos-search-input {
        width: 100%;
        padding: 0.85rem 1rem 0.85rem 2.75rem;
        background: var(--surface); border: 2px solid var(--rule);
        border-radius: 8px; color: var(--text);
        font-family: inherit; font-size: 0.95rem; line-height: 1.4;
        outline: none; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .pos-search-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(196,80,74,0.15);
    }
    .pos-search-icon {
        position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
        color: var(--muted); pointer-events: none;
    }
    .pos-search-results {
        margin-top: 0.75rem;
        max-height: 320px; overflow-y: auto;
        border: 1px solid rgba(32,60,61,0.1);
        border-radius: 8px;
    }
    .pos-search-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.7rem 0.85rem;
        border-bottom: 1px solid rgba(32,60,61,0.08);
        transition: background 0.12s;
    }
    .pos-search-item:last-child { border-bottom: none; }
    .pos-search-item:hover { background: rgba(32,60,61,0.03); }
    .pos-search-item-info { flex: 1; min-width: 0; }
    .pos-search-item-name { font-weight: 600; font-size: 0.88rem; }
    .pos-search-item-meta { font-size: 0.72rem; color: var(--muted); margin-top: 0.15rem; }
    .pos-search-item-price { font-weight: 700; font-size: 0.88rem; margin-right: 0.75rem; }
    .pos-search-item .btn { padding: 0.4rem 0.8rem; font-size: 0.78rem; }

    /* Payment methods */
    .pos-pay-methods { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
    .pos-pay-btn {
        flex: 1; padding: 0.7rem;
        background: var(--surface); border: 2px solid var(--rule);
        border-radius: 8px; cursor: pointer;
        font-family: inherit; font-size: 0.82rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.04em;
        color: var(--text); transition: all 0.15s;
    }
    .pos-pay-btn:hover { border-color: var(--accent); }
    .pos-pay-btn.active { background: var(--text); color: #fff; border-color: var(--text); }

    /* Cash input */
    .pos-cash-block { margin-bottom: 1rem; }
    .pos-cash-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
    .pos-cash-label { font-size: 0.78rem; font-weight: 600; color: var(--muted); min-width: 70px; }
    .pos-cash-input {
        flex: 1; padding: 0.7rem 1rem;
        background: var(--surface); border: 2px solid var(--rule);
        border-radius: 8px; font-family: inherit; font-size: 1rem;
        font-weight: 700; outline: none; text-align: right;
    }
    .pos-change { font-size: 1.1rem; font-weight: 800; color: var(--success); }

    /* Complete button */
    .pos-complete-btn {
        width: 100%; padding: 0.9rem;
        background: var(--text); color: #fff; border: none;
        border-radius: 8px; cursor: pointer;
        font-family: inherit; font-size: 0.95rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.04em;
        transition: opacity 0.15s;
    }
    .pos-complete-btn:hover { opacity: 0.88; }
    .pos-complete-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    /* Empty state */
    .pos-empty { text-align: center; padding: 2rem 1rem; color: var(--muted); font-size: 0.85rem; }

    /* Customer field */
    .pos-customer-bar { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; }
    .pos-customer-bar > div { flex: 1; min-width: 180px; }
    .pos-customer-bar > div.fixed { flex: 0; }
    .pos-customer-input {
        width: 100%; padding: 0.85rem 1rem;
        background: var(--surface); border: 2px solid var(--rule);
        border-radius: 8px; font-family: inherit; font-size: 0.95rem; outline: none;
    }
    .pos-walkin-badge {
        padding: 0.85rem 1rem; background: rgba(32,60,61,0.05);
        border-radius: 8px; font-weight: 600; font-size: 0.88rem; color: var(--muted);
    }
    .pos-label-spacer { visibility: hidden; }

    /* Qty steppers in receipt */
    .pos-qty-wrap { display: inline-flex; align-items: center; gap: 0.35rem; }
    .pos-qty-val { min-width: 1.4rem; text-align: center; font-weight: 700; }
</style>
@endpush

@section('content')
@php
    $employee = auth()->user()->employee;
    $cashierName = $employee ? $employee->fullName() : auth()->user()->username;
    $initials = strtoupper(substr($cashierName, 0, 1));
@endphp

<div class="pos-screen">
    {{-- Header --}}
    <div class="pos-header">
        <div class="pos-header-brand">
            <h1>POS SYSTEM</h1>
            <span class="pos-header-register">Register 01</span>
        </div>
        <div class="pos-header-meta">
            <span class="pos-avatar">{{ $initials }}</span>
            <span>{{ $cashierName }}</span>
            <span class="pos-status-dot"></span>
            <span class="pos-status-active">ACTIVE</span>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="pos-card pos-empty">No products available. A manager needs to add products first.</div>
    @else
        <div class="pos-shell">
            {{-- LEFT: Receipt + Customer --}}
            <div>
                {{-- Customer --}}
                <div class="pos-card">
                    <div class="pos-card-title">Customer</div>
                    <div class="pos-customer-bar">
                        <div>
                            <label for="customer_id">Customer ID (optional)</label>
                            <input id="customer_id" name="customer_id" type="text" placeholder="Enter loyalty / customer ID" class="pos-customer-input">
                        </div>
                        <div class="fixed">
                            <label class="pos-label-spacer">&nbsp;</label>
                            <div class="pos-walkin-badge">Walk-in Customer</div>
                        </div>
                    </div>
                </div>

                {{-- Receipt --}}
                <div class="pos-card">
                    <div class="pos-card-title">Receipt</div>
                    <table class="pos-receipt">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="num">Qty</th>
                                <th class="num">Price</th>
                                <th class="num">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart"></tbody>
                    </table>
                    <div id="cart-empty" class="pos-empty">No items yet. Search or scan a product.</div>

                    <div class="pos-totals">
                        <div class="pos-total-row">
                            <span>Subtotal</span>
                            <span id="subtotal" class="num">₱0.00</span>
                        </div>
                        <div class="pos-total-row discount">
                            <span>Discount</span>
                            <span id="discount" class="num">-₱0.00</span>
                        </div>
                        <div class="pos-total-row grand">
                            <span>TOTAL</span>
                            <span id="total" class="num">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Search + Payment --}}
            <div>
                {{-- Search --}}
                <div class="pos-card">
                    <div class="pos-card-title">Search Product</div>
                    <div class="pos-search-wrap">
                        <svg class="pos-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input id="pos-search" class="pos-search-input" type="text" placeholder="Search by name or barcode..." autocomplete="off">
                    </div>
                    <div class="pos-search-results" id="search-results"></div>
                </div>

                {{-- Payment --}}
                <div class="pos-card">
                    <div class="pos-card-title">Payment</div>
                    <div class="pos-pay-methods">
                        <button type="button" class="pos-pay-btn active" data-method="cash">Cash</button>
                        <button type="button" class="pos-pay-btn" data-method="card">Card</button>
                        <button type="button" class="pos-pay-btn" data-method="e-wallet">E-Wallet</button>
                    </div>

                    <div class="pos-cash-block" id="cash-block">
                        <div class="pos-cash-row">
                            <span class="pos-cash-label">Received</span>
                            <input id="amount_paid" type="number" min="0" step="0.01" value="0" class="pos-cash-input">
                        </div>
                        <div class="pos-cash-row">
                            <span class="pos-cash-label">Change</span>
                            <span class="pos-change" id="change">₱0.00</span>
                        </div>
                    </div>

                    <button type="button" class="pos-complete-btn" id="complete-sale" disabled>Complete Sale</button>
                </div>
            </div>
        </div>
    @endif
</div>

<form id="pos-form" method="POST" action="{{ route('pos.store') }}" style="display:none;">
    @csrf
    <input type="hidden" name="customer_id" id="form-customer-id">
    <input type="hidden" name="payment_method" id="form-payment-method" value="cash">
    <input type="hidden" name="amount_paid" id="form-amount-paid">
    <input type="hidden" name="coupon_code" id="form-coupon-code">
    <span id="form-items"></span>
</form>
@endsection

@push('scripts')
<script>
    const products = @json($productsJson);
    const cart = [];
    const cartEl = document.getElementById('cart');
    const cartEmpty = document.getElementById('cart-empty');
    const searchInput = document.getElementById('pos-search');
    const searchResults = document.getElementById('search-results');
    const completeBtn = document.getElementById('complete-sale');
    const amountPaidEl = document.getElementById('amount_paid');
    const cashBlock = document.getElementById('cash-block');

    let selectedPayment = 'cash';

    function money(n) { return '₱' + Number(n || 0).toFixed(2); }
    function parseMoney(str) { return parseFloat((str || '0').replace(/[₱,]/g, '')) || 0; }
    function getDiscount(subtotal) { return 0; }

    function render() {
        cartEl.innerHTML = '';
        if (cart.length === 0) {
            cartEmpty.style.display = 'block';
            completeBtn.disabled = true;
        } else {
            cartEmpty.style.display = 'none';
            completeBtn.disabled = false;
        }

        let subtotal = 0;
        cart.forEach((line, i) => {
            const lineTotal = line.price * line.qty;
            subtotal += lineTotal;
            const tr = document.createElement('tr');

            const tdName = document.createElement('td');
            tdName.textContent = line.name;
            const hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = `items[${i}][product_id]`;
            hiddenId.value = line.id;
            tdName.appendChild(hiddenId);

            const tdQty = document.createElement('td');
            tdQty.className = 'num';
            const qtyWrap = document.createElement('div');
            qtyWrap.className = 'pos-qty-wrap';
            const btnDec = document.createElement('button');
            btnDec.type = 'button'; btnDec.className = 'qty-btn'; btnDec.dataset.act = 'dec'; btnDec.dataset.i = i; btnDec.textContent = '-';
            const qtySpan = document.createElement('span');
            qtySpan.className = 'pos-qty-val';
            qtySpan.textContent = line.qty;
            const btnInc = document.createElement('button');
            btnInc.type = 'button'; btnInc.className = 'qty-btn'; btnInc.dataset.act = 'inc'; btnInc.dataset.i = i; btnInc.textContent = '+';
            const hiddenQty = document.createElement('input');
            hiddenQty.type = 'hidden'; hiddenQty.name = `items[${i}][quantity]`; hiddenQty.value = line.qty;
            qtyWrap.append(btnDec, qtySpan, btnInc, hiddenQty);
            tdQty.appendChild(qtyWrap);

            const tdPrice = document.createElement('td');
            tdPrice.className = 'num'; tdPrice.textContent = money(line.price);
            const tdLine = document.createElement('td');
            tdLine.className = 'num'; tdLine.textContent = money(lineTotal);
            const tdRm = document.createElement('td');
            const rmBtn = document.createElement('button');
            rmBtn.className = 'btn-ghost remove-btn'; rmBtn.type = 'button'; rmBtn.dataset.i = i;
            rmBtn.textContent = 'x';
            tdRm.appendChild(rmBtn);

            tr.append(tdName, tdQty, tdPrice, tdLine, tdRm);
            cartEl.appendChild(tr);
        });

        const discount = getDiscount(subtotal);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('discount').textContent = '-' + money(discount);
        document.getElementById('total').textContent = money(total);

        const paid = parseMoney(amountPaidEl.value);
        const change = Math.max(0, paid - total);
        document.getElementById('change').textContent = money(change);

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
            btn.addEventListener('click', () => { cart.splice(Number(btn.dataset.i), 1); render(); });
        });
    }

    function addToCart(productId, qty) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        qty = parseInt(qty, 10) || 1;
        if (product.stock <= 0) { alert(product.name + ' is out of stock.'); return; }
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

    let searchTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const val = this.value.trim().toLowerCase();
        if (!val) { searchResults.innerHTML = ''; return; }
        searchTimer = setTimeout(() => {
            const matches = products.filter(p =>
                p.name.toLowerCase().includes(val) ||
                (p.barcode && p.barcode.includes(val))
            ).slice(0, 8);
            searchResults.innerHTML = '';
            if (matches.length === 0) {
                searchResults.innerHTML = '<div class="pos-empty">No products found.</div>';
                return;
            }
            matches.forEach(p => {
                const item = document.createElement('div');
                item.className = 'pos-search-item';
                const info = document.createElement('div');
                info.className = 'pos-search-item-info';
                const name = document.createElement('div');
                name.className = 'pos-search-item-name';
                name.textContent = p.name;
                const meta = document.createElement('div');
                meta.className = 'pos-search-item-meta';
                meta.textContent = (p.barcode ? 'Barcode: ' + p.barcode : '') + (p.stock <= 0 ? '  •  OUT OF STOCK' : '');
                info.append(name, meta);
                const price = document.createElement('span');
                price.className = 'pos-search-item-price';
                price.textContent = money(p.price);
                const addBtn = document.createElement('button');
                addBtn.className = 'btn btn-secondary';
                addBtn.textContent = 'Add';
                addBtn.disabled = p.stock <= 0;
                addBtn.addEventListener('click', () => { addToCart(p.id, 1); searchInput.value = ''; searchResults.innerHTML = ''; });
                item.append(info, price, addBtn);
                searchResults.appendChild(item);
            });
        }, 150);
    });

    document.querySelectorAll('.pos-pay-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pos-pay-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedPayment = btn.dataset.method;
            cashBlock.style.display = selectedPayment === 'cash' ? 'block' : 'none';
            document.getElementById('form-payment-method').value = selectedPayment;
        });
    });

    amountPaidEl.addEventListener('input', render);

    completeBtn.addEventListener('click', () => {
        if (cart.length === 0) { alert('Add at least one item.'); return; }
        const total = parseMoney(document.getElementById('total').textContent);
        const paid = parseMoney(amountPaidEl.value);
        if (selectedPayment === 'cash' && paid < total - 0.001) {
            alert('Amount received (' + money(paid) + ') is less than total (' + money(total) + ').');
            return;
        }
        document.getElementById('form-customer-id').value = document.getElementById('customer_id').value;
        document.getElementById('form-amount-paid').value = paid;
        const formItems = document.getElementById('form-items');
        formItems.innerHTML = '';
        cart.forEach((line, i) => {
            const inpId = document.createElement('input');
            inpId.type = 'hidden'; inpId.name = `items[${i}][product_id]`; inpId.value = line.id;
            const inpQty = document.createElement('input');
            inpQty.type = 'hidden'; inpQty.name = `items[${i}][quantity]`; inpQty.value = line.qty;
            formItems.append(inpId, inpQty);
        });
        document.getElementById('pos-form').submit();
    });

    render();
</script>
@endpush
