(function () {
    'use strict';
    function countCash(entries) {
        let cents = 0;
        for (const entry of entries) {
            const value = Number(entry.value), count = Number(entry.count);
            if (!Number.isFinite(value) || value <= 0 || !Number.isFinite(count) || count < 0 || count > 999999 || (value !== 1 && !Number.isInteger(count)) || (value === 1 && Math.abs(count * 100 - Math.round(count * 100)) > .0000001)) return null;
            cents += Math.round(value * count * 100);
            if (!Number.isSafeInteger(cents)) return null;
        }
        return cents / 100;
    }
    function previewTotal(subtotal, discount = {}, vatEnabled = false, vatRate = 0) {
        const base = discount.specialType ? Math.round((vatEnabled ? subtotal / (1 + vatRate) : subtotal) * 100) / 100 : subtotal;
        const raw = discount.specialType ? base * .2 : discount.type === 'percentage' ? subtotal * Number(discount.value || 0) / 100 : Number(discount.value || 0);
        const reduction = Math.min(base, Math.max(0, Math.round(raw * 100) / 100));
        return Math.max(0, Math.round((base - reduction) * 100) / 100);
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {countCash, previewTotal};
    if (typeof document === 'undefined') return;

    function setup() {
        document.querySelectorAll('.pos-reference [data-select-chips], .pos-reference [data-reason-chips]').forEach(group => {
            const target = document.getElementById(group.dataset.selectChips || group.dataset.reasonChips);
            if (!target) return;
            const sync = () => group.querySelectorAll('[data-choice]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.choice === target.value)));
            group.addEventListener('click', event => {
                const button = event.target.closest('[data-choice]');
                if (!button) return;
                target.value = button.dataset.choice;
                target.dispatchEvent(new Event('input', {bubbles:true}));
                target.dispatchEvent(new Event('change', {bubbles:true}));
                if (!target.value) target.focus();
                sync();
            });
            target.addEventListener('input', sync);
            target.addEventListener('change', sync);
            sync();
        });
        const inputs = Array.from(document.querySelectorAll('.pos-reference [data-cash-denomination]'));
        const actual = document.getElementById('close-actual-input');
        if (!inputs.length || !actual) return;
        let counting = false;
        inputs.forEach(input => input.addEventListener('input', () => {
            const total = countCash(inputs.map(field => ({value:field.dataset.cashDenomination, count:field.value})));
            inputs.forEach(field => {
                const output = document.getElementById(field.dataset.cashOutput);
                if (output) output.textContent = (Number(field.dataset.cashDenomination) * (Number(field.value) || 0)).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            });
            counting = true;
            actual.value = total === null ? '' : total.toFixed(2);
            actual.dispatchEvent(new Event('input', {bubbles:true}));
            counting = false;
        }));
        const resetCount = () => inputs.forEach(field => {
            field.value = '';
            const output = document.getElementById(field.dataset.cashOutput);
            if (output) output.textContent = '0.00';
        });
        actual.addEventListener('input', () => { if (!counting) resetCount(); });
        window.PosReference = {resetCount, previewTotal};
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup, {once:true});
    else setup();
})();
