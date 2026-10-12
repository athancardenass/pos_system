(() => {
    document.querySelectorAll('form[data-shift-action]').forEach(form => {
        form.addEventListener('submit', async event => {
            if (form.dataset.approved === 'true') return;
            event.preventDefault();
            if (!form.reportValidity() || form.dataset.busy === 'true') return;
            form.dataset.busy = 'true';
            const message = form.querySelector('[role="alert"]');
            const value = name => form.elements.namedItem(name)?.value;
            const action = form.dataset.shiftAction;
            let details;
            if (action === 'cash_transfer_cancel') details = {transfer_id:Number(value('transfer_id'))};
            else {
                details = {amount:Number(value('amount')), reason:value('reason').trim(), operation_key:value('operation_key')};
                if (action === 'cash_movement') details.kind = value('kind');
                else details.destination_drawer_id = Number(value('destination_drawer_id'));
            }
            try {
                const approval = await window.requestManagerAuthorization({action, details});
                if (!approval) return;
                form.elements.namedItem('manager_authorization_token').value = approval.token || '';
                form.dataset.approved = 'true';
                form.requestSubmit();
            } catch (error) {
                message.hidden = false;
                message.textContent = 'Authorization failed. Check your connection and try again.';
            } finally { form.dataset.busy = 'false'; }
        });
    });
})();
