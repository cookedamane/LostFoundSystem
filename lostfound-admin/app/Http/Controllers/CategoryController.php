<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', ['categories' => Category::withCount('items')->orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|min:2|max:80|unique:categories,name', 'description' => 'nullable|string|max:255']);
        $c = Category::create($data);
        ActivityLog::record('category_created', $c->name);
        return back()->with('ok', 'Category added.');
    }

    public function update(Request $r, Category $category)
    {
        $data = $r->validate(['name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('categories', 'name')->ignore($category->id)], 'description' => 'nullable|string|max:255']);
        $category->update($data);
        ActivityLog::record('category_updated', $category->name);
        return back()->with('ok', 'Category saved.');
    }

    public function destroy(Category $category)
    {
        if ($category->items()->exists()) {
            return back()->withErrors(['category' => "\"{$category->name}\" still has items. Move them to another category first."]);
        }
        ActivityLog::record('category_deleted', $category->name);
        $category->delete();
        return back()->with('ok', 'Category deleted.');
    }
}
