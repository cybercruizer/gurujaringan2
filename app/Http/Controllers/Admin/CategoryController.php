<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.taxonomy.form-page', [
            'title' => 'Create category',
            'description' => 'Add a category to organize articles.',
            'item' => new Category,
            'action' => route('admin.categories.store'),
            'method' => 'POST',
            'hasDescription' => true,
            'cancelUrl' => route('admin.categories.index'),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.taxonomy.form-page', [
            'title' => 'Edit category',
            'description' => 'Update this category.',
            'item' => $category,
            'action' => route('admin.categories.update', $category),
            'method' => 'PUT',
            'hasDescription' => true,
            'cancelUrl' => route('admin.categories.index'),
        ]);
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category deleted successfully.');
    }
}
