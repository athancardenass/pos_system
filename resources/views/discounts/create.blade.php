@extends('layouts.app')

@section('title', 'New discount')

@section('content')
    <div class="page-head form-page-head">
        <div>
            <h1>New discount</h1>
            <p class="muted">Set the discount details and when it is available.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back</a>
    </div>
    <div class="card form-page-card">
        @include('partials.errors')
        <form method="POST" action="{{ route('discounts.store') }}">
            @csrf
            @include('discounts._form')
            <div class="form-actions">
                <button type="submit">Create discount</button>
            </div>
        </form>
    </div>
@endsection
