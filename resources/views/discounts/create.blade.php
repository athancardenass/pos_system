@extends('layouts.app')

@section('title', 'New discount')

@section('content')
    <div class="page-head">
        <h1>New discount</h1>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('discounts.store') }}">
            @csrf
            @include('discounts._form')
            <button type="submit">Save</button>
        </form>
    </div>
@endsection
