@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Logged in as {{ $employee->username }} ({{ $employee->role->role_name ?? 'none' }})</p>
        </div>
    </div>
    <div class="card">
        <p>Open a module from the navigation bar. Cashiers can ring up sales and manage customers; managers handle catalog and stock; admins also manage employees and audit logs.</p>
    </div>
@endsection
