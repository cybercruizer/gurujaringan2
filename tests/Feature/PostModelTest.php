<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_generates_a_slug_and_has_user_and_category_relationships(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'A Thoughtful Story',
            'content' => 'The story content.',
        ]);
        $category = Category::create([
            'name' => 'Ideas',
            'slug' => 'ideas',
        ]);

        $post->categories()->attach($category);

        $this->assertSame('a-thoughtful-story', $post->slug);
        $this->assertTrue($post->user->is($user));
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($user->posts->contains($post));
        $this->assertTrue($category->posts->contains($post));
    }

    public function test_published_scope_excludes_drafts_and_future_posts(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $publishedPost = $user->posts()->create([
            'title' => 'Published',
            'content' => 'Published content.',
            'published_at' => now()->subMinute(),
        ]);
        $user->posts()->create([
            'title' => 'Draft',
            'content' => 'Draft content.',
        ]);
        $user->posts()->create([
            'title' => 'Scheduled',
            'content' => 'Scheduled content.',
            'published_at' => now()->addMinute(),
        ]);

        $publishedPosts = Post::published()->get();

        $this->assertSame([$publishedPost->id], $publishedPosts->modelKeys());
    }

    public function test_published_at_is_cast_to_a_date_and_has_a_formatted_accessor(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Dated post',
            'content' => 'Post content.',
            'published_at' => Carbon::parse('2026-10-22 15:30:00'),
        ]);

        $this->assertInstanceOf(Carbon::class, $post->published_at);
        $this->assertSame('2026-10-22', $post->formatted_published_at);
    }
}
