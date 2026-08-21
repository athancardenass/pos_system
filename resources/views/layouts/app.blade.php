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
            padding: 1rem 1.5rem;
            background: var(--card);
            border-bottom: 1px solid #334155;
        }
        .topbar a, .topbar button {
            color: var(--accent);
            background: none;
            border: 0;
            cursor: pointer;
            font-size: 1rem;
        }
        .container { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem; }
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .muted { color: var(--muted); }
        .error { color: var(--danger); }
        label { display: block; margin-bottom: 0.35rem; }
        input {
            width: 100%;
            padding: 0.65rem 0.75rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: var(--text);
        }
        button[type="submit"] {
            background: var(--accent);
            color: #0f172a;
            border: 0;
            border-radius: 8px;
            padding: 0.7rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
        }
        .badge {
            display: inline-block;
            background: #0ea5e9;
            color: #0f172a;
            border-radius: 999px;
            padding: 0.15rem 0.7rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    @auth
        <header class="topbar">
            <strong>POS System</strong>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </header>
    @endauth

    <main class="container">
        @yield('content')
    </main>
</body>
</html>
