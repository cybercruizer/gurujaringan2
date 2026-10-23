<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveTagRequest;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount('posts')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('admin.taxonomy.form-page', [
            'title' => 'Create tag',
            'description' => 'Add a tag to label articles.',
            'item' => new Tag,
            'action' => route('admin.tags.store'),
            'method' => 'POST',
            'hasDescription' => false,
            'cancelUrl' => route('admin.tags.index'),
        ]);
    }

    public function store(SaveTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect()
            ->route('admin.tags.index')
            ->with('status', 'Tag created successfully.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.taxonomy.form-page', [
            'title' => 'Edit tag',
            'description' => 'Update this tag.',
            'item' => $tag,
            'action' => route('admin.tags.update', $tag),
            'method' => 'PUT',
            'hasDescription' => false,
            'cancelUrl' => route('admin.tags.index'),
        ]);
    }

    public function update(SaveTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()
            ->route('admin.tags.index')
            ->with('status', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('status', 'Tag deleted successfully.');
    }
}
