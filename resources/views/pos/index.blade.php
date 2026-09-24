@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<style>
    /* ===== Main POS Frame ===== */
    .pos-container {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* Top Bar */
    .pos-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r-lg);
        padding: 0.9rem 1.25rem;
        box-shadow: var(--shadow-card);
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .pos-top-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }
    .pos-brand-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: -0.01em;
        color: var(--text);
    }
    .pos-register-badge {
        background: var(--bg-tint);
        color: var(--text);
        padding: 0.25rem 0.65rem;
        border-radius: var(--r-pill);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border: 1px solid var(--rule-faint);
    }
    .pos-top-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .pos-cashier-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.88rem;
        font-weight: 600;
    }
    .pos-status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--success);
        background: var(--success-soft);
        padding: 0.25rem 0.6rem;
        border-radius: var(--r-pill);
    }
    .pos-status-dot-active {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--success);
    }
    .pos-status-dot-closed {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--muted);
    }

    /* POS 2-Column Grid (Main Work Area) */
    .pos-work-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr;
        gap: 1.25rem;
        align-items: stretch;
    }
    @media (max-width: 960px) {
        .pos-work-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .pos-panel {
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
    }
    .pos-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        margin-bottom: 0.75rem;
    }
    .pos-divider {
        border: none;
        border-top: 1px solid var(--rule-faint);
        margin: 1rem 0;
    }
    .pos-divider-dashed {
        border: none;
        border-top: 2px dashed var(--rule-faint);
        margin: 0.85rem 0;
    }

    /* Customer block */
    .pos-customer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    @media (max-width: 580px) {
        .pos-customer-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Receipt Table */
    .pos-table-wrap {
        flex: 1;
        overflow-y: auto;
        min-height: 220px;
        max-height: 380px;
    }
    .pos-cart-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .pos-cart-table th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--muted);
        padding: 0.5rem 0.6rem;
        border-bottom: 2px solid var(--rule);
        text-align: left;
        background: var(--surface);
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .pos-cart-table th.tar {
        text-align: right;
    }
    .pos-cart-table td {
        padding: 0.65rem 0.6rem;
        border-bottom: 1px solid var(--rule-faint);
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .pos-cart-table tr:hover td {
        background: var(--surface-soft);
    }
    .pos-cart-table td.tar {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .pos-cart-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--muted);
        font-size: 0.9rem;
    }
    .pos-items-count-badge {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--muted);
        text-align: right;
        margin-top: 0.6rem;
    }

    /* Quantity Controls */
    .pos-qty-group {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .pos-qty-btn {
        width: 26px;
        height: 26px;
        border-radius: var(--r-sm);
        border: 2px solid var(--rule);
        background: var(--surface);
        color: var(--text);
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s, color 0.15s;
    }
    .pos-qty-btn:hover {
        background: var(--text);
        color: #fff;
    }
    .pos-qty-text {
        min-width: 1.5rem;
        text-align: center;
        font-weight: 700;
    }
    .pos-remove-link {
        color: var(--danger);
        cursor: pointer;
        padding: 0.25rem 0.4rem;
        border-radius: var(--r-sm);
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        background: transparent;
    }
    .pos-remove-link:hover {
        background: var(--accent-soft);
    }

    /* Product Search */
    .pos-search-box {
        position: relative;
        margin-bottom: 0.85rem;
        display: block;
    }
    .pos-search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: var(--muted);
        pointer-events: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }
    input.pos-search-input,
    input#pos-search-input {
        width: 100%;
        height: 48px;
        line-height: 44px;
        padding: 0 1rem 0 3.1rem !important;
        margin-bottom: 0 !important;
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r);
        color: var(--text);
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
    }
    input.pos-search-input:focus,
    input#pos-search-input:focus {
        border-color: var(--accent);
        box-shadow: var(--ring);
    }

    /* Category Filter Chips */
    .pos-category-chips-bar {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        padding-bottom: 0.4rem;
        margin-bottom: 0.65rem;
        scrollbar-width: thin;
    }
    .pos-cat-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.65rem;
        background: var(--surface);
        border: 1.5px solid var(--rule-faint);
        border-radius: var(--r-pill);
        color: var(--muted);
        font-family: inherit;
        font-size: 0.74rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s var(--ease);
    }
    .pos-cat-chip:hover {
        color: var(--text);
        border-color: var(--rule);
        background: var(--bg-tint);
    }
    .pos-cat-chip.active {
        background: var(--text);
        color: #fff;
        border-color: var(--text);
    }

    /* Shortcuts helper bar */
    .pos-shortcuts-bar {
        display: flex;
        gap: 0.85rem;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 1rem;
        padding: 0.6rem 0.95rem;
        background: var(--surface);
        border: 1px solid var(--rule-faint);
        border-radius: var(--r);
        font-size: 0.76rem;
        color: var(--muted);
        box-shadow: var(--shadow-sm);
    }
    .pos-shortcut-key {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.45rem;
        background: var(--bg-tint);
        border: 1px solid var(--rule-faint);
        border-radius: var(--r-sm);
        font-weight: 800;
        font-family: monospace;
        color: var(--text);
        font-size: 0.72rem;
    }

    .pos-results-list {
        flex: 1;
        overflow-y: auto;
        max-height: 420px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .pos-product-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0.85rem;
        background: var(--surface-soft);
        border: 1px solid var(--rule-faint);
        border-radius: var(--r);
        gap: 0.65rem;
        transition: border-color 0.15s;
    }
    .pos-product-row:hover {
        border-color: var(--rule);
    }
    .pos-product-info {
        flex: 1;
        min-width: 0;
    }
    .pos-product-name {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pos-product-sub {
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 0.15rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .pos-product-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .pos-product-price {
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text);
        white-space: nowrap;
    }
    .pos-add-product-btn {
        padding: 0.45rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: var(--text);
        color: #fff;
        border: none;
        border-radius: var(--r-sm);
        cursor: pointer;
        transition: opacity 0.15s;
        white-space: nowrap;
    }
    .pos-add-product-btn:hover {
        opacity: 0.88;
    }
    .pos-add-product-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Middle Band: Subtotal / Discount / TOTAL */
    .pos-summary-band {
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r-lg);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow-card);
    }
    .pos-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        margin-bottom: 0.35rem;
    }
    .pos-summary-row.discount-text {
        color: var(--success);
        font-weight: 600;
    }
    .pos-grand-total-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding-top: 0.75rem;
        border-top: 2px solid var(--rule);
        margin-top: 0.75rem;
    }
    .pos-grand-label {
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .pos-grand-amount {
        font-size: 2.1rem;
        font-weight: 900;
        letter-spacing: -0.02em;
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }

    /* Bottom: Payment & Action Area */
    .pos-checkout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        align-items: stretch;
    }
    @media (max-width: 960px) {
        .pos-checkout-grid {
            grid-template-columns: 1fr;
        }
    }

    .pos-pay-btn-group {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .pos-payment-option {
        flex: 1;
        padding: 0.75rem 0.5rem;
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r);
        cursor: pointer;
        font-family: inherit;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text);
        transition: all 0.15s var(--ease);
        text-align: center;
    }
    .pos-payment-option:hover {
        border-color: var(--accent);
    }
    .pos-payment-option.active {
        background: var(--text);
        color: #fff;
        border-color: var(--text);
        box-shadow: 0 4px 10px rgba(32, 60, 61, 0.25);
    }

    .pos-cash-details-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }
    .pos-cash-field-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .pos-received-input {
        width: 160px;
        padding: 0.65rem 0.85rem;
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r-sm);
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 800;
        text-align: right;
        outline: none;
    }
    .pos-change-display {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--success);
        font-variant-numeric: tabular-nums;
    }

    .pos-complete-sale-btn {
        width: 100%;
        padding: 1.1rem;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: var(--r);
        cursor: pointer;
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        box-shadow: 0 8px 20px -6px rgba(196, 80, 74, 0.65);
        transition: opacity 0.15s, transform 0.15s;
        margin-top: 0.5rem;
    }
    .pos-complete-sale-btn:hover {
        opacity: 0.92;
        transform: translateY(-1px);
    }
    .pos-complete-sale-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    /* Modals */
    .pos-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(32, 60, 61, 0.55);
        backdrop-filter: blur(3px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }
    .pos-modal-card {
        background: var(--surface);
        border: 2px solid var(--rule);
        border-radius: var(--r-lg);
        width: 100%;
        max-width: 440px;
        padding: 1.5rem;
        box-shadow: var(--shadow-pop);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .pos-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .pos-modal-title {
        font-size: 1.1rem;
        font-weight: 800;
    }
    .pos-preset-chips {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
        margin-top: 0.35rem;
    }
    .pos-chip-btn {
        background: var(--bg-tint);
        border: 1px solid var(--rule-faint);
        border-radius: var(--r-pill);
        padding: 0.3rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        color: var(--text);
    }
    .pos-chip-btn:hover {
        border-color: var(--rule);
    }
    .pos-diff-banner {
        padding: 0.75rem 1rem;
        border-radius: var(--r);
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
    }
    .pos-diff-balanced { background: var(--success-soft); color: var(--success); }
    .pos-diff-short { background: var(--accent-soft); color: var(--danger); }
    .pos-diff-over { background: rgba(32,60,61,0.08); color: var(--text); }
</style>
@endpush

@section('content')
@php
    $employee = auth()->user()->employee;
    $cashierName = $employee ? $employee->fullName() : auth()->user()->username;
@endphp

<div class="pos-container">
    {{-- Top Header Bar --}}
    <div class="pos-top-bar">
        <div class="pos-top-left">
            <span class="pos-brand-tag">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                POS SYSTEM
            </span>
            <span class="pos-register-badge">Register 01</span>
        </div>
        <div class="pos-top-right">
            <div class="pos-cashier-info">
                <span>{{ $cashierName }}</span>
                <span class="pos-status-indicator" id="top-status-badge">
                    <span class="pos-status-dot-active" id="top-status-dot"></span>
                    <span id="top-status-text">ACTIVE</span>
                </span>
            </div>
            <button type="button" class="btn btn-secondary" id="drawer-toggle-btn" style="padding: 0.4rem 0.8rem; font-size: 0.78rem;">
                Drawer Status
            </button>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="pos-panel pos-cart-empty">
            <p>No products available in the inventory. A manager must add products first.</p>
        </div>
    @else
        {{-- Main 2-Column Work Area --}}
        <div class="pos-work-grid">
            {{-- LEFT: Customer & Receipt Area --}}
            <div class="pos-panel">
                <div class="pos-section-title">Customer</div>
                <div class="pos-customer-grid">
                    <div>
                        <label for="customer_id_select">Customer</label>
                        <select id="customer_id_select">
                            <option value="">Walk-in Customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->customer_id }}">{{ $c->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="customer_id_input">Customer ID / Loyalty</label>
                        <input id="customer_id_input" type="text" placeholder="Enter Loyalty or ID">
                    </div>
                </div>
                <div id="customer-loyalty-status" style="font-size:0.78rem; font-weight:700; color:var(--success); margin-top:0.4rem; min-height:1.2em; display:none;"></div>

                <hr class="pos-divider">

                <div class="pos-section-title">Receipt</div>
                <div class="pos-table-wrap">
                    <table class="pos-cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="tar">Qty</th>
                                <th class="tar">Price</th>
                                <th class="tar">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-rows"></tbody>
                    </table>
                    <div id="cart-empty-notice" class="pos-cart-empty">
                        Receipt is empty. Scan a barcode or search products on the right.
                    </div>
                </div>
                <div class="pos-items-count-badge" id="cart-count-badge">0 items</div>
            </div>

            {{-- RIGHT: Search Product Area --}}
            <div class="pos-panel">
                <div class="pos-section-title">Search Product</div>
                <div class="pos-search-box">
                    <span class="pos-search-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input id="pos-search-input" class="pos-search-input" type="text" placeholder="Search / Barcode (Scan or Type)..." autocomplete="off" autofocus>
                </div>

                {{-- Category Filter Chips --}}
                @if (isset($categories) && $categories->count())
                    <div class="pos-category-chips-bar" id="pos-category-chips">
                        <button type="button" class="pos-cat-chip active" data-category="all">All</button>
                        @foreach ($categories as $cat)
                            <button type="button" class="pos-cat-chip" data-category="{{ $cat->category_id }}">{{ $cat->category_name }}</button>
                        @endforeach
                    </div>
                @endif

                <hr class="pos-divider" style="margin-top: 0.15rem;">

                <div class="pos-section-title" style="margin-bottom: 0.5rem;">Search Results</div>
                <div class="pos-results-list" id="search-results-list"></div>
            </div>
        </div>

        {{-- Middle Band: Subtotal / Discount / TOTAL --}}
        <div class="pos-summary-band">
            <div class="pos-summary-row">
                <span>Subtotal</span>
                <span id="subtotal-val">₱0.00</span>
            </div>
            <div class="pos-summary-row discount-text">
                <span>Discount / Promo</span>
                <span id="discount-val">-₱0.00</span>
            </div>
            <div class="pos-grand-total-row">
                <span class="pos-grand-label">TOTAL</span>
                <span class="pos-grand-amount" id="total-val">₱0.00</span>
            </div>
        </div>

        {{-- Bottom Area: Payment Methods & Cash Received / Complete --}}
        <div class="pos-checkout-grid">
            <div class="pos-panel">
                <div class="pos-section-title">Payment</div>
                <div class="pos-pay-btn-group">
                    <button type="button" class="pos-payment-option active" data-method="cash">Cash</button>
                    <button type="button" class="pos-payment-option" data-method="card">Card</button>
                    <button type="button" class="pos-payment-option" data-method="e-wallet">E-Wallet</button>
                </div>
                <div class="pos-coupon-box" style="margin-top: 0.75rem;">
                    <label for="pos-coupon-code">Coupon Code (Optional)</label>
                    <div style="display: flex; gap: 0.4rem; align-items: stretch;">
                        <input id="pos-coupon-code" type="text" placeholder="e.g. SAVE50" maxlength="40" style="margin-bottom:0 !important; text-transform:uppercase;">
                        <button type="button" class="btn btn-secondary" id="apply-coupon-btn" style="padding: 0 1rem; margin-bottom:0; flex-shrink:0;">Apply</button>
                    </div>
                    <div id="coupon-feedback" style="font-size: 0.78rem; font-weight: 600; margin-top: 0.35rem; display: none;"></div>
                </div>
            </div>

            <div class="pos-panel">
                <div class="pos-section-title" id="payment-box-title">Cash Payment</div>
                
                {{-- Cash Fields --}}
                <div id="cash-payment-fields">
                    <div class="pos-cash-details-row">
                        <span class="pos-cash-field-label">Received</span>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <span style="font-weight: 700;">₱</span>
                            <input id="amount_paid_input" class="pos-received-input" type="number" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="pos-cash-details-row">
                        <span class="pos-cash-field-label">Change</span>
                        <span class="pos-change-display" id="change-val">₱0.00</span>
                    </div>
                </div>

                {{-- Card Fields --}}
                <div id="card-payment-fields" style="display:none; flex-direction:column; gap:0.6rem; margin-bottom:0.75rem;">
                    <div>
                        <label for="card-provider-select">Card Network</label>
                        <select id="card-provider-select" class="pos-select">
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="BancNet">BancNet Debit</option>
                            <option value="JCB">JCB</option>
                            <option value="Other">Other Card</option>
                        </select>
                    </div>
                    <div>
                        <label for="card-approval-code">Terminal Auth / Approval Code *</label>
                        <input id="card-approval-code" type="text" placeholder="e.g. APPR-948271" maxlength="50" autocomplete="off" class="pos-customer-input">
                    </div>
                    <div>
                        <label for="card-last4">Card Last 4 Digits (Optional)</label>
                        <input id="card-last4" type="text" placeholder="e.g. 4821" maxlength="4" autocomplete="off" class="pos-customer-input">
                    </div>
                    <p class="muted" style="font-size:0.78rem; margin:0;">Exact total charged via POS terminal. No cash change.</p>
                </div>

                {{-- E-Wallet Fields --}}
                <div id="ewallet-payment-fields" style="display:none; flex-direction:column; gap:0.6rem; margin-bottom:0.75rem;">
                    <div>
                        <label for="ewallet-provider-select">E-Wallet Service</label>
                        <select id="ewallet-provider-select" class="pos-select">
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="GrabPay">GrabPay</option>
                            <option value="Other">Other E-Wallet</option>
                        </select>
                    </div>
                    <div>
                        <label for="ewallet-reference-no">Reference / Transaction No. *</label>
                        <input id="ewallet-reference-no" type="text" placeholder="e.g. 12-digit GCash Ref No." maxlength="50" autocomplete="off" class="pos-customer-input">
                    </div>
                    <p class="muted" style="font-size:0.78rem; margin:0;">Customer scan-to-pay confirmation. No cash change.</p>
                </div>

                <button type="button" class="pos-complete-sale-btn" id="submit-sale-btn" disabled>
                    Complete Sale
                </button>
            </div>
        </div>

        {{-- POS Keyboard Shortcuts Bar --}}
        <div class="pos-shortcuts-bar">
            <span style="font-weight:700; color:var(--text); text-transform:uppercase; font-size:0.7rem; letter-spacing:0.04em;">Shortcuts:</span>
            <span><span class="pos-shortcut-key">F2</span> Search / Scan</span>
            <span><span class="pos-shortcut-key">F4</span> Cash Received</span>
            <span><span class="pos-shortcut-key">F8</span> Payment Method</span>
            <span><span class="pos-shortcut-key">Enter</span> Complete Sale</span>
            <span><span class="pos-shortcut-key">Esc</span> Close Modals</span>
        </div>
    @endif
</div>

{{-- Hidden Form to Submit Sale Data --}}
<form id="pos-store-form" method="POST" action="{{ route('pos.store') }}" style="display:none;">
    @csrf
    <input type="hidden" name="customer_id" id="store-customer-id">
    <input type="hidden" name="payment_method" id="store-payment-method" value="cash">
    <input type="hidden" name="reference_number" id="store-reference-number">
    <input type="hidden" name="payment_provider" id="store-payment-provider">
    <input type="hidden" name="amount_paid" id="store-amount-paid">
    <input type="hidden" name="coupon_code" id="store-coupon-code">
    <span id="store-items-payload"></span>
</form>

{{-- MODAL: Open Cash Register --}}
<div class="pos-modal-backdrop" id="open-register-modal">
    <div class="pos-modal-card">
        <div class="pos-modal-header">
            <span class="pos-modal-title">Open Cash Register</span>
            <button type="button" class="pos-remove-link" onclick="closeModal('open-register-modal')">✕</button>
        </div>
        <p class="muted" style="font-size:0.85rem;">Set opening cash float for Cashier <strong>{{ $cashierName }}</strong>.</p>
        <div>
            <label for="open-float-input">Opening Cash Float</label>
            <input id="open-float-input" type="number" step="0.01" min="0" value="5000.00">
            <div class="pos-preset-chips">
                <button type="button" class="pos-chip-btn" onclick="setFloat(1000)">₱1,000</button>
                <button type="button" class="pos-chip-btn" onclick="setFloat(2000)">₱2,000</button>
                <button type="button" class="pos-chip-btn" onclick="setFloat(5000)">₱5,000</button>
                <button type="button" class="pos-chip-btn" onclick="setFloat(10000)">₱10,000</button>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('open-register-modal')">Cancel</button>
            <button type="button" class="btn" id="confirm-open-btn">Open Register</button>
        </div>
    </div>
</div>

{{-- MODAL: Close Cash Register --}}
<div class="pos-modal-backdrop" id="close-register-modal">
    <div class="pos-modal-card">
        <div class="pos-modal-header">
            <span class="pos-modal-title">Close Register / End Shift</span>
            <button type="button" class="pos-remove-link" onclick="closeModal('close-register-modal')">✕</button>
        </div>
        <div style="display:flex; flex-direction:column; gap:0.4rem; font-size:0.9rem;">
            <div style="display:flex; justify-content:space-between;"><span class="muted">Opening Cash:</span><span id="close-modal-opening" style="font-weight:700;">₱0.00</span></div>
            <div style="display:flex; justify-content:space-between;"><span class="muted">Cash Sales:</span><span id="close-modal-sales" style="font-weight:700;">₱0.00</span></div>
            <div style="display:flex; justify-content:space-between; border-top:1px solid var(--rule-faint); padding-top:0.35rem;"><span class="muted">Expected in Drawer:</span><span id="close-modal-expected" style="font-weight:800;">₱0.00</span></div>
        </div>
        <div>
            <label for="close-actual-input">Actual Cash Counted</label>
            <input id="close-actual-input" type="number" step="0.01" min="0" placeholder="Count and enter total cash in drawer">
        </div>
        <div class="pos-diff-banner pos-diff-balanced" id="close-diff-banner">Difference: ₱0.00</div>
        <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('close-register-modal')">Cancel</button>
            <button type="button" class="btn" id="confirm-close-btn">Confirm & Close Shift</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const products = @json($productsJson ?? []);
    const cart = [];

    // Elements
    const cartRows = document.getElementById('cart-rows');
    const cartEmptyNotice = document.getElementById('cart-empty-notice');
    const cartCountBadge = document.getElementById('cart-count-badge');
    const searchInput = document.getElementById('pos-search-input');
    const searchResultsList = document.getElementById('search-results-list');
    const subtotalVal = document.getElementById('subtotal-val');
    const discountVal = document.getElementById('discount-val');
    const totalVal = document.getElementById('total-val');
    const amountPaidInput = document.getElementById('amount_paid_input');
    const changeVal = document.getElementById('change-val');
    const submitSaleBtn = document.getElementById('submit-sale-btn');
    const paymentBoxTitle = document.getElementById('payment-box-title');
    const cashPaymentFields = document.getElementById('cash-payment-fields');
    const posCouponInput = document.getElementById('pos-coupon-code');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const couponFeedback = document.getElementById('coupon-feedback');

    let appliedCouponDiscount = 0;
    let appliedCouponCode = '';
    let currentPaymentMethod = 'cash';
    let currentDrawer = null;

    // Web Audio Synthesizer for Supermarket Feedback
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            const AudioClass = window.AudioContext || window.webkitAudioContext;
            if (AudioClass) audioCtx = new AudioClass();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function playBeep(type = 'scan') {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            if (type === 'scan') {
                // Classic 1760Hz supermarket scanner beep (70ms)
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1760, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.07);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.07);
            } else if (type === 'error') {
                // Low double buzz for stock limit or invalid entry
                [0, 0.1].forEach(delay => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, ctx.currentTime + delay);
                    gain.gain.setValueAtTime(0.2, ctx.currentTime + delay);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.08);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + delay);
                    osc.stop(ctx.currentTime + delay + 0.08);
                });
            } else if (type === 'complete') {
                // Cheerful 3-tone cash register chime
                [523.25, 659.25, 783.99].forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, ctx.currentTime + (idx * 0.08));
                    gain.gain.setValueAtTime(0.22, ctx.currentTime + (idx * 0.08));
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (idx * 0.08) + 0.22);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + (idx * 0.08));
                    osc.stop(ctx.currentTime + (idx * 0.08) + 0.22);
                });
            }
        } catch (e) {
            // Audio policy blocked until first user gesture; fails silently
        }
    }

    function formatMoney(amount) {
        return '₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseNumeric(val) {
        if (!val) return 0;
        return parseFloat(String(val).replace(/[^0-9.-]+/g, '')) || 0;
    }

    // Render Cart
    function renderCart() {
        cartRows.innerHTML = '';
        if (cart.length === 0) {
            cartEmptyNotice.style.display = 'block';
            cartCountBadge.textContent = '0 items';
            submitSaleBtn.disabled = true;
        } else {
            cartEmptyNotice.style.display = 'none';
            const totalUnits = cart.reduce((sum, item) => sum + item.qty, 0);
            cartCountBadge.textContent = totalUnits + (totalUnits === 1 ? ' item' : ' items');
            submitSaleBtn.disabled = false;
        }

        let subtotal = 0;
        cart.forEach((item, index) => {
            const lineTotal = item.price * item.qty;
            subtotal += lineTotal;

            const tr = document.createElement('tr');

            // Product column
            const tdProduct = document.createElement('td');
            const pName = document.createElement('div');
            pName.style.fontWeight = '700';
            pName.textContent = item.name;
            tdProduct.appendChild(pName);

            // Qty column with steppers
            const tdQty = document.createElement('td');
            tdQty.className = 'tar';
            const qtyGroup = document.createElement('div');
            qtyGroup.className = 'pos-qty-group';

            const btnMinus = document.createElement('button');
            btnMinus.type = 'button';
            btnMinus.className = 'pos-qty-btn';
            btnMinus.textContent = '−';
            btnMinus.onclick = () => updateQty(item.id, -1);

            const qtySpan = document.createElement('span');
            qtySpan.className = 'pos-qty-text';
            qtySpan.textContent = item.qty;

            const btnPlus = document.createElement('button');
            btnPlus.type = 'button';
            btnPlus.className = 'pos-qty-btn';
            btnPlus.textContent = '+';
            btnPlus.onclick = () => updateQty(item.id, 1);

            qtyGroup.append(btnMinus, qtySpan, btnPlus);
            tdQty.appendChild(qtyGroup);

            // Unit Price column
            const tdPrice = document.createElement('td');
            tdPrice.className = 'tar';
            tdPrice.textContent = formatMoney(item.price);

            // Line Total column
            const tdTotal = document.createElement('td');
            tdTotal.className = 'tar';
            tdTotal.style.fontWeight = '700';
            tdTotal.textContent = formatMoney(lineTotal);

            // Action / Remove column
            const tdAction = document.createElement('td');
            tdAction.className = 'tar';
            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'pos-remove-link';
            rmBtn.textContent = '✕';
            rmBtn.title = 'Remove';
            rmBtn.onclick = () => removeItem(index);
            tdAction.appendChild(rmBtn);

            tr.append(tdProduct, tdQty, tdPrice, tdTotal, tdAction);
            cartRows.appendChild(tr);
        });

        // Totals
        const discount = appliedCouponDiscount;
        const total = Math.max(0, subtotal - discount);
        subtotalVal.textContent = formatMoney(subtotal);
        discountVal.textContent = discount > 0 ? ('-' + formatMoney(discount)) : '-₱0.00';
        totalVal.textContent = formatMoney(total);

        // Update Change
        const paid = parseNumeric(amountPaidInput.value);
        if (currentPaymentMethod === 'cash') {
            const change = Math.max(0, paid - total);
            changeVal.textContent = formatMoney(change);
        } else {
            changeVal.textContent = '₱0.00';
        }
    }

    function updateQty(productId, delta) {
        const item = cart.find(i => i.id === productId);
        if (!item) return;
        const p = products.find(p => p.id === productId);
        if (delta > 0 && item.qty + delta > (p ? p.stock : 9999)) {
            alert('Cannot add more. Reached available stock limit (' + p.stock + ').');
            return;
        }
        item.qty += delta;
        if (item.qty <= 0) {
            const idx = cart.indexOf(item);
            cart.splice(idx, 1);
        }
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function addToCart(productId, qty = 1) {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        if (product.stock <= 0) {
            playBeep('error');
            alert('"' + product.name + '" is out of stock.');
            return;
        }

        const existing = cart.find(i => i.id === productId);
        if (existing) {
            if (existing.qty + qty > product.stock) {
                playBeep('error');
                alert('Stock limit reached for ' + product.name + '. (Available: ' + product.stock + ')');
                return;
            }
            existing.qty += qty;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                qty: qty
            });
        }
        playBeep('scan');
        renderCart();
    }

    // Live Product Search & Barcode Scan with Category Filtering
    let searchDebounce;
    let selectedCategory = 'all';

    function renderSearchResults(query = '') {
        const q = query.trim().toLowerCase();
        searchResultsList.innerHTML = '';

        const matched = products.filter(p => {
            const matchesCat = selectedCategory === 'all' || String(p.category_id) === String(selectedCategory);
            if (!matchesCat) return false;
            if (q === '') return true;
            return p.name.toLowerCase().includes(q) ||
                (p.barcode && p.barcode.toLowerCase().includes(q));
        }).slice(0, 20);

        if (matched.length === 0) {
            searchResultsList.innerHTML = '<div class="pos-cart-empty" style="padding:1.5rem 0;">No matching products found.</div>';
            return;
        }

        matched.forEach(p => {
            const row = document.createElement('div');
            row.className = 'pos-product-row';

            const info = document.createElement('div');
            info.className = 'pos-product-info';

            const title = document.createElement('div');
            title.className = 'pos-product-name';
            title.textContent = p.name;

            const sub = document.createElement('div');
            sub.className = 'pos-product-sub';
            sub.innerHTML = `<span>SKU: ${p.barcode || '—'}</span> <span>• Stock: ${p.stock}</span> <span>• ${p.category_name || 'General'}</span>`;

            info.append(title, sub);

            const actions = document.createElement('div');
            actions.className = 'pos-product-actions';

            const price = document.createElement('span');
            price.className = 'pos-product-price';
            price.textContent = formatMoney(p.price);

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'pos-add-product-btn';
            addBtn.textContent = '+ ADD';
            addBtn.disabled = p.stock <= 0;
            addBtn.onclick = () => {
                addToCart(p.id, 1);
            };

            actions.append(price, addBtn);
            row.append(info, actions);
            searchResultsList.appendChild(row);
        });
    }

    // Category chips click listener
    document.querySelectorAll('.pos-cat-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.pos-cat-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            selectedCategory = this.dataset.category;
            renderSearchResults(searchInput.value);
        });
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        const val = this.value;
        searchDebounce = setTimeout(() => {
            // Check exact barcode match first
            const exact = products.find(p => p.barcode && p.barcode === val.trim());
            if (exact) {
                addToCart(exact.id, 1);
                searchInput.value = '';
                renderSearchResults();
                return;
            }
            renderSearchResults(val);
        }, 150);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            const exact = products.find(p => p.barcode && p.barcode === val);
            if (exact) {
                addToCart(exact.id, 1);
                this.value = '';
                renderSearchResults();
            }
        }
    });

    // Elements
    const cardPaymentFields = document.getElementById('card-payment-fields');
    const ewalletPaymentFields = document.getElementById('ewallet-payment-fields');
    const cardApprovalCode = document.getElementById('card-approval-code');
    const cardProviderSelect = document.getElementById('card-provider-select');
    const ewalletReferenceNo = document.getElementById('ewallet-reference-no');
    const ewalletProviderSelect = document.getElementById('ewallet-provider-select');

    // Payment Methods
    document.querySelectorAll('.pos-payment-option').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pos-payment-option').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPaymentMethod = this.dataset.method;

            cashPaymentFields.style.display = 'none';
            cardPaymentFields.style.display = 'none';
            ewalletPaymentFields.style.display = 'none';

            if (currentPaymentMethod === 'cash') {
                paymentBoxTitle.textContent = 'Cash Payment';
                cashPaymentFields.style.display = 'block';
            } else if (currentPaymentMethod === 'card') {
                paymentBoxTitle.textContent = 'Card Payment';
                cardPaymentFields.style.display = 'flex';
                const total = parseNumeric(totalVal.textContent);
                amountPaidInput.value = total.toFixed(2);
                setTimeout(() => cardApprovalCode && cardApprovalCode.focus(), 50);
            } else if (currentPaymentMethod === 'e-wallet') {
                paymentBoxTitle.textContent = 'E-Wallet Payment';
                ewalletPaymentFields.style.display = 'flex';
                const total = parseNumeric(totalVal.textContent);
                amountPaidInput.value = total.toFixed(2);
                setTimeout(() => ewalletReferenceNo && ewalletReferenceNo.focus(), 50);
            }
            renderCart();
        });
    });

    amountPaidInput.addEventListener('input', renderCart);

    // Customer Sync & Loyalty Lookup
    const customersData = @json($customersJson ?? []);
    const customerSelect = document.getElementById('customer_id_select');
    const customerInput = document.getElementById('customer_id_input');
    const customerLoyaltyBadge = document.getElementById('customer-loyalty-status');

    function syncCustomerDisplay(foundCustomer) {
        if (foundCustomer) {
            customerLoyaltyBadge.style.display = 'block';
            customerLoyaltyBadge.textContent = `✓ Customer: ${foundCustomer.name} (Loyalty: ${foundCustomer.points} pts)`;
        } else {
            customerLoyaltyBadge.style.display = 'none';
            customerLoyaltyBadge.textContent = '';
        }
    }

    customerSelect.addEventListener('change', function() {
        if (this.value) {
            customerInput.value = this.value;
            const match = customersData.find(c => String(c.id) === String(this.value));
            syncCustomerDisplay(match);
        } else {
            customerInput.value = '';
            syncCustomerDisplay(null);
        }
    });

    customerInput.addEventListener('input', function() {
        const val = this.value.trim().toLowerCase();
        if (!val) {
            customerSelect.value = '';
            syncCustomerDisplay(null);
            return;
        }

        const match = customersData.find(c => 
            String(c.id) === val || 
            (c.contact && c.contact.toLowerCase() === val)
        );

        if (match) {
            customerSelect.value = match.id;
            syncCustomerDisplay(match);
        } else {
            customerSelect.value = '';
            syncCustomerDisplay(null);
        }
    });

    // Submit Sale Handler
    submitSaleBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            alert('Add at least one item to the receipt.');
            return;
        }

        const total = parseNumeric(totalVal.textContent);
        let paid = total;
        let refNum = '';
        let provider = '';

        // Validation per payment method
        if (currentPaymentMethod === 'cash') {
            paid = parseNumeric(amountPaidInput.value);
            if (paid < total - 0.001) {
                playBeep('error');
                alert('Amount received (' + formatMoney(paid) + ') is less than total due (' + formatMoney(total) + ').');
                amountPaidInput.focus();
                return;
            }
        } else if (currentPaymentMethod === 'card') {
            const approval = cardApprovalCode ? cardApprovalCode.value.trim() : '';
            if (!approval) {
                playBeep('error');
                alert('Please enter the Card Terminal Auth / Approval Code before completing the sale.');
                cardApprovalCode && cardApprovalCode.focus();
                return;
            }
            const last4 = (document.getElementById('card-last4')?.value || '').trim();
            refNum = approval + (last4 ? ' (**** ' + last4 + ')' : '');
            provider = cardProviderSelect ? cardProviderSelect.value : 'Card';
            paid = total;
        } else if (currentPaymentMethod === 'e-wallet') {
            const eRef = ewalletReferenceNo ? ewalletReferenceNo.value.trim() : '';
            if (!eRef) {
                playBeep('error');
                alert('Please enter the E-Wallet Reference / Transaction Number (e.g. GCash/Maya reference) before completing the sale.');
                ewalletReferenceNo && ewalletReferenceNo.focus();
                return;
            }
            refNum = eRef;
            provider = ewalletProviderSelect ? ewalletProviderSelect.value : 'E-Wallet';
            paid = total;
        }

        // Fill hidden form
        document.getElementById('store-customer-id').value = customerSelect.value || customerInput.value || '';
        document.getElementById('store-payment-method').value = currentPaymentMethod;
        document.getElementById('store-reference-number').value = refNum;
        document.getElementById('store-payment-provider').value = provider;
        document.getElementById('store-amount-paid').value = paid;
        document.getElementById('store-coupon-code').value = posCouponInput.value.trim();

        const payloadSpan = document.getElementById('store-items-payload');
        payloadSpan.innerHTML = '';
        cart.forEach((item, i) => {
            const pInput = document.createElement('input');
            pInput.type = 'hidden';
            pInput.name = `items[${i}][product_id]`;
            pInput.value = item.id;

            const qInput = document.createElement('input');
            qInput.type = 'hidden';
            qInput.name = `items[${i}][quantity]`;
            qInput.value = item.qty;

            payloadSpan.append(pInput, qInput);
        });

        playBeep('complete');
        document.getElementById('pos-store-form').submit();
    });

    // Modal Control Helpers
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'flex';
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }
    function setFloat(amt) {
        document.getElementById('open-float-input').value = amt.toFixed(2);
    }

    // Cash Drawer Management
    const drawerToggleBtn = document.getElementById('drawer-toggle-btn');
    const topStatusBadge = document.getElementById('top-status-badge');
    const topStatusDot = document.getElementById('top-status-dot');
    const topStatusText = document.getElementById('top-status-text');

    async function checkDrawerStatus() {
        try {
            const res = await fetch('{{ route("cash-drawer.status") }}');
            const data = await res.json();
            currentDrawer = data.drawer;

            if (data.has_open_drawer && currentDrawer) {
                topStatusDot.className = 'pos-status-dot-active';
                topStatusText.textContent = 'ACTIVE';
                topStatusBadge.style.color = 'var(--success)';
                topStatusBadge.style.background = 'var(--success-soft)';
                drawerToggleBtn.textContent = 'Close Shift (' + formatMoney(currentDrawer.expected_cash) + ')';
                drawerToggleBtn.onclick = () => {
                    document.getElementById('close-modal-opening').textContent = formatMoney(currentDrawer.opening_cash);
                    const sales = Math.max(0, currentDrawer.expected_cash - currentDrawer.opening_cash);
                    document.getElementById('close-modal-sales').textContent = formatMoney(sales);
                    document.getElementById('close-modal-expected').textContent = formatMoney(currentDrawer.expected_cash);
                    document.getElementById('close-actual-input').value = currentDrawer.expected_cash;
                    updateCloseDiff();
                    openModal('close-register-modal');
                };
            } else {
                topStatusDot.className = 'pos-status-dot-closed';
                topStatusText.textContent = 'NO SHIFT';
                topStatusBadge.style.color = 'var(--muted)';
                topStatusBadge.style.background = 'rgba(32,60,61,0.06)';
                drawerToggleBtn.textContent = 'Open Register';
                drawerToggleBtn.onclick = () => openModal('open-register-modal');
            }
        } catch (e) {
            console.error(e);
        }
    }

    function updateCloseDiff() {
        if (!currentDrawer) return;
        const actual = parseNumeric(document.getElementById('close-actual-input').value);
        const expected = parseFloat(currentDrawer.expected_cash);
        const diff = actual - expected;
        const banner = document.getElementById('close-diff-banner');

        if (Math.abs(diff) < 0.01) {
            banner.className = 'pos-diff-banner pos-diff-balanced';
            banner.textContent = 'Exact Match: Balanced (₱0.00)';
        } else if (diff > 0) {
            banner.className = 'pos-diff-banner pos-diff-over';
            banner.textContent = 'Overage: +' + formatMoney(diff);
        } else {
            banner.className = 'pos-diff-banner pos-diff-short';
            banner.textContent = 'Shortage: -' + formatMoney(Math.abs(diff));
        }
    }

    document.getElementById('close-actual-input').addEventListener('input', updateCloseDiff);

    document.getElementById('confirm-open-btn').addEventListener('click', async () => {
        const floatVal = parseNumeric(document.getElementById('open-float-input').value);
        if (floatVal < 0) { alert('Invalid opening float.'); return; }
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const res = await fetch('{{ route("cash-drawer.open") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ opening_cash: floatVal })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            closeModal('open-register-modal');
            checkDrawerStatus();
        }
    });

    document.getElementById('confirm-close-btn').addEventListener('click', async () => {
        const actual = parseNumeric(document.getElementById('close-actual-input').value);
        if (actual < 0) { alert('Invalid actual cash amount.'); return; }
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const res = await fetch('{{ route("cash-drawer.close") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ actual_cash: actual })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            alert(data.message);
            closeModal('close-register-modal');
            checkDrawerStatus();
        }
    });

    // Live Coupon Check & Apply
    async function applyCoupon() {
        if (!posCouponInput) return;
        const code = posCouponInput.value.trim().toUpperCase();
        if (!code) {
            appliedCouponDiscount = 0;
            appliedCouponCode = '';
            if (couponFeedback) couponFeedback.style.display = 'none';
            renderCart();
            return;
        }

        const currentSubtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        if (currentSubtotal <= 0) {
            if (couponFeedback) {
                couponFeedback.textContent = 'Add items to the receipt before applying a coupon.';
                couponFeedback.style.color = 'var(--danger)';
                couponFeedback.style.display = 'block';
            }
            return;
        }

        try {
            if (applyCouponBtn) {
                applyCouponBtn.disabled = true;
                applyCouponBtn.textContent = '...';
            }
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            const res = await fetch('{{ route("pos.check-coupon") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    coupon_code: code,
                    subtotal: currentSubtotal,
                    customer_id: customerSelect?.value ? parseInt(customerSelect.value) : null
                })
            });

            const data = await res.json();
            if (res.ok && data.valid) {
                appliedCouponDiscount = parseFloat(data.discount_amount || 0);
                appliedCouponCode = data.code;
                if (couponFeedback) {
                    couponFeedback.textContent = data.message || `✓ Coupon applied (-₱${appliedCouponDiscount.toFixed(2)})`;
                    couponFeedback.style.color = 'var(--success)';
                    couponFeedback.style.display = 'block';
                }
            } else {
                appliedCouponDiscount = 0;
                appliedCouponCode = '';
                if (couponFeedback) {
                    couponFeedback.textContent = data.message || 'Invalid or ineligible coupon.';
                    couponFeedback.style.color = 'var(--danger)';
                    couponFeedback.style.display = 'block';
                }
            }
        } catch (e) {
            appliedCouponDiscount = 0;
            appliedCouponCode = '';
            if (couponFeedback) {
                couponFeedback.textContent = 'Network error validating coupon.';
                couponFeedback.style.color = 'var(--danger)';
                couponFeedback.style.display = 'block';
            }
        } finally {
            if (applyCouponBtn) {
                applyCouponBtn.disabled = false;
                applyCouponBtn.textContent = 'Apply';
            }
            renderCart();
        }
    }

    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', applyCoupon);
    }
    if (posCouponInput) {
        posCouponInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCoupon();
            }
        });
    }

    // Global Hardware Barcode Scanner Buffer & High-Speed Keystroke Catcher
    let barcodeScanBuffer = '';
    let lastScanKeyTimestamp = 0;

    // POS Global Keyboard Shortcuts
    window.addEventListener('keydown', (e) => {
        const active = document.activeElement;
        const now = Date.now();
        const diff = now - lastScanKeyTimestamp;
        lastScanKeyTimestamp = now;

        // If delay between keystrokes is high (>120ms), reset hardware scanner buffer (indicates human typing)
        if (diff > 120) {
            barcodeScanBuffer = '';
        }

        // Buffer printable characters for hardware scanner (ignore shortcuts with Ctrl/Alt/Meta)
        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            barcodeScanBuffer += e.key;
        }

        // Hardware scanner sends Enter key at end of barcode
        if (e.key === 'Enter') {
            const rawBarcode = barcodeScanBuffer.trim();
            if (rawBarcode.length >= 4) {
                const found = products.find(p => p.barcode && p.barcode.toLowerCase() === rawBarcode.toLowerCase());
                if (found) {
                    e.preventDefault();
                    addToCart(found.id, 1);
                    barcodeScanBuffer = '';
                    if (active === searchInput) {
                        searchInput.value = '';
                        renderSearchResults();
                    }
                    return;
                }
            }
            barcodeScanBuffer = '';
        }

        // Esc: close modals or reset search
        if (e.key === 'Escape') {
            closeModal('open-register-modal');
            closeModal('close-register-modal');
            if (searchInput && document.activeElement === searchInput) {
                searchInput.value = '';
                renderSearchResults();
            }
            return;
        }

        // F2: Focus Search / Barcode
        if (e.key === 'F2') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
            return;
        }

        // F4: Focus Cash Received input
        if (e.key === 'F4') {
            e.preventDefault();
            if (currentPaymentMethod !== 'cash') {
                const cashBtn = document.querySelector('.pos-payment-option[data-method="cash"]');
                if (cashBtn) cashBtn.click();
            }
            if (amountPaidInput) {
                amountPaidInput.focus();
                amountPaidInput.select();
            }
            return;
        }

        // F8: Cycle Payment Methods (Cash -> Card -> E-Wallet -> Cash)
        if (e.key === 'F8') {
            e.preventDefault();
            const methods = ['cash', 'card', 'e-wallet'];
            const curIdx = methods.indexOf(currentPaymentMethod);
            const nextMethod = methods[(curIdx + 1) % methods.length];
            const nextBtn = document.querySelector(`.pos-payment-option[data-method="${nextMethod}"]`);
            if (nextBtn) nextBtn.click();
            return;
        }

        // Enter key inside payment inputs triggers Complete Sale
        if (e.key === 'Enter') {
            const active = document.activeElement;
            if (active === amountPaidInput || active === cardApprovalCode || active === ewalletReferenceNo) {
                e.preventDefault();
                if (submitSaleBtn && !submitSaleBtn.disabled) {
                    submitSaleBtn.click();
                }
            }
        }
    });

    // Init
    renderCart();
    renderSearchResults();
    checkDrawerStatus();
</script>
@endpush
