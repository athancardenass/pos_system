@php
    $discount = $discount ?? null;
    $isSpecialPolicyDiscount = $discount?->specialPolicyType() !== null;
@endphp
<div class="discount-form">
    <section class="discount-form-section" aria-labelledby="discount-details-heading">
        <div class="discount-form-heading">
            <h2 id="discount-details-heading">Discount details</h2>
            <p>Name this discount and set the format and amount.</p>
        </div>
        <div class="form-grid discount-fields">
            <div class="discount-field-wide">
                <label for="discount_name">Name</label>
                <input id="discount_name" name="discount_name" class="input-lg bordered" value="{{ old('discount_name', $isSpecialPolicyDiscount ? ($discount->specialPolicyType() === 'pwd' ? 'PWD 20%' : 'Senior Citizen 20%') : $discount?->discount_name) }}" required>
            </div>
            <div>
                <label for="discount_type">Type</label>
                <select id="discount_type" name="discount_type" required>
                    @foreach (['percentage', 'fixed'] as $type)
                        <option value="{{ $type }}" @selected(old('discount_type', $isSpecialPolicyDiscount ? 'percentage' : $discount?->discount_type) === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="discount_value">Value</label>
                <input id="discount_value" type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $isSpecialPolicyDiscount ? '20.00' : $discount?->discount_value) }}" required @if($isSpecialPolicyDiscount) readonly @endif>
                <p class="discount-field-help">{{ $isSpecialPolicyDiscount ? 'Senior Citizen and PWD are fixed to the simplified 20% VAT-exempt-base policy.' : 'Used as a rate for percentage or an amount for fixed.' }}</p>
            </div>
        </div>
    </section>

    <section class="discount-form-section" aria-labelledby="discount-availability-heading">
        <div class="discount-form-heading">
            <h2 id="discount-availability-heading">Availability</h2>
            <p>Choose the date range for this discount.</p>
        </div>
        <div class="form-grid discount-fields">
            <div>
                <label for="start_date">Start date</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($discount?->start_date)->format('Y-m-d') ?? now()->toDateString()) }}" min="2020-01-01">
            </div>
            <div>
                <label for="end_date">End date</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date', optional($discount?->end_date)->format('Y-m-d')) }}" min="2020-01-01">
            </div>
        </div>
    </section>
</div>
