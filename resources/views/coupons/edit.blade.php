@extends('layouts.app')

@section('title', 'Edit coupon')

@section('content')
    <div class="page-head">
        <h1>Edit coupon</h1>
        <a class="btn btn-secondary" href="{{ route('coupons.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('coupons.update', $coupon) }}">
            @csrf
            @method('PUT')
            @include('coupons._form')
            <div class="form-actions">
                <button type="submit">Update</button>
            </div>
        </form>
    </div>
@endsection
