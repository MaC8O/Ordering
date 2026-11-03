<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        Category::create([
            'name' => $data['name'],
            'display_order' => (int) (Category::max('display_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $category->update(['name' => $data['name']]);

        return back()->with('success', 'Category renamed.');
    }

    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', 'Category updated.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($data['order'] as $position => $id) {
            Category::where('id', $id)->update(['display_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Category $category)
    {
        if ($category->menuItems()->exists()) {
            return back()->with('error', 'Remove or move its items before deleting this category.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
