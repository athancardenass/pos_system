@extends('layouts.app')

@section('title', 'Receipt Settings')

@push('styles')
<style>
    .receipt-settings-card { max-width: 820px; }
    .receipt-settings-copy { max-width: 68ch; color: var(--muted); font-size: .9rem; }
    .receipt-width-options { display: flex; gap: .65rem; flex-wrap: wrap; }
    .receipt-width-choice { display: flex; align-items: center; gap: .5rem; padding: .7rem .9rem; border: 1px solid var(--rule-faint); border-radius: 10px; background: var(--surface); }
    .receipt-width-choice input { width: 1rem; height: 1rem; accent-color: var(--interactive); }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Store setup</p>
            <h1>Receipt settings</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Back to discounts</a>
    </div>

    <section class="card receipt-settings-card">
        <p class="receipt-settings-copy">These details appear on newly issued receipts. Existing receipts keep the store information and paper width saved when they were issued.</p>
        <form method="POST" action="{{ route('receipt-settings.update') }}">
            @csrf
            @method('PUT')
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
                <div>
                    <label>Thermal paper width</label>
                    <div class="receipt-width-options">
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
            <div class="form-actions">
                <a class="btn btn-secondary" href="{{ route('discounts.index') }}">Cancel</a>
                <button class="btn" type="submit">Save receipt settings</button>
            </div>
        </form>
    </section>
@endsection
