<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FEDAB8;
            --surface: #FFFFFF;
            --text: #203C3D;
            --muted: #5A7A7B;
            --rule: #203C3D;
            --accent: #C4504A;
            --success: #2D8A4E;
            --danger: #C4504A;
            --sidebar-w: 230px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
        h2 { font-size: 1.15rem; font-weight: 700; }

        /* --- Sidebar --- */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 2px solid var(--rule);
            display: flex; flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 1.25rem 1.25rem;
            border-bottom: 2px solid var(--rule);
            text-decoration: none; color: var(--text);
            font-weight: 700; font-size: 1.05rem;
            text-transform: uppercase; letter-spacing: 0.12em;
        }
        .sidebar-brand-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            background: var(--accent); color: #fff;
            font-weight: 800; font-size: 0.75rem;
        }
        .sidebar-nav { display: flex; flex-direction: column; flex: 1; padding: 0.5rem 0; }
        .sidebar-nav a {
            text-decoration: none; color: var(--muted);
            font-size: 0.82rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.08em;
            padding: 0.65rem 1.25rem;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .sidebar-nav a:hover { color: var(--text); background: rgba(32,60,61,0.04); }
        .sidebar-nav a.active {
            color: var(--accent); border-left-color: var(--accent);
            font-weight: 700;
        }
        .sidebar-user {
            border-top: 2px solid var(--rule);
            padding: 1rem 1.25rem; margin-top: auto;
        }
        .sidebar-user-info {
            font-size: 0.75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--muted); margin-bottom: 0.6rem;
        }
        .sidebar-user-info strong { color: var(--text); display: block; }
        .sidebar-logout {
            display: block; width: 100%; padding: 0.5rem;
            background: var(--text); color: #fff; border: none;
            font-family: inherit; font-size: 0.78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            cursor: pointer; text-align: center;
        }

        /* --- Main Content --- */
        .main { margin-left: var(--sidebar-w); padding: 2rem 2.5rem; width: calc(100% - var(--sidebar-w)); }
        .page-head {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; margin-bottom: 1.5rem;
            padding-bottom: 1rem; border-bottom: 2px solid var(--rule);
            flex-wrap: wrap;
        }

        /* --- Card --- */
        .card {
            background: var(--surface); border: 2px solid var(--rule);
            border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;
            box-shadow: 4px 4px 0 rgba(32,60,61,0.12);
            overflow-x: auto; -webkit-overflow-scrolling: touch;
        }

        /* --- Buttons --- */
        button[type="submit"], .btn {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.55rem 1.1rem;
            background: var(--text); color: #fff; border: none;
            border-radius: 4px;
            font-family: inherit; font-size: 0.82rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
            cursor: pointer; text-decoration: none; transition: opacity 0.15s;
        }
        button[type="submit"]:hover, .btn:hover { opacity: 0.85; }
        .btn-secondary { background: var(--surface); color: var(--text); border: 2px solid var(--text); }
        .btn-danger { background: var(--danger); }
        .btn-ghost { background: transparent; border: none; padding: 0.25rem 0.5rem; color: var(--danger); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; text-decoration: underline; text-underline-offset: 2px; }
        .btn-ghost:hover { opacity: 0.7; }
        .qty-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; padding: 0;
            background: var(--surface); color: var(--text);
            border: 2px solid var(--rule); border-radius: 4px;
            font-family: inherit; font-size: 1rem; font-weight: 700; line-height: 1;
            cursor: pointer; transition: all 0.12s;
        }
        .qty-btn:hover { background: var(--text); color: #fff; }
        div.actions { display: inline-flex; flex-wrap: nowrap; gap: 0.5rem; align-items: center; white-space: nowrap; }

        /* --- Forms --- */
        label {
            display: block; margin-bottom: 0.3rem;
            font-size: 0.72rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted);
        }
        input[type="text"], input[type="password"], input[type="email"],
        input[type="number"], input[type="date"], input[type="search"],
        select, textarea {
            width: 100%; padding: 0.6rem 0;
            margin-bottom: 1.1rem;
            border: none; border-bottom: 2px solid var(--rule);
            background: transparent; color: var(--text);
            font-family: inherit; font-size: 0.92rem;
            outline: none; transition: border-color 0.15s;
        }
        input:focus, select:focus, textarea:focus { border-bottom-color: var(--accent); border-bottom-width: 3px; }
        textarea { min-height: 90px; resize: vertical; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0 2rem;
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
        thead th:first-child { border-radius: 6px 0 0 6px; }
        thead th:last-child { border-radius: 0 6px 6px 0; }
        tbody td { border-bottom: 1px solid rgba(32,60,61,0.12); transition: background 0.12s; }
        tbody tr:nth-child(even) { background: rgba(32,60,61,0.025); }
        tbody tr:hover { background: rgba(196,80,74,0.06); }
        tbody td:first-child { font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:last-child td.actions { border-bottom: 1px solid rgba(32,60,61,0.12); }
        td code {
            background: rgba(32,60,61,0.07);
            padding: 0.2rem 0.5rem; border-radius: 4px;
            font-size: 0.78rem; letter-spacing: 0.02em;
        }
        th:last-child { width: auto; }
        td.actions {
            width: 25%; text-align: right;
            border-bottom: 1px solid rgba(32,60,61,0.12);
            padding: 0.5rem 0.25rem 0.5rem 1rem;
        }
        td.actions .actions { display: inline-flex; gap: 0.4rem; align-items: center; }
        td.actions .inline-form { display: inline-flex; align-items: center; margin: 0; }
        td.actions a { font-size: 0.8rem; font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }

        /* --- Badge --- */
        .badge {
            display: inline-block; padding: 0.2rem 0.6rem;
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            border-radius: 4px;
        }
        .badge-active { background: rgba(45,138,78,0.12); color: var(--success); border: 1px solid rgba(45,138,78,0.25); }
        .badge-inactive { background: rgba(196,80,74,0.12); color: var(--danger); border: 1px solid rgba(196,80,74,0.25); }
        .badge-pending { background: rgba(196,149,106,0.15); color: #8B6F47; border: 1px solid rgba(196,149,106,0.3); }

        /* --- Flash --- */
        .flash { padding: 0.85rem 1.1rem; margin-bottom: 1.5rem; border-left: 4px solid; font-weight: 500; font-size: 0.88rem; }
        .flash-ok { background: rgba(45,138,78,0.08); border-left-color: var(--success); color: var(--success); }
        .flash-error, .flash-danger { background: rgba(196,80,74,0.08); border-left-color: var(--danger); color: var(--danger); }
        .flash-warning { background: rgba(196,149,106,0.12); border-left-color: #C4956A; color: #8B6F47; }
        .flash-info { background: rgba(32,60,61,0.06); border-left-color: var(--text); color: var(--text); }
        .error, .error li { color: var(--danger); }

        /* --- Pagination --- */
        .pagination {
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 2px solid var(--rule);
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
            color: var(--text); border: 1px solid var(--rule);
            border-radius: 4px;
            background: var(--surface); transition: all 0.15s;
        }
        a.pagination-btn:hover { background: var(--text); color: #fff; }
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
        .warn { color: #C4956A; font-weight: 700; }
        .inline-form { display: inline; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; text-underline-offset: 2px; }

        /* --- Responsive --- */
        @media (max-width: 1024px) {
            .main { padding: 1.5rem 1.5rem; }
        }
        @media (max-width: 900px) {
            .sidebar {
                position: fixed; top: 0; left: 0; right: 0; bottom: auto;
                width: 100%; height: auto; border-right: none;
                border-bottom: 2px solid var(--rule); flex-direction: row; align-items: center;
            }
            .sidebar-brand { border-bottom: none; border-right: 2px solid var(--rule); padding: 0.65rem 1rem; }
            .sidebar-nav { flex-direction: row; overflow-x: auto; padding: 0; }
            .sidebar-nav a { padding: 0.65rem 0.6rem; font-size: 0.72rem; border-left: none; border-bottom: 3px solid transparent; white-space: nowrap; }
            .sidebar-nav a.active { border-bottom-color: var(--accent); }
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
            <a class="sidebar-brand" href="{{ route('dashboard') }}">
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
                    <strong>{{ $navEmployee->username }}</strong>
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
