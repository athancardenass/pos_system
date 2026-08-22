@extends('layouts.app')

@section('title', 'New employee')

@section('content')
    <div class="page-head">
        <h1>New employee</h1>
        <a class="btn btn-secondary" href="{{ route('employees.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf
            @include('employees._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
