@extends('layouts.app')

@section('title', 'Edit supplier')

@section('content')
    <div class="page-head">
        <h1>Edit supplier</h1>
        <a class="btn btn-secondary" href="{{ route('suppliers.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            @include('suppliers._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
