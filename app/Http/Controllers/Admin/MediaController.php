<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use App\Models\Post;
use App\Services\PostImageManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $validated['search'] ?? null;

        $mediaItems = Media::query()
            ->when($search !== null, fn ($query) => $query->where('original_name', 'like', '%'.$search.'%'))
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', compact('mediaItems', 'search'));
    }

    public function store(StoreMediaRequest $request, PostImageManager $images): RedirectResponse
    {
        $images->storeGalleryImage(
            $request->file('image'),
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('alt_text'),
        );

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Image added to the media library.');
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Image alternative text updated.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $isUsed = Post::withTrashed()
            ->where(function ($query) use ($media): void {
                $query->where('featured_media_id', $media->id)
                    ->orWhere('featured_image', $media->path)
                    ->orWhere('featured_image_thumbnail', $media->thumbnail_path)
                    ->orWhere('content', 'like', '%'.$media->path.'%');
            })
            ->exists();

        if ($isUsed) {
            return back()->with('status', 'This image is in use by a post and cannot be deleted.');
        }

        if (! Storage::disk('public')->delete([$media->path, $media->thumbnail_path])) {
            abort(500, 'The media files could not be deleted.');
        }

        $media->delete();

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Image deleted from the media library.');
    }
}
