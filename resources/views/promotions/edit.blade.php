@extends('layouts.app')

@section('title', 'Edit promotion')

@section('content')
    <div class="page-head">
        <h1>Edit promotion</h1>
        <a class="btn btn-secondary" href="{{ route('promotions.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('promotions.update', $promotion) }}">
            @csrf
            @method('PUT')
            @include('promotions._form')
            <div class="form-actions">
                <button type="submit">Update</button>
            </div>
        </form>
    </div>
@endsection
