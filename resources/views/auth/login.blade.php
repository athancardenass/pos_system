@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <span class="login-logo-icon">P</span>
                </div>
                <h1 class="login-title">POS System</h1>
                <p class="login-subtitle">Sign in to your account</p>
            </div>

            @include('partials.errors')

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf
                <div class="login-field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="login-btn">
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p class="login-hint">
                    Demo: <strong>admin</strong> / <strong>manager</strong> / <strong>cashier</strong> &mdash; password: <strong>password</strong>
                </p>
            </div>
        </div>
    </div>

    <style>
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            padding: 2rem 1rem;
        }

        .login-card {
            background: var(--surface);
            border: 2px solid var(--rule);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem 2rem;
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: var(--accent);
            color: #fff;
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .login-subtitle {
            font-size: 0.82rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 500;
        }

        /* Form */
        .login-form {
            display: flex;
            flex-direction: column;
        }

        .login-field {
            margin-bottom: 1.25rem;
        }

        .login-field label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
        }

        .login-field input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 2px solid var(--rule);
            background: transparent;
            color: var(--text);
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.15s;
        }

        .login-field input::placeholder {
            color: var(--muted);
            opacity: 0.5;
        }

        .login-field input:focus {
            border-color: var(--accent);
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--text);
            color: #fff;
            border: none;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            cursor: pointer;
            transition: opacity 0.15s;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            opacity: 0.85;
        }

        .login-btn:active {
            opacity: 0.75;
        }

        /* Footer */
        .login-footer {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }

        .login-hint {
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.6;
        }

        .login-hint strong {
            color: var(--text);
            font-weight: 600;
        }

        /* Override error styling for card context */
        .login-card .error {
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: rgba(196, 80, 74, 0.06);
            border-left: 3px solid var(--danger);
            font-size: 0.82rem;
        }

        .login-card .error li {
            margin: 0;
            list-style: none;
        }

        /* Remove sidebar offset for login page */
        .main {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 0 !important;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.25rem 1.5rem;
            }
        }
    </style>
@endsection
