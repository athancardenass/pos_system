(() => {
    document.querySelectorAll('[data-exchange-form]').forEach(form => {
        const list=form.querySelector('[data-exchange-lines]');
        const template=form.querySelector('template');
        const submit=form.querySelector('[data-submit-exchange]');
        const summary=form.querySelector('[data-exchange-summary]');
        const error=form.querySelector('[data-exchange-error]');
        let revision=0, reviewed=-1, lineNumber=0;
        const money=value=>new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(value);
        function invalidate() { revision++; reviewed=-1; submit.disabled=true; form.elements.settlement_confirmed.checked=false; summary.textContent='Review the updated price difference before confirming.'; }
        function addLine() {
            const fragment=template.content.cloneNode(true);
            const product=fragment.querySelector('[data-replacement-product]');
            const quantity=fragment.querySelector('[data-replacement-quantity]');
            product.name=`replacements[${lineNumber}][product_id]`; quantity.name=`replacements[${lineNumber}][quantity]`; lineNumber++;
            fragment.querySelector('[data-remove-exchange-line]').addEventListener('click',e=>{ if(list.children.length>1) {e.target.closest('.exchange-line').remove(); invalidate();} });
            list.append(fragment); invalidate();
        }
        function payload() {
            const items={}; form.querySelectorAll('input[name^="items["]').forEach(input=>{const key=input.name.match(/\[(\d+)\]/)?.[1]; if(key && Number(input.value)>0) items[key]=Number(input.value);});
            const replacements=Array.from(list.children).map(line=>({product_id:Number(line.querySelector('select').value),quantity:Number(line.querySelector('input').value)}));
            return {items,replacements};
        }
        form.querySelector('[data-add-exchange-line]').addEventListener('click',addLine);
        form.addEventListener('input',event=>{if(event.target.hasAttribute('data-exchange-price-input')) invalidate();});
        form.querySelector('[data-preview-exchange]').addEventListener('click',async event=>{
            const fields=payload(); error.hidden=true;
            if(!Object.keys(fields.items).length || fields.replacements.some(r=>!r.product_id || r.quantity<=0)) {error.hidden=false; error.textContent='Choose a return quantity and replacement item.'; return;}
            const before=revision; event.target.disabled=true;
            try {
                const response=await fetch(form.dataset.previewUrl,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':form.elements._token.value},body:JSON.stringify(fields)});
                const data=await response.json(); if(!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || 'Exchange preview unavailable.');
                if(before!==revision) return;
                const settlement=data.difference>0 ? `Collect ${money(data.difference)}` : data.difference<0 ? `Refund ${money(-data.difference)}` : 'No additional payment';
                summary.textContent=`Return credit ${money(data.returned_value)} · Replacement ${money(data.replacement_value)} · ${settlement} (${data.payment_method}).`;
                form.elements.expected_difference.value=Number(data.difference).toFixed(2); reviewed=revision; submit.disabled=false;
                form.elements.settlement_reference.required=data.payment_method!=='cash' && Math.abs(data.difference)>.001;
            } catch(e) {error.hidden=false; error.textContent=e.message || 'Unable to review exchange. Try again.';}
            finally {event.target.disabled=false;}
        });
        form.addEventListener('submit',async event=>{
            if(form.dataset.approved==='true') return;
            event.preventDefault();
            if(reviewed!==revision || !form.reportValidity() || form.dataset.busy==='true') return;
            form.dataset.busy='true'; submit.disabled=true;
            try {
                const details={sale_id:Number(form.dataset.saleId),...payload(),condition:form.elements.condition.value,reason:form.elements.reason.value,operation_key:form.elements.operation_key.value,settlement_reference:form.elements.settlement_reference.value.trim() || null};
                const approval=await window.requestManagerAuthorization({action:'sale_exchange',details});
                if(!approval) return;
                form.elements.manager_authorization_token.value=approval.token || ''; form.dataset.approved='true'; form.requestSubmit();
            } catch(e) {error.hidden=false; error.textContent='Approval could not be completed. Try again.';}
            finally {form.dataset.busy='false'; submit.disabled=false;}
        });
        addLine();
    });
})();
