@extends('layouts.app')

@section('title', 'Audit logs')

@section('content')
    <div class="page-head">
        <h1>Audit logs</h1>
    </div>
    <div class="card">
        @if ($logs->isEmpty())
            <p class="empty">No activity recorded yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Employee</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ optional($log->action_timestamp)->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->employee->username ?? '—' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->table_affected }}</td>
                            <td>{{ $log->record_id }}</td>
                            <td class="muted">{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $logs->links('partials.pagination') }}
        @endif
    </div>
@endsection
