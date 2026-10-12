@extends('layouts.app')

@section('title', 'Supervisor permissions')

@section('content')
    <div class="page-head form-page-head">
        <div>
            <h1>Supervisor permissions</h1>
            <p class="muted">Choose which actions {{ $employee->displayName() }} may handle and set the maximum amount for each.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('employees.index') }}">Back to employees</a>
    </div>

    <div class="card">
        @include('partials.errors')
        <p class="muted">Blank limits disable that task. A Supervisor starts with no permissions. Managers keep authority to approve exceptional actions and amounts above these limits.</p>
        <form method="POST" action="{{ route('employees.supervisor-permissions.update', $employee) }}">
            @csrf
            @method('PUT')
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Maximum amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($actions as $action => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                <input type="number" name="permissions[{{ $action }}]" min="0.01" max="999999.99" step="0.01" inputmode="decimal" value="{{ old('permissions.'.$action, $permissionLimits[$action] ?? '') }}" aria-label="Maximum amount for {{ $label }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <div class="form-actions">
                <button type="submit">Save permissions</button>
            </div>
        </form>
    </div>
@endsection
