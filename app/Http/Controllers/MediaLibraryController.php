<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaLibraryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $validated['search'] ?? null;
        $mediaItems = Media::query()
            ->when($search !== null, fn ($query) => $query->where('original_name', 'like', '%'.$search.'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(24);

        return response()->json([
            'data' => $mediaItems->getCollection()->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->original_name,
                'alt_text' => $media->alt_text,
                'url' => $media->url(),
                'thumbnail_url' => $media->thumbnailUrl(),
            ]),
            'next_page_url' => $mediaItems->nextPageUrl(),
        ]);
    }
}
