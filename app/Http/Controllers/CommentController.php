<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function index(): View
    {
        $comments = Comment::query()
            ->whereNull('approved_at')
            ->with(['user:id,name', 'post:id,title,slug,type'])
            ->oldest()
            ->paginate(15);

        return view('admin.comments.index', compact('comments'));
    }

    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = $post->comments()->make($request->safe()->only([
            'author_name',
            'author_email',
            'content',
        ]));

        if ($request->user() !== null) {
            $comment->user()->associate($request->user());
        }

        $comment->parent_id = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $comment->save();

        return redirect()
            ->to($post->url())
            ->with('status', 'Your comment was submitted and is awaiting moderation.');
    }

    public function approve(Comment $comment): RedirectResponse
    {
        if ($comment->approved_at === null) {
            $comment->forceFill(['approved_at' => now()])->save();
        }

        return back()->with('status', 'Comment approved.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('status', 'Comment rejected and deleted.');
    }
}
