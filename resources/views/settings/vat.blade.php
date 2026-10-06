@extends('layouts.app')

@section('title', 'VAT Settings')

@push('styles')
<style>
    .vat-settings-page { max-width: 1040px; margin: 0 auto; }
    .vat-settings-heading {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        min-height: 84px; margin-bottom: 14px; padding: 1.15rem 1.6rem;
        border-radius: 14px; border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgb(56, 43, 45); background: linear-gradient(135deg, rgb(56, 43, 45) 0%, rgb(40, 29, 31) 100%);
        color: #fff; box-shadow: 0 10px 25px -5px rgba(56, 43, 45, 0.28), 0 4px 10px -2px rgba(56, 43, 45, 0.16);
        position: relative; overflow: hidden;
    }
    .vat-settings-heading::after {
        content: ''; position: absolute; top: -40px; right: -40px; width: 180px; height: 180px;
        background: radial-gradient(circle, rgba(255, 190, 152, 0.12) 0%, transparent 70%);
        pointer-events: none; border-radius: 50%;
    }
    .vat-settings-heading h1 { margin: 0; color: #fff; font-size: 1.45rem; font-weight: 800; letter-spacing: -0.025em; position: relative; z-index: 1; }
    .vat-settings-heading .eyebrow {
        display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.2rem 0.6rem;
        border-radius: 9999px; background: rgba(255, 190, 152, 0.14); border: 1px solid rgba(255, 190, 152, 0.28);
        color: #FFBE98; font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        margin-bottom: 0.35rem; position: relative; z-index: 1;
    }
    .vat-settings-heading .btn-secondary {
        min-height: 40px; border-radius: 10px; border-color: rgba(255,255,255,.45);
        background: #fff; color: #1E293B; font-weight: 800; box-shadow: 0 2px 6px rgba(0,0,0,.12);
        position: relative; z-index: 1;
    }
    .vat-settings-card { max-width: none; padding: 20px; border: 1px solid var(--rule-faint); border-radius: 14px; box-shadow: var(--shadow-card); }
    .vat-settings-intro { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--rule-faint); }
    .vat-settings-intro h2 { margin: 0; color: #203C3D; font-size: 1rem; font-weight: 800; }
    .vat-settings-description { max-width: 68ch; margin-top: 4px; color: var(--muted); font-size: .86rem; }
    .vat-current-rate { display: grid; flex: 0 0 auto; gap: 1px; min-width: 116px; padding: 9px 12px; border-radius: 10px; background: #203C3D; color: #fff; text-align: right; }
    .vat-current-rate strong { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1.2; }
    .vat-current-rate span { color: #B8D9C4; font-size: .65rem; font-weight: 750; }
    .vat-settings-card form { margin-top: 16px; }
    .vat-rate-input { max-width: 240px; }
    .vat-rate-help { margin-top: -0.45rem; color: var(--muted); font-size: 0.78rem; }
    .vat-settings-card .form-actions { margin-top: 17px; }
    @media (max-width: 560px) { .vat-settings-heading { align-items: flex-start; flex-direction: column; } .vat-settings-intro { align-items: flex-start; flex-direction: column; } .vat-current-rate { min-width: 0; text-align: left; } .vat-settings-card { padding: 16px; } }
</style>
@endpush

@section('content')
    <main class="vat-settings-page">
    <header class="vat-settings-heading">
        <div>
            <p class="eyebrow">Store settings</p>
            <h1>VAT settings</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back to discounts</a>
    </header>

    <div class="card vat-settings-card">
        <div class="vat-settings-intro">
            <div>
                <h2>Transaction tax rate</h2>
                <p class="vat-settings-description">Set the rate used to calculate VAT on receipts. Prices remain VAT-inclusive; changes apply to future sales.</p>
            </div>
            <div class="vat-current-rate" aria-label="Current VAT rate">
                <strong>{{ number_format((float) $ratePercent, 2) }}%</strong>
                <span>Current rate</span>
            </div>
        </div>

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
    </main>
@endsection
