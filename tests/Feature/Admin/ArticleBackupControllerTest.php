<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ArticleBackupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_and_restore_articles_with_taxonomy_comments_and_media(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get(route('admin.article-backups.index'))
            ->assertOk()
            ->assertSee('Backup artikel')
            ->assertSee('Unduh backup ZIP');

        $category = Category::create([
            'name' => 'Networking',
            'description' => 'Networks and infrastructure.',
        ]);
        $tag = Tag::create(['name' => 'Guides']);
        $media = Media::query()->create([
            'user_id' => $admin->id,
            'path' => 'images/library/networking.webp',
            'thumbnail_path' => 'images/library/thumbnails/networking.webp',
            'original_name' => 'networking.webp',
            'mime_type' => 'image/webp',
            'size' => 7,
            'alt_text' => 'Network diagram',
        ]);
        $imageContents = $this->createWebpContents();
        Storage::disk('public')->put($media->path, $imageContents);
        Storage::disk('public')->put($media->thumbnail_path, $imageContents);

        $publishedAt = now()->subDay();
        $post = $admin->posts()->create([
            'title' => 'Networking article',
            'content' => '<p>Backup content.</p><img src="'.url($media->url()).'" alt="Network diagram">',
            'excerpt' => 'A useful networking article.',
            'featured_media_id' => $media->id,
            'featured_image' => $media->path,
            'featured_image_thumbnail' => $media->thumbnail_path,
            'published_at' => $publishedAt,
            'seo_title' => 'Networking article SEO title',
            'seo_description' => 'A description used in search results.',
            'seo_noindex' => true,
            'comments_enabled' => false,
        ]);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);
        $storedPublishedAt = $post->published_at->toISOString();

        $parentComment = $post->comments()->create([
            'author_name' => 'Reader',
            'author_email' => 'reader@example.test',
            'content' => 'A reader comment.',
        ]);
        $parentComment->approved_at = now()->subHour();
        $parentComment->save();
        $reply = $post->comments()->make([
            'author_name' => 'Reply author',
            'author_email' => 'reply@example.test',
            'content' => 'A reply.',
        ]);
        $reply->parent_id = $parentComment->id;
        $reply->save();
        $admin->posts()->create([
            'title' => 'A page that is not part of the article backup',
            'type' => 'page',
            'content' => 'Page content.',
        ]);

        $download = $this->actingAs($admin)
            ->get(route('admin.article-backups.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $archivePath = $download->baseResponse->getFile()->getPathname();
        $this->assertFileExists($archivePath);

        $archive = new ZipArchive;
        $this->assertSame(true, $archive->open($archivePath));
        $this->assertNotFalse($archive->locateName('backup.json'));
        $this->assertNotFalse($archive->locateName("media/{$media->id}/networking.webp"));
        $backupBytes = file_get_contents($archivePath);
        $archive->close();
        $this->assertIsString($backupBytes);

        $post->update([
            'content' => '<p>Changed content.</p>',
            'excerpt' => 'Changed excerpt.',
        ]);
        $post->categories()->detach();
        $post->tags()->detach();
        $post->comments()->delete();
        $post->comments()->create([
            'author_name' => 'New reader',
            'author_email' => 'new@example.test',
            'content' => 'A newer comment that restore should replace.',
        ]);
        Storage::disk('public')->delete([$media->path, $media->thumbnail_path]);

        $this->post(route('admin.article-backups.import'), [
            'backup' => UploadedFile::fake()->createWithContent('articles.zip', $backupBytes),
        ])->assertRedirect()
            ->assertSessionHas('status', '0 artikel ditambahkan dan 1 artikel diperbarui.');

        $post->refresh();
        $this->assertSame('Networking article', $post->title);
        $this->assertStringContainsString('Backup content.', $post->content);
        $this->assertSame('A useful networking article.', $post->excerpt);
        $this->assertSame('Networking article SEO title', $post->seo_title);
        $this->assertSame('A description used in search results.', $post->seo_description);
        $this->assertTrue($post->seo_noindex);
        $this->assertFalse($post->comments_enabled);
        $this->assertSame($storedPublishedAt, $post->published_at->toISOString());
        $this->assertSame(['Networking'], $post->categories()->pluck('name')->all());
        $this->assertSame('Networks and infrastructure.', $post->categories()->first()->description);
        $this->assertSame(['Guides'], $post->tags()->pluck('name')->all());
        $restoredComments = $post->comments()->orderBy('id')->get();
        $this->assertSame(['A reader comment.', 'A reply.'], $restoredComments->pluck('content')->all());
        $this->assertNull($restoredComments[0]->parent_id);
        $this->assertSame($restoredComments[0]->id, $restoredComments[1]->parent_id);
        $this->assertNotNull($restoredComments[0]->approved_at);
        $this->assertNull($restoredComments[1]->approved_at);
        $this->assertSame('reader@example.test', $restoredComments[0]->author_email);
        $this->assertDatabaseMissing('comments', ['content' => 'A newer comment that restore should replace.']);

        $importedMedia = Media::query()->where('alt_text', 'Network diagram')->latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($importedMedia->path);
        Storage::disk('public')->assertExists($importedMedia->thumbnail_path);
        $this->assertSame($importedMedia->path, $post->featured_image);
        $this->assertStringContainsString(Storage::disk('public')->url($importedMedia->path), $post->content);
        $this->assertDatabaseHas('posts', ['title' => 'A page that is not part of the article backup', 'type' => 'page']);

        $mediaCount = Media::query()->count();
        $this->post(route('admin.article-backups.import'), [
            'backup' => UploadedFile::fake()->createWithContent('articles.zip', $backupBytes),
        ])->assertRedirect();
        $this->assertDatabaseCount('posts', 2);
        $this->assertSame(2, $post->comments()->count());
        $this->assertSame($mediaCount, Media::query()->count());
        unlink($archivePath);
    }

    public function test_non_admin_cannot_export_or_import_article_backups(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.article-backups.export'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.article-backups.import'), [
                'backup' => UploadedFile::fake()->create('articles.zip', 1, 'application/zip'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_invalid_archive_is_rejected_without_importing_articles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = $admin->posts()->create([
            'title' => 'Existing article',
            'content' => 'Must remain unchanged.',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.article-backups.index'))
            ->post(route('admin.article-backups.import'), [
                'backup' => UploadedFile::fake()->createWithContent('invalid.zip', 'not a zip archive'),
            ])
            ->assertRedirect(route('admin.article-backups.index'))
            ->assertSessionHasErrors('backup');

        $this->assertSame('Existing article', $post->fresh()->title);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_admin_import_creates_an_article_when_no_slug_matches(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $admin->posts()->create([
            'title' => 'New backup article',
            'content' => 'Article content.',
            'published_at' => now()->subDay(),
        ]);

        $download = $this->actingAs($admin)
            ->get(route('admin.article-backups.export'))
            ->assertOk();
        $archivePath = $download->baseResponse->getFile()->getPathname();
        $backupBytes = file_get_contents($archivePath);
        $this->assertIsString($backupBytes);

        Post::query()->forceDelete();

        $this->post(route('admin.article-backups.import'), [
            'backup' => UploadedFile::fake()->createWithContent('articles.zip', $backupBytes),
        ])->assertRedirect()
            ->assertSessionHas('status', '1 artikel ditambahkan dan 0 artikel diperbarui.');
        unlink($archivePath);

        $this->assertDatabaseHas('posts', [
            'title' => 'New backup article',
            'slug' => 'new-backup-article',
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseCount('posts', 1);
    }

    private function createWebpContents(): string
    {
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagewebp($image);
        $contents = ob_get_clean();

        $this->assertIsString($contents);

        return $contents;
    }
}
