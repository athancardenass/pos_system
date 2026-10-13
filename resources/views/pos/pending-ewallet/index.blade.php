@extends('layouts.app')
@section('title', 'E-wallet payment checks')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos-reference.css') }}">
@endpush
@section('content')
<div class="pos-reference">
    @include('pos._pending-workspace', ['paymentKind' => 'e-wallet'])
</div>
@endsection