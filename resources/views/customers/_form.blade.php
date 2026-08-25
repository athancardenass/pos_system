@php($customer = $customer ?? null)
<div class="form-grid">
    <div>
        <label for="first_name">First name</label>
        <input id="first_name" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" required>
    </div>
    <div>
        <label for="last_name">Last name</label>
        <input id="last_name" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" required>
    </div>
    <div>
        <label for="contact_number">Contact number</label>
        <input id="contact_number" name="contact_number" value="{{ old('contact_number', $customer?->contact_number) }}">
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $customer?->email) }}">
    </div>
    <div>
        <label for="date_of_birth">Date of birth</label>
        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($customer?->date_of_birth)->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" min="1900-01-01">
    </div>
    <div>
        <label for="customer_status">Status</label>
        <select id="customer_status" name="customer_status" required>
            @foreach (['active', 'inactive'] as $status)
                <option value="{{ $status }}" @selected(old('customer_status', $customer?->customer_status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
</div>
<label for="address">Address</label>
<input id="address" name="address" value="{{ old('address', $customer?->address) }}">
