<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->merge([
            'q' => trim($request->string('q')->toString()),
        ]);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);
        $searchTerm = $validated['q'];
        $pattern = '%'.$searchTerm.'%';

        $results = Post::query()
            ->published()
            ->where(function (Builder $query) use ($pattern): void {
                $query->where('title', 'like', $pattern)
                    ->orWhere('excerpt', 'like', $pattern)
                    ->orWhere('content', 'like', $pattern);
            })
            ->latest('published_at')
            ->limit(8)
            ->get(['id', 'title', 'slug', 'type', 'excerpt', 'content', 'published_at'])
            ->map(fn (Post $post): array => [
                'title' => $post->title,
                'excerpt' => Str::limit(trim(strip_tags($post->excerpt ?: $post->content)), 160),
                'url' => $post->url(),
                'type' => $post->type === 'page' ? 'Page' : 'Article',
                'published_at' => $post->published_at?->format('M j, Y'),
            ]);

        return response()->json(['results' => $results]);
    }
}
