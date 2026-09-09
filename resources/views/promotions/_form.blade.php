@php($promotion = $promotion ?? null)
<div class="form-grid promo-grid">
    <div>
        <label for="name">Name</label>
        <input id="name" name="name" class="input-lg bordered" value="{{ old('name', $promotion?->name) }}" maxlength="100" required>
    </div>
    <div>
        <label for="type">Type</label>
        <select id="type" name="type" required>
            @foreach (\App\Models\Promotion::TYPES as $promotionType)
                <option value="{{ $promotionType }}" @selected(old('type', $promotion?->type) === $promotionType)>
                    {{ \Illuminate\Support\Str::headline($promotionType) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="scope">Scope</label>
        <select id="scope" name="scope" required>
            @foreach (\App\Models\Promotion::SCOPES as $promotionScope)
                <option value="{{ $promotionScope }}" @selected(old('scope', $promotion?->scope ?? 'product') === $promotionScope)>
                    {{ ucfirst($promotionScope) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="scope_id">Target (product or category)</label>
        <select id="scope_id" name="scope_id">
            <option value="">— None (cart-wide only) —</option>
            <optgroup label="Categories">
                @foreach ($categories as $category)
                    <option value="{{ $category->category_id }}" @selected((int) old('scope_id', $promotion?->scope_id ?? 0) === (int) $category->category_id)>
                        Category: {{ $category->category_name }}
                    </option>
                @endforeach
            </optgroup>
            <optgroup label="Products">
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" @selected((int) old('scope_id', $promotion?->scope_id ?? 0) === (int) $product->product_id)>
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </optgroup>
        </select>
    </div>
    <div>
        <label for="value">Value (percent or peso amount)</label>
        <input id="value" type="number" step="0.01" min="0" name="value" value="{{ old('value', $promotion?->value) }}">
    </div>
    <div>
        <label for="bundle_qty">Bundle quantity (Bundle price only)</label>
        <input id="bundle_qty" type="number" min="1" step="1" name="bundle_qty" value="{{ old('bundle_qty', $promotion?->bundle_qty) }}">
    </div>
    <div>
        <label for="bundle_price">Bundle price (Bundle price only)</label>
        <input id="bundle_price" type="number" min="0" step="0.01" name="bundle_price" value="{{ old('bundle_price', $promotion?->bundle_price) }}">
    </div>
    <div>
        <label for="x_qty">Buy quantity (Buy X get Y only)</label>
        <input id="x_qty" type="number" min="1" step="1" name="x_qty" value="{{ old('x_qty', $promotion?->x_qty) }}">
    </div>
    <div>
        <label for="y_qty">Free quantity (Buy X get Y only)</label>
        <input id="y_qty" type="number" min="1" step="1" name="y_qty" value="{{ old('y_qty', $promotion?->y_qty) }}">
    </div>
    <div>
        <label for="starts_at">Starts at</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($promotion?->starts_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label for="ends_at">Ends at</label>
        <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($promotion?->ends_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label for="is_active">Status</label>
        <select id="is_active" name="is_active" required>
            <option value="1" @selected(old('is_active', $promotion?->is_active ?? true))>Active</option>
            <option value="0" @selected(old('is_active', $promotion?->is_active ?? true) == false)>Inactive</option>
        </select>
    </div>
</div>
