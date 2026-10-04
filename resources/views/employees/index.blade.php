@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="page-head">
        <h1>Employees</h1>
        <x-ui.button :href="route('employees.create')">New employee</x-ui.button>
    </div>
    <div class="card">
        <x-ui.table class="employee-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr>
                        <td style="font-weight: 700;">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                        <td>{{ $employee->username }}</td>
                        <td>{{ $employee->role->role_name ?? '—' }}</td>
                        <td><x-ui.badge :variant="$employee->status === 'active' ? 'active' : 'inactive'">{{ $employee->status }}</x-ui.badge></td>
                        <td class="actions">
                            <x-ui.button variant="slate" size="compact" :href="route('employees.edit', $employee)">Edit</x-ui.button>
                            <form class="inline-form" method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button variant="danger" type="submit" size="compact">Delete</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
        {{ $employees->links('partials.pagination') }}
    </div>
@endsection
