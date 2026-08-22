@extends('layouts.app')

@section('title', 'Edit product')

@section('content')
    <div class="page-head">
        <h1>Edit product</h1>
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')
            @include('products._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
