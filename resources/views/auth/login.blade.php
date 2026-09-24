@extends('layouts.app')

@section('title', 'Terminal Login — POS System')

@section('content')
    <div class="login-wrapper">
        <div class="login-card">
            {{-- Header Branding --}}
            <div class="login-header">
                <div class="login-brand-badge">
                    <svg class="login-brand-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h1 class="login-title">POS SYSTEM</h1>
                <p class="login-subtitle">Supermarket Terminal & Operations</p>
                <div class="login-system-status">
                    <span class="status-pulse-dot"></span>
                    <span>Terminal Online &bull; Asia/Manila (PHT)</span>
                </div>
            </div>

            @include('partials.errors')

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" class="login-form" id="terminal-login-form">
                @csrf
                <div class="login-group">
                    <label for="username">Operator Username</label>
                    <div class="input-with-icon">
                        <span class="field-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter operator username"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="login-group">
                    <label for="password">Security Password</label>
                    <div class="input-with-icon">
                        <span class="field-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter security password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password-btn" id="toggle-pw-btn" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn" id="login-submit-btn">
                    <span>Sign In to Terminal</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="login-footer">
                <div class="footer-security-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Authorized Personnel Only &bull; Shift Audited</span>
                </div>
                <div class="dev-demo-hint">
                    Demo credentials: <strong>manager</strong> or <strong>cashier</strong> (password: <code>password</code>)
                </div>
            </div>
        </div>
    </div>

    <style>
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 2.5rem 1.25rem;
        }

        .login-card {
            background: var(--surface);
            border: 2px solid var(--rule);
            border-radius: var(--r-lg);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2.25rem 2rem;
            box-shadow: var(--shadow-pop);
            position: relative;
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            background: var(--accent);
            color: #fff;
            border-radius: var(--r);
            box-shadow: 0 10px 24px -10px rgba(196, 80, 74, 0.95);
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 0.25rem;
            color: var(--text);
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .login-system-status {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--bg-tint);
            border: 1px solid var(--rule-faint);
            padding: 0.25rem 0.65rem;
            border-radius: var(--r-pill);
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text);
        }

        .status-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 2px rgba(45, 138, 78, 0.25);
        }

        /* Form */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .login-group {
            display: flex;
            flex-direction: column;
        }

        .login-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .input-with-icon {
            position: relative;
            display: block;
            width: 100%;
        }

        .field-icon {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            pointer-events: none;
            z-index: 2;
        }

        .input-with-icon input {
            width: 100%;
            height: 48px;
            padding: 0 2.6rem 0 2.75rem;
            margin-bottom: 0 !important;
            border: 2px solid var(--rule);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            line-height: 44px;
            outline: none;
            transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
        }

        .input-with-icon input:focus {
            border-color: var(--accent);
            box-shadow: var(--ring);
        }

        .input-with-icon input::placeholder {
            color: var(--muted);
            opacity: 0.6;
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: transparent;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--r-sm);
            transition: color 0.15s;
            z-index: 2;
        }

        .toggle-password-btn:hover {
            color: var(--text);
        }

        .login-btn {
            width: 100%;
            padding: 0.95rem;
            background: var(--text);
            color: #fff;
            border: none;
            border-radius: var(--r);
            font-family: inherit;
            font-size: 0.92rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(32, 60, 61, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: transform 0.15s var(--ease), background-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
            margin-top: 0.35rem;
        }

        .login-btn:hover {
            background: #172D2E;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(32, 60, 61, 0.35);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Footer */
        .login-footer {
            margin-top: 1.5rem;
            padding-top: 1.15rem;
            border-top: 1px solid var(--rule-faint);
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .footer-security-note {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 600;
        }

        .dev-demo-hint {
            font-size: 0.72rem;
            color: var(--muted);
        }

        .dev-demo-hint code {
            background: var(--bg-tint);
            padding: 0.1rem 0.35rem;
            border-radius: var(--r-sm);
            font-family: monospace;
            border: 1px solid var(--rule-faint);
        }

        /* Error box */
        .login-card .error {
            margin-bottom: 1.25rem;
            padding: 0.85rem 1rem;
            background: var(--accent-soft);
            border-left: 4px solid var(--danger);
            border-radius: var(--r-sm);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--danger);
        }

        .login-card .error li {
            margin: 0;
            list-style: none;
        }

        /* Layout adjustment: hide sidebar on login view */
        .main {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 0 !important;
        }
    </style>

    <script>
        function togglePasswordVisibility() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (!pw) return;

            if (pw.type === 'password') {
                pw.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                pw.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
@endsection
