@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<style>
    /* ===== Main POS Frame ===== */
    .pos-container {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
        max-width: 100%;
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
        justify-content: flex-end;
        gap: 12px;
        min-width: 0;
        flex: 1;
    }
    .pos-header-actions { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 7px; }
    .pos-header-action {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 7px 11px;
        border: 1px solid var(--rule-faint);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: .76rem;
        font-weight: 750;
        line-height: 1.15;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }
    .pos-header-action:hover, .pos-header-action:focus-visible {
        background: var(--bg-tint);
        color: var(--text);
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(32,60,61,.1);
    }
    .pos-header-action:focus-visible { outline: 3px solid rgba(32,60,61,.2); outline-offset: 2px; }
    .pos-header-badge { display: inline-grid; min-width: 20px; height: 20px; place-items: center; padding: 0 5px; border-radius: 999px; background: var(--bg-tint); color: var(--text); font-size: .69rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pos-header-ewallet.has-pending .pos-header-badge { background: #fff0d2; color: #865300; }
    .pos-header-shift { min-width: 148px; border-color: var(--text); background: var(--text); color: #fff; box-shadow: 0 3px 9px rgba(32,60,61,.16); }
    .pos-header-shift:hover, .pos-header-shift:focus-visible { border-color: #2b4d4e; background: #2b4d4e; color: #fff; }
    .pos-header-shift-amount { font-variant-numeric: tabular-nums; }
    .pos-header-shift-copy { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
    .pos-header-shift-time { min-height: 1em; color: rgba(255,255,255,.78); font-size: .62rem; font-weight: 650; font-variant-numeric: tabular-nums; }
    .pos-ewallet-note { margin: 2px 0 10px; color: var(--muted); font-size: .76rem; line-height: 1.45; }
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
        grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
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
        min-width: 0;
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
        min-width: 0;
        max-width: 100%;
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
    .pos-reprint-shortcut { display: inline-flex; align-items: center; gap: 4px; color: inherit; text-decoration: none; }
    .pos-reprint-shortcut:hover { color: var(--text); }

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
    .pos-held-modal-card { width: min(100%, 620px); max-height: calc(100dvh - 32px); padding: 18px; gap: 12px; overflow: hidden; }
    .pos-held-modal-copy { margin: 0; color: var(--muted); font-size: .78rem; line-height: 1.5; }
    .pos-held-list { display: grid; gap: 8px; max-height: min(55vh, 480px); overflow: auto; padding: 2px 3px 2px 1px; }
    .pos-held-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 8px 14px; padding: 10px 12px; border: 1px solid rgba(32,60,61,.1); border-radius: 10px; background: #fff; }
    .pos-held-main { min-width: 0; display: grid; gap: 3px; }
    .pos-held-number { color: var(--text); font-size: .82rem; font-weight: 750; }
    .pos-held-meta { color: var(--muted); font-size: .68rem; line-height: 1.4; }
    .pos-held-total { color: var(--text); font-size: .92rem; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .pos-held-actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; grid-column: 1 / -1; }
    .pos-held-actions .btn, .pos-held-actions .btn-secondary, .pos-held-actions .btn-danger { min-height: 32px; padding: 6px 10px; font-size: .7rem; }
    .pos-held-empty { padding: 22px 12px; border: 1px dashed rgba(32,60,61,.16); border-radius: 10px; color: var(--muted); font-size: .78rem; text-align: center; }
    .pos-resume-choice-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 7px; }
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
    .pos-diff-short { background: var(--danger-soft); color: var(--danger); }
    .pos-diff-over { background: rgba(32,60,61,0.08); color: var(--text); }

    /* Retail workstation: product picking, ticket review and payment stay in one fixed-height register. */
    .pos-container { height: 100%; min-height: 0; display: grid; grid-template-rows: 40px minmax(0, 1fr) 20px; gap: 9px; padding: 10px; border-radius: 16px; background: #f3f5f3; color: var(--text); }
    .pos-top-bar { min-height: 40px; padding: 0 3px; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
    .pos-brand-tag { gap: 9px; color: var(--text); font-size: .9rem; font-weight: 750; letter-spacing: -.015em; }
    .pos-register-badge { padding: 5px 9px; border: 1px solid rgba(32,60,61,.08); border-radius: 999px; background: #fff; color: var(--text); font-size: .66rem; font-weight: 700; }
    .pos-cashier-info { color: #53615f; font-size: .74rem; }
    .pos-header-action { border-color: var(--text); background: var(--text); color: #fff; font-weight: 800; }
    .pos-header-action:hover, .pos-header-action:focus-visible { border-color: #2b4d4e; background: #2b4d4e; color: #fff; }
    .pos-header-action .pos-header-badge { background: #fff; color: var(--text); }
    .pos-header-ewallet.has-pending .pos-header-badge { background: #ffe3a3; color: #704700; }
    .pos-status-indicator { padding: 6px 10px; border-radius: 999px; background: #16803c; color: #fff; font-size: .68rem; font-weight: 800; }
    .pos-status-indicator.is-inactive { background: rgba(32,60,61,.08); color: var(--muted); }
    .pos-status-dot-active { background: #fff; }
    .pos-top-bar .btn-secondary { min-width: 150px; min-height: 36px; display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border: 0 !important; border-radius: 9px; background: var(--text) !important; color: #fff !important; font-size: .72rem !important; font-weight: 700; line-height: 1.2; text-align: center; text-transform: none; white-space: nowrap; }
    .pos-top-bar .btn-secondary:hover { background: #2b4d4e; }
    .pos-work-grid { min-height: 0; display: grid; grid-template-columns: minmax(0, 1fr) minmax(240px, 278px); gap: 10px; }
    .pos-main-workspace { min-width: 0; min-height: 0; display: grid; grid-template-columns: minmax(235px, .88fr) minmax(330px, 1.12fr); grid-template-rows: minmax(0, 1fr) auto; column-gap: 10px; row-gap: 0; }
    .pos-panel { min-height: 0; padding: 13px; border: 0; border-radius: 13px; background: #fff; box-shadow: 0 4px 16px rgba(32,60,61,.07); overflow: hidden; }
    .pos-catalog-panel { grid-column: 1; grid-row: 1 / 3; display: flex; flex-direction: column; }
    .pos-transaction-panel { grid-column: 2; grid-row: 1; display: flex; flex-direction: column; border-radius: 13px 13px 0 0; box-shadow: 0 2px 8px rgba(32,60,61,.035); }
    .pos-payment-panel { grid-column: 2; grid-row: 2; border-top: 1px solid rgba(32,60,61,.09); border-radius: 0 0 13px 13px; box-shadow: 0 4px 16px rgba(32,60,61,.07); }
    .pos-section-title { margin: 0 0 8px; color: var(--text); font-size: .82rem; font-weight: 700; letter-spacing: -.012em; line-height: 1.25; text-transform: none; }
    .pos-search-box { flex: 0 0 auto; margin-bottom: 8px; }
    input#pos-search-input { height: 40px; line-height: 38px; padding-left: 2.55rem !important; border: 1px solid rgba(32,60,61,.15); border-radius: 9px; background: #fbfcfb; color: var(--text); font-size: .8rem; }
    input#pos-search-input:focus, .pos-right-rail input:focus, .pos-right-rail select:focus, .pos-payment-panel input:focus, .pos-payment-panel select:focus { border-color: var(--focus); box-shadow: var(--ring); outline: none; }
    .pos-search-icon { left: .8rem; color: #657271; }
    .pos-category-chips-bar { flex: 0 0 auto; margin: 0 0 7px; padding-bottom: 3px; gap: 4px; scrollbar-width: none; }
    .pos-category-chips-bar::-webkit-scrollbar { display: none; }
    .pos-cat-chip { padding: 6px 9px; border: 0; border-radius: 7px; background: transparent; color: #586562; font-size: .68rem; font-weight: 600; }
    .pos-cat-chip:hover { background: rgba(32,60,61,.06); }
    .pos-cat-chip.active { background: var(--text); color: #fff; }
    .pos-results-list { flex: 1; min-height: 0; max-height: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(178px, 1fr)); align-content: start; gap: 7px; padding: 1px 3px 3px 1px; overflow: auto; scrollbar-color: rgba(32,60,61,.24) transparent; scrollbar-width: thin; container: product-list / inline-size; }
    .pos-product-row { min-width: 0; min-height: 116px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; gap: 7px; padding: 10px 12px 15px; border: 1px solid rgba(32,60,61,.09); border-radius: 10px; background: #fff; transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
    .pos-product-row:hover { border-color: rgba(32,60,61,.18); box-shadow: 0 4px 12px rgba(32,60,61,.075); transform: translateY(-1px); }
    .pos-product-info { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 5px; }
    .pos-product-name { color: var(--text); font-size: .84rem; font-weight: 800; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
    .pos-product-sub { min-width: 0; margin: 0; color: #53615f; font-size: .66rem; }
    .pos-stock-label { display: inline-block; max-width: 100%; overflow: hidden; padding: 4px 8px; border-radius: 6px; background: var(--text); color: #fff; font-size: .61rem; font-weight: 800; line-height: 1.2; text-overflow: ellipsis; white-space: nowrap; }
    .pos-stock-label.low-stock { background: #fff2c7; color: #76520a; }
    .pos-stock-label.reorder-level, .pos-stock-label.out-of-stock { background: var(--danger); color: #fff; }
    .pos-cart-table td:nth-child(3), .pos-cart-table td:nth-child(4) { white-space: nowrap; }
    .pos-product-actions { width: 100%; min-width: 0; display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-top: auto; }
    .pos-product-price { min-width: 0; overflow: hidden; color: var(--text); font-size: .85rem; font-weight: 750; font-variant-numeric: tabular-nums; text-overflow: ellipsis; white-space: nowrap; }
    .pos-add-product-btn { flex: 0 0 auto; max-width: 100%; min-height: 32px; padding: 6px 12px; border: 0; border-radius: 7px; background: #16803c; color: #fff; font-size: .7rem; font-weight: 800; text-transform: none; white-space: nowrap; box-shadow: 0 2px 5px rgba(22,128,60,.16); }
    .pos-add-product-btn:hover, .pos-add-product-btn:focus-visible { background: #126b32; color: #fff; }
    .pos-add-product-btn:focus-visible { outline: 3px solid rgba(22,128,60,.22); outline-offset: 2px; }
    .pos-add-product-btn:disabled { background: #e7ece9; color: #7b8781; box-shadow: none; cursor: not-allowed; }
    .pos-transaction-heading { display: flex; align-items: center; justify-content: space-between; margin: -13px -13px 0; padding: 11px 14px; border: 0; border-radius: 13px 13px 0 0; background: var(--text); color: #fff; }
    .pos-transaction-heading .pos-section-title { margin: 0; color: #fff; font-size: .8rem; }
    .pos-table-wrap { flex: 1; min-height: 0; max-height: none; margin-top: 5px; overflow: auto; scrollbar-color: rgba(32,60,61,.24) transparent; scrollbar-width: thin; }
    .pos-cart-table { border-collapse: collapse; }
    .pos-cart-table thead { position: sticky; top: 0; z-index: 1; background: #fff; }
    .pos-cart-table th { padding: 8px 6px; border-bottom: 1px solid rgba(32,60,61,.12); color: #657271; font-size: .64rem; font-weight: 650; }
    .pos-cart-table td { padding: 8px 6px; border-bottom: 1px solid rgba(32,60,61,.07); color: var(--text); font-size: .74rem; }
    .pos-cart-row-highlight > td { background: var(--bg-tint); transition: background-color .35s ease; }
    @media (prefers-reduced-motion: reduce) {
        .pos-cart-row-highlight > td { transition: none; }
    }
    .pos-cart-empty { padding: 26px 8px; color: #687674; font-size: .78rem; text-align: center; }
    #cart-empty-notice { flex: 1; display: grid; align-content: center; justify-items: center; gap: 8px; padding: 18px; color: #64706e; }
    #cart-empty-notice strong { color: var(--text); font-size: .84rem; font-weight: 700; }
    #cart-empty-notice > span:last-child { max-width: 28ch; font-size: .73rem; line-height: 1.5; }
    .pos-empty-mark { display: grid; width: 38px; height: 38px; place-items: center; margin-bottom: 2px; border-radius: 11px; background: #f1f5f3; color: var(--text); }
    .pos-empty-mark svg { width: 20px; height: 20px; }
    .pos-qty-group { gap: 4px; }
    .pos-qty-input { width: 64px; min-width: 48px; height: 30px; padding: 0 4px; border: 1px solid rgba(32,60,61,.15); border-radius: 7px; background: #fff; color: var(--text); font: inherit; font-size: .75rem; font-weight: 700; font-variant-numeric: tabular-nums; text-align: center; }
    .pos-qty-btn { width: 26px; height: 26px; border: 0; border-radius: 7px; background: rgba(32,60,61,.07); color: var(--text); }
    .pos-qty-btn:hover { background: rgba(32,60,61,.14); }
    .pos-items-count-badge { margin-top: 0; padding: 4px 8px; border-radius: 999px; background: rgba(255,255,255,.14); color: #fff; font-size: .65rem; font-weight: 650; }
    .pos-transaction-summary { flex: 0 0 auto; margin: 7px -13px -13px; padding: 9px 13px 13px; border-top: 1px solid rgba(32,60,61,.08); background: #fcfdfc; }
    .pos-summary-row { margin: 0 0 4px; color: #596663; font-size: .73rem; }
    .pos-summary-row.discount-text { color: var(--accent); }
    .pos-discount-adjustment { display: none; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 2px 8px; margin: 6px 0; padding: 7px 9px; border-radius: 8px; background: var(--surface-active); color: var(--text); font-size: .72rem; }
    .pos-discount-adjustment.active { display: grid; }
    .pos-discount-adjustment-code { grid-column: 1; color: var(--muted); font-size: .66rem; }
    .pos-discount-adjustment-amount { grid-column: 2; grid-row: 1 / 3; color: var(--accent); font-weight: 750; font-variant-numeric: tabular-nums; }
    .pos-vat-exemption-row { color: var(--muted); }
    .pos-senior-proof { display: grid; gap: 8px; margin: 8px 0 10px; padding: 10px; border: 1px solid rgba(32,60,61,.1); border-radius: 10px; background: var(--surface-soft); }
    .pos-senior-proof[hidden] { display: none; }
    .pos-senior-proof p { margin: 0; color: var(--muted); font-size: .68rem; line-height: 1.4; }
    .pos-grand-total-row { margin: 6px 0 0; padding: 9px 11px; align-items: center; border: 0; border-radius: 9px; background: var(--text); color: #fff; }
    .pos-grand-label { color: rgba(255,255,255,.76); font-size: .7rem; font-weight: 650; }
    .pos-grand-amount { color: #fff; font-size: 1.35rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pos-right-rail { min-height: 0; display: flex; flex-direction: column; gap: 9px; overflow-y: auto; scrollbar-color: rgba(32,60,61,.24) transparent; scrollbar-width: thin; }
    .pos-right-rail .pos-panel { flex: 0 0 auto; padding: 15px; }
    .pos-right-rail label { display: block; margin: 0 0 5px; color: #53615f; font-size: .68rem; font-weight: 650; letter-spacing: 0; text-transform: none; }
    .pos-right-rail input, .pos-right-rail select { width: 100%; height: 40px; margin: 0; padding: 0 10px; border: 1px solid rgba(32,60,61,.15); border-radius: 9px; background: #fbfcfb; color: var(--text); font: inherit; font-size: .76rem; }
    .pos-discount-select-wrap { position: relative; z-index: 4; border-radius: 12px; }
    html.pos-js .pos-discount-native-select { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0,0,0,0) !important; clip-path: inset(50%) !important; white-space: nowrap !important; border: 0 !important; }
    .pos-discount-trigger { width: 100%; min-height: 48px; display: grid; grid-template-columns: minmax(0,1fr) 28px; align-items: center; gap: 10px; padding: 6px 9px 6px 12px; border: 1px solid rgba(32,60,61,.14); border-radius: 12px; background: #fff; color: var(--text); font: inherit; text-align: left; cursor: pointer; box-shadow: 0 2px 6px rgba(32,60,61,.045); transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease; }
    .pos-discount-trigger:hover { border-color: rgba(24,118,94,.36); box-shadow: 0 3px 9px rgba(32,60,61,.075); }
    .pos-discount-trigger:focus-visible, .pos-discount-select-wrap.is-open .pos-discount-trigger { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px rgba(24,118,94,.13), 0 3px 9px rgba(32,60,61,.07); }
    .pos-discount-trigger:disabled { cursor: wait; opacity: .72; background: #f2f6f3; }
    .pos-discount-trigger-copy { min-width: 0; display: grid; gap: 2px; }
    .pos-discount-trigger-title { overflow: hidden; color: #203C3D; font-size: .76rem; font-weight: 750; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
    .pos-discount-trigger-code { color: #687773; font-size: .63rem; font-weight: 650; line-height: 1.2; }
    .pos-discount-trigger-code:empty { display: none; }
    .pos-discount-chevron { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 9px; background: #edf5f0; color: #18765E; transition: transform .16s ease, background-color .16s ease; }
    .pos-discount-select-wrap.is-open .pos-discount-chevron { transform: rotate(180deg); background: #dceee3; }
    .pos-discount-chevron svg { width: 14px; height: 14px; }
    .pos-discount-options { position: fixed; top: 0; left: 0; z-index: 220; display: grid; gap: 3px; max-height: min(310px, 42vh); overflow-y: auto; padding: 5px; border: 1px solid rgba(32,60,61,.12); border-radius: 13px; background: #fff; box-shadow: 0 14px 30px rgba(32,60,61,.16); }
    .pos-discount-options[hidden] { display: none; }
    .pos-discount-option { width: 100%; min-height: 47px; display: grid; grid-template-columns: auto minmax(0,1fr) auto; align-items: center; gap: 9px; padding: 7px 9px; border: 0; border-radius: 9px; background: #fff; color: var(--text); font: inherit; text-align: left; cursor: pointer; transition: background-color .12s ease, color .12s ease; }
    .pos-discount-option:hover, .pos-discount-option:focus-visible { outline: none; background: #eef6f0; }
    .pos-discount-option[aria-selected="true"] { background: #e3f1e7; color: #145844; }
    .pos-discount-option-code { padding: 4px 6px; border-radius: 6px; background: #edf1ef; color: #203C3D; font-size: .59rem; font-weight: 800; letter-spacing: .015em; white-space: nowrap; }
    .pos-discount-option[aria-selected="true"] .pos-discount-option-code { background: #203C3D; color: #fff; }
    .pos-discount-option-main { min-width: 0; display: grid; gap: 2px; }
    .pos-discount-option-title { overflow: hidden; font-size: .72rem; font-weight: 700; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
    .pos-discount-option-detail { overflow: hidden; color: #6c7975; font-size: .61rem; line-height: 1.2; text-overflow: ellipsis; white-space: nowrap; }
    .pos-discount-option-check { width: 7px; height: 7px; border-radius: 50%; background: #18765E; opacity: 0; }
    .pos-discount-option[aria-selected="true"] .pos-discount-option-check { opacity: 1; }
    #discount-description:not(:empty) { margin-top: 7px; padding: 8px 10px; border: 1px solid rgba(24,118,94,.13); border-left: 3px solid var(--accent); border-radius: 9px; background: #f0f7f2; color: var(--interactive-ink); font-size: .69rem; font-weight: 650; line-height: 1.45; }
    .pos-field { margin-bottom: 10px; }
    .pos-customer-search { position: relative; }
    .pos-customer-results { position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 8; max-height: 190px; overflow: auto; border: 1px solid rgba(32,60,61,.12); border-radius: 10px; background: #fff; box-shadow: 0 10px 26px rgba(32,60,61,.14); }
    .pos-customer-results[hidden] { display: none; }
    .pos-customer-result { width: 100%; display: block; padding: 9px 10px; border: 0; border-bottom: 1px solid rgba(32,60,61,.07); background: #fff; color: var(--text); text-align: left; font: inherit; font-size: .74rem; cursor: pointer; }
    .pos-customer-result:hover, .pos-customer-result:focus { background: #f1f5f3; }
    #customer-loyalty-status { min-height: 22px; padding: 6px 8px; border-radius: 7px; background: #f5f7f5; color: #53615f; font-size: .7rem; line-height: 1.45; }
    .pos-pay-btn-group { display: flex; gap: 4px; margin: 0 0 8px; padding: 4px; border-radius: 9px; background: #f0f3f1; }
    .pos-payment-option { flex: 1; min-height: 35px; padding: 7px 5px; border: 0; border-radius: 7px; background: transparent; color: #596663; font-size: .7rem; font-weight: 650; letter-spacing: 0; text-transform: none; }
    .pos-payment-option:hover { background: rgba(255,255,255,.75); }
    .pos-payment-option.active { background: #fff; color: var(--text); box-shadow: 0 2px 6px rgba(32,60,61,.1); }
    .pos-payment-due { display: flex; justify-content: space-between; align-items: baseline; margin: 0 0 7px; padding: 0 1px; color: #657271; font-size: .72rem; }
    .pos-payment-due strong { color: var(--text); font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pos-payment-panel label { display: block; margin: 0 0 3px; color: #53615f; font-size: .65rem; font-weight: 650; }
    .pos-payment-panel input, .pos-payment-panel select { height: 35px; margin: 0; padding: 0 9px; border: 1px solid rgba(32,60,61,.15); border-radius: 8px; background: #fbfcfb; color: var(--text); font-size: .75rem; }
    .pos-alt-payment-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 5px 8px; margin-bottom: 5px; }
    .pos-alt-payment-fields > div { min-width: 0; }
    .pos-alt-payment-fields > div:first-child { grid-column: 1 / -1; }
    .pos-alt-payment-fields[hidden] { display: none; }
    .pos-payment-reference-field { display: flex; align-items: stretch; gap: 5px; }
    .pos-payment-reference-field input { min-width: 0; flex: 1 1 auto; }
    .pos-payment-visibility-toggle { flex: 0 0 auto; padding: 0 9px; border: 1px solid var(--rule); border-radius: 7px; background: var(--surface); color: var(--text); font: inherit; font-size: .7rem; font-weight: 700; }
    .pos-payment-visibility-toggle:hover { background: var(--surface-soft); }
    .pos-card-note { grid-column: 1 / -1; margin: -1px 0 4px; color: var(--muted); font-size: .68rem; line-height: 1.4; }
    .pos-cash-details-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin: 4px 0; }
    .pos-cash-field-label { color: #596663; font-size: .68rem; letter-spacing: 0; text-transform: none; }
    .pos-received-input { width: 128px !important; height: 35px !important; font-size: .84rem !important; font-weight: 700; font-variant-numeric: tabular-nums; }
    .pos-change-display { color: var(--text); font-size: .88rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .pos-cash-presets { display: flex; flex-wrap: wrap; gap: 5px; }
    .pos-cash-presets .pos-chip-btn { padding: 5px 8px; border: 1px solid rgba(32,60,61,.12); border-radius: 7px; background: #fff; color: #53615f; font-size: .66rem; }
    .pos-cash-presets .pos-chip-btn:hover { border-color: rgba(32,60,61,.3); color: var(--text); }
    .pos-cash-presets .pos-exact-amount-btn { border-color: rgba(32,60,61,.2); background: rgba(32,60,61,.07); color: var(--text); font-weight: 700; }
    .pos-sale-actions { display: grid; grid-template-columns: minmax(92px, .65fr) minmax(0, 1.5fr); gap: 6px; margin-top: 8px; }
    .pos-sale-actions .pos-complete-sale-btn { margin: 0; }
    .pos-hold-sale-btn { min-height: 38px; padding: 9px 10px; border: 1px solid rgba(32,60,61,.16); border-radius: 8px; background: #fff; color: var(--text); font-size: .74rem; font-weight: 700; cursor: pointer; transition: background .16s ease, border-color .16s ease; }
    .pos-hold-sale-btn:hover:not(:disabled) { border-color: rgba(32,60,61,.3); background: rgba(32,60,61,.06); }
    .pos-hold-sale-btn:disabled { color: #89918e; cursor: not-allowed; opacity: .55; }
    .pos-complete-sale-btn { width: 100%; padding: 10px 12px; border: 0; border-radius: 8px; background: var(--text); color: #fff; font-size: .78rem; font-weight: 700; letter-spacing: 0; text-transform: none; transition: background .16s ease, transform .16s ease; }
    .pos-complete-sale-btn:hover:not(:disabled) { background: #2b4d4e; transform: translateY(-1px); }
    .pos-complete-sale-btn:disabled { cursor: not-allowed; opacity: .46; }
    .pos-shortcuts-bar { margin: 0; padding: 1px 4px; gap: 10px; border: 0; border-radius: 0; box-shadow: none; background: transparent; color: #62706e; font-size: .64rem; }
    .pos-reprint-shortcut { color: #62706e; }
    .pos-shortcut-key { padding: 2px 5px; border: 1px solid rgba(32,60,61,.1); border-radius: 5px; background: #fff; color: #465351; font-size: .6rem; font-weight: 650; }
    .pos-divider { margin: 10px 0; border-color: rgba(32,60,61,.09); }
    .pos-container :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    @media (max-width: 520px) {
        .pos-held-modal-card { padding: 14px; }
        .pos-held-row { grid-template-columns: minmax(0, 1fr); }
        .pos-held-actions { justify-content: stretch; }
        .pos-held-actions button { flex: 1; }
        .pos-resume-choice-actions > button { flex: 1 1 100%; }
    }
    @container product-list (max-width: 420px) {
        .pos-results-list { grid-template-columns: minmax(0, 1fr); }
        .pos-product-row { min-height: 64px; flex-direction: row; align-items: center; gap: 9px; padding: 8px 9px; }
        .pos-product-info { gap: 3px; }
        .pos-product-name { -webkit-line-clamp: 2; font-size: .81rem; }
        .pos-product-actions { width: auto; flex: 0 0 auto; flex-direction: column; align-items: flex-end; gap: 4px; margin: 0; }
        .pos-product-price { font-size: .78rem; }
        .pos-add-product-btn { min-height: 28px; padding: 4px 8px; font-size: .67rem; }
    }
    @media (max-width: 1120px) {
        .pos-work-grid { grid-template-columns: minmax(0, 1fr) minmax(220px, 250px); }
        .pos-main-workspace { grid-template-columns: minmax(220px, .88fr) minmax(300px, 1.12fr); column-gap: 8px; }
        .pos-panel { padding: 10px; }
        .pos-transaction-heading { margin: -10px -10px 0; padding: 10px 12px; }
        .pos-transaction-summary { margin: 7px -10px -10px; padding: 8px 10px 10px; }
    }
    @media (max-width: 900px) {
        .main.main-pos { height: auto; min-height: calc(100dvh - 48px); overflow: visible; }
        .pos-container { height: auto; min-height: calc(100dvh - 64px); grid-template-rows: 36px auto 22px; }
        .pos-work-grid { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto auto; }
        .pos-main-workspace { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); grid-template-rows: minmax(340px, 55vh) auto; column-gap: 8px; row-gap: 0; }
        .pos-catalog-panel { grid-column: 1; grid-row: 1 / 3; }
        .pos-transaction-panel { grid-column: 2; grid-row: 1; }
        .pos-payment-panel { grid-column: 2; grid-row: 2; }
        .pos-right-rail { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-content: start; overflow: visible; }
    }
    @media (max-width: 600px) {
        .pos-work-grid, .pos-main-workspace { display: flex; flex-direction: column; }
        .pos-catalog-panel { min-height: 52vh; }
        .pos-transaction-panel { min-height: 48vh; border-radius: 13px 13px 0 0; }
        .pos-payment-panel { min-height: 0; border-radius: 0 0 13px 13px; }
        .pos-right-rail { display: flex; }
        .pos-top-right { display: grid; grid-template-columns: minmax(0, 1fr); justify-items: stretch; gap: 8px; }
        .pos-cashier-info { justify-content: flex-end; }
        .pos-header-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); justify-content: stretch; }
        .pos-header-action { width: 100%; min-width: 0; padding-inline: 8px; }
        .pos-header-shift { grid-column: 1 / -1; }
        .pos-cashier-info { font-size: .66rem; }
        .pos-shortcuts-bar { overflow-x: auto; flex-wrap: nowrap; white-space: nowrap; }
    }
</style>
@endpush

@section('content')
@php
    $employee = auth()->user()->employee;
    $cashierName = $employee ? $employee->fullName() : auth()->user()->username;
@endphp

<div class="pos-container">
    <div class="pos-top-bar">
        <div class="pos-top-left">
            <span class="pos-brand-tag">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Register
            </span>
            <span class="pos-register-badge">Register 01</span>
        </div>
        <div class="pos-top-right">
            <div class="pos-cashier-info">
                <span>{{ $cashierName }}</span>
                <span aria-hidden="true">·</span>
                <span class="pos-status-indicator" id="top-status-badge">
                    <span class="pos-status-dot-active" id="top-status-dot"></span>
                    <span id="top-status-text">ACTIVE</span>
                </span>
            </div>
            <div class="pos-header-actions" aria-label="Register actions">
                <button type="button" class="pos-header-action" id="held-list-btn" aria-haspopup="dialog" aria-controls="held-transactions-modal">
                    <span>Held</span><span class="pos-header-badge" id="held-count-badge">0</span>
                </button>
                <a class="pos-header-action pos-header-ewallet {{ ($pendingEwalletCount ?? 0) > 0 ? 'has-pending' : '' }}" href="{{ route('pos.pending-ewallet.index') }}" aria-label="Pending E-Wallet, {{ $pendingEwalletCount ?? 0 }} payments">
                    <span>Pending E-Wallet</span><span class="pos-header-badge" id="pending-ewallet-count-badge">{{ $pendingEwalletCount ?? 0 }}</span>
                </a>
                <a class="pos-header-action pos-header-ewallet {{ ($pendingCardCount ?? 0) > 0 ? 'has-pending' : '' }}" href="{{ route('pos.pending-card.index') }}" aria-label="Pending Card, {{ $pendingCardCount ?? 0 }} payments">
                    <span>Pending Card</span><span class="pos-header-badge" id="pending-card-count-badge">{{ $pendingCardCount ?? 0 }}</span>
                </a>
                <button type="button" class="pos-header-action pos-header-shift" id="drawer-toggle-btn">
                    <span class="pos-header-shift-copy">
                        <span id="drawer-toggle-label">Open Shift</span>
                        <span class="pos-header-shift-time" id="drawer-toggle-time"></span>
                    </span>
                    <span class="pos-header-shift-amount" id="drawer-toggle-amount"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="pos-work-grid">
      <div class="pos-main-workspace" aria-label="Register workspace">
        <section class="pos-panel pos-catalog-panel" aria-label="Product catalog">
            <div class="pos-section-title">Products</div>
            <div class="pos-search-box">
                <span class="pos-search-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
                <input id="pos-search-input" class="pos-search-input" type="text" placeholder="Scan barcode or type product name" autocomplete="off" autofocus>
            </div>
            @if (isset($categories) && $categories->count())
                <div class="pos-category-chips-bar" id="pos-category-chips">
                    <button type="button" class="pos-cat-chip active" data-category="all">All</button>
                    @foreach ($categories as $cat)
                        <button type="button" class="pos-cat-chip" data-category="{{ $cat->category_id }}">{{ $cat->category_name }}</button>
                    @endforeach
                </div>
            @endif
            <div class="pos-results-list" id="search-results-list"></div>
            @if ($products->isEmpty())
                <div class="pos-cart-empty">No products are available. Ask a manager to add products.</div>
            @endif
        </section>

        <section class="pos-panel pos-transaction-panel" aria-label="Live transaction">
            <div class="pos-transaction-heading">
                <div class="pos-section-title">Live transaction</div>
                <div class="pos-items-count-badge" id="cart-count-badge">0 items</div>
            </div>
            <div class="pos-table-wrap">
                <table class="pos-cart-table">
                    <thead><tr><th>Product</th><th class="tar">Qty</th><th class="tar">Price</th><th class="tar">Total</th><th></th></tr></thead>
                    <tbody id="cart-rows"></tbody>
                </table>
                <div id="cart-empty-notice" class="pos-cart-empty">
                    <span class="pos-empty-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16l-1 13H5L4 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/><path d="M9 13h6"/></svg>
                    </span>
                    <strong>Ready to scan</strong>
                    <span>Search for a product or scan its barcode to start.</span>
                </div>
            </div>
            <div class="pos-transaction-summary">
                <div class="pos-summary-row"><span>Subtotal</span><span id="subtotal-val">₱0.00</span></div>
                <div class="pos-summary-row pos-vat-exemption-row" id="vat-exemption-summary" hidden><span>VAT exemption</span><span id="vat-exemption-val">−₱0.00</span></div>
                <div class="pos-discount-adjustment" id="discount-adjustment" aria-live="polite">
                    <span id="discount-adjustment-label"></span>
                    <span class="pos-discount-adjustment-code" id="discount-adjustment-code"></span>
                    <span class="pos-discount-adjustment-amount" id="discount-adjustment-amount"></span>
                </div>
                <div class="pos-grand-total-row"><span class="pos-grand-label">Total</span><span class="pos-grand-amount" id="total-val">₱0.00</span></div>
            </div>
        </section>

            <section class="pos-panel pos-payment-panel" aria-label="Payment controls">
                <div class="pos-section-title" id="payment-box-title">Payment</div>
                <div class="pos-payment-due"><span>Amount due</span><strong id="payment-due-val">₱0.00</strong></div>
                <div class="pos-pay-btn-group">
                    <button type="button" class="pos-payment-option active" data-method="cash">Cash</button>
                    <button type="button" class="pos-payment-option" data-method="card">Card</button>
                    <button type="button" class="pos-payment-option" data-method="e-wallet">E-Wallet</button>
                </div>
                <div id="cash-payment-fields">
                    <div class="pos-cash-details-row"><label class="pos-cash-field-label" for="amount_paid_input">Cash received</label><input id="amount_paid_input" class="pos-received-input" type="number" min="0" step="0.01" value="0.00"></div>
                    <div class="pos-cash-details-row"><span class="pos-cash-field-label">Change</span><span class="pos-change-display" id="change-val">₱0.00</span></div>
                    <div class="pos-cash-presets" aria-label="Cash shortcuts">
                        <button type="button" class="pos-chip-btn pos-exact-amount-btn" id="exact-amount-btn">Exact amount</button><button type="button" class="pos-chip-btn" data-cash="500">₱500</button><button type="button" class="pos-chip-btn" data-cash="1000">₱1,000</button><button type="button" class="pos-chip-btn" data-cash="2000">₱2,000</button><button type="button" class="pos-chip-btn" data-cash="5000">₱5,000</button>
                    </div>
                </div>
                <div id="card-payment-fields" class="pos-alt-payment-fields" hidden>
                    <div><label for="card-provider-select">Card network</label><select id="card-provider-select" class="pos-select"><option value="Visa">Visa</option><option value="Mastercard">Mastercard</option><option value="BancNet">BancNet Debit</option><option value="JCB">JCB</option><option value="Other">Other Card</option></select></div>
                    <div><label for="card-approval-code">Terminal auth / approval code *</label><div class="pos-payment-reference-field"><input id="card-approval-code" type="text" maxlength="50" autocomplete="off" class="pos-customer-input" data-payment-reference-input><button type="button" class="pos-payment-visibility-toggle" data-payment-visibility-toggle aria-label="Hide Card approval code">Hide</button></div></div>
                    <div><label for="card-last4">Card last 4 digits (optional)</label><input id="card-last4" type="text" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" class="pos-customer-input"></div>
                    <p class="pos-card-note">The sale waits for a manager to compare the approval code and amount with the terminal record. Do not enter a full card number or CVV.</p>
                </div>
                <div id="ewallet-payment-fields" class="pos-alt-payment-fields" hidden>
                    <div><label for="ewallet-provider-select">E-wallet service</label><select id="ewallet-provider-select" class="pos-select"><option value="GCash">GCash</option><option value="Maya">Maya</option><option value="ShopeePay">ShopeePay</option><option value="GrabPay">GrabPay</option><option value="Other">Other E-Wallet</option></select></div>
                    <div><label for="ewallet-reference-no">Reference / transaction number *</label><div class="pos-payment-reference-field"><input id="ewallet-reference-no" type="text" maxlength="100" autocomplete="off" class="pos-customer-input" data-payment-reference-input><button type="button" class="pos-payment-visibility-toggle" data-payment-visibility-toggle aria-label="Hide E-Wallet reference">Hide</button></div></div>
                    <p class="pos-ewallet-note">This request stays pending. A manager must confirm the completed payment in the merchant app before a sale or receipt is created.</p>
                </div>
                <div class="pos-sale-actions">
                    <button type="button" class="pos-hold-sale-btn" id="hold-sale-btn" disabled>Hold <span class="pos-shortcut-key">F9</span></button>
                    <button type="button" class="pos-complete-sale-btn" id="submit-sale-btn" disabled>Complete sale</button>
                </div>
            </section>
      </div>

        <aside class="pos-right-rail" aria-label="Customer and customer actions">
            <section class="pos-panel">
                <div class="pos-section-title">Customer</div>
                <div class="pos-field pos-customer-search">
                    <label for="customer_id_input">Find customer</label>
                    <input id="customer_id_input" type="search" placeholder="Search ID, name, phone, or email" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="customer-search-results" aria-expanded="false">
                    <div id="customer-search-results" class="pos-customer-results" role="listbox" hidden></div>
                </div>
                <div id="customer-loyalty-status" aria-live="polite">Walk-in Customer</div>
                <hr class="pos-divider">
                <div class="pos-field">
                    <label for="discount-select-trigger">Discount</label>
                    <div class="pos-discount-select-wrap">
                    <button type="button" class="pos-discount-trigger" id="discount-select-trigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="discount-select-options" aria-describedby="discount-description">
                        <span class="pos-discount-trigger-copy">
                            <span class="pos-discount-trigger-title" id="discount-select-title">No discount</span>
                            <span class="pos-discount-trigger-code" id="discount-select-code"></span>
                        </span>
                        <span class="pos-discount-chevron" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    </button>
                    <div class="pos-discount-options" id="discount-select-options" role="listbox" aria-label="Available discounts" hidden></div>
                    <select id="discount_id_select" class="pos-discount-select pos-discount-native-select" aria-describedby="discount-description" aria-hidden="true" tabindex="-1">
                        <option value="">No discount</option>
                        @foreach ($discounts as $discount)
                            <option value="{{ $discount->discount_id }}" data-code="DISC-{{ str_pad((string) $discount->discount_id, 3, '0', STR_PAD_LEFT) }}" data-type="{{ $discount->specialPolicyType() ? 'percentage' : $discount->discount_type }}" data-value="{{ $discount->specialPolicyType() ? 20 : $discount->discount_value }}" data-special-type="{{ $discount->specialPolicyType() ?? '' }}">
                                DISC-{{ str_pad((string) $discount->discount_id, 3, '0', STR_PAD_LEFT) }} · {{ $discount->policyDisplayName() }}
                            </option>
                        @endforeach
                    </select>
                    </div>
                </div>
                <div class="pos-senior-proof" id="senior-pwd-proof" hidden>
                    <p id="senior-pwd-policy-note">Simplified policy: 20% of the VAT-exempt base across all products.</p>
                    <div><label for="senior_pwd_name">Full name on ID</label><input id="senior_pwd_name" type="text" maxlength="150" autocomplete="name"></div>
                    <div><label for="senior_pwd_id_number">Senior Citizen / PWD ID number</label><input id="senior_pwd_id_number" type="text" maxlength="80" autocomplete="off"></div>
                </div>
                <div id="discount-description" aria-live="polite"></div>
            </section>

        </aside>
    </div>

    <div class="pos-shortcuts-bar">
        <span><span class="pos-shortcut-key">F2</span> Search</span><span><span class="pos-shortcut-key">F4</span> Cash</span><span><span class="pos-shortcut-key">F8</span> Method</span><span><span class="pos-shortcut-key">F9</span> Hold</span><a class="pos-reprint-shortcut" href="{{ route('pos.reprint-last') }}"><span class="pos-shortcut-key">F10</span> Reprint last receipt</a><span><span class="pos-shortcut-key">Enter</span> Complete</span><span><span class="pos-shortcut-key">Esc</span> Close</span>
    </div>
</div>

{{-- Hidden Form to Submit Sale Data --}}
<form id="pos-store-form" method="POST" action="{{ route('pos.store') }}" style="display:none;">
    @csrf
    <input type="hidden" name="idempotency_key" id="store-idempotency-key">
    <input type="hidden" name="customer_id" id="store-customer-id">
    <input type="hidden" name="discount_id" id="store-discount-id">
    <input type="hidden" name="senior_pwd_type" id="store-senior-pwd-type">
    <input type="hidden" name="senior_pwd_name" id="store-senior-pwd-name">
    <input type="hidden" name="senior_pwd_id_number" id="store-senior-pwd-id-number">
    <input type="hidden" name="payment_method" id="store-payment-method" value="cash">
    <input type="hidden" name="reference_number" id="store-reference-number">
    <input type="hidden" name="payment_provider" id="store-payment-provider">
    <input type="hidden" name="card_last4" id="store-card-last4">
    <input type="hidden" name="amount_paid" id="store-amount-paid">
    <input type="hidden" name="manager_authorization_token" id="store-manager-auth-token">
    <input type="hidden" name="register_id" id="store-register-id">
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

{{-- MODAL: Held Transactions --}}
<div class="pos-modal-backdrop" id="held-transactions-modal" role="dialog" aria-modal="true" aria-labelledby="held-transactions-title">
    <div class="pos-modal-card pos-held-modal-card">
        <div class="pos-modal-header">
            <span class="pos-modal-title" id="held-transactions-title">Held transactions</span>
            <button type="button" class="pos-remove-link" onclick="closeModal('held-transactions-modal')" aria-label="Close held transactions">✕</button>
        </div>
        <p class="pos-held-modal-copy">Held carts stay on this register for up to 24 hours and do not reserve stock.</p>
        <div class="pos-held-list" id="held-transactions-list"></div>
        <div class="pos-held-empty" id="held-transactions-empty">No held transactions</div>
    </div>
</div>

{{-- MODAL: Resolve the live cart before resuming a held transaction --}}
<div class="pos-modal-backdrop" id="resume-held-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="resume-held-confirm-title">
    <div class="pos-modal-card pos-held-modal-card">
        <div class="pos-modal-header">
            <span class="pos-modal-title" id="resume-held-confirm-title">Resolve the live transaction</span>
            <button type="button" class="pos-remove-link" onclick="closeModal('resume-held-confirm-modal')" aria-label="Cancel held transaction resume">✕</button>
        </div>
        <p class="pos-held-modal-copy">Choose what to do with the current cart before resuming the held transaction.</p>
        <div class="pos-resume-choice-actions">
            <button type="button" class="btn btn-secondary" id="cancel-held-resume-btn">Cancel</button>
            <button type="button" class="btn btn-danger" id="discard-live-and-resume-btn">Discard current &amp; resume</button>
            <button type="button" class="btn" id="hold-live-and-resume-btn">Hold current &amp; resume</button>
        </div>
    </div>
</div>

<x-manager-pin-modal :is-manager="auth()->user()->hasRole('Manager')" />
@endsection

@push('scripts')
<script>
    const products = @json($productsJson ?? []);
    const vatRate = Number(@json($vatRate ?? 0.12));
    const configVatEnabled = @json((bool) config('vat.enabled', true));
    const reprintLastUrl = @json(route('pos.reprint-last'));
    const cart = [];
    const registerId = document.querySelector('.pos-register-badge')?.textContent.trim() || 'Register 01';
    const heldCashierName = @json($cashierName);
    const heldCashierId = @json((string) auth()->id());
    const checkoutKeyStorageKey = 'pos.checkout-idempotency.v1.' + heldCashierId;
    const heldStorageKey = 'pos.held-transactions.v1.' + registerId.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const holdExpiryMs = 24 * 60 * 60 * 1000;
    let pendingResumeHoldId = null;
    let activeCheckoutIdempotencyKey = null;
    let checkoutSubmissionStarted = false;

    function createCheckoutUuid() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();

        const bytes = new Uint8Array(16);
        if (window.crypto?.getRandomValues) {
            window.crypto.getRandomValues(bytes);
            bytes[6] = (bytes[6] & 0x0f) | 0x40;
            bytes[8] = (bytes[8] & 0x3f) | 0x80;
            const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
            return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
            const value = Math.floor(Math.random() * 16);
            return (character === 'x' ? value : (value & 0x3) | 0x8).toString(16);
        });
    }

    function setCheckoutIdempotencyKey(key) {
        activeCheckoutIdempotencyKey = key || createCheckoutUuid();
        try {
            window.sessionStorage.setItem(checkoutKeyStorageKey, activeCheckoutIdempotencyKey);
        } catch (error) {
            console.warn('Checkout retry key could not be persisted in this browser tab.', error);
        }

        return activeCheckoutIdempotencyKey;
    }

    function getCheckoutIdempotencyKey() {
        if (activeCheckoutIdempotencyKey) return activeCheckoutIdempotencyKey;

        try {
            activeCheckoutIdempotencyKey = window.sessionStorage.getItem(checkoutKeyStorageKey);
        } catch (error) {
            activeCheckoutIdempotencyKey = null;
        }

        return setCheckoutIdempotencyKey(activeCheckoutIdempotencyKey || createCheckoutUuid());
    }

    function clearCheckoutIdempotencyKey(expectedKey = null) {
        try {
            const storedKey = window.sessionStorage.getItem(checkoutKeyStorageKey);
            if (expectedKey === null || storedKey === expectedKey) {
                window.sessionStorage.removeItem(checkoutKeyStorageKey);
            }
        } catch (error) {
            console.warn('Checkout retry key could not be cleared from this browser tab.', error);
        }

        if (expectedKey === null || activeCheckoutIdempotencyKey === expectedKey) {
            activeCheckoutIdempotencyKey = null;
        }
    }

    // Elements
    const cartRows = document.getElementById('cart-rows');
    const cartScrollContainer = document.querySelector('.pos-table-wrap');
    const cartEmptyNotice = document.getElementById('cart-empty-notice');
    const cartCountBadge = document.getElementById('cart-count-badge');
    const searchInput = document.getElementById('pos-search-input');
    const searchResultsList = document.getElementById('search-results-list');
    const subtotalVal = document.getElementById('subtotal-val');
    const totalVal = document.getElementById('total-val');
    const paymentDueVal = document.getElementById('payment-due-val');
    const amountPaidInput = document.getElementById('amount_paid_input');
    const changeVal = document.getElementById('change-val');
    const submitSaleBtn = document.getElementById('submit-sale-btn');
    const holdSaleBtn = document.getElementById('hold-sale-btn');
    const heldCountBadge = document.getElementById('held-count-badge');
    const paymentBoxTitle = document.getElementById('payment-box-title');
    const cashPaymentFields = document.getElementById('cash-payment-fields');
    const discountSelect = document.getElementById('discount_id_select');
    const discountSelectWrap = document.querySelector('.pos-discount-select-wrap');
    const discountTrigger = document.getElementById('discount-select-trigger');
    const discountTriggerTitle = document.getElementById('discount-select-title');
    const discountTriggerCode = document.getElementById('discount-select-code');
    const discountOptionsList = document.getElementById('discount-select-options');
    const discountAdjustment = document.getElementById('discount-adjustment');
    const discountAdjustmentLabel = document.getElementById('discount-adjustment-label');
    const discountAdjustmentAmount = document.getElementById('discount-adjustment-amount');
    const discountAdjustmentCode = document.getElementById('discount-adjustment-code');
    const discountDescription = document.getElementById('discount-description');
    const vatExemptionSummary = document.getElementById('vat-exemption-summary');
    const vatExemptionValue = document.getElementById('vat-exemption-val');
    const seniorPwdProof = document.getElementById('senior-pwd-proof');
    const seniorPwdTypeInput = document.getElementById('store-senior-pwd-type');
    const seniorPwdNameInput = document.getElementById('senior_pwd_name');
    const seniorPwdIdInput = document.getElementById('senior_pwd_id_number');

    let selectedDiscountAmount = 0;
    let vatExemptionAmount = 0;
    let visibleSeniorPwdType = '';
    let selectedCustomerId = '';
    let authorizedDiscountId = '';
    let discountAuthorizationToken = '';
    let currentPaymentMethod = 'cash';
    let currentDrawer = null;

    document.documentElement.classList.add('pos-js');

    function getDiscountTitle(option) {
        if (!option?.value) return 'No discount';
        return option.textContent.trim().replace(/^DISC-\d+\s*[·-]\s*/, '');
    }

    function getDiscountDetail(option) {
        if (!option?.value) return 'Standard transaction pricing';
        if (option.dataset.specialType) return '20% · VAT-exempt base';
        const value = Number(option.dataset.value || 0);
        return option.dataset.type === 'percentage'
            ? `${value}% discount`
            : `${formatMoney(value)} discount`;
    }

    function syncDiscountDropdown() {
        const selectedOption = discountSelect.selectedOptions[0];
        discountTriggerTitle.textContent = getDiscountTitle(selectedOption);
        discountTriggerCode.textContent = selectedOption?.dataset.code || '';
        discountTrigger.disabled = discountSelect.disabled;
        discountOptionsList.querySelectorAll('[role="option"]').forEach(optionButton => {
            const selected = optionButton.dataset.discountId === discountSelect.value;
            optionButton.setAttribute('aria-selected', String(selected));
        });
    }

    function closeDiscountOptions(returnFocus = false) {
        discountOptionsList.hidden = true;
        discountTrigger.setAttribute('aria-expanded', 'false');
        discountSelectWrap.classList.remove('is-open');
        discountOptionsList.style.removeProperty('top');
        discountOptionsList.style.removeProperty('left');
        discountOptionsList.style.removeProperty('width');
        discountOptionsList.style.removeProperty('max-height');
        if (returnFocus) discountTrigger.focus();
    }

    function positionDiscountOptions() {
        if (discountOptionsList.hidden) return;
        const triggerRect = discountTrigger.getBoundingClientRect();
        const viewportPadding = 8;
        const gap = 6;
        const maxHeight = Math.min(310, Math.floor(window.innerHeight * 0.42));
        const desiredHeight = Math.min(discountOptionsList.scrollHeight, maxHeight);
        const roomBelow = Math.max(0, window.innerHeight - triggerRect.bottom - viewportPadding - gap);
        const roomAbove = Math.max(0, triggerRect.top - viewportPadding - gap);
        const openAbove = roomBelow < desiredHeight && roomAbove > roomBelow;
        const availableHeight = Math.max(80, Math.min(maxHeight, openAbove ? roomAbove : roomBelow));
        const menuHeight = Math.min(discountOptionsList.scrollHeight, availableHeight);
        const menuWidth = Math.min(triggerRect.width, window.innerWidth - viewportPadding * 2);
        const menuLeft = Math.max(viewportPadding, Math.min(triggerRect.left, window.innerWidth - menuWidth - viewportPadding));
        const menuTop = openAbove
            ? Math.max(viewportPadding, triggerRect.top - gap - menuHeight)
            : Math.min(window.innerHeight - menuHeight - viewportPadding, triggerRect.bottom + gap);

        discountOptionsList.style.top = `${menuTop}px`;
        discountOptionsList.style.left = `${menuLeft}px`;
        discountOptionsList.style.width = `${menuWidth}px`;
        discountOptionsList.style.maxHeight = `${availableHeight}px`;
    }

    function openDiscountOptions() {
        if (discountTrigger.disabled) return;
        syncDiscountDropdown();
        discountOptionsList.hidden = false;
        discountTrigger.setAttribute('aria-expanded', 'true');
        discountSelectWrap.classList.add('is-open');
        positionDiscountOptions();
        const selectedOption = discountOptionsList.querySelector('[aria-selected="true"]');
        (selectedOption || discountOptionsList.querySelector('[role="option"]'))?.focus();
        requestAnimationFrame(positionDiscountOptions);
    }

    function buildDiscountOptions() {
        discountOptionsList.replaceChildren();
        Array.from(discountSelect.options).forEach(discountOption => {
            const optionButton = document.createElement('button');
            optionButton.type = 'button';
            optionButton.className = 'pos-discount-option';
            optionButton.setAttribute('role', 'option');
            optionButton.setAttribute('aria-selected', String(discountOption.value === discountSelect.value));
            optionButton.dataset.discountId = discountOption.value;

            const code = document.createElement('span');
            code.className = 'pos-discount-option-code';
            code.textContent = discountOption.dataset.code || 'CLEAR';

            const copy = document.createElement('span');
            copy.className = 'pos-discount-option-main';
            const title = document.createElement('span');
            title.className = 'pos-discount-option-title';
            title.textContent = getDiscountTitle(discountOption);
            const detail = document.createElement('span');
            detail.className = 'pos-discount-option-detail';
            detail.textContent = getDiscountDetail(discountOption);
            copy.append(title, detail);

            const selectedMark = document.createElement('span');
            selectedMark.className = 'pos-discount-option-check';
            selectedMark.setAttribute('aria-hidden', 'true');
            optionButton.append(code, copy, selectedMark);
            optionButton.addEventListener('click', () => {
                closeDiscountOptions();
                if (discountSelect.value === discountOption.value) {
                    syncDiscountDropdown();
                    discountTrigger.focus();
                    return;
                }
                discountSelect.value = discountOption.value;
                discountSelect.dispatchEvent(new Event('change', { bubbles: true }));
                discountTrigger.focus();
            });
            optionButton.addEventListener('keydown', event => {
                const options = Array.from(discountOptionsList.querySelectorAll('[role="option"]'));
                const currentIndex = options.indexOf(optionButton);
                let nextIndex = null;
                if (event.key === 'ArrowDown') nextIndex = Math.min(currentIndex + 1, options.length - 1);
                else if (event.key === 'ArrowUp') nextIndex = Math.max(currentIndex - 1, 0);
                else if (event.key === 'Home') nextIndex = 0;
                else if (event.key === 'End') nextIndex = options.length - 1;
                else if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    closeDiscountOptions(true);
                    return;
                }
                if (nextIndex !== null) {
                    event.preventDefault();
                    options[nextIndex]?.focus();
                }
            });
            discountOptionsList.appendChild(optionButton);
        });
        syncDiscountDropdown();
    }

    discountSelect.addEventListener('change', syncDiscountDropdown);
    discountTrigger.addEventListener('click', () => {
        if (discountOptionsList.hidden) openDiscountOptions();
        else closeDiscountOptions(true);
    });
    discountTrigger.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            openDiscountOptions();
        } else if (event.key === 'Escape' && !discountOptionsList.hidden) {
            event.preventDefault();
            closeDiscountOptions();
        }
    });
    document.addEventListener('click', event => {
        if (!discountSelectWrap.contains(event.target)) closeDiscountOptions();
    });
    window.addEventListener('resize', positionDiscountOptions);
    document.querySelector('.pos-right-rail')?.addEventListener('scroll', positionDiscountOptions, { passive: true });
    discountSelectWrap.addEventListener('focusout', event => {
        if (!discountSelectWrap.contains(event.relatedTarget)) closeDiscountOptions();
    });
    buildDiscountOptions();

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

    function isWeightProduct(product) {
        return product.unit_of_measure === 'kg' || /\bper\s*kg\b/i.test(product.name || '');
    }

    function formatQuantity(quantity, isWeight) {
        return isWeight
            ? Number(quantity).toFixed(3).replace(/\.?0+$/, '')
            : String(Math.round(Number(quantity)));
    }

    function formatStock(stock) {
        return Number(stock || 0).toLocaleString('en-US', { maximumFractionDigits: 3 });
    }

    function loadHeldState() {
        let state = { sequence: 0, items: [] };
        try {
            const parsed = JSON.parse(localStorage.getItem(heldStorageKey) || '{}');
            state = {
                sequence: Number.isInteger(parsed.sequence) && parsed.sequence >= 0 ? parsed.sequence : 0,
                items: Array.isArray(parsed.items) ? parsed.items : []
            };
        } catch (error) {
            console.warn('Could not read held transactions from this register.', error);
        }

        const now = Date.now();
        const validItems = state.items.filter(hold => {
            if (!hold || hold.registerId !== registerId || !Array.isArray(hold.items)) return false;
            const expiresAt = Date.parse(hold.expiresAt || '');
            return Number.isFinite(expiresAt) && expiresAt > now;
        });
        if (validItems.length !== state.items.length) {
            state.items = validItems;
            try {
                localStorage.setItem(heldStorageKey, JSON.stringify(state));
            } catch (error) {
                console.warn('Could not prune expired held transactions.', error);
            }
        }
        if (heldCountBadge) heldCountBadge.textContent = String(state.items.length);
        return state;
    }

    function saveHeldState(state) {
        try {
            localStorage.setItem(heldStorageKey, JSON.stringify(state));
            if (heldCountBadge) heldCountBadge.textContent = String(state.items.length);
            return true;
        } catch (error) {
            console.error('Could not save held transactions on this register.', error);
            return false;
        }
    }

    function renderHeldTransactions() {
        const list = document.getElementById('held-transactions-list');
        const empty = document.getElementById('held-transactions-empty');
        const state = loadHeldState();
        list.replaceChildren();
        empty.hidden = state.items.length > 0;

        state.items.forEach(hold => {
            const row = document.createElement('article');
            row.className = 'pos-held-row';

            const main = document.createElement('div');
            main.className = 'pos-held-main';
            const number = document.createElement('strong');
            number.className = 'pos-held-number';
            number.textContent = `${hold.holdNumber || 'Held transaction'} · ${hold.registerId}`;
            const meta = document.createElement('span');
            meta.className = 'pos-held-meta';
            meta.textContent = `${new Date(hold.heldAt).toLocaleString()} · ${hold.items.length} ${hold.items.length === 1 ? 'item' : 'items'} · ${hold.cashierName || 'Unknown cashier'}`;
            main.append(number, meta);

            const total = document.createElement('strong');
            total.className = 'pos-held-total';
            total.textContent = formatMoney(hold.total);

            const actions = document.createElement('div');
            actions.className = 'pos-held-actions';
            const resume = document.createElement('button');
            resume.type = 'button';
            resume.className = 'btn';
            resume.textContent = 'Resume';
            resume.addEventListener('click', () => requestResumeHeldTransaction(hold.id));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-danger';
            remove.textContent = 'Remove';
            remove.addEventListener('click', () => removeHeldTransaction(hold.id));

            actions.append(resume, remove);
            row.append(main, total, actions);
            list.appendChild(row);
        });
    }

    function holdCurrentTransaction() {
        if (cart.length === 0) return false;

        const state = loadHeldState();
        const heldAt = new Date();
        state.sequence += 1;
        const discountOption = discountSelect.selectedOptions[0];
        const hold = {
            id: `${heldAt.getTime().toString(36)}-${Math.random().toString(36).slice(2, 9)}`,
            holdNumber: `HLD-${String(state.sequence).padStart(4, '0')}`,
            registerId,
            cashierId: heldCashierId,
            cashierName: heldCashierName,
            heldAt: heldAt.toISOString(),
            expiresAt: new Date(heldAt.getTime() + holdExpiryMs).toISOString(),
            items: cart.map(item => ({ productId: item.id, quantity: item.qty })),
            customerId: selectedCustomerId || '',
            customerSearchValue: customerInput.value,
            discountId: discountSelect.value,
            discountCode: discountOption?.dataset.code || '',
            discountName: discountOption?.textContent.trim() || '',
            discountAmount: selectedDiscountAmount,
            seniorPwdType: discountOption?.dataset.specialType || '',
            seniorPwdName: seniorPwdNameInput.value.trim(),
            seniorPwdIdNumber: seniorPwdIdInput.value.trim(),
            idempotencyKey: getCheckoutIdempotencyKey(),
            total: parseNumeric(totalVal.textContent)
        };

        state.items.unshift(hold);
        if (!saveHeldState(state)) {
            alert('This browser could not save the held transaction. The current cart is still open.');
            return false;
        }

        resetLiveTransaction();
        return true;
    }

    function resetLiveTransaction() {
        cart.splice(0, cart.length);
        clearCheckoutIdempotencyKey();
        selectedCustomerId = '';
        customerInput.value = '';
        syncCustomerDisplay(null);
        discountSelect.value = '';
        syncDiscountDropdown();
        seniorPwdTypeInput.value = '';
        seniorPwdNameInput.value = '';
        seniorPwdIdInput.value = '';
        syncSeniorPwdProof();
        authorizedDiscountId = '';
        discountAuthorizationToken = '';
        amountPaidInput.value = '0.00';
        if (cardApprovalCode) cardApprovalCode.value = '';
        if (document.getElementById('card-last4')) document.getElementById('card-last4').value = '';
        if (ewalletReferenceNo) ewalletReferenceNo.value = '';

        if (currentPaymentMethod !== 'cash') {
            document.querySelector('.pos-payment-option[data-method="cash"]')?.click();
        } else {
            renderCart();
        }
        renderSearchResults(searchInput.value);
    }

    async function removeHeldTransaction(holdId) {
        const state = loadHeldState();
        const hold = state.items.find(item => item.id === holdId);
        if (!hold || !window.confirm(`Remove ${hold.holdNumber}? This held transaction will be deleted from this register.`)) return;

        const authorization = await window.requestManagerAuthorization({
            action: 'held_transaction_delete',
            details: { hold_number: hold.holdNumber, item_count: hold.items.length, total: hold.total },
            requiresReason: true
        });
        if (!authorization) return;

        state.items = state.items.filter(item => item.id !== holdId);
        if (!saveHeldState(state)) {
            alert('The held transaction could not be removed from this browser.');
            return;
        }
        renderHeldTransactions();
    }

    function requestResumeHeldTransaction(holdId) {
        if (cart.length > 0) {
            pendingResumeHoldId = holdId;
            openModal('resume-held-confirm-modal');
            return;
        }
        resumeHeldTransaction(holdId);
    }

    async function refreshProductStock() {
        const response = await fetch(@json(route('pos.stock')), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        if (!response.ok) throw new Error('Stock could not be refreshed.');
        const payload = await response.json();
        if (!payload.stocks || typeof payload.stocks !== 'object') throw new Error('Stock data was unavailable.');

        products.forEach(product => {
            const latest = payload.stocks[String(product.id)];
            if (latest) {
                product.stock = Number(latest.stock);
                product.unit_of_measure = latest.unit_of_measure || product.unit_of_measure || 'piece';
            }
        });
        renderSearchResults(searchInput.value);
        return payload.stocks;
    }

    async function resumeHeldTransaction(holdId) {
        const state = loadHeldState();
        const hold = state.items.find(item => item.id === holdId);
        if (!hold) {
            alert('That held transaction is no longer available.');
            renderHeldTransactions();
            return;
        }

        try {
            await refreshProductStock();
        } catch (error) {
            console.error(error);
            alert('Current stock could not be verified. The held transaction remains saved; try again when the register is online.');
            return;
        }

        const resumedCustomer = hold.customerId
            ? await findActivePosCustomer(hold.customerId)
            : null;

        const resumedItems = [];
        const remainingByProduct = new Map();
        const adjustments = [];
        hold.items.forEach(heldItem => {
            const productId = Number(heldItem.productId);
            const product = products.find(item => Number(item.id) === productId);
            const savedQuantity = Number(heldItem.quantity);
            if (!product || !Number.isFinite(savedQuantity) || savedQuantity <= 0) {
                adjustments.push(`An item is no longer available and was skipped.`);
                return;
            }

            const weightItem = isWeightProduct(product);
            const remaining = remainingByProduct.has(productId) ? remainingByProduct.get(productId) : product.stock;
            const availableQuantity = weightItem
                ? Math.floor((Math.max(0, remaining) + 0.000001) * 1000) / 1000
                : Math.floor(Math.max(0, remaining) + 0.000001);
            const requestedQuantity = weightItem
                ? Math.round(savedQuantity * 1000) / 1000
                : Math.round(savedQuantity);
            const quantity = Math.min(requestedQuantity, availableQuantity);
            remainingByProduct.set(productId, remaining - quantity);

            if (quantity < requestedQuantity) {
                adjustments.push(`${product.name}: ${formatQuantity(requestedQuantity, weightItem)} → ${formatQuantity(quantity, weightItem)} available`);
            }
            if (quantity <= 0) return;

            const existing = resumedItems.find(item => item.id === product.id);
            if (existing) {
                existing.qty += quantity;
            } else {
                resumedItems.push({
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    qty: quantity,
                    unit_of_measure: product.unit_of_measure || 'piece'
                });
            }
        });

        if (resumedItems.length === 0) {
            alert('None of the held items are currently available. The held transaction remains saved.');
            return;
        }

        const savedDiscountId = hold.discountId && Array.from(discountSelect.options).some(option => option.value === String(hold.discountId))
            ? String(hold.discountId)
            : '';
        let resumeDiscountAuthorizationToken = '';
        if (savedDiscountId) {
            if (authorizedDiscountId === savedDiscountId && discountAuthorizationToken) {
                resumeDiscountAuthorizationToken = discountAuthorizationToken;
            } else {
                const option = Array.from(discountSelect.options).find(item => item.value === savedDiscountId);
                const authorization = await window.requestManagerAuthorization({
                    action: 'discount_apply',
                    details: {
                        discount_id: Number(savedDiscountId),
                        discount_name: option?.textContent.trim() || '',
                        discount_amount: Number(hold.discountAmount || 0)
                    }
                });
                if (!authorization) return;
                resumeDiscountAuthorizationToken = authorization.token || '';
            }
        }

        state.items = state.items.filter(item => item.id !== holdId);
        if (!saveHeldState(state)) {
            alert('The held transaction could not be removed from storage. It remains available to resume.');
            return;
        }

        cart.splice(0, cart.length, ...resumedItems);
        setCheckoutIdempotencyKey(hold.idempotencyKey || createCheckoutUuid());
        discountSelect.value = savedDiscountId;
        syncDiscountDropdown();
        authorizedDiscountId = savedDiscountId;
        discountAuthorizationToken = resumeDiscountAuthorizationToken;
        syncSeniorPwdProof();
        seniorPwdNameInput.value = hold.seniorPwdName || '';
        seniorPwdIdInput.value = hold.seniorPwdIdNumber || '';
        if (hold.discountId && !discountSelect.value) adjustments.push('The saved discount is no longer available and was cleared.');

        const customer = resumedCustomer;
        if (customer) {
            customerInput.value = `${customer.name} · #${customer.id}`;
            syncCustomerDisplay(customer);
        } else {
            selectedCustomerId = '';
            syncCustomerDisplay(null);
            customerInput.value = hold.customerSearchValue || '';
            if (hold.customerId) adjustments.push('The saved customer could not be restored; Walk-in Customer is selected.');
        }

        amountPaidInput.value = '0.00';
        if (currentPaymentMethod !== 'cash') {
            document.querySelector('.pos-payment-option[data-method="cash"]')?.click();
        } else {
            renderCart();
        }
        closeModal('resume-held-confirm-modal');
        closeModal('held-transactions-modal');
        pendingResumeHoldId = null;
        if (adjustments.length) alert(`Stock or saved data changed while this cart was held:\n${adjustments.join('\n')}`);
    }

    function parseNumeric(val) {
        if (!val) return 0;
        return parseFloat(String(val).replace(/[^0-9.-]+/g, '')) || 0;
    }

    function syncSeniorPwdProof() {
        const specialType = discountSelect.selectedOptions[0]?.dataset.specialType || '';
        if (specialType !== visibleSeniorPwdType) {
            seniorPwdNameInput.value = '';
            seniorPwdIdInput.value = '';
        }
        visibleSeniorPwdType = specialType;
        seniorPwdTypeInput.value = specialType;
        seniorPwdProof.hidden = !specialType;
        seniorPwdNameInput.required = Boolean(specialType);
        seniorPwdIdInput.required = Boolean(specialType);
    }

    // Render Cart
    function renderCart({ scrollToLatest = false, highlightProductId = null, revealHighlightedIfFollowing = false } = {}) {
        const wasFollowingLatest = cartScrollContainer
            ? cartScrollContainer.scrollHeight - cartScrollContainer.clientHeight - cartScrollContainer.scrollTop <= 32
            : true;
        cartRows.innerHTML = '';
        if (cart.length === 0) {
            cartEmptyNotice.style.display = 'grid';
            cartCountBadge.textContent = '0 items';
        } else {
            cartEmptyNotice.style.display = 'none';
            cartCountBadge.textContent = cart.length + (cart.length === 1 ? ' item' : ' items');
        }

        let subtotal = 0;
        cart.forEach((item, index) => {
            const lineTotal = item.price * item.qty;
            subtotal += lineTotal;

            const tr = document.createElement('tr');
            tr.dataset.productId = String(item.id);

            // Product column
            const tdProduct = document.createElement('td');
            const pName = document.createElement('div');
            pName.className = 'pos-cart-product-name';
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

            const quantityInput = document.createElement('input');
            const weightItem = isWeightProduct(item);
            quantityInput.type = 'number';
            quantityInput.className = 'pos-qty-input';
            quantityInput.min = weightItem ? '0.001' : '1';
            quantityInput.step = weightItem ? '0.001' : '1';
            quantityInput.value = formatQuantity(item.qty, weightItem);
            quantityInput.setAttribute('aria-label', `Quantity for ${item.name}`);
            quantityInput.dataset.productId = String(item.id);
            quantityInput.addEventListener('change', () => {
                const requestedQty = Number(quantityInput.value);
                const hasTooManyDecimals = weightItem && Math.abs(requestedQty * 1000 - Math.round(requestedQty * 1000)) > 0.000001;
                if (!Number.isFinite(requestedQty) || requestedQty <= 0 || hasTooManyDecimals || (!weightItem && !Number.isInteger(requestedQty))) {
                    playBeep('error');
                    alert(weightItem ? 'Enter a weight greater than zero, up to three decimal places.' : 'Enter a whole-number quantity greater than zero.');
                    quantityInput.value = formatQuantity(item.qty, weightItem);
                    return;
                }
                setCartQuantity(item.id, requestedQty, true);
            });

            const btnPlus = document.createElement('button');
            btnPlus.type = 'button';
            btnPlus.className = 'pos-qty-btn';
            btnPlus.textContent = '+';
            btnPlus.onclick = () => updateQty(item.id, 1);

            qtyGroup.append(btnMinus, quantityInput, btnPlus);
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

        // Discount preview. Checkout remains authoritative.
        const selectedOption = discountSelect.selectedOptions[0];
        const discountValue = selectedOption?.value ? parseFloat(selectedOption.dataset.value || '0') : 0;
        const specialType = selectedOption?.dataset.specialType || '';
        const specialBase = specialType
            ? Math.round((configVatEnabled ? subtotal / (1 + vatRate) : subtotal) * 100) / 100
            : subtotal;
        vatExemptionAmount = specialType ? Math.max(0, Math.round((subtotal - specialBase) * 100) / 100) : 0;
        const discountRaw = specialType
            ? specialBase * 0.20
            : (selectedOption?.dataset.type === 'percentage' ? subtotal * discountValue / 100 : discountValue);
        selectedDiscountAmount = Math.min(specialType ? specialBase : subtotal, Math.max(0, Math.round(discountRaw * 100) / 100));
        const total = Math.max(0, Math.round(((specialType ? specialBase : subtotal) - selectedDiscountAmount) * 100) / 100);
        subtotalVal.textContent = formatMoney(subtotal);
        vatExemptionSummary.hidden = !specialType || vatExemptionAmount <= 0;
        vatExemptionValue.textContent = '−' + formatMoney(vatExemptionAmount);
        discountAdjustment.classList.toggle('active', selectedDiscountAmount > 0);
        if (selectedDiscountAmount > 0 && selectedOption) {
            const discountName = selectedOption.textContent.trim().replace(/^DISC-\d+\s*[·-]\s*/, '');
            discountAdjustmentLabel.textContent = `Discount: ${discountName}`;
            discountAdjustmentCode.textContent = `Code: ${selectedOption.dataset.code || 'Discount #' + selectedOption.value}`;
            discountAdjustmentAmount.textContent = '−' + formatMoney(selectedDiscountAmount);
        } else {
            discountAdjustmentLabel.textContent = '';
            discountAdjustmentCode.textContent = '';
            discountAdjustmentAmount.textContent = '';
        }
        if (selectedOption?.value) {
            if (specialType) {
                discountDescription.textContent = `Simplified 20% policy · VAT-exempt base ${formatMoney(specialBase)} · VAT removed ${formatMoney(vatExemptionAmount)}.`;
            } else {
                const typeLabel = selectedOption.dataset.type === 'percentage'
                    ? `${discountValue}% off`
                    : `${formatMoney(discountValue)} off`;
                discountDescription.textContent = `${typeLabel} · saving ${formatMoney(selectedDiscountAmount)}`;
            }
        } else {
            discountDescription.textContent = '';
        }
        totalVal.textContent = formatMoney(total);
        paymentDueVal.textContent = formatMoney(total);

        const cashTendered = parseNumeric(amountPaidInput.value);
        submitSaleBtn.disabled = checkoutSubmissionStarted || cart.length === 0 || (currentPaymentMethod === 'cash' && cashTendered + 0.000001 < total);
        holdSaleBtn.disabled = cart.length === 0;

        // Update Change
        if (currentPaymentMethod === 'cash') {
            const change = Math.max(0, cashTendered - total);
            changeVal.textContent = formatMoney(change);
        } else {
            changeVal.textContent = '₱0.00';
        }

        if (highlightProductId !== null) {
            const highlightedRow = Array.from(cartRows.rows).find(row => row.dataset.productId === String(highlightProductId));
            if (highlightedRow) {
                highlightedRow.classList.add('pos-cart-row-highlight');
                window.setTimeout(() => highlightedRow.classList.remove('pos-cart-row-highlight'), 1000);

                if (revealHighlightedIfFollowing && wasFollowingLatest && cartScrollContainer) {
                    window.requestAnimationFrame(() => {
                        const rowRect = highlightedRow.getBoundingClientRect();
                        const containerRect = cartScrollContainer.getBoundingClientRect();
                        const scrollDelta = rowRect.bottom > containerRect.bottom
                            ? rowRect.bottom - containerRect.bottom
                            : rowRect.top < containerRect.top
                                ? rowRect.top - containerRect.top
                                : 0;
                        if (scrollDelta !== 0) cartScrollContainer.scrollBy({ top: scrollDelta, behavior: 'smooth' });
                    });
                }
            }
        }

        if (scrollToLatest && cartScrollContainer) {
            window.requestAnimationFrame(() => cartScrollContainer.scrollTo({
                top: cartScrollContainer.scrollHeight,
                behavior: 'smooth'
            }));
        }
    }

    function updateQty(productId, delta) {
        const item = cart.find(i => i.id === productId);
        if (!item) return;
        const increment = isWeightProduct(item) ? 0.1 : 1;
        setCartQuantity(productId, Math.round((item.qty + delta * increment) * 1000) / 1000);
    }

    function setCartQuantity(productId, requestedQty, restoreFocus = false) {
        const item = cart.find(i => i.id === productId);
        if (!item) return;
        const product = products.find(p => p.id === productId);
        const weightItem = isWeightProduct(item);
        const quantity = weightItem ? Math.round(requestedQty * 1000) / 1000 : Math.round(requestedQty);
        if (quantity <= 0) {
            removeCartItem(productId);
            return;
        } else if (quantity > (product ? product.stock : Infinity) + 0.000001) {
            playBeep('error');
            alert('Cannot add more. Reached available stock limit (' + formatStock(product ? product.stock : 0) + ').');
            if (restoreFocus) {
                const currentInput = cartRows.querySelector(`[data-product-id="${productId}"]`);
                if (currentInput) {
                    currentInput.value = formatQuantity(item.qty, weightItem);
                    currentInput.focus();
                    currentInput.select();
                }
            }
            return;
        } else {
            const previousQuantity = item.qty;
            item.qty = quantity;
            const quantityIncreased = quantity > previousQuantity;
            renderCart({
                highlightProductId: quantityIncreased ? productId : null,
                revealHighlightedIfFollowing: quantityIncreased
            });
        }
        if (restoreFocus) {
            const updatedInput = cartRows.querySelector(`[data-product-id="${productId}"]`);
            if (updatedInput) {
                updatedInput.focus();
                updatedInput.select();
            }
        }
    }

    async function removeCartItem(productId) {
        const item = cart.find(cartItem => cartItem.id === productId);
        if (!item) return;

        const authorization = await window.requestManagerAuthorization({
            action: 'cart_item_void',
            details: {
                product_id: item.id,
                product_name: item.name,
                quantity: item.qty,
                line_total: Math.round(item.price * item.qty * 100) / 100
            },
            requiresReason: true
        });
        if (!authorization) return;

        const index = cart.findIndex(cartItem => cartItem.id === productId);
        if (index < 0) return;
        cart.splice(index, 1);
        if (cart.length === 0) clearCheckoutIdempotencyKey();
        renderCart();
    }

    function removeItem(index) {
        const item = cart[index];
        if (item) removeCartItem(item.id);
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
        const remainingStock = product.stock - (existing ? existing.qty : 0);
        const quantityToAdd = isWeightProduct(product) && qty === 1
            ? Math.min(qty, Math.floor((remainingStock + 0.000001) * 1000) / 1000)
            : qty;
        if (quantityToAdd <= 0 || quantityToAdd > remainingStock + 0.000001) {
            playBeep('error');
            alert('Stock limit reached for ' + product.name + '. (Available: ' + formatStock(Math.max(0, remainingStock)) + ')');
            return;
        }
        if (cart.length === 0) getCheckoutIdempotencyKey();
        if (existing) {
            existing.qty += quantityToAdd;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                qty: quantityToAdd,
                unit_of_measure: product.unit_of_measure || 'piece'
            });
        }
        playBeep('scan');
        renderCart({
            scrollToLatest: !existing,
            highlightProductId: existing ? productId : null,
            revealHighlightedIfFollowing: Boolean(existing)
        });
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
            const stock = document.createElement('span');
            stock.className = 'pos-stock-label';
            stock.textContent = p.stock <= 0 ? 'Out of stock' : `${formatStock(p.stock)} in stock`;
            const reorderLevel = Number(p.reorder_level || 0);
            const criticalReorderLevel = Number(p.critical_reorder_level || 0);
            const isAtReorderLevel = p.stock > 0 && (
                p.stock <= reorderLevel ||
                (criticalReorderLevel > 0 && p.stock <= criticalReorderLevel)
            );
            stock.classList.toggle('reorder-level', isAtReorderLevel);
            stock.classList.toggle('low-stock', p.stock > 0 && p.stock <= 10 && !isAtReorderLevel);
            stock.classList.toggle('out-of-stock', p.stock <= 0);
            sub.appendChild(stock);

            info.append(title, sub);

            const actions = document.createElement('div');
            actions.className = 'pos-product-actions';

            const price = document.createElement('span');
            price.className = 'pos-product-price';
            price.textContent = formatMoney(p.price);

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'pos-add-product-btn';
            addBtn.textContent = 'Add';
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
    const cardLast4Input = document.getElementById('card-last4');
    const ewalletReferenceNo = document.getElementById('ewallet-reference-no');
    const ewalletProviderSelect = document.getElementById('ewallet-provider-select');

    document.querySelectorAll('[data-payment-visibility-toggle]').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement?.querySelector('[data-payment-reference-input]');
            if (!input) return;

            const hiding = input.type === 'text';
            input.type = hiding ? 'password' : 'text';
            this.textContent = hiding ? 'Show' : 'Hide';
            this.setAttribute('aria-label', `${hiding ? 'Show' : 'Hide'} ${input.id === 'card-approval-code' ? 'Card approval code' : 'E-Wallet reference'}`);
            input.focus({ preventScroll: true });
        });
    });

    // Payment Methods
    document.querySelectorAll('.pos-payment-option').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pos-payment-option').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPaymentMethod = this.dataset.method;

            cashPaymentFields.hidden = true;
            cardPaymentFields.hidden = true;
            ewalletPaymentFields.hidden = true;

            if (currentPaymentMethod === 'cash') {
                paymentBoxTitle.textContent = 'Cash Payment';
                cashPaymentFields.hidden = false;
                submitSaleBtn.textContent = 'Complete sale';
            } else if (currentPaymentMethod === 'card') {
                paymentBoxTitle.textContent = 'Card Payment';
                cardPaymentFields.hidden = false;
                submitSaleBtn.textContent = 'Submit for verification';
                const total = parseNumeric(totalVal.textContent);
                amountPaidInput.value = total.toFixed(2);
                setTimeout(() => cardApprovalCode && cardApprovalCode.focus(), 50);
            } else if (currentPaymentMethod === 'e-wallet') {
                paymentBoxTitle.textContent = 'E-Wallet Payment';
                ewalletPaymentFields.hidden = false;
                submitSaleBtn.textContent = 'Submit for verification';
                const total = parseNumeric(totalVal.textContent);
                amountPaidInput.value = total.toFixed(2);
                setTimeout(() => ewalletReferenceNo && ewalletReferenceNo.focus(), 50);
            }
            renderCart();
        });
    });

    amountPaidInput.addEventListener('input', renderCart);

    // Server-side customer search keeps the full CRM directory out of the register page.
    const customerSearchUrl = @json(route('pos.customers.search'));
    const customerInput = document.getElementById('customer_id_input');
    const customerLoyaltyBadge = document.getElementById('customer-loyalty-status');
    const customerSearchResults = document.getElementById('customer-search-results');
    let customerSearchTimer = null;
    let customerSearchRequest = null;
    let customerSearchSequence = 0;

    async function requestPosCustomers(query, signal = undefined) {
        const url = new URL(customerSearchUrl, window.location.origin);
        url.searchParams.set('q', query);
        const response = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal
        });
        if (!response.ok) throw new Error('Customer search failed.');
        return response.json();
    }

    async function findActivePosCustomer(customerId) {
        try {
            const payload = await requestPosCustomers(String(customerId));
            return (Array.isArray(payload.customers) ? payload.customers : [])
                .find(customer => String(customer.id) === String(customerId)) || null;
        } catch (error) {
            return null;
        }
    }

    function syncCustomerDisplay(foundCustomer) {
        if (foundCustomer) {
            selectedCustomerId = String(foundCustomer.id);
            customerLoyaltyBadge.textContent = `${foundCustomer.name} · Customer #${foundCustomer.id} · ${foundCustomer.contact || 'No contact'} · ${foundCustomer.points} loyalty points`;
        } else {
            selectedCustomerId = '';
            customerLoyaltyBadge.textContent = 'Walk-in Customer';
        }
    }

    function renderCustomerMatches(query) {
        clearTimeout(customerSearchTimer);
        customerSearchRequest?.abort();
        customerSearchRequest = null;
        customerSearchResults.replaceChildren();
        if (!query) {
            customerSearchResults.hidden = true;
            customerInput.setAttribute('aria-expanded', 'false');
            return;
        }
        const sequence = ++customerSearchSequence;
        const searching = document.createElement('div');
        searching.className = 'pos-customer-result';
        searching.setAttribute('role', 'status');
        searching.textContent = 'Searching customers…';
        customerSearchResults.appendChild(searching);
        customerSearchResults.hidden = false;
        customerInput.setAttribute('aria-expanded', 'true');

        customerSearchTimer = setTimeout(async () => {
            const controller = new AbortController();
            customerSearchRequest = controller;

            try {
                const payload = await requestPosCustomers(query, controller.signal);
                if (sequence !== customerSearchSequence || query !== customerInput.value.trim()) return;

                customerSearchResults.replaceChildren();
                const matches = Array.isArray(payload.customers) ? payload.customers : [];
                if (!matches.length) {
                    const empty = document.createElement('div');
                    empty.className = 'pos-customer-result';
                    empty.setAttribute('role', 'status');
                    empty.textContent = 'No registered customer found';
                    customerSearchResults.appendChild(empty);
                }
                matches.forEach(customer => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'pos-customer-result';
                    option.setAttribute('role', 'option');
                    option.textContent = `${customer.name} · #${customer.id}${customer.contact ? ` · ${customer.contact}` : ''}`;
                    option.addEventListener('click', () => {
                        customerInput.value = `${customer.name} · #${customer.id}`;
                        syncCustomerDisplay(customer);
                        customerSearchResults.hidden = true;
                        customerInput.setAttribute('aria-expanded', 'false');
                    });
                    customerSearchResults.appendChild(option);
                });
            } catch (error) {
                if (error.name === 'AbortError' || sequence !== customerSearchSequence) return;
                customerSearchResults.replaceChildren();
                const failed = document.createElement('div');
                failed.className = 'pos-customer-result';
                failed.setAttribute('role', 'status');
                failed.textContent = 'Customer search is unavailable. Try again.';
                customerSearchResults.appendChild(failed);
            }
        }, 220);
    }

    customerInput.addEventListener('input', function() {
        syncCustomerDisplay(null);
        renderCustomerMatches(this.value.trim());
    });
    customerInput.addEventListener('keydown', event => {
        const firstResult = customerSearchResults.querySelector('[role="option"]');
        if (event.key === 'ArrowDown' && firstResult) {
            event.preventDefault();
            firstResult.focus();
        } else if (event.key === 'Enter' && firstResult) {
            event.preventDefault();
            event.stopPropagation();
            firstResult.click();
        } else if (event.key === 'Escape') {
            customerSearchResults.hidden = true;
            customerInput.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.pos-customer-search')) {
            customerSearchResults.hidden = true;
            customerInput.setAttribute('aria-expanded', 'false');
        }
    });

    // Submit Sale Handler
    submitSaleBtn.addEventListener('click', () => {
        if (checkoutSubmissionStarted) return;

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
            if (paid + 0.000001 < total) {
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
            if (/^\d{3,4}$/.test(approval)) {
                playBeep('error');
                alert('Enter the terminal approval code. Do not enter the card CVV.');
                cardApprovalCode && cardApprovalCode.focus();
                return;
            }
            const last4 = (cardLast4Input?.value || '').trim();
            if (last4 && !/^\d{4}$/.test(last4)) {
                playBeep('error');
                alert('Card last 4 digits must contain exactly four numbers.');
                document.getElementById('card-last4')?.focus();
                return;
            }
            refNum = approval;
            provider = cardProviderSelect ? cardProviderSelect.value : 'Other';
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
            provider = ewalletProviderSelect ? ewalletProviderSelect.value : 'Other';
            paid = total;
        }

        // Fill hidden form
        document.getElementById('store-customer-id').value = selectedCustomerId;
        const checkoutIdempotencyKey = getCheckoutIdempotencyKey();
        document.getElementById('store-idempotency-key').value = checkoutIdempotencyKey;
        document.getElementById('store-discount-id').value = discountSelect.value;
        const selectedOption = discountSelect.selectedOptions[0];
        const specialType = selectedOption?.dataset.specialType || '';
        if (specialType && (!seniorPwdNameInput.value.trim() || !seniorPwdIdInput.value.trim())) {
            alert('Enter the full name and ID number shown on the Senior Citizen/PWD ID.');
            (!seniorPwdNameInput.value.trim() ? seniorPwdNameInput : seniorPwdIdInput).focus();
            return;
        }
        document.getElementById('store-senior-pwd-type').value = specialType;
        document.getElementById('store-senior-pwd-name').value = specialType ? seniorPwdNameInput.value.trim() : '';
        document.getElementById('store-senior-pwd-id-number').value = specialType ? seniorPwdIdInput.value.trim() : '';
        document.getElementById('store-payment-method').value = currentPaymentMethod;
        document.getElementById('store-reference-number').value = refNum;
        document.getElementById('store-payment-provider').value = provider;
        document.getElementById('store-card-last4').value = currentPaymentMethod === 'card'
            ? (cardLast4Input?.value || '').trim()
            : '';
        document.getElementById('store-amount-paid').value = paid;
        document.getElementById('store-manager-auth-token').value = discountAuthorizationToken;
        document.getElementById('store-register-id').value = registerId;

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
        checkoutSubmissionStarted = true;
        submitSaleBtn.disabled = true;
        submitSaleBtn.setAttribute('aria-busy', 'true');
        submitSaleBtn.textContent = currentPaymentMethod === 'e-wallet' ? 'Submitting…' : 'Completing…';
        window.history.replaceState({ ...(window.history.state || {}), posCheckoutAttemptKey: checkoutIdempotencyKey }, document.title, window.location.href);
        document.getElementById('pos-store-form').submit();
    });

    // Modal Control Helpers
    function openModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.style.display = 'flex';
        m.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        requestAnimationFrame(() => m.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), button:not([disabled])')?.focus());
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
        if (id === 'resume-held-confirm-modal') pendingResumeHoldId = null;
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
            const shiftTime = document.getElementById('drawer-toggle-time');

            if (data.has_open_drawer && currentDrawer) {
                topStatusDot.className = 'pos-status-dot-active';
                topStatusText.textContent = 'ACTIVE';
                topStatusBadge.classList.remove('is-inactive');
                document.getElementById('drawer-toggle-label').textContent = 'Close Shift';
                shiftTime.textContent = currentDrawer.opened_at ? 'Opened ' + formatShiftTime(currentDrawer.opened_at) : '';
                document.getElementById('drawer-toggle-amount').textContent = '(' + formatMoney(currentDrawer.expected_cash) + ')';
                drawerToggleBtn.onclick = () => {
                    const heldCount = loadHeldState().items.length;
                    if (heldCount > 0 && !window.confirm(`${heldCount} held ${heldCount === 1 ? 'transaction is' : 'transactions are'} still on this register. They will remain saved for up to 24 hours. Continue to Close Shift?`)) return;
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
                topStatusBadge.classList.add('is-inactive');
                document.getElementById('drawer-toggle-label').textContent = 'Open Shift';
                shiftTime.textContent = data.last_closed_at ? 'Last closed ' + formatShiftTime(data.last_closed_at, true) : '';
                document.getElementById('drawer-toggle-amount').textContent = '';
                drawerToggleBtn.onclick = () => openModal('open-register-modal');
            }
        } catch (e) {
            console.error(e);
        }
    }

    function formatShiftTime(value, includeDate = false) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const time = new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(date);
        if (!includeDate) return time;
        const day = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(date);
        return day + ', ' + time;
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
        const authorization = await window.requestManagerAuthorization({
            action: 'cash_drawer_open',
            details: { opening_cash: Math.round(floatVal * 100) / 100 }
        });
        if (!authorization) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const res = await fetch('{{ route("cash-drawer.open") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ opening_cash: floatVal, manager_authorization_token: authorization.token, register_id: registerId })
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

    discountSelect.addEventListener('change', async () => {
        const nextDiscountId = discountSelect.value;
        if (!nextDiscountId) {
            syncSeniorPwdProof();
            authorizedDiscountId = '';
            discountAuthorizationToken = '';
            renderCart();
            return;
        }

        if (nextDiscountId === authorizedDiscountId && discountAuthorizationToken) {
            renderCart();
            return;
        }

        const previousDiscountId = authorizedDiscountId;
        const option = discountSelect.selectedOptions[0];
        const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        const discountValue = Number(option?.dataset.value || 0);
        const specialType = option?.dataset.specialType || '';
        const specialBase = specialType ? Math.round((configVatEnabled ? subtotal / (1 + vatRate) : subtotal) * 100) / 100 : subtotal;
        const rawDiscount = specialType
            ? specialBase * 0.20
            : (option?.dataset.type === 'percentage' ? subtotal * discountValue / 100 : discountValue);
        discountSelect.disabled = true;
        discountTrigger.disabled = true;
        discountTrigger.setAttribute('aria-busy', 'true');
        try {
            const authorization = await window.requestManagerAuthorization({
                action: 'discount_apply',
                details: {
                    discount_id: Number(nextDiscountId),
                    discount_name: option?.textContent.trim() || '',
                    discount_amount: Math.min(subtotal, Math.max(0, Math.round(rawDiscount * 100) / 100))
                }
            });
            if (!authorization) {
                discountSelect.value = previousDiscountId;
                syncDiscountDropdown();
                syncSeniorPwdProof();
                renderCart();
                return;
            }

            authorizedDiscountId = nextDiscountId;
            discountAuthorizationToken = authorization.token || '';
            syncSeniorPwdProof();
            renderCart();
        } catch (error) {
            console.error('Discount authorization could not be completed.', error);
            discountSelect.value = previousDiscountId;
            syncDiscountDropdown();
            syncSeniorPwdProof();
            renderCart();
            alert('The discount could not be changed. The previous discount has been restored. Try again.');
        } finally {
            discountSelect.disabled = false;
            discountTrigger.disabled = false;
            discountTrigger.removeAttribute('aria-busy');
        }
    });
    document.getElementById('held-list-btn').addEventListener('click', () => {
        renderHeldTransactions();
        openModal('held-transactions-modal');
    });
    holdSaleBtn.addEventListener('click', holdCurrentTransaction);
    document.getElementById('cancel-held-resume-btn').addEventListener('click', () => closeModal('resume-held-confirm-modal'));
    document.getElementById('discard-live-and-resume-btn').addEventListener('click', () => {
        const holdId = pendingResumeHoldId;
        if (!holdId) return;
        resetLiveTransaction();
        closeModal('resume-held-confirm-modal');
        resumeHeldTransaction(holdId);
    });
    document.getElementById('hold-live-and-resume-btn').addEventListener('click', () => {
        const holdId = pendingResumeHoldId;
        if (!holdId || !holdCurrentTransaction()) return;
        closeModal('resume-held-confirm-modal');
        resumeHeldTransaction(holdId);
    });
    document.querySelectorAll('[data-cash]').forEach(button => {
        button.addEventListener('click', () => {
            amountPaidInput.value = Number(button.dataset.cash).toFixed(2);
            renderCart();
        });
    });
    document.getElementById('exact-amount-btn').addEventListener('click', () => {
        amountPaidInput.value = parseNumeric(totalVal.textContent).toFixed(2);
        renderCart();
    });

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
            closeModal('held-transactions-modal');
            closeModal('resume-held-confirm-modal');
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

        if (e.key === 'F9') {
            e.preventDefault();
            holdCurrentTransaction();
            return;
        }

        if (e.key === 'F10') {
            e.preventDefault();
            window.location.assign(reprintLastUrl);
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

    window.addEventListener('pageshow', event => {
        if (event.persisted) window.location.reload();
    });

    // Init
    loadHeldState();
    renderCart();
    renderSearchResults();
    checkDrawerStatus();
</script>
@endpush
