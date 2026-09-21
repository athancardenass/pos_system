@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<style>
    .pos-shell { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
    @media (max-width: 1024px) { .pos-shell { grid-template-columns: 1fr; } }

    .pos-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 2px solid var(--rule); flex-wrap: wrap; gap: 0.75rem; }
    .pos-header-left { display: flex; align-items: center; gap: 0.55rem; }
    .pos-header-icon { width: 28px; height: 28px; border-radius: 50%; background: var(--accent); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
    .pos-header-title { font-weight: 800; font-size: 1.05rem; letter-spacing: 0.02em; }
    .pos-header-mid { text-align: center; flex: 1; }
    .pos-header-register { font-size: 0.78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }
    .pos-header-right { text-align: right; }
    .pos-header-name { font-weight: 700; font-size: 0.92rem; }
    .pos-header-role { font-size: 0.72rem; color: var(--muted); display: flex; align-items: center; gap: 0.25rem; justify-content: flex-end; }
    .pos-status-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--success); box-shadow: 0 0 0 2px rgba(45,138,78,0.2); }

    .pos-card { background: var(--surface); border: 2px solid var(--rule); border-radius: 10px; padding: 1.1rem; margin-bottom: 0.85rem; }
    .pos-section-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 0.6rem; }

    .pos-select { width: 100%; padding: 0.75rem 1rem; background: var(--surface); border: 2px solid var(--rule); border-radius: 8px; color: var(--text); font-family: inherit; font-size: 0.9rem; outline: none; cursor: pointer; }
    .pos-customer-input { width: 100%; padding: 0.75rem 1rem; background: var(--surface); border: 2px solid var(--rule); border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; }
    label { display: block; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 0.3rem; }

    .pos-receipt-item { padding: 0.6rem 0; border-bottom: 1px solid rgba(32,60,61,0.1); }
    .pos-receipt-item:last-child { border-bottom: none; }
    .pos-item-name { font-weight: 600; font-size: 0.88rem; }
    .pos-item-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 0.25rem; font-size: 0.82rem; color: var(--muted); }
    .pos-item-price { font-weight: 700; color: var(--text); }
    .pos-item-qty { display: inline-flex; align-items: center; gap: 0.4rem; }
    .pos-qty-btn { width: 24px; height: 24px; padding: 0; background: var(--surface); color: var(--text); border: 2px solid var(--rule); border-radius: 4px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .pos-qty-val { min-width: 1.2rem; text-align: center; font-weight: 700; }
    .pos-item-remove { color: var(--danger); cursor: pointer; font-size: 0.78rem; }
    .pos-item-remove:hover { opacity: 0.7; }
    .pos-receipt-divider { border: none; border-top: 2px solid var(--rule); margin: 0.5rem 0; }
    .pos-item-count { font-size: 0.78rem; font-weight: 600; color: var(--muted); text-align: right; margin-top: 0.4rem; }

    .pos-search-wrap { position: relative; }
    .pos-search-input { width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; background: var(--surface); border: 2px solid var(--rule); border-radius: 8px; color: var(--text); font-family: inherit; font-size: 0.9rem; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .pos-search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(196,80,74,0.15); }
    .pos-search-icon { position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
    .pos-search-item { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0; border-bottom: 1px solid rgba(32,60,61,0.08); gap: 0.5rem; }
    .pos-search-item:last-child { border-bottom: none; }
    .pos-search-item-info { flex: 1; min-width: 0; }
    .pos-search-item-name { font-weight: 600; font-size: 0.85rem; }
    .pos-search-item-sku { font-size: 0.7rem; color: var(--muted); }
    .pos-search-item-price { font-weight: 700; font-size: 0.82rem; white-space: nowrap; }
    .pos-add-btn { padding: 0.35rem 0.7rem; font-size: 0.75rem; background: var(--text); color: #fff; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap; }
    .pos-add-btn:hover { opacity: 0.85; }
    .pos-add-btn:disabled { opacity: 0.35; cursor: not-allowed; }

    .pos-bottom { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
    @media (max-width: 1024px) { .pos-bottom { grid-template-columns: 1fr; } }
    .pos-totals-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; font-size: 0.88rem; }
    .pos-totals-row.discount { color: var(--success); }
    .pos-totals-grand { display: flex; justify-content: space-between; align-items: center; font-size: 1.3rem; font-weight: 800; margin-top: 0.5rem; }

    .pos-pay-toggle { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; }
    .pos-pay-btn { flex: 1; padding: 0.6rem; background: var(--surface); border: 2px solid var(--rule); border-radius: 6px; cursor: pointer; font-family: inherit; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: var(--text); transition: all 0.15s; }
    .pos-pay-btn:hover { border-color: var(--accent); }
    .pos-pay-btn.active { background: var(--text); color: #fff; border-color: var(--text); }

    .pos-cash-row { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem; }
    .pos-cash-label { font-size: 0.78rem; font-weight: 600; color: var(--muted); min-width: 65px; }
    .pos-cash-input { flex: 1; padding: 0.6rem 0.8rem; background: var(--surface); border: 2px solid var(--rule); border-radius: 6px; font-family: inherit; font-size: 0.95rem; font-weight: 700; outline: none; text-align: right; }
    .pos-change-val { font-size: 1rem; font-weight: 800; color: var(--success); text-align: right; }

    .pos-complete-btn { width: 100%; padding: 0.85rem; margin-top: 0.75rem; background: var(--text); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-family: inherit; font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; transition: opacity 0.15s; }
    .pos-complete-btn:hover { opacity: 0.88; }
    .pos-complete-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .pos-empty { text-align: center; padding: 1.5rem 1rem; color: var(--muted); font-size: 0.82rem; }
</style>
@endpush

@section('content')
@php
    $employee = auth()->user()->employee;
    $cashierName = $employee ? $employee->fullName() : auth()->user()->username;
    $initials = strtoupper(substr($cashierName, 0, 1));
@endphp

<div class="pos-screen">
    <div class="pos-header">
        <div class="pos-header-left">
            <span class="pos-header-icon">P</span>
            <span class="pos-header-title">POS</span>
        </div>
        <div class="pos-header-mid">
            <div class="pos-header-register">POS-01</div>
        </div>
        <div class="pos-header-right">
            <div class="pos-header-name">{{ $cashierName }}</div>
            <div class="pos-header-role">Cashier <span class="pos-status-dot"></span> Shift Active</div>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="pos-card pos-empty">No products available. A manager needs to add products first.</div>
    @else
        <div class="pos-card" id="drawer-bar">
            <div class="pos-section-label">Cash Drawer</div>
            <div id="drawer-info">Checking...</div>
            <div id="drawer-actions" style="margin-top:0.5rem;"></div>
        </div>

        <div class="pos-shell">
            <div>
                <div class="pos-card">
                    <div class="pos-section-label">Current Sale</div>
                    <div style="margin-bottom: 0.6rem;">
                        <select id="customer_id" class="pos-select">
                            <option value="">Walk-in Customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->customer_id }}">{{ $c->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 0.85rem;">
                        <label for="customer_id_field">Customer ID</label>
                        <input id="customer_id_field" type="text" placeholder="______________" class="pos-customer-input">
                    </div>
                    <hr class="pos-receipt-divider">
                    <div id="cart-items"></div>
                    <div id="cart-empty" class="pos-empty">No items yet</div>
                    <div class="pos-item-count" id="item-count" style="display:none;"></div>
                </div>
            </div>
            <div>
                <div class="pos-card">
                    <div class="pos-section-label">Find a product</div>
                    <div class="pos-search-wrap" style="margin-bottom: 0.85rem;">
                        <svg class="pos-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input id="pos-search" class="pos-search-input" type="text" placeholder="Search products..." autocomplete="off">
                    </div>
                    <div id="search-results"></div>
                </div>
            </div>
        </div>

        <div class="pos-bottom" style="margin-top: 1.25rem;">
            <div>
                <div class="pos-card">
                    <div class="pos-totals-row">
                        <span>Subtotal</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="pos-totals-row discount">
                        <span>Discount</span>
                        <span id="discount">₱0.00</span>
                    </div>
                    <hr class="pos-receipt-divider">
                    <div class="pos-totals-grand">
                        <span>TOTAL</span>
                        <span id="total">₱0.00</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="pos-card">
                    <div class="pos-pay-toggle">
                        <button type="button" class="pos-pay-btn active" data-method="cash">Cash</button>
                        <button type="button" class="pos-pay-btn" data-method="card">Card</button>
                        <button type="button" class="pos-pay-btn" data-method="e-wallet">Other</button>
                    </div>
                    <div class="pos-cash-block" id="cash-block">
                        <div class="pos-cash-row">
                            <span class="pos-cash-label">Cash</span>
                            <input id="amount_paid" type="number" min="0" step="0.01" value="0" class="pos-cash-input">
                        </div>
                        <div class="pos-cash-row">
                            <span class="pos-cash-label">Change</span>
                            <span class="pos-change-val" id="change">₱0.00</span>
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
    const cartItemsEl = document.getElementById('cart-items');
    const cartEmptyEl = document.getElementById('cart-empty');
    const itemCountEl = document.getElementById('item-count');
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
        cartItemsEl.innerHTML = '';
        if (cart.length === 0) {
            cartEmptyEl.style.display = 'block';
            itemCountEl.style.display = 'none';
            completeBtn.disabled = true;
        } else {
            cartEmptyEl.style.display = 'none';
            itemCountEl.style.display = 'block';
            itemCountEl.textContent = cart.length + ' item' + (cart.length > 1 ? 's' : '');
            completeBtn.disabled = false;
        }

        let subtotal = 0;
        cart.forEach((line, i) => {
            const lineTotal = line.price * line.qty;
            subtotal += lineTotal;
            const item = document.createElement('div');
            item.className = 'pos-receipt-item';

            const nameRow = document.createElement('div');
            nameRow.className = 'pos-item-name';
            nameRow.textContent = line.name;

            const metaRow = document.createElement('div');
            metaRow.className = 'pos-item-meta';

            const qtyWrap = document.createElement('div');
            qtyWrap.className = 'pos-item-qty';
            const btnDec = document.createElement('button');
            btnDec.type = 'button'; btnDec.className = 'pos-qty-btn'; btnDec.textContent = '-';
            btnDec.addEventListener('click', () => {
                line.qty -= 1;
                if (line.qty <= 0) cart.splice(i, 1);
                render();
            });
            const qtySpan = document.createElement('span');
            qtySpan.className = 'pos-qty-val';
            qtySpan.textContent = '₱' + money(line.price) + ' × ' + line.qty;
            const btnInc = document.createElement('button');
            btnInc.type = 'button'; btnInc.className = 'pos-qty-btn'; btnInc.textContent = '+';
            btnInc.addEventListener('click', () => {
                const p = products.find(p => p.id === line.id);
                if (line.qty + 1 > p.stock) { alert('Not enough stock.'); return; }
                line.qty += 1;
                render();
            });
            qtyWrap.append(btnDec, qtySpan, btnInc);

            const priceRemove = document.createElement('div');
            priceRemove.style.cssText = 'display:flex;align-items:center;gap:0.75rem;';
            const price = document.createElement('span');
            price.className = 'pos-item-price';
            price.textContent = money(lineTotal);
            const removeBtn = document.createElement('span');
            removeBtn.className = 'pos-item-remove';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', () => { cart.splice(i, 1); render(); });
            priceRemove.append(price, removeBtn);

            metaRow.append(qtyWrap, priceRemove);
            item.append(nameRow, metaRow);
            cartItemsEl.appendChild(item);
        });

        const discount = getDiscount(subtotal);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('discount').textContent = money(discount);
        document.getElementById('total').textContent = money(total);

        const paid = parseMoney(amountPaidEl.value);
        document.getElementById('change').textContent = money(Math.max(0, paid - total));
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
            alert('Not enough stock for ' + product.name);
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
                const sku = document.createElement('div');
                sku.className = 'pos-search-item-sku';
                sku.textContent = p.barcode ? 'SKU ' + p.barcode : '';
                info.append(name, sku);
                const price = document.createElement('span');
                price.className = 'pos-search-item-price';
                price.textContent = money(p.price);
                const addBtn = document.createElement('button');
                addBtn.className = 'pos-add-btn';
                addBtn.textContent = '+';
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
            alert('Amount received is less than total.');
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

@push('scripts')
<script>
    const drawerInfo = document.getElementById('drawer-info');
    const drawerActions = document.getElementById('drawer-actions');

    function fmt(n) { return '₱' + Number(n || 0).toFixed(2); }

    async function loadDrawer() {
        const res = await fetch('{{ route("cash-drawer.status") }}');
        const data = await res.json();
        if (data.has_open_drawer) {
            const d = data.drawer;
            drawerInfo.innerHTML = `Open &bull; Opening: ${fmt(d.opening_cash)} &bull; Expected: ${fmt(d.expected_cash)}`;
            drawerActions.innerHTML = `<button class="btn btn-secondary" onclick="closeDrawer()">Close Drawer</button>`;
        } else {
            drawerInfo.innerHTML = 'No open drawer';
            drawerActions.innerHTML = `<button class="btn btn-secondary" onclick="openDrawer()">Open Drawer</button>`;
        }
    }

    function openDrawer() {
        const amount = prompt('Opening cash amount:', '5000');
        if (amount === null) return;
        const val = parseFloat(amount);
        if (isNaN(val) || val < 0) { alert('Invalid amount'); return; }
        fetch('{{ route("cash-drawer.open") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ opening_cash: val }),
        }).then(r => r.json()).then(() => loadDrawer());
    }

    function closeDrawer() {
        const amount = prompt('Actual cash counted:', '0');
        if (amount === null) return;
        const val = parseFloat(amount);
        if (isNaN(val) || val < 0) { alert('Invalid amount'); return; }
        fetch('{{ route("cash-drawer.close") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ actual_cash: val }),
        }).then(r => r.json()).then(d => {
            alert(d.message);
            loadDrawer();
        });
    }

    loadDrawer();
</script>
@endpush
