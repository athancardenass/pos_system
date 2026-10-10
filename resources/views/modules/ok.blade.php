<p>Access granted.</p>
<p>route: {{ $routeName }}</p>
<p>user: {{ $employee->username }}</p>
<p>role: {{ $employee->role->role_name ?? 'none' }}</p>
<p><a href="{{ route('dashboard') }}">Back to dashboard</a></p>
