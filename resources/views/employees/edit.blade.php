@extends('layouts.app')

@section('title', 'Edit employee')

@section('content')
    <div class="page-head">
        <h1>Edit employee</h1>
        <a class="btn btn-secondary" href="{{ route('employees.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf
            @method('PUT')
            @include('employees._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
