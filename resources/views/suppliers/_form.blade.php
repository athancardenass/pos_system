@php($supplier = $supplier ?? null)
<label for="supplier_name">Name</label>
<input id="supplier_name" name="supplier_name" class="input-lg bordered" value="{{ old('supplier_name', $supplier?->supplier_name) }}" required>

<div class="field-pair">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" class="input-lg bordered" value="{{ old('email', $supplier?->email) }}">
    </div>
    <div>
        <label for="contact_number">Contact number</label>
        <input id="contact_number" name="contact_number" class="input-lg bordered" value="{{ old('contact_number', $supplier?->contact_number) }}">
    </div>
</div>

<label for="address">Address</label>
<textarea id="address" name="address" class="input-lg bordered" rows="3">{{ old('address', $supplier?->address) }}</textarea>
