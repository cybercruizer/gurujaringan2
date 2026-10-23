<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts(): void
    {
        $user = User::factory()->create();
        $publishedPost = $user->posts()->create([
            'title' => 'Published article',
            'content' => 'Public content.',
            'published_at' => now()->subMinute(),
        ]);
        $user->posts()->create([
            'title' => 'Draft article',
            'content' => 'Private draft.',
        ]);
        $user->posts()->create([
            'title' => 'Scheduled article',
            'content' => 'Future content.',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee($publishedPost->title)
            ->assertDontSee('Draft article')
            ->assertDontSee('Scheduled article');
    }

    public function test_published_post_sanitizes_legacy_html_before_rendering(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Public article',
            'content' => '<script>alert("unsafe")</script><p onclick="alert(1)">Safe content.</p>',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<p>Safe content.</p>', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertDontSee('onclick="alert(1)"', false);
    }

    public function test_code_samples_render_with_language_and_reject_unapproved_classes(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Code sample article',
            'content' => '<pre class="language-javascript bg-red-500" onclick="alert(1)"><code>&lt;script&gt;alert("unsafe")&lt;/script&gt;</code></pre>',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<pre class="language-javascript"><code>&lt;script&gt;alert("unsafe")&lt;/script&gt;</code></pre>', false)
            ->assertDontSee('bg-red-500', false)
            ->assertDontSee('onclick="alert(1)"', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_post_page_generates_a_table_of_contents_from_content_headings(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'A post with sections',
            'type' => 'page',
            'content' => '<h2>First section</h2><p>Section content.</p><h3>Details &amp; notes</h3><h2>First section</h2>',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('pages.show', $post))
            ->assertOk()
            ->assertSee('Page')
            ->assertSee('aria-label="Table of contents"', false)
            ->assertSee('href="#section-first-section"', false)
            ->assertSee('id="section-first-section"', false)
            ->assertSee('href="#section-details-notes"', false)
            ->assertSee('id="section-first-section-2"', false);
    }

    public function test_pages_use_page_urls_and_articles_keep_post_urls(): void
    {
        $author = User::factory()->create();
        $page = $author->posts()->create([
            'title' => 'About us',
            'type' => 'page',
            'content' => 'About our team.',
            'published_at' => now()->subMinute(),
        ]);
        $article = $author->posts()->create([
            'title' => 'Latest news',
            'content' => 'News content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('pages.show', $page))->assertOk();
        $this->get(route('posts.show', $article))->assertOk();
        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee(route('pages.show', $page), false)
            ->assertSee(route('posts.show', $article), false);
        $this->get(route('posts.show', $page))
            ->assertMovedPermanently()
            ->assertRedirect(route('pages.show', $page));
        $this->get(route('pages.show', $article))
            ->assertMovedPermanently()
            ->assertRedirect(route('posts.show', $article));
    }

    public function test_post_page_omits_the_table_of_contents_without_content_headings(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'A post without sections',
            'content' => '<p>Just a paragraph.</p>',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('aria-label="Table of contents"', false);
    }

    public function test_only_the_author_can_view_an_unpublished_draft(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $draft = $author->posts()->create([
            'title' => 'Private draft',
            'content' => 'Draft content.',
        ]);

        $this->get(route('posts.show', $draft))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('posts.show', $draft))
            ->assertOk()
            ->assertSee('Private draft');

        $this->actingAs($otherUser)
            ->get(route('posts.show', $draft))
            ->assertForbidden();
    }

    public function test_index_paginates_published_posts_nine_at_a_time(): void
    {
        $author = User::factory()->create();

        foreach (range(1, 10) as $number) {
            $author->posts()->create([
                'title' => "Article {$number}",
                'content' => 'Article content.',
                'published_at' => now()->subMinutes($number),
            ]);
        }

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertViewHas('posts', function ($posts): bool {
                return $posts->count() === 9 && $posts->perPage() === 9 && $posts->lastPage() === 2;
            });
    }

    public function test_guest_cannot_open_the_post_creation_form(): void
    {
        $this->get(route('posts.create'))
            ->assertRedirect(route('login'));
    }

    public function test_create_form_submits_a_published_post_by_default(): void
    {
        $this->travelTo('2026-10-22 14:00:00');
        $author = User::factory()->create();

        $this->actingAs($author)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('name="published_at"', false)
            ->assertSee('value="2026-10-22T14:00"', false)
            ->assertDontSee('<textarea id="content" name="content" rows="14" required', false);

        $response = $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Published from the editor',
            'type' => 'page',
            'content' => 'Post content.',
            'published_at' => '2026-10-22T14:00',
        ]);

        $post = Post::query()->where('slug', 'published-from-the-editor')->firstOrFail();
        $response->assertRedirect(route('pages.show', $post));
        $this->assertNotNull($post->published_at);
        $this->assertSame('page', $post->type);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('Page');
    }

    public function test_author_can_create_a_post_with_categories_and_an_image(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $category = Category::create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
        $tag = Tag::create(['name' => 'Tutorials']);

        $this->actingAs($author)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('Write a post');

        $response = $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'A New Article',
            'content' => '<h2>Article heading</h2><p onclick="alert(1)">The article body.<script>alert(2)</script></p>',
            'excerpt' => 'A short summary.',
            'published_at' => now()->toDateTimeLocalString(),
            'categories' => [$category->id],
            'tags' => [$tag->id],
            'featured_image' => UploadedFile::fake()->image('cover.jpg', 2400, 1200),
        ]);

        $post = Post::query()->where('slug', 'a-new-article')->firstOrFail();

        $response->assertRedirect(route('posts.show', $post));
        $this->actingAs($author)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee($tag->name);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'user_id' => $author->id,
            'title' => 'A New Article',
            'excerpt' => 'A short summary.',
            'content' => '<h2>Article heading</h2><p>The article body.</p>',
        ]);
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
        Storage::disk('public')->assertExists($post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image_thumbnail);
        $this->assertStringEndsWith('.webp', $post->featured_image);
        $dimensions = getimagesize(Storage::disk('public')->path($post->featured_image));
        $this->assertSame(2000, $dimensions[0]);
        $this->assertSame(1000, $dimensions[1]);
        $thumbnailDimensions = getimagesize(Storage::disk('public')->path($post->featured_image_thumbnail));
        $this->assertSame(640, $thumbnailDimensions[0]);
        $this->assertSame(320, $thumbnailDimensions[1]);
    }

    public function test_author_can_reuse_a_library_image_as_a_featured_image(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $media = Media::query()->create([
            'user_id' => $author->id,
            'path' => 'images/library/shared.webp',
            'thumbnail_path' => 'images/library/thumbnails/shared.webp',
            'original_name' => 'shared-image.webp',
            'mime_type' => 'image/webp',
            'size' => 128,
            'alt_text' => 'A shared image',
        ]);
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Post using library image',
            'content' => 'Post content.',
            'featured_media_id' => $media->id,
            'published_at' => now()->subMinute(),
        ])->assertRedirect();

        $post = Post::query()->where('slug', 'post-using-library-image')->firstOrFail();
        $this->assertSame($media->id, $post->featured_media_id);
        $this->assertSame($media->path, $post->featured_image);
        $this->assertSame($media->thumbnail_path, $post->featured_image_thumbnail);
        $this->assertSame($media->id, $post->featuredMedia->id);
        Storage::disk('public')->assertExists($media->path);
        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('alt="A shared image"', false);
    }

    public function test_author_can_upload_a_gif_featured_image_without_losing_the_original(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $gif = $this->createGifContents();

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Animated cover',
            'content' => 'Post content.',
            'featured_image' => UploadedFile::fake()->createWithContent('cover.gif', $gif),
        ])->assertRedirect();

        $post = Post::query()->where('slug', 'animated-cover')->firstOrFail();

        $this->assertStringEndsWith('.gif', $post->featured_image);
        $this->assertSame($gif, Storage::disk('public')->get($post->featured_image));
        Storage::disk('public')->assertExists($post->featured_image_thumbnail);
    }

    public function test_post_creation_removes_unsafe_link_and_image_schemes(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Unsafe link test',
            'content' => '<p><a href="javascript:alert(1)">Link</a><img src="javascript:alert(1)" onerror="alert(1)"></p>',
        ])->assertRedirect();

        $post = Post::query()->where('slug', 'unsafe-link-test')->firstOrFail();

        $this->assertStringContainsString('Link', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);
        $this->assertStringNotContainsString('onerror', $post->content);
    }

    public function test_post_creation_rejects_unknown_categories(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->from(route('posts.create'))
            ->post(route('posts.store'), [
                'title' => 'Invalid category',
                'content' => 'Post content.',
                'categories' => [999],
            ])
            ->assertRedirect(route('posts.create'))
            ->assertSessionHasErrors('categories.0');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_creation_rejects_unknown_tags(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->from(route('posts.create'))
            ->post(route('posts.store'), [
                'title' => 'Invalid tag',
                'content' => 'Post content.',
                'tags' => [999],
            ])
            ->assertRedirect(route('posts.create'))
            ->assertSessionHasErrors('tags.0');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_creation_requires_a_title_and_content(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->from(route('posts.create'))
            ->post(route('posts.store'), [])
            ->assertRedirect(route('posts.create'))
            ->assertSessionHasErrors(['title', 'content']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_editor_form_exposes_the_tinymce_upload_endpoint(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('data-rich-text-editor', false)
            ->assertSee('data-upload-url="'.route('posts.content-images.store').'"', false);
    }

    public function test_post_editor_keeps_existing_image_urls_available_when_editing(): void
    {
        $author = User::factory()->create();
        $imageHtml = '<p><img src="/storage/images/content/'.$author->id.'/inline.webp" alt="Inline image"></p>';
        $post = $author->posts()->create([
            'title' => 'Post with inline image',
            'content' => $imageHtml,
        ]);

        $this->actingAs($author)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee(e($imageHtml), false)
            ->assertSee('data-rich-text-editor', false);
    }

    public function test_author_can_update_their_post_without_changing_ownership(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::create([
            'name' => 'Writing',
            'slug' => 'writing',
        ]);
        $tag = Tag::create(['name' => 'Guides']);
        $post = $author->posts()->create([
            'title' => 'Original title',
            'content' => 'Original content.',
        ]);

        $response = $this->actingAs($author)->put(route('posts.update', $post), [
            'title' => 'Updated title',
            'content' => '<p onmouseover="alert(1)">Updated content.</p><script>alert(2)</script>',
            'categories' => [$category->id],
            'tags' => [$tag->id],
            'user_id' => $otherUser->id,
        ]);

        $post->refresh();
        $response->assertRedirect(route('posts.show', $post));
        $this->assertSame($author->id, $post->user_id);
        $this->assertSame('updated-title', $post->slug);
        $this->assertSame('<p>Updated content.</p>', $post->content);
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
    }

    public function test_replacing_a_featured_image_deletes_the_previous_image_and_thumbnail(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Image replacement',
            'content' => 'Post content.',
            'featured_image' => 'images/posts/old.webp',
            'featured_image_thumbnail' => 'images/posts/thumbnails/old.webp',
        ]);
        Storage::disk('public')->put($post->featured_image, 'old image');
        Storage::disk('public')->put($post->featured_image_thumbnail, 'old thumbnail');

        $this->actingAs($author)->put(route('posts.update', $post), [
            'title' => $post->title,
            'content' => $post->content,
            'featured_image' => UploadedFile::fake()->image('replacement.png'),
        ])->assertRedirect();

        $post->refresh();
        Storage::disk('public')->assertMissing('images/posts/old.webp');
        Storage::disk('public')->assertMissing('images/posts/thumbnails/old.webp');
        Storage::disk('public')->assertExists($post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image_thumbnail);
    }

    public function test_author_can_remove_a_featured_image_without_uploading_a_replacement(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Remove image',
            'content' => 'Post content.',
            'featured_image' => 'images/posts/current.webp',
            'featured_image_thumbnail' => 'images/posts/thumbnails/current.webp',
        ]);
        Storage::disk('public')->put($post->featured_image, 'image');
        Storage::disk('public')->put($post->featured_image_thumbnail, 'thumbnail');

        $this->actingAs($author)->put(route('posts.update', $post), [
            'title' => $post->title,
            'content' => $post->content,
            'remove_featured_image' => '1',
        ])->assertRedirect();

        $post->refresh();
        $this->assertNull($post->featured_image);
        $this->assertNull($post->featured_image_thumbnail);
        Storage::disk('public')->assertMissing('images/posts/current.webp');
        Storage::disk('public')->assertMissing('images/posts/thumbnails/current.webp');
    }

    public function test_non_author_cannot_edit_update_or_delete_a_post(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Author article',
            'content' => 'Author content.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('posts.edit', $post))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('posts.update', $post), [
                'title' => 'Taken over',
                'content' => 'Unauthorized update.',
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();

        $this->assertModelExists($post);
        $this->assertSame('Author article', $post->fresh()->title);
    }

    public function test_author_can_soft_delete_their_post(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'To be removed',
            'content' => 'Post content.',
            'featured_image' => 'images/posts/deleted.webp',
            'featured_image_thumbnail' => 'images/posts/thumbnails/deleted.webp',
        ]);
        Storage::disk('public')->put($post->featured_image, 'image');
        Storage::disk('public')->put($post->featured_image_thumbnail, 'thumbnail');

        $this->actingAs($author)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertSoftDeleted($post);
        Storage::disk('public')->assertMissing('images/posts/deleted.webp');
        Storage::disk('public')->assertMissing('images/posts/thumbnails/deleted.webp');
    }

    public function test_soft_deleting_a_post_keeps_its_reusable_media(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $media = Media::query()->create([
            'user_id' => $author->id,
            'path' => 'images/library/reusable.webp',
            'thumbnail_path' => 'images/library/thumbnails/reusable.webp',
            'original_name' => 'reusable.webp',
            'mime_type' => 'image/webp',
            'size' => 128,
        ]);
        $post = $author->posts()->create([
            'title' => 'Post to remove',
            'content' => 'Post content.',
            'featured_media_id' => $media->id,
            'featured_image' => $media->path,
            'featured_image_thumbnail' => $media->thumbnail_path,
        ]);
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');

        $this->actingAs($author)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertSoftDeleted($post);
        $this->assertModelExists($media);
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
    }

    private function createGifContents(): string
    {
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagegif($image);
        imagedestroy($image);
        $contents = ob_get_clean();

        $this->assertIsString($contents);

        return $contents;
    }
}
