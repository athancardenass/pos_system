@props(['isManager' => false])

@push('styles')
<style>
    .manager-pin-backdrop { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; padding: 16px; background: rgba(21,35,34,.48); backdrop-filter: blur(4px); }
    .manager-pin-backdrop[hidden], .manager-pin-panel [hidden] { display: none !important; }
    .manager-pin-panel { width: min(100%, 390px); padding: 20px; border: 1px solid rgba(32,60,61,.12); border-radius: 15px; background: #fff; color: var(--text); box-shadow: 0 18px 48px rgba(24,44,43,.22); }
    .manager-pin-heading { margin: 0 0 5px; font-size: 1.1rem; font-weight: 750; }
    .manager-pin-copy { margin: 0 0 15px; color: var(--muted); font-size: .82rem; line-height: 1.45; }
    .manager-pin-entry { margin-bottom: 12px; }
    .manager-pin-keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin: 9px 0 14px; }
    .manager-pin-key { min-height: 42px; border: 1px solid var(--rule-faint); border-radius: 8px; background: var(--surface-soft); color: var(--text); font-size: 1rem; font-weight: 700; cursor: pointer; }
    .manager-pin-key:hover { border-color: rgba(32,60,61,.24); background: var(--surface-active); }
    .manager-pin-reason { margin: 0 0 12px; }
    .manager-pin-message { min-height: 1.3em; margin: 0 0 8px; color: var(--danger); font-size: .78rem; }
    .manager-pin-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
    .manager-pin-actions button { min-height: 38px; }
</style>
@endpush

<div class="manager-pin-backdrop" id="manager-pin-modal" role="dialog" aria-modal="true" aria-labelledby="manager-pin-title" hidden>
    <form class="manager-pin-panel" id="manager-pin-form">
        <h2 class="manager-pin-heading" id="manager-pin-title">Manager approval required</h2>
        <p class="manager-pin-copy" id="manager-pin-copy">Ask a manager to enter their PIN to continue.</p>
        <div class="manager-pin-entry" id="manager-pin-entry">
            <label for="manager-pin-input">Manager PIN</label>
            <input id="manager-pin-input" class="input-lg bordered" type="password" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="6" autocomplete="one-time-code" required>
            <div class="manager-pin-keypad" aria-label="Numeric PIN keypad">
                @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', 'clear', '0', 'backspace'] as $key)
                    <button type="button" class="manager-pin-key" data-pin-key="{{ $key }}">
                        {{ $key === 'clear' ? 'Clear' : ($key === 'backspace' ? '⌫' : $key) }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="manager-pin-reason" id="manager-pin-reason" hidden>
            <label for="manager-pin-reason-select">Reason</label>
            <select id="manager-pin-reason-select">
                <option value="">Select a reason</option>
                @foreach (\App\Services\RefundService::REASONS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <label for="manager-pin-notes">Note (optional)</label>
            <textarea id="manager-pin-notes" class="input-lg bordered" rows="2" maxlength="255"></textarea>
        </div>
        <p class="manager-pin-message" id="manager-pin-message" role="alert" aria-live="polite"></p>
        <div class="manager-pin-actions">
            <button type="button" class="btn btn-secondary" id="manager-pin-cancel">Cancel</button>
            <button type="submit" class="btn" id="manager-pin-submit">Authorize</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('manager-pin-modal');
        const form = document.getElementById('manager-pin-form');
        if (!modal || !form) return;

        const managerRole = @json((bool) $isManager);
        const endpoint = @json(route('manager-authorization.authorize'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const pinEntry = document.getElementById('manager-pin-entry');
        const pinInput = document.getElementById('manager-pin-input');
        const reasonPanel = document.getElementById('manager-pin-reason');
        const reasonSelect = document.getElementById('manager-pin-reason-select');
        const notesInput = document.getElementById('manager-pin-notes');
        const message = document.getElementById('manager-pin-message');
        const submitButton = document.getElementById('manager-pin-submit');
        let pending = null;
        let lockTimer = null;
        let lockUntil = 0;

        const registerId = () => document.querySelector('.pos-register-badge')?.textContent.trim() || 'REG 01';

        function finish(result) {
            if (lockTimer) window.clearInterval(lockTimer);
            lockTimer = null;
            lockUntil = 0;
            modal.hidden = true;
            pinInput.value = '';
            pinInput.disabled = false;
            reasonSelect.value = '';
            notesInput.value = '';
            message.textContent = '';
            submitButton.disabled = false;
            modal.querySelectorAll('[data-pin-key]').forEach(button => button.disabled = false);
            const completedRequest = pending;
            pending = null;
            if (completedRequest) completedRequest.resolve(result);
        }

        function showLock(seconds) {
            lockUntil = Date.now() + seconds * 1000;
            pinInput.disabled = true;
            submitButton.disabled = true;
            modal.querySelectorAll('[data-pin-key]').forEach(button => button.disabled = true);
            const tick = () => {
                const remaining = Math.max(0, Math.ceil((lockUntil - Date.now()) / 1000));
                message.textContent = remaining > 0
                    ? `Too many incorrect PINs. Try again in ${remaining} seconds.`
                    : '';
                if (remaining === 0) {
                    window.clearInterval(lockTimer);
                    lockTimer = null;
                    pinInput.disabled = false;
                    submitButton.disabled = false;
                    modal.querySelectorAll('[data-pin-key]').forEach(button => button.disabled = false);
                    pinInput.focus();
                }
            };
            tick();
            lockTimer = window.setInterval(tick, 250);
        }

        async function submitAuthorization() {
            if (!pending || submitButton.disabled) return;
            const reason = pending.reason || (pending.requiresReason ? reasonSelect.value : '');
            const notes = pending.notes || (pending.requiresReason ? notesInput.value.trim() : '');
            if (pending.requiresReason && !reason) {
                message.textContent = 'Select a reason to continue.';
                reasonSelect.focus();
                return;
            }
            if (!managerRole && !/^\d{4,6}$/.test(pinInput.value)) {
                message.textContent = 'Enter a 4–6 digit manager PIN.';
                pinInput.focus();
                return;
            }

            submitButton.disabled = true;
            message.textContent = 'Checking authorization…';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        action: pending.action,
                        pin: managerRole ? null : pinInput.value,
                        register_id: registerId(),
                        details: pending.details,
                        reason: reason || null,
                        notes: notes || null
                    })
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    pinInput.value = '';
                    submitButton.disabled = false;
                    const failureMessage = data.message || Object.values(data.errors || {}).flat()[0] || 'Manager authorization failed.';
                    message.textContent = failureMessage;
                    if (managerRole && !pending.requiresReason) {
                        window.alert(failureMessage);
                        finish(null);
                        return;
                    }
                    if (Number(data.locked_for) > 0) showLock(Number(data.locked_for));
                    else if (!managerRole) pinInput.focus();
                    return;
                }
                finish({ token: data.token || null, approver: data.approved_by, reason, notes });
            } catch (error) {
                submitButton.disabled = false;
                message.textContent = 'Authorization could not be checked. Try again when the register is online.';
                if (managerRole && !pending.requiresReason) finish(null);
            }
        }

        window.requestManagerAuthorization = function (options) {
            const settings = options || {};
            if (pending) return Promise.resolve(null);

            if (managerRole && !settings.requiresReason) {
                pending = { ...settings, resolve: () => {} };
                return new Promise(resolve => {
                    pending.resolve = resolve;
                    submitAuthorization();
                });
            }

            return new Promise(resolve => {
                pending = { ...settings, resolve };
                pinEntry.hidden = managerRole;
                pinInput.required = !managerRole;
                reasonPanel.hidden = !settings.requiresReason;
                document.getElementById('manager-pin-title').textContent = managerRole ? 'Confirm sensitive action' : 'Manager approval required';
                document.getElementById('manager-pin-copy').textContent = managerRole
                    ? 'Record a reason for this action.'
                    : 'Ask a manager to enter their PIN to continue.';
                message.textContent = '';
                pinInput.value = '';
                modal.hidden = false;
                modal.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                requestAnimationFrame(() => (managerRole && settings.requiresReason ? reasonSelect : pinInput).focus());
            });
        };

        modal.querySelectorAll('[data-pin-key]').forEach(button => {
            button.addEventListener('click', () => {
                if (pinInput.disabled) return;
                const key = button.dataset.pinKey;
                if (key === 'clear') pinInput.value = '';
                else if (key === 'backspace') pinInput.value = pinInput.value.slice(0, -1);
                else if (pinInput.value.length < 6) pinInput.value += key;
                pinInput.focus();
            });
        });
        pinInput.addEventListener('input', () => pinInput.value = pinInput.value.replace(/\D/g, '').slice(0, 6));
        form.addEventListener('submit', event => {
            event.preventDefault();
            submitAuthorization();
        });
        document.getElementById('manager-pin-cancel').addEventListener('click', () => finish(null));
        modal.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !submitButton.disabled) finish(null);
        });
    })();
</script>
@endpush
