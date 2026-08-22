@php($product = $product ?? null)
<div class="form-grid">
    <div>
        <label for="product_name">Name</label>
        <input id="product_name" name="product_name" value="{{ old('product_name', $product?->product_name) }}" required>
    </div>
    <div>
        <label for="barcode">Barcode</label>
        <input id="barcode" name="barcode" value="{{ old('barcode', $product?->barcode) }}" required>
    </div>
    <div>
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">— None —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->category_id }}" @selected(old('category_id', $product?->category_id) == $category->category_id)>{{ $category->category_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id">
            <option value="">— None —</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $product?->supplier_id) == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="unit_price">Unit price</label>
        <input id="unit_price" type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $product?->unit_price) }}" required>
    </div>
    <div>
        <label for="cost_price">Cost price</label>
        <input id="cost_price" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product?->cost_price) }}" required>
    </div>
    <div>
        <label for="reorder_level">Reorder level</label>
        <input id="reorder_level" type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $product?->reorder_level ?? 10) }}" required>
    </div>
</div>
<label for="description">Description</label>
<input id="description" name="description" value="{{ old('description', $product?->description) }}">
