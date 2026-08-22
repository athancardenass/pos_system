@extends('layouts.app')

@section('title', 'Edit customer')

@section('content')
    <div class="page-head">
        <h1>Edit customer</h1>
        <a class="btn btn-secondary" href="{{ route('customers.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('customers._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
