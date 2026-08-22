@extends('layouts.app')

@section('title', 'Edit discount')

@section('content')
    <div class="page-head">
        <h1>Edit discount</h1>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('discounts.update', $discount) }}">
            @csrf
            @method('PUT')
            @include('discounts._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection
