@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="page-head">
        <h1>Employees</h1>
        <a class="btn" href="{{ route('employees.create') }}">New employee</a>
    </div>
    <div class="card">
        <table>
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
                        <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                        <td>{{ $employee->username }}</td>
                        <td>{{ $employee->role->role_name ?? '—' }}</td>
                        <td><span class="badge">{{ $employee->status }}</span></td>
                        <td class="actions">
                            <a href="{{ route('employees.edit', $employee) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $employees->links() }}</div>
    </div>
@endsection
