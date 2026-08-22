@php($supplier = $supplier ?? null)
<label for="supplier_name">Name</label>
<input id="supplier_name" name="supplier_name" value="{{ old('supplier_name', $supplier?->supplier_name) }}" required>
<div class="form-grid">
    <div>
        <label for="contact_number">Contact number</label>
        <input id="contact_number" name="contact_number" value="{{ old('contact_number', $supplier?->contact_number) }}">
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $supplier?->email) }}">
    </div>
</div>
<label for="address">Address</label>
<input id="address" name="address" value="{{ old('address', $supplier?->address) }}">
