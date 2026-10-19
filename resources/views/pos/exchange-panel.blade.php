@if (!$sale->isFullyRefunded())
@push('styles')
<style>
    .exchange-lines { display: grid; gap: 0.75rem; }
    .exchange-line { display: grid; grid-template-columns: minmax(0, 2fr) minmax(9rem, 1fr) auto; align-items: end; gap: 0.85rem; padding: 1rem; border: 1px solid var(--rule-faint); border-radius: var(--r-lg); background: var(--surface-soft); }
    .exchange-field { display: grid; gap: 0.35rem; min-width: 0; margin: 0; color: var(--muted); font-size: 0.82rem; font-weight: 600; }
    .exchange-field select, .exchange-field input { min-width: 0; min-height: 2.75rem; margin: 0; }
    .exchange-line .btn { min-height: 2.75rem; white-space: nowrap; }
    @media (max-width: 760px) {
        .exchange-line { grid-template-columns: minmax(0, 1fr) auto; }
        .exchange-product-field { grid-column: 1 / -1; }
    }
    @media (max-width: 480px) {
        .exchange-line { grid-template-columns: minmax(0, 1fr); padding: 0.85rem; }
        .exchange-product-field { grid-column: auto; }
        .exchange-line .btn { width: 100%; }
    }
</style>
@endpush
<details class="card exchange-form"><summary><strong>Exchange items</strong></summary>
    <p>Use the original receipt credit toward replacement items. Settle any difference through {{ ucfirst($sale->payment_method) }}.</p>
    <form method="POST" action="{{ route('pos.exchange',$sale) }}" data-exchange-form data-sale-id="{{ $sale->transaction_id }}" data-preview-url="{{ route('pos.exchange.preview',$sale) }}">
        @csrf<input type="hidden" name="operation_key" value="{{ old('operation_key',(string)\Illuminate\Support\Str::uuid()) }}"><input type="hidden" name="manager_authorization_token"><input type="hidden" name="register_id" value="REG 01"><input type="hidden" name="expected_difference">
        <div class="form-grid">
            @foreach ($sale->saleDetails as $line)
                <div><label for="exchange-return-{{ $line->sale_detail_id }}">Return {{ $line->product?->product_name }} ({{ $line->refundableQuantity() }} remaining)</label><input id="exchange-return-{{ $line->sale_detail_id }}" name="items[{{ $line->sale_detail_id }}]" type="number" min="0" max="{{ $line->refundableQuantity() }}" step="0.001" value="0" data-exchange-price-input></div>
            @endforeach
            <div><label for="exchange-condition">Returned condition</label><select id="exchange-condition" name="condition" required><option value="resellable">Resellable — restore available stock</option><option value="damaged">Damaged — keep out of available stock</option></select></div>
            <div><label for="exchange-reason">Reason</label><select id="exchange-reason" name="reason" required>@foreach (\App\Services\RefundService::REASONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
        </div>
        <h3>Replacement items</h3><div class="exchange-lines" data-exchange-lines></div>
        <template data-exchange-line-template><div class="exchange-line">
            <label class="exchange-field exchange-product-field">Product<select required data-replacement-product data-exchange-price-input><option value="">Choose a product</option>@foreach ($exchangeProducts as $product)<option value="{{ $product->product_id }}">{{ $product->product_name }} · ₱{{ number_format($product->unit_price,2) }}</option>@endforeach</select></label>
            <label class="exchange-field">Quantity<input type="number" min="0.001" max="999999" step="0.001" value="1" required data-replacement-quantity data-exchange-price-input></label>
            <button type="button" class="btn btn-secondary" data-remove-exchange-line>Remove</button>
        </div></template>
        <div class="shift-actions"><button type="button" class="btn btn-secondary" data-add-exchange-line>Add replacement</button><button type="button" class="btn btn-secondary" data-preview-exchange>Review price difference</button></div>
        <p class="exchange-summary" data-exchange-summary role="status">Select returned and replacement items, then review the difference.</p>
        <label for="exchange-reference">Payment / refund reference (required for non-cash difference)</label><input id="exchange-reference" name="settlement_reference" maxlength="100" autocomplete="off">
        <label class="shift-check"><input type="checkbox" name="settlement_confirmed" value="1" required><span>I have collected or refunded the displayed difference through the original payment method.</span></label>
        <p class="shift-form-error" role="alert" data-exchange-error hidden></p>
        <button type="submit" class="btn" data-submit-exchange disabled>Approve and complete exchange</button>
    </form>
</details>
@endif
