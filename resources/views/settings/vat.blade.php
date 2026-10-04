@extends('layouts.app')

@section('title', 'VAT Settings')

@push('styles')
<style>
    .vat-settings-card { max-width: 720px; }
    .vat-settings-description { max-width: 68ch; color: var(--muted); font-size: 0.9rem; }
    .vat-settings-card form { margin-top: 1.25rem; }
    .vat-rate-input { max-width: 220px; }
    .vat-rate-help { margin-top: -0.45rem; color: var(--muted); font-size: 0.78rem; }
</style>
@endpush

@section('content')
    <div class="page-head">
        <h1>VAT settings</h1>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back to discounts</a>
    </div>

    <div class="card vat-settings-card">
        <p class="vat-settings-description">
            Set the VAT rate used to calculate the VAT portion shown on receipts. Product prices remain VAT-inclusive, and changing this rate applies to future sales only.
        </p>

        <form method="POST" action="{{ route('vat-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div>
                    <label for="rate_percent">VAT rate (%)</label>
                    <input
                        class="input-lg bordered vat-rate-input"
                        id="rate_percent"
                        name="rate_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        value="{{ old('rate_percent', number_format($ratePercent, 2, '.', '')) }}"
                        aria-describedby="vat-rate-help"
                        required
                    >
                    <p class="vat-rate-help" id="vat-rate-help">Enter the current percentage, for example 12 for 12%.</p>
                    @error('rate_percent')
                        <span class="error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-actions">
                <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Cancel</a>
                <button class="btn" type="submit">Save VAT rate</button>
            </div>
        </form>
    </div>
@endsection
