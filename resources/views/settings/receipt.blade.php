@extends('layouts.app')

@section('title', 'Receipt Settings')

@push('styles')
<style>
    .receipt-settings-page { max-width: 1040px; margin: 0 auto; }
    .receipt-settings-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 90px; margin-bottom: 14px; padding: 16px 20px; border-radius: 14px; background: #203C3D; color: #fff; box-shadow: 0 5px 14px rgba(32,60,61,.12); }
    .receipt-settings-heading h1 { margin: 0; color: #fff; font-size: 1.3rem; font-weight: 800; }
    .receipt-settings-heading .eyebrow { margin: 0 0 3px; color: #B8D9C4; font-size: .66rem; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
    .receipt-settings-heading .btn-secondary { min-height: 36px; border-color: rgba(255,255,255,.42); background: #fff; color: #203C3D; font-weight: 800; }
    .receipt-settings-card { max-width: none; padding: 20px; border: 1px solid var(--rule-faint); border-radius: 14px; box-shadow: var(--shadow-card); }
    .receipt-settings-copy { max-width: 72ch; margin-bottom: 17px; color: var(--muted); font-size: .86rem; }
    .receipt-settings-section { padding: 16px 0; border-top: 1px solid var(--rule-faint); }
    .receipt-settings-section:first-of-type { padding-top: 0; border-top: 0; }
    .receipt-settings-section h2 { margin: 0 0 3px; color: #203C3D; font-size: .96rem; font-weight: 800; }
    .receipt-settings-section > p { margin: 0 0 12px; color: var(--muted); font-size: .78rem; }
    .receipt-width-label { display: block; margin-bottom: 7px; color: var(--text); font-size: .85rem; font-weight: 700; }
    .receipt-width-options { display: flex; gap: .65rem; flex-wrap: wrap; }
    .receipt-width-choice { display: flex; align-items: center; gap: .65rem; min-width: 124px; min-height: 44px; padding: .55rem .8rem; border: 1px solid var(--rule-faint); border-radius: 9px; background: #F5F8F6; box-shadow: var(--shadow-sm); }
    .receipt-width-choice:has(input:checked) { border-color: rgba(24,118,94,.5); background: #E7F2EB; box-shadow: inset 0 0 0 1px rgba(24,118,94,.12); }
    .receipt-width-choice label { flex: 1; margin: 0; color: #203C3D; font-weight: 800; cursor: pointer; }
    .receipt-width-choice input { width: 1rem; height: 1rem; accent-color: var(--accent); }
    .receipt-settings-card .form-actions { margin-top: 16px; }
    @media (max-width: 560px) { .receipt-settings-heading { align-items: flex-start; flex-direction: column; } .receipt-settings-card { padding: 16px; } }
</style>
@endpush

@section('content')
    <main class="receipt-settings-page">
    <header class="receipt-settings-heading">
        <div>
            <p class="eyebrow">Store setup</p>
            <h1>Receipt settings</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back to discounts</a>
    </header>

    <section class="card receipt-settings-card">
        <p class="receipt-settings-copy">These details appear on newly issued receipts. Existing receipts keep the store information and paper width saved when they were issued.</p>
        <form method="POST" action="{{ route('receipt-settings.update') }}">
            @csrf
            @method('PUT')
            <section class="receipt-settings-section" aria-labelledby="receipt-store-title">
                <h2 id="receipt-store-title">Store information</h2>
                <p>Printed at the top of newly issued receipts.</p>
                <div class="form-grid">
                <div>
                    <label for="store_name">Store / business name</label>
                    <input id="store_name" name="store_name" class="input-lg bordered" maxlength="150" required value="{{ old('store_name', $settings['store_name']) }}">
                    @error('store_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="store_address">Store information / address</label>
                    <textarea id="store_address" name="store_address" class="input-lg bordered" rows="2" maxlength="1000">{{ old('store_address', $settings['store_address']) }}</textarea>
                    @error('store_address') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tin">TIN</label>
                    <input id="tin" name="tin" class="input-lg bordered" maxlength="50" value="{{ old('tin', $settings['tin']) }}" autocomplete="off">
                    @error('tin') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                </div>
            </section>
            <section class="receipt-settings-section" aria-labelledby="receipt-format-title">
                <h2 id="receipt-format-title">Receipt format</h2>
                <p>Choose the paper width and optional footer for future receipts.</p>
                <div class="form-grid">
                <div>
                    <span class="field-label" id="paper-width-label">Thermal paper width</span>
                    <div class="receipt-width-options" role="group" aria-labelledby="paper-width-label">
                        <div class="receipt-width-choice"><label for="paper_width_58">58 mm</label><input id="paper_width_58" type="radio" name="paper_width" value="58" @checked(old('paper_width', $settings['paper_width']) === '58')></div>
                        <div class="receipt-width-choice"><label for="paper_width_80">80 mm</label><input id="paper_width_80" type="radio" name="paper_width" value="80" @checked(old('paper_width', $settings['paper_width']) === '80')></div>
                    </div>
                    @error('paper_width') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="footer_text">Receipt footer</label>
                    <textarea id="footer_text" name="footer_text" class="input-lg bordered" rows="2" maxlength="500">{{ old('footer_text', $settings['footer_text']) }}</textarea>
                    @error('footer_text') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            </section>
            <div class="form-actions">
                <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Cancel</a>
                <button class="btn" type="submit">Save receipt settings</button>
            </div>
        </form>
    </section>
    </main>
@endsection
