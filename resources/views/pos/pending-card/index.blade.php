@extends('layouts.app')
@section('title', 'Pending Card payments')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos-reference.css') }}">
@endpush
@section('content')
<div class="pos-reference">
    @include('pos._pending-workspace', ['paymentKind' => 'card'])
</div>
@endsection