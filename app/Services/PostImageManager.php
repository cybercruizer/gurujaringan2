<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

class PostImageManager
{
    /**
     * @return array{featured_image: string, featured_image_thumbnail: string, featured_media_id: int}
     */
    public function storeFeaturedImage(UploadedFile $file, int $userId): array
    {
        $media = $this->store($file, $userId, 'images/posts');

        return [
            'featured_image' => $media->path,
            'featured_image_thumbnail' => $media->thumbnail_path,
            'featured_media_id' => $media->id,
        ];
    }

    public function storeContentImage(UploadedFile $file, int $userId): string
    {
        return $this->store($file, $userId, "images/content/{$userId}")->path;
    }

    public function storeGalleryImage(UploadedFile $file, int $userId, ?string $altText): Media
    {
        return $this->store($file, $userId, 'images/library', $altText);
    }

    private function store(UploadedFile $file, int $userId, string $directory, ?string $altText = null): Media
    {
        $filename = Str::random(40);
        $extension = $this->isGif($file) ? 'gif' : 'webp';
        $imagePath = "{$directory}/{$filename}.{$extension}";
        $thumbnailPath = "{$directory}/thumbnails/{$filename}.webp";
        $disk = Storage::disk('public');
        $imageContents = $this->isGif($file)
            ? $file->getContent()
            : $this->webpContents($file, 2000, 2000, 82);
        $thumbnailContents = $this->webpContents($file, 640, 480, 75);

        if (! $disk->put($imagePath, $imageContents)) {
            throw new RuntimeException('The image could not be stored.');
        }

        if (! $disk->put($thumbnailPath, $thumbnailContents)) {
            if (! $disk->delete($imagePath)) {
                throw new RuntimeException('The image thumbnail could not be stored, and the original could not be cleaned up.');
            }

            throw new RuntimeException('The image thumbnail could not be stored.');
        }

        try {
            return Media::query()->create([
                'user_id' => $userId,
                'path' => $imagePath,
                'thumbnail_path' => $thumbnailPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'alt_text' => $altText,
            ]);
        } catch (\Throwable $exception) {
            if (! $disk->delete([$imagePath, $thumbnailPath])) {
                throw new RuntimeException('The media record could not be saved, and its image files could not be cleaned up.', previous: $exception);
            }

            throw $exception;
        }
    }

    public function deleteFeaturedImage(?string $imagePath, ?string $thumbnailPath): void
    {
        $paths = array_values(array_filter([$imagePath, $thumbnailPath]));

        if ($paths !== [] && ! Storage::disk('public')->delete($paths)) {
            throw new RuntimeException('The post images could not be deleted.');
        }
    }

    private function isGif(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'image/gif';
    }

    private function webpContents(UploadedFile $file, int $width, int $height, int $quality): string
    {
        return Image::decodeSplFileInfo($file)
            ->scaleDown($width, $height)
            ->encode(new WebpEncoder(quality: $quality))
            ->toString();
    }
}
