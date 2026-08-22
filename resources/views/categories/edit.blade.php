@extends('layouts.app')

@section('title', 'Edit category')

@section('content')
    <div class="page-head">
        <h1>Edit category</h1>
        <a class="btn btn-secondary" href="{{ route('categories.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('categories._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
