<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_search_all_articles_and_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create(['name' => 'Article author']);
        $post = $author->posts()->create([
            'title' => 'Searchable article',
            'content' => 'Published content.',
            'published_at' => now()->subDay(),
        ]);
        $author->posts()->create([
            'title' => 'Private draft',
            'content' => 'Draft content.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.posts.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee('Searchable article')
            ->assertSee('Article author')
            ->assertSee('Published')
            ->assertDontSee('Private draft')
            ->assertSee(route('posts.edit', $post), false)
            ->assertSee(route('admin.posts.unpublish', $post), false)
            ->assertSee(route('posts.destroy', $post), false);
    }

    public function test_admin_can_unpublish_another_users_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Published article',
            'content' => 'Article content.',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.posts.unpublish', $post))
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('status', 'Article unpublished.');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'published_at' => null,
        ]);
    }

    public function test_admin_can_edit_and_delete_another_users_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Original title',
            'content' => 'Original content.',
        ]);

        $this->actingAs($admin)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('Original title');

        $this->put(route('posts.update', $post), [
            'title' => 'Edited by admin',
            'content' => 'Updated content.',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame($author->id, $post->user_id);
        $this->assertSame('Edited by admin', $post->title);

        $this->delete(route('posts.destroy', $post))->assertRedirect(route('posts.index'));

        $this->assertSoftDeleted($post);
    }

    public function test_non_admin_cannot_access_admin_article_management(): void
    {
        $user = User::factory()->create();
        $post = User::factory()->create()->posts()->create([
            'title' => 'Protected article',
            'content' => 'Article content.',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.posts.index'))
            ->assertForbidden();

        $this->patch(route('admin.posts.unpublish', $post))
            ->assertForbidden();

        $this->assertNotNull($post->fresh()->published_at);
    }
}
