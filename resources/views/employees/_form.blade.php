@php($employee = $employee ?? null)
<div class="form-grid">
    <div>
        <label for="first_name">First name</label>
        <input id="first_name" name="first_name" value="{{ old('first_name', $employee?->first_name) }}" required>
    </div>
    <div>
        <label for="last_name">Last name</label>
        <input id="last_name" name="last_name" value="{{ old('last_name', $employee?->last_name) }}" required>
    </div>
    <div>
        <label for="username">Username</label>
        <input id="username" name="username" value="{{ old('username', $employee?->username) }}" required>
    </div>
    <div>
        <label for="password">Password @if($employee)<span class="muted">(leave blank to keep)</span>@endif</label>
        <input id="password" type="password" name="password" @required(! $employee) autocomplete="new-password">
    </div>
    <div>
        <label for="role_id">Role</label>
        <select id="role_id" name="role_id" required>
            @foreach ($roles as $role)
                <option value="{{ $role->role_id }}" @selected(old('role_id', $employee?->role_id) == $role->role_id)>{{ $role->role_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="contact_number">Contact number</label>
        <input id="contact_number" name="contact_number" value="{{ old('contact_number', $employee?->contact_number) }}">
    </div>
    <div>
        <label for="hire_date">Hire date</label>
        <input id="hire_date" type="date" name="hire_date" value="{{ old('hire_date', optional($employee?->hire_date)->format('Y-m-d') ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="1900-01-01" required>
    </div>
    <div>
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach (['active', 'inactive'] as $status)
                <option value="{{ $status }}" @selected(old('status', $employee?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
</div>
