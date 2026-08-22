@extends('layouts.app')

@section('title', 'Forbidden')

@section('content')
    <div class="card">
        <h1>403 Forbidden</h1>
        <p class="error">Your role cannot access this page.</p>
        <p><a class="btn btn-secondary" href="{{ route('dashboard') }}">Back to dashboard</a></p>
    </div>
@endsection
