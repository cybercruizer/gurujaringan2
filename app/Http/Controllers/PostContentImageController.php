<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPostContentImageRequest;
use App\Services\PostImageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PostContentImageController extends Controller
{
    public function __invoke(UploadPostContentImageRequest $request, PostImageManager $images): JsonResponse
    {
        $path = $images->storeContentImage($request->file('image'), (int) $request->user()->getAuthIdentifier());

        return response()->json([
            'location' => Storage::disk('public')->url($path),
        ]);
    }
}
