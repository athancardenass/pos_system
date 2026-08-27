@extends('layouts.app')

@section('title', 'Edit product')

@section('content')
    <div class="page-head">
        <h1>Edit product</h1>
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')
            @include('products._form')
            <button type="submit">Update</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const generateBtn = document.getElementById('generate-barcode');
    const barcodeInput = document.getElementById('barcode');
    if (barcodeInput && barcodeInput.value.trim() !== '') {
        generateBtn.disabled = true;
    }
    generateBtn?.addEventListener('click', function () {
        if (barcodeInput.value.trim() !== '') return;
        fetch("{{ route('products.generate-barcode') }}")
            .then(r => r.json())
            .then(data => {
                barcodeInput.value = data.barcode;
                generateBtn.disabled = true;
            })
            .catch(() => alert('Could not generate a barcode. Try again.'));
    });
</script>
@endpush
