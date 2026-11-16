<?php

namespace Tests\Feature;

use App\Models\BlogSetting;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_blog_seo_settings_and_default_metadata_is_rendered(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.settings.seo.update'), [
                'site_title' => 'Field Journal',
                'meta_description' => 'Stories and ideas from the field.',
                'default_og_image' => 'https://example.com/social-card.jpg',
                'site_description' => 'A short description for the footer.',
                'hero_eyebrow' => 'Notes from the field',
                'hero_title' => 'Ideas worth exploring.',
                'hero_description' => 'Research, lessons, and practical advice.',
                'hero_cta_label' => 'Explore articles',
                'allow_indexing' => true,
                'sitemap_enabled' => true,
            ])
            ->assertRedirect(route('admin.settings.seo.edit'));

        $this->assertDatabaseHas('blog_settings', [
            'site_title' => 'Field Journal',
            'meta_description' => 'Stories and ideas from the field.',
            'default_og_image' => 'https://example.com/social-card.jpg',
            'site_description' => 'A short description for the footer.',
            'hero_eyebrow' => 'Notes from the field',
            'hero_title' => 'Ideas worth exploring.',
            'hero_description' => 'Research, lessons, and practical advice.',
            'hero_cta_label' => 'Explore articles',
            'allow_indexing' => true,
            'sitemap_enabled' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>Field Journal</title>', false)
            ->assertSee('name="description" content="Stories and ideas from the field."', false)
            ->assertSee('property="og:image" content="https://example.com/social-card.jpg"', false)
            ->assertSee('A short description for the footer.')
            ->assertSee('Notes from the field')
            ->assertSee('Ideas worth exploring.')
            ->assertSee('Research, lessons, and practical advice.')
            ->assertSee('Explore articles');

        $this->get(route('admin.settings.seo.edit'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('name="site_description"', false)
            ->assertSee('name="hero_title"', false);
    }

    public function test_public_site_text_falls_back_to_defaults_and_escapes_saved_copy(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Thoughtful stories, ideas, and guides from our community.')
            ->assertSee('Publishing new articles every week')
            ->assertSee('Clear thinking about technology, from the people building it.');

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.settings.seo.update'), [
                'site_description' => '<script>alert("xss")</script>',
                'hero_eyebrow' => 'Weekly <script>alert("xss")</script>',
                'hero_title' => 'Headline <script>alert("xss")</script>',
                'hero_description' => 'Description <script>alert("xss")</script>',
                'hero_cta_label' => 'Button <script>alert("xss")</script>',
                'allow_indexing' => true,
                'sitemap_enabled' => true,
            ])
            ->assertRedirect(route('admin.settings.seo.edit'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_blog_settings_reject_hero_text_that_exceeds_its_limit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.settings.seo.edit'))
            ->put(route('admin.settings.seo.update'), [
                'hero_title' => str_repeat('a', 256),
                'allow_indexing' => true,
                'sitemap_enabled' => true,
            ])
            ->assertRedirect(route('admin.settings.seo.edit'))
            ->assertSessionHasErrors('hero_title');

        $this->assertDatabaseCount('blog_settings', 0);
    }

    public function test_only_admins_can_manage_blog_seo_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.seo.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.settings.seo.update'), [
                'site_title' => 'Unauthorized',
                'allow_indexing' => true,
                'sitemap_enabled' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('blog_settings', 0);
    }

    public function test_post_seo_metadata_is_saved_and_rendered(): void
    {
        $author = User::factory()->create();
        BlogSetting::query()->create([
            'site_title' => 'Journal',
            'allow_indexing' => true,
            'sitemap_enabled' => true,
        ]);

        $this->actingAs($author)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('SEO settings')
            ->assertSee('data-seo-counter="post-title"', false)
            ->assertSee('name="seo_title"', false)
            ->assertSee('data-seo-counter="title"', false)
            ->assertSee('data-seo-counter="description"', false)
            ->assertSee('data-seo-counter="excerpt"', false);

        $this->post(route('posts.store'), [
            'title' => 'Search-friendly story',
            'content' => '<p>Article body.</p>',
            'published_at' => now()->subDay()->toDateTimeString(),
            'seo_title' => 'Custom search title',
            'seo_description' => 'A custom description for search and social previews.',
            'seo_noindex' => false,
        ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'Search-friendly story')->firstOrFail();

        $this->assertSame('Custom search title', $post->seo_title);
        $this->assertSame('A custom description for search and social previews.', $post->seo_description);

        $this->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('value="Custom search title"', false)
            ->assertSee('A custom description for search and social previews.');

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<title>Custom search title | Journal</title>', false)
            ->assertSee('name="description" content="A custom description for search and social previews."', false)
            ->assertSee('rel="canonical" href="'.$post->url().'"', false)
            ->assertSee('name="robots" content="index,follow"', false);
    }

    public function test_sitemap_index_separates_published_indexable_articles_and_pages(): void
    {
        $author = User::factory()->create();
        $article = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Published content.',
            'published_at' => now()->subDay(),
        ]);
        $page = $author->posts()->create([
            'title' => 'Public page',
            'type' => 'page',
            'content' => 'Published page content.',
            'published_at' => now()->subDay(),
        ]);
        $noindex = $author->posts()->create([
            'title' => 'Hidden article',
            'content' => 'Not for search.',
            'published_at' => now()->subDay(),
            'seo_noindex' => true,
        ]);
        $draft = $author->posts()->create([
            'title' => 'Draft article',
            'content' => 'Not published.',
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('https://', false)
            ->assertDontSee('<loc>http://', false)
            ->assertSee(str_replace('http://', 'https://', route('sitemap.articles')), false)
            ->assertSee(str_replace('http://', 'https://', route('sitemap.pages')), false)
            ->assertDontSee($article->url(), false)
            ->assertDontSee($page->url(), false);

        $this->get(route('sitemap.articles'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', $article->url()), false)
            ->assertSee('https://', false)
            ->assertDontSee('<loc>http://', false)
            ->assertDontSee(str_replace('http://', 'https://', $page->url()), false)
            ->assertDontSee(str_replace('http://', 'https://', $noindex->url()), false)
            ->assertDontSee(str_replace('http://', 'https://', $draft->url()), false);

        $this->get(route('sitemap.pages'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', $page->url()), false)
            ->assertSee('https://', false)
            ->assertDontSee('<loc>http://', false)
            ->assertDontSee(str_replace('http://', 'https://', $article->url()), false)
            ->assertDontSee(str_replace('http://', 'https://', $noindex->url()), false)
            ->assertDontSee(str_replace('http://', 'https://', $draft->url()), false);

        $this->get(route('posts.show', $noindex))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        BlogSetting::query()->create([
            'allow_indexing' => true,
            'sitemap_enabled' => false,
        ]);

        $this->get(route('sitemap'))->assertNotFound();
        $this->get(route('sitemap.articles'))->assertNotFound();
        $this->get(route('sitemap.pages'))->assertNotFound();
        $this->get(route('sitemap.categories'))->assertNotFound();
        $this->get(route('sitemap.tags'))->assertNotFound();
        $this->get(route('sitemap.media'))->assertNotFound();

        BlogSetting::query()->firstOrFail()->update([
            'allow_indexing' => false,
            'sitemap_enabled' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);
        $this->get(route('sitemap'))->assertNotFound();
    }

    public function test_sitemap_index_lists_public_taxonomy_archives_and_media_files(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();
        $category = Category::create(['name' => 'Networking']);
        $tag = Tag::create(['name' => 'Guides']);
        $article = $author->posts()->create([
            'title' => 'Public networking guide',
            'content' => 'Public content.',
            'published_at' => now()->subDay(),
        ]);
        $article->categories()->attach($category);
        $article->tags()->attach($tag);

        $draftCategory = Category::create(['name' => 'Draft only']);
        $draftTag = Tag::create(['name' => 'Draft tag']);
        $draft = $author->posts()->create([
            'title' => 'Draft networking guide',
            'content' => 'Private content.',
        ]);
        $draft->categories()->attach($draftCategory);
        $draft->tags()->attach($draftTag);

        $media = Media::query()->create([
            'user_id' => $author->id,
            'path' => 'media/networking-guide.webp',
            'thumbnail_path' => 'media/thumbnails/networking-guide.webp',
            'original_name' => 'networking-guide.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', route('sitemap.categories')), false)
            ->assertSee(str_replace('http://', 'https://', route('sitemap.tags')), false)
            ->assertSee(str_replace('http://', 'https://', route('sitemap.media')), false);

        $this->get(route('sitemap.categories'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', route('categories.show', $category->slug)), false)
            ->assertDontSee(route('categories.show', $draftCategory->slug), false)
            ->assertDontSee('<loc>http://', false);

        $this->get(route('sitemap.tags'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', route('tags.show', $tag->slug)), false)
            ->assertDontSee(route('tags.show', $draftTag->slug), false)
            ->assertDontSee('<loc>http://', false);

        $this->get(route('sitemap.media'))
            ->assertOk()
            ->assertSee(str_replace('http://', 'https://', url($media->url())), false)
            ->assertDontSee('<loc>http://', false);
    }
}
