<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_navigation_initializes_search_with_the_registered_endpoint(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('x-data="siteSearch(', false)
            ->assertSee('\/search', false);
    }

    public function test_search_returns_published_articles_and_pages_matching_the_query(): void
    {
        $author = User::factory()->create();
        $article = $author->posts()->create([
            'title' => 'A guide to alpine plants',
            'excerpt' => 'Practical notes',
            'content' => 'How to care for alpine plants.',
            'published_at' => now()->subDay(),
        ]);
        $page = $author->posts()->create([
            'title' => 'About the alpine project',
            'content' => 'Learn about our work.',
            'type' => 'page',
            'published_at' => now()->subDay(),
        ]);
        $author->posts()->create([
            'title' => 'Draft alpine article',
            'content' => 'Draft only.',
        ]);
        $author->posts()->create([
            'title' => 'Scheduled alpine article',
            'content' => 'Not published yet.',
            'published_at' => now()->addDay(),
        ]);

        $this->getJson(route('search', ['q' => 'alpine']))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.title', 'About the alpine project')
            ->assertJsonPath('results.0.type', 'Page')
            ->assertJsonPath('results.0.url', route('pages.show', $page->slug))
            ->assertJsonPath('results.1.title', 'A guide to alpine plants')
            ->assertJsonPath('results.1.url', route('posts.show', $article->slug));
    }

    public function test_search_rejects_queries_shorter_than_three_characters(): void
    {
        $this->getJson(route('search', ['q' => 'ab']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_matches_post_content_and_returns_plain_text_snippets(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'A content example',
            'content' => '<p>Find <strong>noteworthy</strong> details.</p>',
            'published_at' => now()->subMinute(),
        ]);

        $this->getJson(route('search', ['q' => 'noteworthy']))
            ->assertOk()
            ->assertJsonPath('results.0.title', $post->title)
            ->assertJsonPath('results.0.excerpt', 'Find noteworthy details.');
    }
}
