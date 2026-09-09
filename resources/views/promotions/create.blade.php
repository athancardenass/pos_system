@extends('layouts.app')

@section('title', 'New promotion')

@section('content')
    <div class="page-head">
        <h1>New promotion</h1>
        <a class="btn btn-secondary" href="{{ route('promotions.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('promotions.store') }}">
            @csrf
            @include('promotions._form')
            <div class="form-actions">
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection
