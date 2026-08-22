@extends('layouts.app')

@section('title', 'New product')

@section('content')
    <div class="page-head">
        <h1>New product</h1>
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
