@extends('layouts.app')

@section('title', 'New customer')

@section('content')
    <div class="page-head">
        <h1>New customer</h1>
        <a class="btn btn-secondary" href="{{ route('customers.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
