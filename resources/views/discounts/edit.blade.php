@extends('layouts.app')

@section('title', 'Edit discount')

@section('content')
    <div class="page-head form-page-head">
        <div>
            <h1>Edit discount</h1>
            <p class="muted">Update the discount details and availability dates.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back</a>
    </div>
    <div class="card form-page-card">
        @include('partials.errors')
        <form method="POST" action="{{ route('discounts.update', $discount) }}">
            @csrf
            @method('PUT')
            @include('discounts._form')
            <div class="form-actions">
                <button type="submit">Update discount</button>
            </div>
        </form>
    </div>
@endsection
