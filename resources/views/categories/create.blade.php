@extends('layouts.app')

@section('title', 'New category')

@section('content')
    <div class="page-head">
        <h1>New category</h1>
        <a class="btn btn-secondary" href="{{ route('categories.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            @include('categories._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
