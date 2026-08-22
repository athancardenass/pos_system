@php($category = $category ?? null)
<label for="category_name">Name</label>
<input id="category_name" name="category_name" value="{{ old('category_name', $category?->category_name) }}" required>
<label for="description">Description</label>
<input id="description" name="description" value="{{ old('description', $category?->description) }}">
