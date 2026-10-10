<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()->orderBy('category_name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::query()->create($data);

        AuditLogger::record('create', 'category', $category->category_id, 'Created category '.$category->category_name);

        return redirect()->route('categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $category->update($data);

        AuditLogger::record('update', 'category', $category->category_id, 'Updated category '.$category->category_name);

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category that still has products.');
        }

        $id = $category->category_id;
        $name = $category->category_name;
        $category->delete();

        AuditLogger::record('delete', 'category', $id, 'Deleted category '.$name);

        return redirect()->route('categories.index')->with('status', 'Category deleted.');
    }
}
