<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Site Admin']);
        $matchingUser = User::factory()->create(['name' => 'Taylor Writer', 'email' => 'taylor@example.com']);
        User::factory()->create(['name' => 'Morgan Reader', 'email' => 'morgan@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'taylor']))
            ->assertOk()
            ->assertSee('taylor@example.com')
            ->assertDontSee('morgan@example.com');

        $this->get(route('admin.users.index', ['search' => 'morgan@example.com']))
            ->assertOk()
            ->assertSee('Morgan Reader')
            ->assertDontSee('Taylor Writer');
    }

    public function test_admin_can_grant_and_revoke_another_users_admin_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $member), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_admin' => true]);

        $this->patch(route('admin.users.role', $member), ['role' => 'member'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_admin' => false]);
    }

    public function test_role_update_rejects_roles_outside_the_supported_choices(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $member), ['role' => 'superadmin'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_admin' => false]);
    }

    public function test_admin_cannot_demote_or_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin), ['role' => 'member'])
            ->assertSessionHas('status', 'You cannot remove your own administrator access.');

        $this->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('status', 'You cannot delete your own account from user management.');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_admin' => true]);
    }

    public function test_user_with_authored_content_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Authored post',
            'content' => 'Post content.',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $author))
            ->assertSessionHas('status', 'This user has authored posts or comments and cannot be deleted.');

        $this->assertModelExists($author);
        $this->assertModelExists($post);
    }

    public function test_user_with_comments_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create();
        $post = $admin->posts()->create([
            'title' => 'Commented post',
            'content' => 'Post content.',
        ]);
        $comment = Comment::query()->forceCreate([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'A comment by the account being deleted.',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $author))
            ->assertSessionHas('status', 'This user has authored posts or comments and cannot be deleted.');

        $this->assertModelExists($author);
        $this->assertModelExists($comment);
    }

    public function test_admin_can_delete_an_account_without_authored_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $member))
            ->assertRedirect();

        $this->assertModelMissing($member);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->patch(route('admin.users.role', $otherUser), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'is_admin' => false]);
    }
}
