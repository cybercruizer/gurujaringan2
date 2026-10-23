<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostManagementController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $validated['search'] ?? null;

        $posts = Post::query()
            ->with('user:id,name')
            ->when($search !== null, fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'search'));
    }

    public function unpublish(Post $post): RedirectResponse
    {
        $post->update(['published_at' => null]);

        return redirect()
            ->route('admin.posts.index')
            ->with('status', 'Article unpublished.');
    }
}
