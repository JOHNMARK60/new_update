<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Category::create($request->validate(['name' => 'required|max:120|unique:categories,name']));

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($request->validate(['name' => 'required|max:120|unique:categories,name,'.$category->id]));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, 'Move products out of this category before deleting it.');
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
