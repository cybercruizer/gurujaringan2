<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthProfileAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_saves_bio_and_avatar_without_granting_admin_access(): void
    {
        Notification::fake();
        Storage::fake('public');

        $response = $this->post(route('register'), [
            'name' => 'New Writer',
            'email' => 'writer@example.com',
            'bio' => 'A curious writer.',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => true,
        ]);

        $user = User::query()->where('email', 'writer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Writer',
            'bio' => 'A curious writer.',
            'is_admin' => false,
            'email_verified_at' => null,
        ]);
        Storage::disk('public')->assertExists($user->avatar);
        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_profile_can_update_bio_and_avatar_without_changing_admin_status(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'avatar' => 'avatars/old-avatar.jpg',
        ]);
        Storage::disk('public')->put('avatars/old-avatar.jpg', 'old image');

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Writer',
            'email' => $user->email,
            'bio' => 'Updated biography.',
            'avatar' => UploadedFile::fake()->image('new-avatar.png'),
            'is_admin' => true,
        ]);

        $user->refresh();
        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Writer',
            'bio' => 'Updated biography.',
            'is_admin' => false,
        ]);
        Storage::disk('public')->assertExists($user->avatar);
        Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    }

    public function test_changing_email_requires_email_verification_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new-address@example.com',
            'bio' => $user->bio,
        ])->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_admin_dashboard_requires_an_admin_account(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $regularUser = User::factory()->create();
        $this->actingAs($regularUser)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();

        $this->actingAs($administrator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin dashboard');
    }
}
