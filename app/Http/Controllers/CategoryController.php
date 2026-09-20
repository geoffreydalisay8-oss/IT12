<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL CATEGORIES
    // =========================================================
    public function index()
    {
        $categories = Category::orderBy('category_name')->get();

        return view('categories.index', [
            'categories' => $categories
        ]);
    }

    // =========================================================
    // 2. DISPLAY CREATE CATEGORY FORM
    // =========================================================
    public function create()
    {
        return view('categories.create');
    }

    // =========================================================
    // 3. SAVE A NEW CATEGORY
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:100|',
        ]);

        $category = new Category();

        $category->category_name = $request->input('category_name');

        $category->save();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category added successfully.');
    }

    // =========================================================
    // 4. DISPLAY ONE CATEGORY
    // =========================================================
    public function show($id)
    {
        $category = Category::findOrFail($id);

        return view('categories.show', [
            'category' => $category
        ]);
    }

    // =========================================================
    // 5. DISPLAY EDIT CATEGORY FORM
    // =========================================================
    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return view('categories.edit', [
            'category' => $category
        ]);
    }

    // =========================================================
    // 6. UPDATE AN EXISTING CATEGORY
    // =========================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required|string|max:100|',
        ]);

        $category = Category::findOrFail($id);

        $category->category_name = $request->input('category_name');

        $category->save();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    // =========================================================
    // 7. DELETE A CATEGORY
    // =========================================================
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}