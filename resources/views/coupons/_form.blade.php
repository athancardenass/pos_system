@php($coupon = $coupon ?? null)
<div class="form-grid">
    <div>
        <label for="code">Code</label>
        <input id="code" name="code" class="input-lg bordered" value="{{ old('code', $coupon?->code) }}" maxlength="40" required autocomplete="off">
    </div>
    <div>
        <label for="type">Type</label>
        <select id="type" name="type" required>
            @foreach (\App\Models\Coupon::TYPES as $couponType)
                <option value="{{ $couponType }}" @selected(old('type', $coupon?->type) === $couponType)>{{ ucfirst($couponType) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="value">Value (percent or peso amount)</label>
        <input id="value" type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon?->value) }}" required>
    </div>
    <div>
        <label for="min_purchase">Minimum purchase</label>
        <input id="min_purchase" type="number" step="0.01" min="0" name="min_purchase" value="{{ old('min_purchase', $coupon?->min_purchase ?? 0) }}">
    </div>
    <div>
        <label for="max_uses">Max uses (blank = unlimited)</label>
        <input id="max_uses" type="number" min="1" step="1" name="max_uses" value="{{ old('max_uses', $coupon?->max_uses) }}">
    </div>
    <div>
        <label for="per_customer_limit">Limit per customer (blank = unlimited)</label>
        <input id="per_customer_limit" type="number" min="1" step="1" name="per_customer_limit" value="{{ old('per_customer_limit', $coupon?->per_customer_limit) }}">
    </div>
    <div>
        <label for="starts_at">Starts at</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon?->starts_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label for="ends_at">Ends at</label>
        <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($coupon?->ends_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label for="is_active">Status</label>
        <select id="is_active" name="is_active" required>
            <option value="1" @selected(old('is_active', $coupon?->is_active ?? true))>Active</option>
            <option value="0" @selected(old('is_active', $coupon?->is_active ?? true) == false)>Inactive</option>
        </select>
    </div>
</div>
<div class="field-stack">
    <label for="description">Description</label>
    <textarea id="description" name="description" class="input-lg bordered" rows="2" maxlength="255">{{ old('description', $coupon?->description) }}</textarea>
</div>
