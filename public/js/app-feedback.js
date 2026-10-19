(function (root, factory) {
    const api = factory();

    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root && root.document) {
        root.AppFeedback = api.createAppFeedback(root.document);

        const approvedForms = new WeakSet();
        root.document.addEventListener('submit', event => {
            const form = event.target;
            if (event.defaultPrevented || !form?.dataset?.confirm) return;
            if (approvedForms.has(form)) {
                approvedForms.delete(form);
                return;
            }

            event.preventDefault();
            const submitter = event.submitter;
            root.AppFeedback.confirm(form.dataset.confirm, {
                confirmLabel: form.dataset.confirmLabel || 'Continue',
            }).then(confirmed => {
                if (!confirmed) return;
                if (typeof form.requestSubmit !== 'function') {
                    root.AppFeedback.notify('This form cannot be submitted by this browser.', 'error');
                    return;
                }

                approvedForms.add(form);
                try {
                    if (submitter) form.requestSubmit(submitter);
                    else form.requestSubmit();
                } catch {
                    approvedForms.delete(form);
                    root.AppFeedback.notify('This form could not be submitted. Try again.', 'error');
                }
            });
        });
    }
})(typeof window === 'undefined' ? globalThis : window, function () {
    function createAppFeedback(document) {
        const notice = document.getElementById('app-feedback');
        const dialog = document.getElementById('app-confirm-dialog');
        const message = document.getElementById('app-confirm-message');
        const accept = document.getElementById('app-confirm-accept');
        const cancel = document.getElementById('app-confirm-cancel');

        function notify(text, type = 'error') {
            if (!notice) return;
            const variants = {
                error: ['flash-error', 'alert', 'assertive'],
                warning: ['flash-warning', 'alert', 'assertive'],
                success: ['flash-ok', 'status', 'polite'],
                info: ['flash-info', 'status', 'polite'],
            };
            const [variant, role, live] = variants[type] || variants.error;
            notice.className = `flash app-feedback ${variant}`;
            notice.textContent = String(text ?? '');
            notice.setAttribute('role', role);
            notice.setAttribute('aria-live', live);
            notice.hidden = false;
        }

        if (dialog && message && accept && cancel) {
            accept.addEventListener('click', () => dialog.close('confirm'));
            cancel.addEventListener('click', () => dialog.close('cancel'));
        }

        function confirm(text, options = {}) {
            if (!dialog || !message || !accept || !cancel || typeof dialog.showModal !== 'function') {
                notify('This confirmation is not available in this browser.', 'error');
                return Promise.resolve(false);
            }

            message.textContent = String(text ?? 'Continue with this action?');
            accept.textContent = options.confirmLabel || 'Continue';
            cancel.textContent = options.cancelLabel || 'Cancel';
            dialog.returnValue = '';
            dialog.showModal();

            return new Promise(resolve => {
                dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
            });
        }

        return { notify, confirm };
    }

    return { createAppFeedback };
});
