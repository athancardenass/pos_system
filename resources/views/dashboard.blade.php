<p>Logged in as {{ $employee->username }} ({{ $employee->role->role_name ?? 'none' }})</p>

<p>Allowed modules:</p>
<ul>
    @foreach ($modules as $name)
        @if ($name === 'dashboard')
            <li>dashboard</li>
        @else
            <li><a href="{{ route($name) }}">{{ $name }}</a></li>
        @endif
    @endforeach
</ul>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>
