@extends('layouts.app')

@section('title', 'New coupon')

@section('content')
    <div class="page-head">
        <h1>New coupon</h1>
        <a class="btn btn-secondary" href="{{ route('coupons.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('coupons.store') }}">
            @csrf
            @include('coupons._form')
            <div class="form-actions">
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection
