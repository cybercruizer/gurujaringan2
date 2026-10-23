<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostImageManager;
use App\Services\PostViewRecorder;
use App\Support\CommentCaptcha;
use App\Support\PostTableOfContents;
use App\Support\RichTextSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()
            ->with('user')
            ->latest('published_at')
            ->paginate(9);

        return view('posts.index', compact('posts'));
    }

    public function create(): View
    {
        Gate::authorize('create', Post::class);

        return view('posts.create', [
            'post' => new Post,
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function store(
        StorePostRequest $request,
        RichTextSanitizer $sanitizer,
        PostImageManager $images,
    ): RedirectResponse {
        $validated = $request->validated();
        $categoryIds = array_values(array_filter(
            $validated['categories'] ?? [],
            fn ($categoryId): bool => $categoryId !== null && $categoryId !== '',
        ));
        $tagIds = array_values(array_filter(
            $validated['tags'] ?? [],
            fn ($tagId): bool => $tagId !== null && $tagId !== '',
        ));
        unset($validated['categories'], $validated['tags']);
        $validated['content'] = $sanitizer->sanitize($validated['content']);

        if ($request->hasFile('featured_image')) {
            $validated = array_merge(
                $validated,
                $images->storeFeaturedImage($request->file('featured_image'), (int) $request->user()->getAuthIdentifier()),
            );
        } elseif (! empty($validated['featured_media_id'])) {
            $media = Media::query()->findOrFail($validated['featured_media_id']);
            $validated['featured_image'] = $media->path;
            $validated['featured_image_thumbnail'] = $media->thumbnail_path;
        }

        $post = $request->user()->posts()->create($validated);
        $post->categories()->sync($categoryIds);
        $post->tags()->sync($tagIds);

        return redirect()
            ->to($post->url())
            ->with('status', 'Post created successfully.');
    }

    public function show(
        Request $request,
        Post $post,
        RichTextSanitizer $sanitizer,
        PostTableOfContents $tableOfContents,
        PostViewRecorder $views,
    ): View|RedirectResponse {
        Gate::authorize('view', $post);

        if (($post->type === 'page') !== $request->routeIs('pages.show')) {
            return redirect()->to($post->url(), 301);
        }

        $post->load(['user', 'categories', 'tags', 'featuredMedia:id,alt_text']);
        $tableOfContentsData = $tableOfContents->generate($sanitizer->sanitize($post->content));
        $content = $tableOfContentsData['content'];
        $headings = $tableOfContentsData['headings'];
        $isPublished = $post->published_at !== null && $post->published_at->lessThanOrEqualTo(now());

        if ($isPublished) {
            $views->record($post, $request);
        }

        $comments = $post->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with([
                'user:id,name,avatar',
                'replies' => fn ($query) => $query
                    ->approved()
                    ->with('user:id,name,avatar')
                    ->oldest(),
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $canComment = $post->comments_enabled && $isPublished;
        $commentCaptchas = [];

        if ($canComment) {
            $captcha = app(CommentCaptcha::class);
            $commentCaptchas['root'] = $captcha->issue();

            foreach ($comments as $comment) {
                $commentCaptchas[$comment->id] = $captcha->issue();
            }
        }

        return view('posts.show', compact(
            'post',
            'content',
            'headings',
            'comments',
            'isPublished',
            'canComment',
            'commentCaptchas',
        ));
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        $post->load(['categories', 'tags', 'featuredMedia']);

        return view('posts.edit', [
            'post' => $post,
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdatePostRequest $request,
        Post $post,
        RichTextSanitizer $sanitizer,
        PostImageManager $images,
    ): RedirectResponse {
        $validated = $request->validated();
        $categoryIds = array_values(array_filter(
            $validated['categories'] ?? [],
            fn ($categoryId): bool => $categoryId !== null && $categoryId !== '',
        ));
        $tagIds = array_values(array_filter(
            $validated['tags'] ?? [],
            fn ($tagId): bool => $tagId !== null && $tagId !== '',
        ));
        $previousImage = $post->featured_image;
        $previousThumbnail = $post->featured_image_thumbnail;
        $previousMediaId = $post->featured_media_id;
        unset($validated['categories'], $validated['tags']);
        $validated['content'] = $sanitizer->sanitize($validated['content']);

        if ($request->hasFile('featured_image')) {
            $validated = array_merge(
                $validated,
                $images->storeFeaturedImage($request->file('featured_image'), (int) $request->user()->getAuthIdentifier()),
            );
        } elseif ($request->boolean('remove_featured_image')) {
            $validated['featured_image'] = null;
            $validated['featured_image_thumbnail'] = null;
            $validated['featured_media_id'] = null;
        } elseif (! empty($validated['featured_media_id'])) {
            $media = Media::query()->findOrFail($validated['featured_media_id']);
            $validated['featured_image'] = $media->path;
            $validated['featured_image_thumbnail'] = $media->thumbnail_path;
        }
        unset($validated['remove_featured_image']);

        $post->update($validated);
        $post->categories()->sync($categoryIds);
        $post->tags()->sync($tagIds);

        if (
            $previousMediaId === null
            && ($previousImage !== $post->featured_image || $previousThumbnail !== $post->featured_image_thumbnail)
        ) {
            $images->deleteFeaturedImage($previousImage, $previousThumbnail);
        }

        return redirect()
            ->to($post->url())
            ->with('status', 'Post updated successfully.');
    }

    public function destroy(Post $post, PostImageManager $images): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $featuredImage = $post->featured_media_id === null ? $post->featured_image : null;
        $featuredImageThumbnail = $post->featured_media_id === null ? $post->featured_image_thumbnail : null;
        $post->delete();
        $images->deleteFeaturedImage($featuredImage, $featuredImageThumbnail);

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post deleted successfully.');
    }
}
