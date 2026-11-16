<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class ArticleBackupManager
{
    private const FORMAT = 'guru-jaringan-article-backup';

    private const VERSION = 1;

    private const MAX_ARCHIVE_BYTES = 100 * 1024 * 1024;

    private const MAX_MANIFEST_BYTES = 32 * 1024 * 1024;

    private const MAX_MEDIA_FILE_BYTES = 10 * 1024 * 1024;

    /**
     * Create a ZIP archive containing articles, their taxonomies, comments, and referenced images.
     */
    public function export(): string
    {
        /** @var Collection<int, Post> $posts */
        $posts = Post::query()
            ->where('type', 'article')
            ->with(['categories', 'tags', 'comments.user', 'featuredMedia'])
            ->orderBy('id')
            ->get();
        $media = $this->mediaForPosts($posts);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'article-backup-');

        if ($temporaryPath === false) {
            throw new RuntimeException('A temporary file for the article backup could not be created.');
        }

        $archive = new ZipArchive;
        $opened = $archive->open($temporaryPath, ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($temporaryPath);

            throw new RuntimeException('The article backup archive could not be opened for writing.');
        }

        try {
            $manifest = [
                'format' => self::FORMAT,
                'version' => self::VERSION,
                'source_url' => rtrim((string) config('app.url'), '/'),
                'articles' => $posts->map(fn (Post $post): array => $this->exportPost($post))->all(),
                'media' => $media->map(fn (Media $item): array => $this->exportMedia($archive, $item))->all(),
            ];
            $encodedManifest = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (strlen($encodedManifest) > self::MAX_MANIFEST_BYTES) {
                throw new RuntimeException('The article backup manifest exceeds the supported size limit.');
            }

            if (! $archive->addFromString('backup.json', $encodedManifest) || $archive->numFiles > 10000) {
                throw new RuntimeException('The article backup archive could not be completed.');
            }

            $uncompressedSize = 0;

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);

                if ($stat === false) {
                    throw new RuntimeException('The article backup archive could not be verified.');
                }

                $uncompressedSize += $stat['size'];

                if ($uncompressedSize > self::MAX_ARCHIVE_BYTES) {
                    throw new RuntimeException('The article backup exceeds the supported 100 MB size limit.');
                }
            }

            if (! $archive->close()) {
                throw new RuntimeException('The article backup archive could not be completed.');
            }
        } catch (Throwable $exception) {
            $archive->close();
            @unlink($temporaryPath);

            throw $exception;
        }

        return $temporaryPath;
    }

    /**
     * Restore articles by slug, replacing their taxonomy and comments.
     *
     * @return array{created: int, updated: int}
     */
    public function import(UploadedFile $file, User $user): array
    {
        $manifest = $this->readArchive($file);
        $disk = Storage::disk('public');
        $createdPaths = [];

        try {
            $result = DB::transaction(function () use ($manifest, $user, $disk, &$createdPaths): array {
                $mediaByOriginalPath = [];

                foreach ($manifest['media'] as $media) {
                    $imageContents = $this->archiveContents($manifest['_archive'], $media['file']);
                    $thumbnailContents = $this->archiveContents($manifest['_archive'], $media['thumbnail_file']);
                    $imageMimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContents);
                    $thumbnailMimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($thumbnailContents);

                    if ($imageMimeType !== $media['mime_type'] || $thumbnailMimeType !== 'image/webp') {
                        $this->rejectImport('backup', 'The backup archive contains a media file with an invalid image type.');
                    }

                    $imageHash = hash('sha256', $imageContents);
                    $thumbnailHash = hash('sha256', $thumbnailContents);
                    $imageExtension = $this->imageExtension($media['mime_type']);
                    $path = "images/backups/{$imageHash}.{$imageExtension}";
                    $thumbnailPath = "images/backups/thumbnails/{$thumbnailHash}.webp";

                    foreach ([
                        $path => $imageContents,
                        $thumbnailPath => $thumbnailContents,
                    ] as $storagePath => $contents) {
                        if (! $disk->exists($storagePath)) {
                            if (! $disk->put($storagePath, $contents)) {
                                throw new RuntimeException('A media file from the backup could not be restored.');
                            }

                            $createdPaths[] = $storagePath;
                        }
                    }

                    $record = Media::query()->firstOrNew(['path' => $path]);
                    $record->forceFill([
                        'user_id' => $record->exists ? $record->user_id : $user->getKey(),
                        'thumbnail_path' => $thumbnailPath,
                        'original_name' => $media['original_name'],
                        'mime_type' => $media['mime_type'],
                        'size' => strlen($imageContents),
                        'alt_text' => $media['alt_text'],
                    ])->save();

                    $mediaByOriginalPath[$media['path']] = [
                        'record' => $record,
                        'path' => $path,
                        'thumbnail_path' => $thumbnailPath,
                    ];
                    $mediaByOriginalPath[$media['thumbnail_path']] = [
                        'record' => $record,
                        'path' => $thumbnailPath,
                        'thumbnail_path' => $thumbnailPath,
                    ];
                }

                $categoryIds = $this->restoreCategories($manifest['articles']);
                $tagIds = $this->restoreTags($manifest['articles']);
                $created = 0;
                $updated = 0;

                foreach ($manifest['articles'] as $article) {
                    $post = Post::withTrashed()->where('slug', $article['slug'])->first();
                    $isNew = $post === null;
                    $post ??= new Post;

                    if ($post->trashed()) {
                        $post->restore();
                    }

                    $content = $this->rewriteMediaReferences($article['content'], $manifest, $mediaByOriginalPath);
                    $featuredImage = $this->mappedMediaPath($article['featured_image'], $mediaByOriginalPath);
                    $featuredThumbnail = $this->mappedMediaPath($article['featured_image_thumbnail'], $mediaByOriginalPath);
                    $featuredMedia = $article['featured_media_path'] === null
                        ? null
                        : ($mediaByOriginalPath[$article['featured_media_path']] ?? null);

                    $post->forceFill([
                        'user_id' => $isNew ? $user->getKey() : $post->user_id,
                        'title' => $article['title'],
                        'slug' => $article['slug'],
                        'type' => 'article',
                        'content' => $content,
                        'excerpt' => $article['excerpt'],
                        'featured_image' => $featuredImage,
                        'featured_image_thumbnail' => $featuredThumbnail,
                        'featured_media_id' => $featuredMedia === null ? null : $featuredMedia['record']->getKey(),
                        'published_at' => $article['published_at'],
                        'seo_title' => $article['seo_title'],
                        'seo_description' => $article['seo_description'],
                        'seo_noindex' => $article['seo_noindex'],
                        'comments_enabled' => $article['comments_enabled'],
                    ])->save();

                    $post->categories()->sync(array_map(
                        fn (array $category): int => $categoryIds[$category['slug']],
                        $article['categories'],
                    ));
                    $post->tags()->sync(array_map(
                        fn (array $tag): int => $tagIds[$tag['slug']],
                        $article['tags'],
                    ));

                    $post->comments()->delete();
                    $this->restoreComments($post, $article['comments']);

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                return compact('created', 'updated');
            });
        } catch (Throwable $exception) {
            if ($createdPaths !== [] && ! $disk->delete($createdPaths)) {
                throw new RuntimeException('The article backup failed, and restored media files could not be cleaned up.', previous: $exception);
            }

            throw $exception;
        } finally {
            $manifest['_archive']->close();
        }

        return $result;
    }

    /**
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, Media>
     */
    private function mediaForPosts(Collection $posts): Collection
    {
        $mediaItems = Media::query()->orderBy('id')->get();

        return $mediaItems->filter(function (Media $media) use ($posts): bool {
            foreach ($posts as $post) {
                if ($post->featured_media_id === $media->getKey()
                    || $post->featured_image === $media->path
                    || $post->featured_image_thumbnail === $media->thumbnail_path
                    || str_contains($post->content, $media->path)
                    || str_contains($post->content, $media->thumbnail_path)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function exportPost(Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'featured_image' => $post->featured_image,
            'featured_image_thumbnail' => $post->featured_image_thumbnail,
            'featured_media_path' => $post->featuredMedia?->path,
            'published_at' => $post->published_at?->toISOString(),
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
            'seo_noindex' => $post->seo_noindex,
            'comments_enabled' => $post->comments_enabled,
            'categories' => $post->categories->map(fn (Category $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ])->all(),
            'tags' => $post->tags->map(fn (Tag $tag): array => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->all(),
            'comments' => $post->comments->map(fn (Comment $comment): array => [
                'id' => (string) $comment->getKey(),
                'parent_id' => $comment->parent_id === null ? null : (string) $comment->parent_id,
                'author_name' => $comment->author_name ?: $comment->user?->name,
                'author_email' => $comment->author_email ?: $comment->user?->email,
                'content' => $comment->content,
                'approved_at' => $comment->approved_at?->toISOString(),
                'created_at' => $comment->created_at?->toISOString(),
                'updated_at' => $comment->updated_at?->toISOString(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportMedia(ZipArchive $archive, Media $media): array
    {
        $disk = Storage::disk('public');
        $imageContents = $disk->get($media->path);
        $thumbnailContents = $disk->get($media->thumbnail_path);

        if (! is_string($imageContents) || ! is_string($thumbnailContents)) {
            throw new RuntimeException("The media files for {$media->original_name} could not be read for the backup.");
        }

        if (strlen($imageContents) > self::MAX_MEDIA_FILE_BYTES
            || strlen($thumbnailContents) > self::MAX_MEDIA_FILE_BYTES) {
            throw new RuntimeException("The media files for {$media->original_name} exceed the supported 10 MB per-file limit.");
        }

        $file = "media/{$media->getKey()}/".basename($media->path);
        $thumbnailFile = "media/{$media->getKey()}/".basename($media->thumbnail_path);

        if (! $archive->addFromString($file, $imageContents)
            || ! $archive->addFromString($thumbnailFile, $thumbnailContents)) {
            throw new RuntimeException("The media files for {$media->original_name} could not be added to the backup.");
        }

        return [
            'path' => $media->path,
            'thumbnail_path' => $media->thumbnail_path,
            'url' => $media->url(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'alt_text' => $media->alt_text,
            'file' => $file,
            'thumbnail_file' => $thumbnailFile,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readArchive(UploadedFile $file): array
    {
        if ($file->getSize() > self::MAX_ARCHIVE_BYTES) {
            $this->rejectImport('backup', 'The backup archive must not exceed 100 MB.');
        }

        $archive = new ZipArchive;

        if ($archive->open($file->getRealPath()) !== true) {
            $this->rejectImport('backup', 'The uploaded file is not a valid article backup archive.');
        }

        try {
            if ($archive->numFiles < 1 || $archive->numFiles > 10000) {
                $this->rejectImport('backup', 'The backup archive contains an invalid number of files.');
            }

            $manifestStat = $archive->statName('backup.json');

            if ($manifestStat === false || $manifestStat['size'] > self::MAX_MANIFEST_BYTES) {
                $this->rejectImport('backup', 'The backup manifest is missing or too large.');
            }

            $uncompressedSize = 0;

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);

                if ($stat === false) {
                    $this->rejectImport('backup', 'The backup archive contains an unreadable file.');
                }

                $uncompressedSize += $stat['size'];

                if ($uncompressedSize > self::MAX_ARCHIVE_BYTES) {
                    $this->rejectImport('backup', 'The uncompressed backup archive must not exceed 100 MB.');
                }
            }

            $manifestContents = $archive->getFromName('backup.json');

            if (! is_string($manifestContents)) {
                $this->rejectImport('backup', 'The backup manifest could not be read.');
            }

            $manifest = json_decode($manifestContents, true);

            if (! is_array($manifest)
                || ($manifest['format'] ?? null) !== self::FORMAT
                || ($manifest['version'] ?? null) !== self::VERSION) {
                $this->rejectImport('backup', 'The archive is not a supported article backup.');
            }

            $this->validateManifest($manifest);

            foreach ($manifest['media'] as $media) {
                $this->validateArchivePath($media['file']);
                $this->validateArchivePath($media['thumbnail_file']);
                $this->archiveContents($archive, $media['file']);
                $this->archiveContents($archive, $media['thumbnail_file']);
            }

            $manifest['_archive'] = $archive;

            return $manifest;
        } catch (Throwable $exception) {
            $archive->close();

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function validateManifest(array $manifest): void
    {
        $validator = Validator::make($manifest, [
            'format' => ['required', 'string'],
            'version' => ['required', 'integer'],
            'source_url' => ['required', 'string', 'max:2048'],
            'articles' => ['present', 'array', 'max:10000'],
            'articles.*.title' => ['required', 'string', 'max:255'],
            'articles.*.slug' => ['required', 'string', 'max:255'],
            'articles.*.content' => ['required', 'string', 'max:200000'],
            'articles.*.excerpt' => ['nullable', 'string', 'max:1000'],
            'articles.*.featured_image' => ['nullable', 'string', 'max:1000'],
            'articles.*.featured_image_thumbnail' => ['nullable', 'string', 'max:1000'],
            'articles.*.featured_media_path' => ['nullable', 'string', 'max:1000'],
            'articles.*.published_at' => ['nullable', 'date'],
            'articles.*.seo_title' => ['nullable', 'string', 'max:255'],
            'articles.*.seo_description' => ['nullable', 'string', 'max:320'],
            'articles.*.seo_noindex' => ['required', 'boolean'],
            'articles.*.comments_enabled' => ['required', 'boolean'],
            'articles.*.categories' => ['present', 'array'],
            'articles.*.categories.*.name' => ['required', 'string', 'max:255'],
            'articles.*.categories.*.slug' => ['required', 'string', 'max:255'],
            'articles.*.categories.*.description' => ['nullable', 'string'],
            'articles.*.tags' => ['present', 'array'],
            'articles.*.tags.*.name' => ['required', 'string', 'max:255'],
            'articles.*.tags.*.slug' => ['required', 'string', 'max:255'],
            'articles.*.comments' => ['present', 'array'],
            'articles.*.comments.*.id' => ['required', 'string', 'max:100'],
            'articles.*.comments.*.parent_id' => ['nullable', 'string', 'max:100'],
            'articles.*.comments.*.author_name' => ['nullable', 'string', 'max:255'],
            'articles.*.comments.*.author_email' => ['nullable', 'email', 'max:255'],
            'articles.*.comments.*.content' => ['required', 'string'],
            'articles.*.comments.*.approved_at' => ['nullable', 'date'],
            'articles.*.comments.*.created_at' => ['nullable', 'date'],
            'articles.*.comments.*.updated_at' => ['nullable', 'date'],
            'media' => ['present', 'array'],
            'media.*.path' => ['required', 'string', 'max:1000'],
            'media.*.thumbnail_path' => ['required', 'string', 'max:1000'],
            'media.*.url' => ['required', 'string', 'max:2048'],
            'media.*.original_name' => ['required', 'string', 'max:255'],
            'media.*.mime_type' => ['required', 'in:image/jpeg,image/png,image/gif,image/webp'],
            'media.*.alt_text' => ['nullable', 'string', 'max:255'],
            'media.*.file' => ['required', 'string', 'max:500'],
            'media.*.thumbnail_file' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'backup' => 'The backup archive contains invalid article data: '.implode(' ', $validator->errors()->all()),
            ]);
        }

        foreach ($manifest['articles'] as $article) {
            $commentIds = array_column($article['comments'], 'id');

            if (count($commentIds) !== count(array_unique($commentIds))) {
                $this->rejectImport('backup', 'The backup contains duplicate comment identifiers.');
            }
        }

        $mediaPaths = [];

        foreach ($manifest['media'] as $media) {
            foreach ([$media['path'], $media['thumbnail_path']] as $path) {
                if (isset($mediaPaths[$path])) {
                    $this->rejectImport('backup', 'The backup contains duplicate media paths.');
                }

                $mediaPaths[$path] = true;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, int>
     */
    private function restoreCategories(array $articles): array
    {
        $categories = collect($articles)
            ->flatMap(fn (array $article): array => $article['categories'])
            ->unique('slug');
        $ids = [];

        foreach ($categories as $data) {
            $category = Category::query()->firstOrNew(['slug' => $data['slug']]);
            $category->forceFill([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ])->save();
            $ids[$data['slug']] = (int) $category->getKey();
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $articles
     * @return array<string, int>
     */
    private function restoreTags(array $articles): array
    {
        $tags = collect($articles)
            ->flatMap(fn (array $article): array => $article['tags'])
            ->unique('slug');
        $ids = [];

        foreach ($tags as $data) {
            $tag = Tag::query()->firstOrNew(['slug' => $data['slug']]);
            $tag->forceFill([
                'name' => $data['name'],
                'slug' => $data['slug'],
            ])->save();
            $ids[$data['slug']] = (int) $tag->getKey();
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $comments
     */
    private function restoreComments(Post $post, array $comments): void
    {
        $pending = collect($comments)->keyBy('id');
        $commentIds = [];

        while ($pending->isNotEmpty()) {
            $restoredCount = 0;

            foreach ($pending as $backupId => $data) {
                $parentId = $data['parent_id'];

                if ($parentId !== null && ! array_key_exists($parentId, $commentIds)) {
                    continue;
                }

                $comment = new Comment;
                $comment->forceFill([
                    'post_id' => $post->getKey(),
                    'parent_id' => $parentId === null ? null : $commentIds[$parentId],
                    'user_id' => null,
                    'author_name' => $data['author_name'],
                    'author_email' => $data['author_email'],
                    'content' => $data['content'],
                    'approved_at' => $data['approved_at'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                ])->save();

                $commentIds[$backupId] = (int) $comment->getKey();
                $pending->forget($backupId);
                $restoredCount++;
            }

            if ($restoredCount === 0) {
                $this->rejectImport('backup', 'The backup contains a comment reply with a missing or circular parent.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array{record: Media, path: string, thumbnail_path: string}>  $mediaMap
     */
    private function rewriteMediaReferences(string $content, array $manifest, array $mediaMap): string
    {
        $replacements = [];

        foreach ($manifest['media'] as $media) {
            $mapped = $mediaMap[$media['path']] ?? null;

            if ($mapped === null) {
                continue;
            }

            $replacementUrl = Storage::disk('public')->url($mapped['path']);
            $replacementThumbnailUrl = Storage::disk('public')->url($mapped['thumbnail_path']);
            $replacements[$media['url']] = $replacementUrl;
            $replacements[$manifest['source_url'].'/storage/'.$media['path']] = $replacementUrl;
            $replacements[$manifest['source_url'].'/storage/'.$media['thumbnail_path']] = $replacementThumbnailUrl;
            $replacements[$media['path']] = $mapped['path'];
            $replacements[$media['thumbnail_path']] = $mapped['thumbnail_path'];
        }

        return strtr($content, $replacements);
    }

    /**
     * @param  array<string, array{record: Media, path: string, thumbnail_path: string}>  $mediaMap
     */
    private function mappedMediaPath(?string $path, array $mediaMap): ?string
    {
        if ($path === null) {
            return null;
        }

        $media = $mediaMap[$path] ?? null;

        return $media['path'] ?? $path;
    }

    private function archiveContents(ZipArchive $archive, string $path): string
    {
        $contents = $archive->getFromName($path);

        if (! is_string($contents) || strlen($contents) > self::MAX_MEDIA_FILE_BYTES) {
            $this->rejectImport('backup', 'The backup archive contains a missing or oversized media file.');
        }

        return $contents;
    }

    private function validateArchivePath(string $path): void
    {
        if (! str_starts_with($path, 'media/')
            || str_contains($path, "\0")
            || in_array('..', explode('/', $path), true)) {
            $this->rejectImport('backup', 'The backup archive contains an unsafe media path.');
        }
    }

    private function imageExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        };
    }

    private function rejectImport(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
