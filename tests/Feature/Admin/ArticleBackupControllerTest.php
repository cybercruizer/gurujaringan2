<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
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
        $category = Category::create(['name' => 'Networking']);
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
        Storage::disk('public')->put($media->path, 'image-data');
        Storage::disk('public')->put($media->thumbnail_path, 'thumb-data');

        $post = $admin->posts()->create([
            'title' => 'Networking article',
            'content' => '<p>Backup content.</p><img src="'.url($media->url()).'" alt="Network diagram">',
            'excerpt' => 'A useful networking article.',
            'featured_media_id' => $media->id,
            'featured_image' => $media->path,
            'featured_image_thumbnail' => $media->thumbnail_path,
            'published_at' => now()->subDay(),
            'seo_title' => 'Networking article SEO title',
            'seo_description' => 'A description used in search results.',
            'seo_noindex' => true,
            'comments_enabled' => false,
        ]);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $parentComment = $post->comments()->create([
            'author_name' => 'Reader',
            'author_email' => 'reader@example.test',
            'content' => 'A reader comment.',
            'approved_at' => now()->subHour(),
        ]);
        $post->comments()->create([
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply author',
            'author_email' => 'reply@example.test',
            'content' => 'A reply.',
        ]);
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
        $this->assertNotFalse($archive->locateName('media/'));
        $backupBytes = file_get_contents($archivePath);
        $archive->close();
        $this->assertIsString($backupBytes);

        $post->update([
            'title' => 'Changed after export',
            'content' => '<p>Changed content.</p>',
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
            ->assertSessionHas('status');

        $post->refresh();
        $this->assertSame('Networking article', $post->title);
        $this->assertSame('A useful networking article.', $post->excerpt);
        $this->assertSame('Networking article SEO title', $post->seo_title);
        $this->assertTrue($post->seo_noindex);
        $this->assertFalse($post->comments_enabled);
        $this->assertSame(['Networking'], $post->categories()->pluck('name')->all());
        $this->assertSame(['Guides'], $post->tags()->pluck('name')->all());
        $this->assertSame(['A reader comment.', 'A reply.'], $post->comments()->orderBy('id')->pluck('content')->all());
        $this->assertDatabaseMissing('comments', ['content' => 'A newer comment that restore should replace.']);

        $importedMedia = Media::query()->where('alt_text', 'Network diagram')->latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($importedMedia->path);
        Storage::disk('public')->assertExists($importedMedia->thumbnail_path);
        $this->assertSame($importedMedia->path, $post->featured_image);
        $this->assertStringContainsString(Storage::disk('public')->url($importedMedia->path), $post->content);
        $this->assertDatabaseHas('posts', ['title' => 'A page that is not part of the article backup', 'type' => 'page']);
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
}
