@extends('layouts.app')

@section('title', 'Edit employee')

@section('content')
    <div class="page-head form-page-head">
        <div>
            <h1>Edit employee</h1>
            <p class="muted">Update the employee profile, account access, and status.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('employees.index') }}">Back</a>
    </div>
    <div class="card form-page-card">
        @include('partials.errors')
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf
            @method('PUT')
            @include('employees._form')
            <div class="form-actions">
                <button type="submit">Save employee changes</button>
            </div>
        </form>
    </div>
@endsection
