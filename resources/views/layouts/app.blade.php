<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* --- Palette: bright peach canvas, warm ink, lively botanical green --- */
            --bg: #FFF5EE;
            --bg-tint: #F9E4D5;
            --surface: #FFFEFC;
            --surface-soft: #FFF9F4;
            --text: #382B2D;
            --muted: #746467;
            --rule: #EEDBD0;
            --rule-faint: rgba(83, 58, 58, 0.11);
            --accent: #18765E;
            --accent-soft: rgba(24, 118, 94, 0.11);
            --focus: #18765E;
            --danger-soft: rgba(180, 63, 59, 0.10);
            --surface-hover: #FFEDE2;
            --surface-active: #E5F3EC;
            --interactive-ink: #145844;
            --success: #2B8050;
            --success-ink: #246640;
            --success-soft: rgba(45, 138, 78, 0.12);
            --danger: #AE3F3B;
            --warn: #AD8050;
            --warn-ink: #76552F;
            --warn-soft: rgba(173, 128, 80, 0.12);

            /* --- Shape --- */
            --r-sm: 6px;
            --r: 10px;
            --r-lg: 14px;
            --r-pill: 999px;

            /* --- Depth: soft and modern; form controls stay flat --- */
            --shadow-sm: 0 1px 3px rgba(83, 58, 58, 0.05);
            --shadow-card: 0 1px 2px rgba(83, 58, 58, 0.025), 0 10px 28px -22px rgba(83, 58, 58, 0.22);
            --shadow-pop: 0 8px 24px -18px rgba(83, 58, 58, 0.24);
            --ring: 0 0 0 4px rgba(24, 118, 94, 0.16);

            --ease: cubic-bezier(0.2, 0.7, 0.3, 1);
            --sidebar-w: 230px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Manrope', system-ui, sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(1100px 520px at 88% -8%, #F9E4D5 0%, rgba(249, 228, 213, 0) 62%);
            background-attachment: fixed;
            color: var(--text);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        body:not(.pos-shell) { --surface-hover: #EEF2F1; }
        ::selection { background: var(--accent-soft); color: var(--text); }
        h1 { font-size: clamp(1.4rem, 1.1rem + 0.7vw, 1.6rem); font-weight: 700; letter-spacing: -0.025em; }
        h2 { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.015em; }

        /* --- Sidebar --- */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--rule-faint);
            box-shadow: 0 0 60px -40px rgba(83, 58, 58, 0.28);
            display: flex; flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 1.3rem 1.25rem;
            border-bottom: 1px solid var(--rule-faint);
            text-decoration: none; color: var(--text);
            font-weight: 700; font-size: 1rem;
            text-transform: uppercase; letter-spacing: 0.14em;
        }
        .sidebar-brand-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px;
            background: var(--accent); color: #fff;
            border-radius: var(--r-sm);
            font-weight: 800; font-size: 0.75rem;
            box-shadow: 0 8px 16px -10px rgba(24, 118, 94, 0.34);
        }
        .sidebar-nav { display: flex; flex-direction: column; flex: 1; padding: 0.75rem; gap: 0.35rem; }
        .sidebar-nav a {
            display: inline-flex; align-items: center;
            min-height: 34px; padding: 0.38rem 0.68rem;
            border: 1px solid var(--rule-faint); border-radius: var(--r);
            background: var(--surface-soft); box-shadow: var(--shadow-sm);
            text-decoration: none; color: var(--muted);
            font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.01em;
            transition: color 0.18s var(--ease), background-color 0.18s var(--ease), border-color 0.18s var(--ease), box-shadow 0.18s var(--ease), transform 0.18s var(--ease);
        }
        .sidebar-nav a:hover:not(.active) {
            color: #205B3A; background: #DDEBE2;
            border-color: rgba(32, 128, 69, 0.24); box-shadow: 0 3px 8px rgba(32, 60, 61, 0.08);
            transform: translateY(-1px); text-decoration: none;
        }
        .sidebar-nav a.active {
            color: #fff; background: #203C3D;
            border-color: #203C3D; font-weight: 800;
            box-shadow: 0 2px 7px rgba(32, 60, 61, 0.18);
        }
        .sidebar-nav a:focus-visible { outline: none; box-shadow: var(--ring); }
        @media (prefers-reduced-motion: reduce) {
            .sidebar-nav a { transition: none; }
        }
        .sidebar-user {
            border-top: 1px solid var(--rule-faint);
            padding: 1rem 1.25rem; margin-top: auto;
        }
        .sidebar-user-info {
            font-size: 0.75rem; font-weight: 600;
            letter-spacing: 0.01em;
            color: var(--muted); margin-bottom: 0.6rem;
        }
        .sidebar-user-info strong { color: var(--text); display: block; }
        .sidebar-logout {
            display: block; width: 100%; padding: 0.6rem;
            background: var(--text); color: #fff; border: none;
            border-radius: var(--r-sm);
            font-family: inherit; font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer; text-align: center;
            transition: background-color 0.15s var(--ease), transform 0.15s var(--ease);
        }
        .sidebar-logout:hover { background: #315D59; transform: translateY(-1px); }

        /* --- Main Content --- */
        .main { margin-left: var(--sidebar-w); padding: 2rem 2.5rem; width: calc(100% - var(--sidebar-w)); }
        .page-head {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; margin-bottom: 1.1rem;
            padding-bottom: 0.8rem; border-bottom: 1px solid var(--rule-faint);
            flex-wrap: wrap;
        }
        /* Shared form workspace styling for edit pages and New Discount. */
        .form-page-head {
            min-height: 82px; padding: 0.9rem 1.2rem; border: 0; border-radius: var(--r-lg);
            background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12);
        }
        .form-page-head h1 { color: #fff; font-weight: 800; letter-spacing: -0.025em; }
        .form-page-head p.muted { color: rgba(255,255,255,.78); }
        .form-page-head .eyebrow { color: #B8D9C4; }
        .form-page-head .btn-secondary,
        .form-page-head a.btn-secondary:link,
        .form-page-head a.btn-secondary:visited {
            --btn-bg: #fff; --btn-bg-hover: #E7F2EB;
            min-height: 36px; background: #fff; color: #203C3D !important;
            border: 1px solid rgba(255,255,255,.42); font-weight: 800;
        }
        .form-page-card.card {
            display: block; max-width: 1240px; margin: 0 auto 1rem; padding: 1.2rem;
            overflow: visible; border: 1px solid rgba(32,60,61,.1); border-radius: var(--r-lg);
            background: var(--surface); box-shadow: var(--shadow-card);
        }
        .form-page-card.card:hover { box-shadow: var(--shadow-card); }
        .form-page-card .form-actions {
            justify-content: flex-end; margin-top: 1.1rem; padding-top: .9rem;
            border-top: 1px solid var(--rule-faint);
        }
        .form-page-card .form-actions .btn-secondary,
        .form-page-card .form-actions a.btn-secondary:link,
        .form-page-card .form-actions a.btn-secondary:visited {
            --btn-bg: #203C3D; --btn-bg-hover: #2B4D4E;
            min-height: 40px; padding: .58rem 1rem; background: #203C3D;
            color: #fff !important; border: 1px solid #203C3D; font-weight: 800;
        }
        .form-page-card button[type="submit"] { min-height: 40px; font-weight: 800; }
        .form-page-record { margin-bottom: 1rem; padding: .7rem .9rem; border-radius: var(--r); background: #E7F2EB; color: #244A3A; font-weight: 800; }
        @media (max-width: 600px) {
            .form-page-head { align-items: flex-start; flex-direction: column; padding: 1rem; }
            .form-page-card.card { padding: 1rem; }
            .form-page-card .form-actions { justify-content: stretch; }
            .form-page-card .form-actions > * { flex: 1 1 100%; justify-content: center; }
        }

        /* --- Card --- */
        .card {
            background: var(--surface); border: 1px solid var(--rule-faint);
            border-radius: var(--r-lg); padding: 1.25rem; margin-bottom: 1rem;
            box-shadow: var(--shadow-card);
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            transition: box-shadow 0.2s var(--ease);
        }
        .card:hover { box-shadow: var(--shadow-pop); }

        /* --- Buttons --- */
        button[type="submit"], .btn,
        a.btn, a.btn:link, a.btn:visited {
            --btn-bg: var(--accent);
            --btn-bg-hover: #2B5556;
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.6rem 1.15rem;
            background: var(--btn-bg);
            color: #ffffff !important;
            border: none;
            border-radius: var(--r);
            font-family: inherit; font-size: 0.82rem; font-weight: 600;
            letter-spacing: 0.01em;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 1px 2px rgba(83, 58, 58, 0.12);
            transition: background-color 0.15s var(--ease), box-shadow 0.15s var(--ease), transform 0.15s var(--ease), color 0.15s var(--ease);
        }
        a.btn { text-decoration: none; }
        button[type="submit"]:hover, .btn:hover,
        a.btn:hover {
            background-color: var(--btn-bg-hover);
            color: #ffffff !important;
            box-shadow: 0 8px 18px -10px rgba(83, 58, 58, 0.34);
            transform: translateY(-1px);
        }
        button[type="submit"]:active, .btn:active,
        a.btn:active {
            transform: translateY(0);
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgba(83, 58, 58, 0.14);
        }
        button[type="submit"]:focus-visible, .btn:focus-visible,
        a.btn:focus-visible {
            outline: none;
            color: #ffffff !important;
            box-shadow: var(--ring);
        }
        button[type="submit"]:disabled, .btn:disabled, .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            color: #ffffff !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .btn-secondary, a.btn-secondary, a.btn-secondary:link, a.btn-secondary:visited {
            --btn-bg: var(--surface);
            --btn-bg-hover: var(--surface-hover);
            color: var(--text) !important;
            border: 1px solid var(--rule-faint);
        }
        .btn-secondary:hover, a.btn-secondary:hover,
        .btn-secondary:focus-visible, a.btn-secondary:focus-visible,
        .btn-secondary:active, a.btn-secondary:active {
            color: var(--text) !important;
        }
        .btn-danger { --btn-bg: var(--danger); --btn-bg-hover: #A8403A; color: #ffffff !important; }
        .btn-slate { --btn-bg: #203C3D; --btn-bg-hover: #2B4D4E; color: #ffffff !important; border-color: #203C3D; }
        .btn-blue { --btn-bg: #2563A6; --btn-bg-hover: #1D4F87; color: #ffffff !important; border-color: #2563A6; }
        .btn-compact { min-height: 32px; padding: 0.4rem 0.7rem; font-size: 0.76rem; }
        a.btn.btn-slate, a.btn.btn-slate:link, a.btn.btn-slate:visited {
            --btn-bg: #203C3D; --btn-bg-hover: #2B4D4E;
            background-color: #203C3D; color: #ffffff !important; border: 1px solid #203C3D;
        }
        a.btn.btn-slate:hover, a.btn.btn-slate:focus-visible, a.btn.btn-slate:active {
            background-color: #2B4D4E; color: #ffffff !important; border-color: #2B4D4E;
        }
        a.btn.btn-blue, a.btn.btn-blue:link, a.btn.btn-blue:visited {
            --btn-bg: #2563A6; --btn-bg-hover: #1D4F87;
            background-color: #2563A6; color: #ffffff !important; border: 1px solid #2563A6;
        }
        a.btn.btn-blue:hover, a.btn.btn-blue:focus-visible, a.btn.btn-blue:active {
            background-color: #1D4F87; color: #ffffff !important; border-color: #1D4F87;
        }
        a.btn.btn-compact, a.btn.btn-compact:link, a.btn.btn-compact:visited {
            min-height: 32px; padding: 0.4rem 0.7rem; font-size: 0.76rem;
        }
        .btn-ghost { --btn-bg-hover: var(--surface-hover); background: transparent; border: none; box-shadow: none; padding: 0.25rem 0.5rem; color: var(--interactive-ink); font-size: 0.78rem; letter-spacing: 0; text-decoration: none; }
        .btn-ghost:hover { transform: none; opacity: 0.7; }
        .qty-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; padding: 0;
            background: var(--surface); color: var(--text);
            border: 1px solid var(--rule-faint); border-radius: var(--r);
            font-family: inherit; font-size: 1rem; font-weight: 700; line-height: 1;
            cursor: pointer; transition: background-color 0.12s var(--ease), color 0.12s var(--ease), transform 0.12s var(--ease);
        }
        .qty-btn:hover { background: var(--surface-active); color: var(--interactive-ink); transform: translateY(-1px); }
        div.actions { display: inline-flex; flex-wrap: nowrap; gap: 0.5rem; align-items: center; white-space: nowrap; }

        /* --- Forms --- */
        label {
            display: block; margin-bottom: 0.35rem;
            font-size: 0.82rem; font-weight: 600;
            letter-spacing: 0.01em; color: var(--muted);
        }
        input[type="text"], input[type="password"], input[type="email"],
        input[type="number"], input[type="date"], input[type="search"],
        select, textarea {
            width: 100%;
            padding: 0.72rem 0.85rem;
            margin-bottom: 0.9rem;
            background: var(--surface); border: 1px solid var(--rule-faint);
            border-radius: var(--r); color: var(--text);
            font-family: inherit; font-size: 1rem; line-height: 1.4;
            outline: none;
            transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--focus); box-shadow: var(--ring);
        }
        /* Larger fields only for prominent text inputs (name, barcode, description). */
        .input-lg { padding: 0.72rem 0.85rem; font-size: 0.95rem; line-height: 1.4; }
        /* Explicit solid border for the prominent text boxes (name/barcode/description). */
        .bordered { border: 1px solid var(--rule-faint); border-radius: var(--r); background: var(--surface); }
        /* Full-width field whose action button sits below the input. */
        .field-stack { grid-column: 1 / -1; margin-bottom: 1.25rem; }
        .field-stack .btn { margin-top: 0.5rem; }
        /* Generate button after it's used once (no spam). */
        #generate-barcode:disabled { opacity: 0.5; cursor: not-allowed; }
        textarea { min-height: 90px; resize: vertical; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0 1.25rem;
        }
        .employee-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .employee-form-section { min-width: 0; padding: 1.1rem; border: 1px solid var(--rule-faint); border-radius: var(--r-lg); background: var(--surface-soft); }
        .employee-form-heading { margin-bottom: 0.9rem; }
        .employee-form-heading h2 { font-size: 1rem; }
        .employee-form-heading p { margin-top: 0.2rem; color: var(--muted); font-size: 0.82rem; }
        .employee-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 0.85rem; }
        .employee-fields > div { min-width: 0; }
        .employee-fields input, .employee-fields select { margin-bottom: 0.75rem; }
        .discount-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .discount-form-section { min-width: 0; padding: 1.1rem; border: 1px solid var(--rule-faint); border-radius: var(--r-lg); background: var(--surface-soft); }
        .discount-form-heading { margin-bottom: 0.9rem; }
        .discount-form-heading h2 { font-size: 1rem; }
        .discount-form-heading p { margin-top: 0.2rem; color: var(--muted); font-size: 0.82rem; }
        .discount-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 0.85rem; }
        .discount-fields > div { min-width: 0; }
        .discount-fields > .discount-field-wide { grid-column: 1 / -1; }
        .discount-fields input, .discount-fields select { margin-bottom: 0.75rem; }
        .discount-field-help { margin-top: -0.4rem; color: var(--muted); font-size: 0.78rem; }
        /* Compact side-by-side fields (email + contact) — fixed width, not stretched. */
        .field-pair { display: flex; gap: 2rem; flex-wrap: wrap; }
        .field-pair > div { width: 300px; }
        /* Input + action button on the same row (e.g. barcode + Generate). */
        .field-with-btn {
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1.1rem;
        }
        .field-with-btn input { flex: 1 1 auto; min-width: 0; margin-bottom: 0; }
        .field-with-btn .btn { flex: 0 0 auto; margin-bottom: 0; }
        /* Group of form action buttons with consistent top spacing. */
        .form-actions { margin-top: 1.5rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        /* Scoped grid for the promotion/coupon create-edit forms ONLY.
           Does not affect the shared .form-grid used by POS, products, customers, etc. */
        .promo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem 1.5rem;
            align-items: start;
        }
        .promo-grid > div { display: flex; flex-direction: column; min-width: 0; }
        .promo-grid > div > label { min-height: 2.2em; }
        /* All single-line controls in this form share ONE box size, including
           datetime-local (which has browser-internal sub-parts that ignore normal
           CSS inheritance on Chrome/Edge). Scoped to .promo-grid only. */
        .promo-grid > div > input,
        .promo-grid > div > input[type="datetime-local"],
        .promo-grid > div > select,
        .promo-grid > div > textarea {
            width: 100%;
            margin-bottom: 0;
            height: 2.9rem;
            padding: 0.65rem 0.85rem;
            background: var(--surface);
            border: 1px solid var(--rule-faint);
            border-radius: var(--r);
            color: var(--text);
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.4;
            box-sizing: border-box;
        }
        /* Keep the calendar picker icon inside the padded box (Chrome/Edge render an
           internal indicator that ignores padding). nudge it so it isn't clipped. */
        .promo-grid > div > input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            margin: 0;
            margin-left: 0.4rem;
            opacity: 0.7;
            cursor: pointer;
        }
        @media (max-width: 900px) { .promo-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .promo-grid { grid-template-columns: 1fr; } .promo-grid > div > label { min-height: 0; } }
        /* Barcode "Generate" button: matches .btn-secondary, fills green on hover. */
        #generate-barcode { transition: background-color 0.15s, color 0.15s, border-color 0.15s; }
        #generate-barcode:hover {
            background: var(--success); color: #fff; border-color: var(--success); opacity: 1;
        }

        /* --- Tables --- */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-clean-slate { background: #fff; }
        .table-clean-slate thead th { padding: 13px 12px; border-bottom: 0; background: var(--text); color: #fff; font-size: .75rem; font-weight: 800; letter-spacing: .01em; }
        .table-clean-slate tbody tr,
        .table-clean-slate tbody tr:nth-child(even) { background: #fff; }
        .table-clean-slate tbody tr:hover { background: #f7f9f8; }
        th, td {
            padding: 0.75rem 1rem;
            text-align: left; vertical-align: middle;
        }
        thead th {
            font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.01em;
            background: var(--bg-tint); color: var(--text);
            border-bottom: 1px solid var(--rule-faint);
            padding: 0.72rem 1rem;
            white-space: nowrap;
            position: relative;
            z-index: 1;
            box-shadow: inset 0 -1px rgba(32,60,61,.12), 0 3px 6px rgba(32,60,61,.08);
        }
        .table-clean-slate thead th { box-shadow: inset 0 -1px rgba(255,255,255,.16), 0 3px 7px rgba(32,60,61,.18); }
        thead th:first-child { border-radius: var(--r-sm) 0 0 var(--r-sm); }
        thead th:last-child { border-radius: 0 var(--r-sm) var(--r-sm) 0; }
        tbody td { border-bottom: 1px solid var(--rule-faint); transition: background-color 0.12s var(--ease); }
        tbody tr:nth-child(even) { background: var(--surface-soft); }
        tbody tr { box-shadow: 0 1px 2px rgba(32,60,61,.06); }
        .pos-cart-table tbody tr, .receipt-items tbody tr { box-shadow: none; }
        tbody tr:hover { background: var(--surface-hover); }
        tbody td:first-child { font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:last-child td.actions { border-bottom: 1px solid var(--rule-faint); }
        td code {
            background: rgba(83, 58, 58, 0.07);
            padding: 0.2rem 0.5rem; border-radius: var(--r-sm);
            font-size: 0.78rem; letter-spacing: 0.02em;
        }
        th:last-child { width: auto; }
        td.actions {
            width: 10%; text-align: right;
            border-bottom: 1px solid var(--rule-faint);
            padding: 0.5rem 0.25rem 0.5rem 1rem;
            white-space: nowrap;
        }
        td.actions .actions { display: inline-flex; gap: 0.4rem; align-items: center; }
        td.actions .inline-form { display: inline-flex; align-items: center; margin: 0; }
        td.actions a { font-size: 0.8rem; font-weight: 600; text-decoration: none; text-underline-offset: 2px; }
        /* Long text (names, emails, addresses) wraps instead of breaking the layout. */
        tbody td { word-break: break-word; overflow-wrap: break-word; }
        tbody td:not(.actions) { max-width: 260px; }

        /* --- Badge --- */
        .badge {
            display: inline-block; padding: 0.25rem 0.65rem;
            font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.01em;
            border-radius: var(--r-pill);
        }
        .badge-active { padding: 0.32rem 0.7rem; background: #16803c; color: #fff; border: 1px solid #16803c; font-weight: 800; }
        .badge-inactive { padding: 0.32rem 0.7rem; background: var(--danger); color: #fff; border: 1px solid var(--danger); font-weight: 800; }
        .badge-slate { background: var(--text); color: #fff; border: 1px solid var(--text); font-weight: 800; }
        .badge-points { padding: 0.32rem 0.7rem; background: var(--text); color: #fff; border: 1px solid var(--text); font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .badge-success { background: #16803c; color: #fff; border: 1px solid #16803c; font-weight: 800; }
        .badge-warning { background: #805400; color: #fff; border: 1px solid #805400; font-weight: 800; }
        .badge-neutral { background: var(--surface-soft); color: var(--text); border: 1px solid var(--rule-faint); }
        .badge-pending { background: var(--warn-soft); color: var(--warn-ink); border: 1px solid rgba(173, 128, 80, 0.24); }
        .badge-balanced { background: #16803c; color: #fff; border: 1px solid #16803c; font-weight: 800; }
        .badge-shortage { background: var(--danger); color: #fff; border: 1px solid var(--danger); font-weight: 800; }
        .badge-overage { background: #805400; color: #fff; border: 1px solid #805400; font-weight: 800; }

        /* --- Modern Metric & Stat Cards --- */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--rule-faint);
            border-radius: var(--r-lg);
            padding: 1.25rem 1.4rem;
            box-shadow: var(--shadow-card);
            transition: transform 0.15s var(--ease), box-shadow 0.15s var(--ease);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card:hover { box-shadow: var(--shadow-pop); }
        .stat-card-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: var(--muted);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card-val {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text);
            font-variant-numeric: tabular-nums;
            line-height: 1.2;
        }
        .stat-card-sub {
            font-size: 0.76rem;
            color: var(--muted);
            margin-top: 0.45rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .card-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        /* --- Flash --- */
        .flash {
            padding: 0.9rem 1.15rem; margin-bottom: 1.5rem;
            border: 1px solid var(--rule-faint); border-radius: var(--r-sm);
            font-weight: 500; font-size: 0.88rem;
        }
        .flash-ok { background: rgba(45, 138, 78, 0.08); border-color: rgba(43, 128, 80, 0.24); color: var(--success-ink); }
        .flash-error, .flash-danger { background: var(--danger-soft); border-color: rgba(174, 63, 59, 0.24); color: var(--danger); }
        .flash-warning { background: var(--warn-soft); border-color: rgba(173, 128, 80, 0.28); color: var(--warn-ink); }
        .flash-info { background: rgba(24, 118, 94, 0.06); border-color: rgba(24, 118, 94, 0.22); color: var(--text); }
        .error, .error li { color: var(--danger); }

        /* --- Pagination --- */
        .pagination {
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--rule-faint);
            display: flex; justify-content: space-between; align-items: center;
            gap: 0.75rem; flex-wrap: wrap;
        }
        .pagination-info {
            font-size: 0.78rem; font-weight: 600;
            letter-spacing: 0.01em;
            color: var(--muted);
        }
        .pagination-links { display: inline-flex; align-items: center; gap: 0.25rem; flex-wrap: wrap; }
        .pagination-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 0.6rem;
            font-size: 0.82rem; font-weight: 600; text-decoration: none;
            color: var(--text); border: 1px solid var(--rule-faint);
            border-radius: var(--r-sm);
            background: var(--surface);
            transition: background-color 0.15s var(--ease), color 0.15s var(--ease), border-color 0.15s var(--ease);
        }
        a.pagination-btn:hover { background: var(--surface-hover); color: var(--interactive-ink); border-color: rgba(24, 118, 94, 0.18); text-decoration: none; }
        .pagination-btn.is-current {
            background: var(--surface-active); border-color: rgba(24, 118, 94, 0.14); color: var(--interactive-ink);
            font-weight: 700;
        }
        .pagination-btn.is-disabled {
            color: var(--muted); border-color: #d0d0d0; opacity: 0.6; cursor: default;
        }
        .pagination-ellipsis { padding: 0 0.35rem; color: var(--muted); font-weight: 700; }

        /* --- Misc --- */
        .muted { color: var(--muted); }
        .empty { padding: 2.5rem 0; color: var(--muted); text-align: center; font-size: 0.9rem; }
        .warn { color: var(--warn-ink); font-weight: 700; }
        .inline-form { display: inline; }
        a { color: var(--accent); text-decoration: none; }
        :focus-visible { outline: 2px solid var(--focus); outline-offset: 2px; }
        a:hover { text-decoration: underline; text-underline-offset: 2px; }
        .sidebar-nav a,
        .sidebar-nav a:hover,
        .sidebar-nav a:focus,
        .sidebar-nav a:focus-visible,
        .sidebar-nav a:active { text-decoration: none; }

        /* --- Responsive --- */
        @media (max-width: 1024px) {
            .main { padding: 1.5rem 1.5rem; }
        }
        @media (max-width: 900px) {
            .sidebar {
                position: fixed; top: 0; left: 0; right: 0; bottom: auto;
                width: 100%; height: auto; border-right: none;
                border-bottom: 1px solid var(--rule-faint); flex-direction: row; align-items: center;
            }
            .sidebar-brand { border-bottom: none; border-right: 1px solid var(--rule-faint); padding: 0.65rem 1rem; }
            .sidebar-nav { flex-direction: row; overflow-x: auto; padding: 0.35rem 0.5rem; gap: 0.25rem; }
            .sidebar-nav a { padding: 0.5rem 0.6rem; font-size: 0.72rem; white-space: nowrap; }
            .sidebar-nav a.active { color: var(--interactive-ink); background: var(--surface-active); }
            .sidebar-user { display: none; }
            .main { margin-left: 0; width: 100%; padding: 4.5rem 1rem 1rem; }
        }
        @media (max-width: 480px) {
            .main { padding: 4.5rem 0.75rem 0.75rem; }
            .page-head { flex-direction: column; align-items: flex-start; }
        }

        /* Compact retail shell: navigation remains a single top workstation bar. */
        .sidebar {
            top: 0; right: 0; bottom: auto; width: 100%; height: 48px;
            flex-direction: row; align-items: center; overflow: hidden;
            border-right: 0; border-bottom: 1px solid var(--rule-faint);
            box-shadow: 0 2px 12px rgba(83,58,58,.08);
        }
        .sidebar-brand { flex: 0 0 auto; padding: .45rem .75rem; border: 0; font-size: .82rem; letter-spacing: .08em; }
        .sidebar-brand-icon { width: 26px; height: 26px; }
        .sidebar-nav { min-width: 0; flex-direction: row; align-items: center; overflow-x: auto; padding: .25rem .35rem; gap: .3rem; }
        .sidebar-nav a { flex: 0 0 auto; min-height: 32px; padding: .35rem .58rem; font-size: .7rem; white-space: nowrap; }
        .sidebar-user { display: flex; align-items: center; gap: .6rem; flex: 0 0 auto; margin: 0 0 0 auto; padding: 0 .65rem; border: 0; }
        .sidebar-user-info { margin: 0; font-size: .65rem; text-align: right; white-space: nowrap; }
        .sidebar-user-info strong { display: inline; margin-right: .25rem; }
        .sidebar-logout { width: auto; padding: .38rem .6rem; font-size: .68rem; }
        .main { margin: 0; padding: .75rem 1rem; width: 100%; }
        body.authenticated-shell .main { margin-top: 48px; }
        .main.main-pos { height: calc(100dvh - 48px); padding: 8px; overflow: hidden; }
        @media (max-width: 900px) {
            .sidebar { height: 48px; flex-direction: row; }
            .sidebar-brand { padding: .35rem .5rem; }
            .sidebar-user { display: flex; }
            body.authenticated-shell .main { margin-top: 48px; width: 100%; padding: .75rem; }
            .main.main-pos { height: calc(100dvh - 48px); padding: 6px; }
        }
        @media (max-width: 760px) { .employee-form { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .discount-form { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .employee-fields { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .discount-fields { grid-template-columns: 1fr; } }
        @media (max-width: 560px) {
            .sidebar-user-info { display: none; }
            .sidebar-user { padding: 0 .35rem; }
            .sidebar-nav a { min-height: 30px; padding: .3rem .45rem; }
        }
    </style>
    @stack('styles')
</head>
<body class="{{ auth()->check() ? 'authenticated-shell' : '' }} {{ request()->routeIs('pos.*') ? 'pos-shell' : '' }}">
    @auth
        <aside class="sidebar">
            <a class="sidebar-brand" href="{{ $navEmployee?->hasRole('Cashier') ? route('pos.index') : route('dashboard') }}">
                <span class="sidebar-brand-icon">P</span>
                POS
            </a>
            @php
                $navOrder = ['dashboard', 'pos.index', 'customers.index', 'cash-drawers.index', 'categories.index', 'products.index', 'inventory.index', 'purchase-orders.index', 'suppliers.index', 'discounts.index', 'employees.index', 'audit-logs.index', 'reports.index'];
                $allowedNavModules = collect($navModules ?? [])->all();
                $visibleNavModules = array_values(array_filter($navOrder, fn ($name) => in_array($name, $allowedNavModules, true)));
            @endphp
            <nav class="sidebar-nav">
                @foreach ($visibleNavModules as $name)
                    <a href="{{ $name === 'dashboard' ? route('dashboard') : route($name) }}"
                       class="{{ request()->routeIs($name) || request()->routeIs(str_replace('.index', '.*', $name)) ? 'active' : '' }}">
                        {{ config('roles.labels')[$name] ?? $name }}
                    </a>
                @endforeach
            </nav>
            <div class="sidebar-user">
                <div class="sidebar-user-info">
                    <strong>{{ $navEmployee->displayName() }}</strong>
                    {{ $navEmployee->role->role_name ?? 'none' }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout">Logout</button>
                </form>
            </div>
        </aside>
    @endauth

    <main class="main {{ request()->routeIs('pos.index') ? 'main-pos' : '' }}">
        @if (session('status'))
            <div class="flash flash-ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="flash flash-error" @if (session('pos_reprint_no_receipt')) data-auto-dismiss-ms="3000" @endif>{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    <script>
    document.querySelectorAll('[data-auto-dismiss-ms]').forEach(function(flash) {
        var delay = Number(flash.dataset.autoDismissMs);
        window.setTimeout(function() { flash.remove(); }, Number.isFinite(delay) && delay > 0 ? delay : 3000);
    });

    // Date validation: discount end_date must be after start_date
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var startEl = form.querySelector('#start_date');
            var endEl = form.querySelector('#end_date');
            if (startEl && endEl && startEl.value && endEl.value) {
                if (endEl.value < startEl.value) {
                    e.preventDefault();
                    alert('End date must be on or after the start date.');
                    endEl.focus();
                    return false;
                }
            }
            // Ensure hire_date is not in the future
            var hireEl = form.querySelector('#hire_date');
            if (hireEl && hireEl.value) {
                var today = new Date().toISOString().split('T')[0];
                if (hireEl.value > today) {
                    e.preventDefault();
                    alert('Hire date cannot be in the future.');
                    hireEl.focus();
                    return false;
                }
            }
            // Ensure order_date is not too far in the past
            var orderEl = form.querySelector('#order_date');
            if (orderEl && orderEl.value) {
                var minDate = new Date();
                minDate.setDate(minDate.getDate() - 7);
                var minStr = minDate.toISOString().split('T')[0];
                if (orderEl.value < minStr) {
                    e.preventDefault();
                    alert('Order date cannot be more than 7 days in the past.');
                    orderEl.focus();
                    return false;
                }
            }
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
