@php($category = $category ?? null)
<label for="category_name">Name</label>
<input id="category_name" name="category_name" class="input-lg bordered" value="{{ old('category_name', $category?->category_name) }}" required>
<label for="description">Description</label>
<textarea id="description" name="description" class="input-lg bordered">{{ old('description', $category?->description) }}</textarea>
