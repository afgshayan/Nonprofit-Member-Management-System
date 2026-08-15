<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $categories = Category::withCount('persons')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['name' => 'required|string|max:100|unique:categories,name']);
        Category::create(['name' => trim($data['name'])]);
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['name' => 'required|string|max:100|unique:categories,name,' . $category->id]);
        $category->update(['name' => trim($data['name'])]);
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->authorizeAdmin();
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted. It was removed from linked members.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Only administrators can manage categories.');
    }
}