@extends('layouts.app')

@section('title', 'New product')

@section('content')
    <div class="page-head">
        <h1>New product</h1>
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Back</a>
    </div>
    <div class="card">
        @include('partials.errors')
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
            <div class="form-actions" style="margin-top: 0.5rem;">
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const generateBtn = document.getElementById('generate-barcode');
    const barcodeInput = document.getElementById('barcode');
    // Already has a code (edit form or prior generate)? Lock the button.
    if (barcodeInput && barcodeInput.value.trim() !== '') {
        generateBtn.disabled = true;
    }
    generateBtn?.addEventListener('click', function () {
        if (barcodeInput.value.trim() !== '') return; // don't overwrite / spam
        fetch("{{ route('products.generate-barcode') }}")
            .then(r => r.json())
            .then(data => {
                barcodeInput.value = data.barcode;
                generateBtn.disabled = true; // one-time only
            })
            .catch(() => alert('Could not generate a barcode. Try again.'));
    });
</script>
@endpush
