@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="card" style="max-width: 420px; margin: 4rem auto;">
        <h1>Employee Login</h1>
        <p class="muted">Sign in with your POS username.</p>
        @include('partials.errors')
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
@endsection
