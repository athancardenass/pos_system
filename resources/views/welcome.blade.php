<!DOCTYPE html>
<html>
<head>
    <title>POS Login</title>
</head>
<body>
    <h2>POS System Login</h2>

    @if ($errors->any())
        <div style="color: red;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Username:</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus>
        <br><br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>