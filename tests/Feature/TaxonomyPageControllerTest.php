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
}
