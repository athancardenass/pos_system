@extends('layouts.app')

@section('title', 'Report product safety concern')

@section('content')
    <div class="incident-report-page">
        <header class="page-head incident-report-head">
            <div class="incident-report-heading">
                <p class="eyebrow">Product safety</p>
                <h1>Report product safety concern</h1>
                <p class="muted">Give the manager enough detail to identify and investigate the affected product.</p>
            </div>
            <a class="btn btn-secondary incident-back-link" href="{{ route('incidents.index') }}">Back to incidents</a>
        </header>

        <aside class="incident-safety-note" aria-label="Sales hold information">
            <span class="incident-safety-label">Sales hold</span>
            <p>Submitting this report blocks sales of the <strong>entire product</strong> until a manager resolves the case. The affected quantity below is descriptive and does not limit the hold.</p>
        </aside>

        @include('incidents.errors')

        <form class="incident-report-form" method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data">
            @csrf

            <section class="incident-form-section" aria-labelledby="incident-product-heading">
                <div class="incident-form-section-heading">
                    <span class="incident-form-step" aria-hidden="true">01</span>
                    <div>
                        <h2 id="incident-product-heading">Affected product</h2>
                        <p>Select the item and estimate how much may be affected.</p>
                    </div>
                </div>
                <div class="incident-field-grid">
                    <div class="incident-field incident-field-wide">
                        <label for="product_id">Product <span aria-hidden="true">*</span></label>
                        <select id="product_id" name="product_id" required>
                            <option value="">Select a product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->product_id }}" @selected(old('product_id') == $product->product_id)>{{ $product->product_name }} — {{ $product->barcode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="incident-field">
                        <label for="quantity">Affected quantity <span aria-hidden="true">*</span></label>
                        <input id="quantity" name="quantity" type="number" min="0.001" max="999999999" step="0.001" value="{{ old('quantity', 1) }}" required>
                        <small>Approximate amount reported.</small>
                    </div>
                </div>
            </section>

            <section class="incident-form-section" aria-labelledby="incident-concern-heading">
                <div class="incident-form-section-heading">
                    <span class="incident-form-step" aria-hidden="true">02</span>
                    <div>
                        <h2 id="incident-concern-heading">What happened?</h2>
                        <p>Describe what you saw or what the customer reported.</p>
                    </div>
                </div>
                <div class="incident-field">
                    <label for="description">Concern or complaint <span aria-hidden="true">*</span></label>
                    <textarea id="description" name="description" rows="5" maxlength="3000" required>{{ old('description') }}</textarea>
                    <small>Include observable details such as suspected contamination, damaged packaging, expiry, or the reported symptoms.</small>
                </div>
            </section>

            <section class="incident-form-section" aria-labelledby="incident-reference-heading">
                <div class="incident-form-section-heading">
                    <span class="incident-form-step" aria-hidden="true">03</span>
                    <div>
                        <h2 id="incident-reference-heading">References and evidence</h2>
                        <p>These details help Inventory locate the item or transaction.</p>
                    </div>
                </div>
                <div class="incident-field-grid incident-reference-grid">
                    <div class="incident-field">
                        <label for="batch_number">Batch or lot number <span class="incident-optional">Optional</span></label>
                        <input id="batch_number" name="batch_number" maxlength="100" value="{{ old('batch_number') }}">
                    </div>
                    <div class="incident-field">
                        <label for="expiry_date">Expiry date <span class="incident-optional">Optional</span></label>
                        <input id="expiry_date" name="expiry_date" type="date" value="{{ old('expiry_date') }}">
                    </div>
                    <div class="incident-field">
                        <label for="receipt_number">Receipt number <span class="incident-optional">Optional</span></label>
                        <input id="receipt_number" name="receipt_number" maxlength="100" value="{{ old('receipt_number') }}">
                    </div>
                    <div class="incident-field incident-field-wide incident-evidence-field">
                        <label for="evidence">Evidence photo <span class="incident-optional">Optional</span></label>
                        <input id="evidence" name="evidence" type="file" accept="image/jpeg,image/png,image/webp">
                        <small>JPG, PNG or WebP, up to 5 MB. Batch information is recorded as reported; POS does not track stock by batch.</small>
                    </div>
                </div>
            </section>

            <div class="incident-form-actions">
                <p><span aria-hidden="true">*</span> Required field</p>
                <button class="btn" type="submit">Submit concern and place hold</button>
            </div>
        </form>
    </div>
@endsection

@push('styles')
<style>
    .incident-report-page { max-width: 1040px; margin: 0 auto; }
    .incident-report-head { align-items: flex-start; }
    .incident-report-heading { min-width: min(100%, 25rem); position: relative; z-index: 1; }
    .incident-report-heading h1 { margin: 0; }
    .incident-report-heading .muted { max-width: 42rem; }
    .incident-back-link { position: relative; z-index: 1; }
    .incident-safety-note { display: flex; align-items: flex-start; gap: .75rem; margin: 0 0 1rem; padding: .9rem 1rem; border: 1px solid #EED6C2; border-radius: var(--r-lg); background: #FFF6EF; color: var(--text); }
    .incident-safety-label { flex: 0 0 auto; margin-top: .05rem; padding: .24rem .55rem; border-radius: 999px; background: #F5E0D0; color: #7C4422; font-size: .68rem; font-weight: 900; letter-spacing: .035em; text-transform: uppercase; }
    .incident-safety-note p { margin: 0; color: #644C40; font-size: .84rem; line-height: 1.55; }
    .incident-safety-note strong { color: #4E3325; }
    .incident-report-page .flash-error { margin-bottom: 1rem; }
    .incident-report-form { overflow: hidden; border: 1px solid var(--rule-faint); border-radius: var(--r-lg); background: var(--surface); box-shadow: var(--shadow-card); }
    .incident-form-section { padding: 1.35rem 1.5rem; border-bottom: 1px solid var(--rule-faint); }
    .incident-form-section-heading { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: 1.1rem; }
    .incident-form-step { display: grid; place-items: center; flex: 0 0 2rem; height: 2rem; border: 1px solid rgba(24, 118, 94, .16); border-radius: 9px; background: var(--surface-active); color: var(--interactive-ink); font-size: .7rem; font-weight: 900; font-variant-numeric: tabular-nums; }
    .incident-form-section-heading h2 { margin: .05rem 0 0; font-size: 1rem; }
    .incident-form-section-heading p { margin: .2rem 0 0; color: var(--muted); font-size: .8rem; line-height: 1.45; }
    .incident-field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .incident-field { display: grid; align-content: start; gap: .38rem; min-width: 0; }
    .incident-field-wide { grid-column: 1 / -1; }
    .incident-field label { color: var(--text); font-size: .82rem; font-weight: 800; }
    .incident-field label > span[aria-hidden="true"], .incident-form-actions p > span { color: var(--danger); }
    .incident-field input, .incident-field select, .incident-field textarea { width: 100%; margin: 0; }
    .incident-field textarea { min-height: 8rem; line-height: 1.5; }
    .incident-field small { color: var(--muted); font-size: .74rem; line-height: 1.45; }
    .incident-optional { margin-left: .25rem; color: var(--muted); font-size: .7rem; font-weight: 600; }
    .incident-evidence-field input[type="file"] { padding: .6rem; background: var(--surface-soft); }
    .incident-form-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.5rem; background: #FFFCFA; }
    .incident-form-actions p { margin: 0; color: var(--muted); font-size: .74rem; }
    .incident-form-actions .btn { min-height: 42px; }

    @media (max-width: 640px) {
        .incident-report-head { align-items: flex-start; }
        .incident-report-head .incident-back-link { align-self: flex-start; }
        .incident-form-section { padding: 1.1rem 1rem; }
        .incident-field-grid { grid-template-columns: 1fr; }
        .incident-field-wide { grid-column: auto; }
        .incident-form-actions { align-items: stretch; flex-direction: column-reverse; padding: .9rem 1rem 1rem; }
        .incident-form-actions .btn { justify-content: center; width: 100%; }
    }
</style>
@endpush
