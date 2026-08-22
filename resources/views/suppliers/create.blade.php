@extends('layouts.app')

@section('title', 'New supplier')

@section('content')
    <div class="page-head">
        <h1>New supplier</h1>
        <a class="btn btn-secondary" href="{{ route('suppliers.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            @include('suppliers._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
