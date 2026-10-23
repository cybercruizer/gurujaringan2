<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_archive_shows_its_published_articles_and_excludes_drafts(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Field notes']);
        $publishedPost = $author->posts()->create([
            'title' => 'Published field report',
            'content' => 'A public report.',
            'published_at' => now()->subDay(),
        ]);
        $draft = $author->posts()->create([
            'title' => 'Draft field report',
            'content' => 'A private report.',
        ]);
        $publishedPost->categories()->attach($category);
        $draft->categories()->attach($category);

        $this->get(route('categories.show', $category->slug))
            ->assertOk()
            ->assertSee('Field notes')
            ->assertSee('Published field report')
            ->assertDontSee('Draft field report');
    }

    public function test_tag_archive_shows_posts_assigned_to_that_tag(): void
    {
        $author = User::factory()->create();
        $tag = Tag::create(['name' => 'Garden']);
        $post = $author->posts()->create([
            'title' => 'Garden growing guide',
            'content' => 'Growing tips.',
            'published_at' => now()->subDay(),
        ]);
        $post->tags()->attach($tag);

        $this->get(route('tags.show', $tag->slug))
            ->assertOk()
            ->assertSee('Garden')
            ->assertSee('Garden growing guide');
    }

    public function test_post_category_and_tag_labels_link_to_their_public_archives(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Field notes']);
        $tag = Tag::create(['name' => 'Outdoor']);
        $post = $author->posts()->create([
            'title' => 'Field report',
            'content' => 'A public report.',
            'published_at' => now()->subDay(),
        ]);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('href="'.route('categories.show', $category->slug).'"', false)
            ->assertSee('href="'.route('tags.show', $tag->slug).'"', false);
    }

    public function test_footer_tag_cloud_ranks_tags_by_published_article_count(): void
    {
        $author = User::factory()->create();
        $popularTag = Tag::create(['name' => 'Popular topic']);
        $otherTag = Tag::create(['name' => 'Another topic']);
        $pageOnlyTag = Tag::create(['name' => 'Page only topic']);
        $draftOnlyTag = Tag::create(['name' => 'Draft only topic']);

        foreach (['First public article', 'Second public article'] as $title) {
            $post = $author->posts()->create([
                'title' => $title,
                'content' => 'Public content.',
                'published_at' => now()->subDay(),
            ]);
            $post->tags()->attach($popularTag);
        }

        $otherPost = $author->posts()->create([
            'title' => 'Other public article',
            'content' => 'Public content.',
            'published_at' => now()->subDay(),
        ]);
        $otherPost->tags()->attach($otherTag);

        $page = $author->posts()->create([
            'title' => 'Public page',
            'type' => 'page',
            'content' => 'Page content.',
            'published_at' => now()->subDay(),
        ]);
        $page->tags()->attach($pageOnlyTag);

        $draft = $author->posts()->create([
            'title' => 'Draft article',
            'content' => 'Draft content.',
        ]);
        $draft->tags()->attach($draftOnlyTag);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Topik populer')
            ->assertSeeInOrder([
                route('tags.show', $popularTag->slug),
                route('tags.show', $otherTag->slug),
            ], false)
            ->assertSee('Popular topic, 2 artikel', false)
            ->assertSee('Another topic, 1 artikel', false)
            ->assertDontSee(route('tags.show', $pageOnlyTag->slug), false)
            ->assertDontSee(route('tags.show', $draftOnlyTag->slug), false);
    }
}
