<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_categories_with_generated_slugs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Categories');

        $this->post(route('admin.categories.store'), [
            'name' => 'Field Notes',
            'description' => 'Stories from the field.',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'field-notes')->firstOrFail();
        $this->assertSame('Stories from the field.', $category->description);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Travel Notes',
            'description' => 'Travel writing.',
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('travel-notes', $category->slug);
        $this->assertSame('Travel writing.', $category->description);
    }

    public function test_admin_can_create_and_update_tags_with_generated_slugs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->post(route('admin.tags.store'), ['name' => 'How To'])
            ->assertRedirect(route('admin.tags.index'));

        $tag = Tag::query()->where('slug', 'how-to')->firstOrFail();

        $this->put(route('admin.tags.update', $tag), ['name' => 'Field Guide'])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertSame('field-guide', $tag->fresh()->slug);
    }

    public function test_deleting_taxonomy_keeps_posts_and_detaches_the_deleted_terms(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = User::factory()->create()->posts()->create([
            'title' => 'Taxonomy article',
            'content' => 'Post content.',
        ]);
        $category = Category::create(['name' => 'Travel']);
        $tag = Tag::create(['name' => 'Outdoors']);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertModelExists($post);
        $this->assertModelMissing($category);
        $this->assertModelMissing($tag);
        $this->assertDatabaseCount('posts_categories', 0);
        $this->assertDatabaseCount('post_tag', 0);
    }

    public function test_non_admin_cannot_access_taxonomy_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.tags.store'), ['name' => 'Unauthorized'])
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_admin_navigation_groups_article_comment_settings_and_profile_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.categories.index'), false)
            ->assertSee(route('admin.tags.index'), false)
            ->assertSee(route('admin.comments.index'), false)
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee(route('profile.edit'), false);
    }
}
