@php($employee = $employee ?? null)
<div class="employee-form">
    <section class="employee-form-section" aria-labelledby="employee-profile-heading">
        <div class="employee-form-heading">
            <h2 id="employee-profile-heading">Personal details</h2>
            <p>Employee name, contact number, and hire date.</p>
        </div>
        <div class="form-grid employee-fields">
            <div>
                <label for="first_name">First name</label>
                <input id="first_name" name="first_name" class="input-lg bordered" value="{{ old('first_name', $employee?->first_name) }}" required>
            </div>
            <div>
                <label for="last_name">Last name</label>
                <input id="last_name" name="last_name" class="input-lg bordered" value="{{ old('last_name', $employee?->last_name) }}" required>
            </div>
            <div>
                <label for="contact_number">Contact number</label>
                <input id="contact_number" name="contact_number" class="input-lg bordered" value="{{ old('contact_number', $employee?->contact_number) }}">
            </div>
            <div>
                <label for="hire_date">Hire date</label>
                <input id="hire_date" type="date" name="hire_date" value="{{ old('hire_date', optional($employee?->hire_date)->format('Y-m-d') ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="1900-01-01" required>
            </div>
        </div>
    </section>

    <section class="employee-form-section" aria-labelledby="employee-access-heading">
        <div class="employee-form-heading">
            <h2 id="employee-access-heading">Account access</h2>
            <p>Sign-in details, role, and account status.</p>
        </div>
        <div class="form-grid employee-fields">
            <div>
                <label for="username">Username</label>
                <input id="username" name="username" class="input-lg bordered" value="{{ old('username', $employee?->username) }}" required>
            </div>
            <div>
                <label for="password">Password @if($employee)<span class="muted">(leave blank to keep)</span>@endif</label>
                <input id="password" type="password" name="password" class="input-lg bordered" @required(! $employee) autocomplete="new-password">
            </div>
            <div>
                <label for="role_id">Role</label>
                <select id="role_id" name="role_id" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->role_id }}" data-role-name="{{ strtolower($role->role_name) }}" @selected(old('role_id', $employee?->role_id) == $role->role_id)>{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach (['active', 'inactive'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $employee?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-grid employee-fields employee-manager-pin-fields" id="employee-manager-pin-fields" data-required="{{ ! $employee || blank($employee->manager_pin_hash) ? 'true' : 'false' }}" hidden>
            <div>
                <label for="manager_pin">Manager PIN</label>
                <input id="manager_pin" type="password" name="manager_pin" class="input-lg bordered" inputmode="numeric" pattern="[0-9]{4,6}" minlength="4" maxlength="6" autocomplete="new-password" aria-describedby="manager-pin-help">
                <p class="muted" id="manager-pin-help">Use 4–6 digits. The PIN is stored as a one-way hash and cannot be viewed later. Leave blank to keep an existing PIN.</p>
            </div>
            <div>
                <label for="manager_pin_confirmation">Confirm manager PIN</label>
                <input id="manager_pin_confirmation" type="password" name="manager_pin_confirmation" class="input-lg bordered" inputmode="numeric" pattern="[0-9]{4,6}" minlength="4" maxlength="6" autocomplete="new-password">
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .employee-manager-pin-fields[hidden] { display: none !important; }
    .employee-manager-pin-fields p { margin: 6px 0 0; font-size: .78rem; line-height: 1.45; }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const role = document.getElementById('role_id');
        const fields = document.getElementById('employee-manager-pin-fields');
        const pin = document.getElementById('manager_pin');
        const confirmation = document.getElementById('manager_pin_confirmation');
        if (!role || !fields || !pin || !confirmation) return;

        const syncManagerPinFields = () => {
            const managerSelected = role.selectedOptions[0]?.dataset.roleName === 'manager';
            fields.hidden = !managerSelected;
            pin.disabled = !managerSelected;
            confirmation.disabled = !managerSelected;
            pin.required = managerSelected && fields.dataset.required === 'true';
            confirmation.required = managerSelected && (pin.value.length > 0 || pin.required);
        };

        role.addEventListener('change', syncManagerPinFields);
        pin.addEventListener('input', syncManagerPinFields);
        syncManagerPinFields();
    })();
</script>
@endpush
