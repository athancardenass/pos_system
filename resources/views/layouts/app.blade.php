<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS System')</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --danger: #f87171;
            --ok: #34d399;
            --line: #334155;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.5rem;
            background: var(--card);
            border-bottom: 1px solid var(--line);
        }
        .brand { color: var(--text); text-decoration: none; font-weight: 700; }
        .nav { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; }
        .nav a { color: var(--muted); text-decoration: none; font-size: 0.95rem; }
        .nav a.active, .nav a:hover { color: var(--accent); }
        .topbar-user { display: flex; align-items: center; gap: 0.75rem; }
        .topbar a, .topbar button {
            color: var(--accent);
            background: none;
            border: 0;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .page-head h1 { margin: 0; font-size: 1.4rem; }
        .muted { color: var(--muted); }
        .error, .flash-error { color: var(--danger); }
        .flash-ok { color: var(--ok); }
        .flash { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.35rem; }
        input, select, textarea {
            width: 100%;
            padding: 0.65rem 0.75rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: #0f172a;
            color: var(--text);
        }
        textarea { min-height: 90px; resize: vertical; }
        button[type="submit"], .btn {
            display: inline-block;
            background: var(--accent);
            color: #0f172a;
            border: 0;
            border-radius: 8px;
            padding: 0.65rem 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .btn-secondary { background: #334155; color: var(--text); }
        .btn-danger { background: var(--danger); color: #0f172a; }
        .btn-ghost { background: transparent; color: var(--accent); padding: 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .badge {
            display: inline-block;
            background: #0ea5e9;
            color: #0f172a;
            border-radius: 999px;
            padding: 0.15rem 0.7rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            text-align: left;
            padding: 0.7rem 0.5rem;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        th { color: var(--muted); font-weight: 600; font-size: 0.85rem; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0 1rem;
        }
        .inline-form { display: inline; }
        .empty { padding: 1.5rem 0; color: var(--muted); }
        .pagination { margin-top: 1rem; }
        .pagination a, .pagination span { color: var(--accent); margin-right: 0.5rem; }
        .warn { color: #fbbf24; }
    </style>
    @stack('styles')
</head>
<body>
    @auth
        <header class="topbar">
            <a class="brand" href="{{ route('dashboard') }}">POS System</a>
            <nav class="nav">
                @foreach ($navModules as $name)
                    <a href="{{ $name === 'dashboard' ? route('dashboard') : route($name) }}"
                       class="{{ request()->routeIs($name) || request()->routeIs(str_replace('.index', '.*', $name)) ? 'active' : '' }}">
                        {{ config('roles.labels.'.$name, $name) }}
                    </a>
                @endforeach
            </nav>
            <div class="topbar-user">
                <span class="muted">{{ $navEmployee->username }} · {{ $navEmployee->role->role_name ?? 'none' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="container">
        @if (session('status'))
            <p class="flash flash-ok">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="flash flash-error">{{ session('error') }}</p>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
