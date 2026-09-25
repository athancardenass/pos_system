<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* --- Palette: softened "peach fuzz" canvas --- */
            --bg: #FFF3E7;
            --bg-tint: #FFE9D7;
            --surface: #FFFFFF;
            --surface-soft: #FFFCF8;
            --text: #203C3D;
            --muted: #66888A;
            --rule: #203C3D;
            --rule-faint: rgba(32, 60, 61, 0.12);
            --accent: #C4504A;
            --accent-soft: rgba(196, 80, 74, 0.10);
            --success: #2D8A4E;
            --success-soft: rgba(45, 138, 78, 0.12);
            --danger: #C4504A;
            --warn: #C4956A;
            --warn-ink: #8B6F47;

            /* --- Shape --- */
            --r-sm: 6px;
            --r: 10px;
            --r-lg: 14px;
            --r-pill: 999px;

            /* --- Depth: soft and modern; form controls stay flat --- */
            --shadow-card: 0 1px 2px rgba(32, 60, 61, 0.05), 0 14px 30px -20px rgba(32, 60, 61, 0.38);
            --shadow-pop: 0 2px 4px rgba(32, 60, 61, 0.06), 0 18px 34px -20px rgba(32, 60, 61, 0.45);
            --ring: 0 0 0 4px rgba(196, 80, 74, 0.16);

            --ease: cubic-bezier(0.2, 0.7, 0.3, 1);
            --sidebar-w: 230px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(1100px 520px at 88% -8%, #FFE6CD 0%, rgba(255, 230, 205, 0) 62%);
            background-attachment: fixed;
            color: var(--text);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        ::selection { background: var(--accent-soft); color: var(--text); }
        h1 { font-size: clamp(1.4rem, 1.1rem + 0.7vw, 1.6rem); font-weight: 700; letter-spacing: -0.025em; }
        h2 { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.015em; }

        /* --- Sidebar --- */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--rule-faint);
            box-shadow: 0 0 60px -40px rgba(32, 60, 61, 0.55);
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
            box-shadow: 0 8px 16px -10px rgba(196, 80, 74, 0.95);
        }
        .sidebar-nav { display: flex; flex-direction: column; flex: 1; padding: 0.75rem; gap: 0.15rem; }
        .sidebar-nav a {
            text-decoration: none; color: var(--muted);
            font-size: 0.82rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.08em;
            padding: 0.6rem 0.75rem;
            border-radius: var(--r-sm);
            transition: color 0.15s var(--ease), background-color 0.15s var(--ease);
        }
        .sidebar-nav a:hover { color: var(--text); background: var(--bg-tint); }
        .sidebar-nav a.active {
            color: var(--accent); background: var(--accent-soft);
            font-weight: 700;
        }
        .sidebar-user {
            border-top: 1px solid var(--rule-faint);
            padding: 1rem 1.25rem; margin-top: auto;
        }
        .sidebar-user-info {
            font-size: 0.75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--muted); margin-bottom: 0.6rem;
        }
        .sidebar-user-info strong { color: var(--text); display: block; }
        .sidebar-logout {
            display: block; width: 100%; padding: 0.6rem;
            background: var(--text); color: #fff; border: none;
            border-radius: var(--r-sm);
            font-family: inherit; font-size: 0.78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            cursor: pointer; text-align: center;
            transition: background-color 0.15s var(--ease), transform 0.15s var(--ease);
        }
        .sidebar-logout:hover { background: #2B4D4E; transform: translateY(-1px); }

        /* --- Main Content --- */
        .main { margin-left: var(--sidebar-w); padding: 2rem 2.5rem; width: calc(100% - var(--sidebar-w)); }
        .page-head {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; margin-bottom: 1.5rem;
            padding-bottom: 1rem; border-bottom: 1px solid var(--rule-faint);
            flex-wrap: wrap;
        }

        /* --- Card --- */
        .card {
            background: var(--surface); border: 2px solid var(--rule);
            border-radius: var(--r-lg); padding: 1.35rem; margin-bottom: 1.5rem;
            box-shadow: var(--shadow-card);
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            transition: box-shadow 0.2s var(--ease);
        }
        .card:hover { box-shadow: var(--shadow-pop); }

        /* --- Buttons --- */
        button[type="submit"], .btn,
        a.btn, a.btn:link, a.btn:visited {
            --btn-bg: var(--text);
            --btn-bg-hover: #2B4D4E;
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.6rem 1.15rem;
            background: var(--btn-bg);
            color: #ffffff !important;
            border: none;
            border-radius: var(--r);
            font-family: inherit; font-size: 0.82rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 1px 2px rgba(32, 60, 61, 0.16);
            transition: background-color 0.15s var(--ease), box-shadow 0.15s var(--ease), transform 0.15s var(--ease), color 0.15s var(--ease);
        }
        button[type="submit"]:hover, .btn:hover,
        a.btn:hover {
            background-color: var(--btn-bg-hover);
            color: #ffffff !important;
            box-shadow: 0 8px 18px -10px rgba(32, 60, 61, 0.75);
            transform: translateY(-1px);
        }
        button[type="submit"]:active, .btn:active,
        a.btn:active {
            transform: translateY(0);
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgba(32, 60, 61, 0.2);
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
            --btn-bg-hover: var(--bg-tint);
            color: var(--text) !important;
            border: 2px solid var(--text);
        }
        .btn-secondary:hover, a.btn-secondary:hover,
        .btn-secondary:focus-visible, a.btn-secondary:focus-visible,
        .btn-secondary:active, a.btn-secondary:active {
            color: var(--text) !important;
        }
        .btn-danger { --btn-bg: var(--danger); --btn-bg-hover: #A8403A; color: #ffffff !important; }
        .btn-ghost { --btn-bg-hover: transparent; background: transparent; border: none; box-shadow: none; padding: 0.25rem 0.5rem; color: var(--danger); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; text-decoration: underline; text-underline-offset: 2px; }
        .btn-ghost:hover { transform: none; opacity: 0.7; }
        .qty-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; padding: 0;
            background: var(--surface); color: var(--text);
            border: 2px solid var(--rule); border-radius: var(--r-sm);
            font-family: inherit; font-size: 1rem; font-weight: 700; line-height: 1;
            cursor: pointer; transition: background-color 0.12s var(--ease), color 0.12s var(--ease), transform 0.12s var(--ease);
        }
        .qty-btn:hover { background: var(--text); color: #fff; transform: translateY(-1px); }
        div.actions { display: inline-flex; flex-wrap: nowrap; gap: 0.5rem; align-items: center; white-space: nowrap; }

        /* --- Forms --- */
        label {
            display: block; margin-bottom: 0.35rem;
            font-size: 0.72rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted);
        }
        input[type="text"], input[type="password"], input[type="email"],
        input[type="number"], input[type="date"], input[type="search"],
        select, textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            margin-bottom: 1.1rem;
            background: var(--surface); border: 2px solid var(--rule);
            border-radius: var(--r-sm); color: var(--text);
            font-family: inherit; font-size: 1rem; line-height: 1.4;
            outline: none;
            transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent); box-shadow: var(--ring);
        }
        /* Larger fields only for prominent text inputs (name, barcode, description). */
        .input-lg { padding: 0.85rem 1rem; font-size: 1rem; line-height: 1.4; }
        /* Explicit solid border for the prominent text boxes (name/barcode/description). */
        .bordered { border: 2px solid var(--rule); border-radius: var(--r-sm); background: var(--surface); }
        /* Full-width field whose action button sits below the input. */
        .field-stack { grid-column: 1 / -1; margin-bottom: 1.25rem; }
        .field-stack .btn { margin-top: 0.5rem; }
        /* Generate button after it's used once (no spam). */
        #generate-barcode:disabled { opacity: 0.5; cursor: not-allowed; }
        textarea { min-height: 90px; resize: vertical; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0 2rem;
        }
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
            height: 3.35rem;
            padding: 0.85rem 1rem;
            background: var(--surface);
            border: 2px solid var(--rule);
            border-radius: var(--r-sm);
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
        th, td {
            padding: 0.75rem 1rem;
            text-align: left; vertical-align: middle;
        }
        thead th {
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em;
            background: var(--text); color: #fff;
            border-bottom: none;
            padding: 0.7rem 1rem;
            white-space: nowrap;
        }
        thead th:first-child { border-radius: var(--r-sm) 0 0 var(--r-sm); }
        thead th:last-child { border-radius: 0 var(--r-sm) var(--r-sm) 0; }
        tbody td { border-bottom: 1px solid var(--rule-faint); transition: background-color 0.12s var(--ease); }
        tbody tr:nth-child(even) { background: var(--surface-soft); }
        tbody tr:hover { background: var(--accent-soft); }
        tbody td:first-child { font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:last-child td.actions { border-bottom: 1px solid var(--rule-faint); }
        td code {
            background: rgba(32, 60, 61, 0.07);
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
        td.actions a { font-size: 0.8rem; font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
        /* Long text (names, emails, addresses) wraps instead of breaking the layout. */
        tbody td { word-break: break-word; overflow-wrap: break-word; }
        tbody td:not(.actions) { max-width: 260px; }

        /* --- Badge --- */
        .badge {
            display: inline-block; padding: 0.25rem 0.65rem;
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            border-radius: var(--r-pill);
        }
        .badge-active { background: var(--success-soft); color: var(--success); border: 1px solid rgba(45, 138, 78, 0.25); }
        .badge-inactive { background: var(--accent-soft); color: var(--danger); border: 1px solid rgba(196, 80, 74, 0.25); }
        .badge-pending { background: rgba(196, 149, 106, 0.15); color: var(--warn-ink); border: 1px solid rgba(196, 149, 106, 0.3); }
        .badge-balanced { background: var(--success-soft); color: var(--success); border: 1px solid rgba(45, 138, 78, 0.25); }
        .badge-shortage { background: var(--accent-soft); color: var(--danger); border: 1px solid rgba(196, 80, 74, 0.25); }
        .badge-overage { background: rgba(32, 60, 61, 0.08); color: var(--text); border: 1px solid var(--rule-faint); }

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
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-pop);
        }
        .stat-card-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
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
            border-left: 4px solid; border-radius: var(--r-sm);
            font-weight: 500; font-size: 0.88rem;
        }
        .flash-ok { background: rgba(45, 138, 78, 0.08); border-left-color: var(--success); color: var(--success); }
        .flash-error, .flash-danger { background: rgba(196, 80, 74, 0.08); border-left-color: var(--danger); color: var(--danger); }
        .flash-warning { background: rgba(196, 149, 106, 0.12); border-left-color: var(--warn); color: var(--warn-ink); }
        .flash-info { background: rgba(32, 60, 61, 0.06); border-left-color: var(--text); color: var(--text); }
        .error, .error li { color: var(--danger); }

        /* --- Pagination --- */
        .pagination {
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--rule-faint);
            display: flex; justify-content: space-between; align-items: center;
            gap: 0.75rem; flex-wrap: wrap;
        }
        .pagination-info {
            font-size: 0.78rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
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
        a.pagination-btn:hover { background: var(--text); color: #fff; border-color: var(--text); }
        .pagination-btn.is-current {
            background: var(--accent); border-color: var(--accent); color: #fff;
            font-weight: 700;
        }
        .pagination-btn.is-disabled {
            color: var(--muted); border-color: #d0d0d0; opacity: 0.6; cursor: default;
        }
        .pagination-ellipsis { padding: 0 0.35rem; color: var(--muted); font-weight: 700; }

        /* --- Misc --- */
        .muted { color: var(--muted); }
        .empty { padding: 2.5rem 0; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; text-align: center; font-size: 0.85rem; }
        .warn { color: var(--warn); font-weight: 700; }
        .inline-form { display: inline; }
        a { color: var(--accent); text-decoration: none; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        a:hover { text-decoration: underline; text-underline-offset: 2px; }

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
            .sidebar-nav a.active { background: var(--accent-soft); }
            .sidebar-user { display: none; }
            .main { margin-left: 0; width: 100%; padding: 4.5rem 1rem 1rem; }
        }
        @media (max-width: 480px) {
            .main { padding: 4.5rem 0.75rem 0.75rem; }
            .page-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @auth
        <aside class="sidebar">
            <a class="sidebar-brand" href="{{ $navEmployee?->hasRole('Cashier') ? route('pos.index') : route('dashboard') }}">
                <span class="sidebar-brand-icon">P</span>
                POS
            </a>
            <nav class="sidebar-nav">
                @foreach ($navModules as $name)
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

    <main class="main">
        @if (session('status'))
            <div class="flash flash-ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="flash flash-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    <script>
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
