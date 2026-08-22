@php($discount = $discount ?? null)
<label for="discount_name">Name</label>
<input id="discount_name" name="discount_name" value="{{ old('discount_name', $discount?->discount_name) }}" required>
<div class="form-grid">
    <div>
        <label for="discount_type">Type</label>
        <select id="discount_type" name="discount_type" required>
            @foreach (['percentage', 'fixed'] as $type)
                <option value="{{ $type }}" @selected(old('discount_type', $discount?->discount_type) === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="discount_value">Value</label>
        <input id="discount_value" type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $discount?->discount_value) }}" required>
    </div>
    <div>
        <label for="start_date">Start date</label>
        <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($discount?->start_date)->format('Y-m-d')) }}">
    </div>
    <div>
        <label for="end_date">End date</label>
        <input id="end_date" type="date" name="end_date" value="{{ old('end_date', optional($discount?->end_date)->format('Y-m-d')) }}">
    </div>
</div>
