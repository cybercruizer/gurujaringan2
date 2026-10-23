<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_browse_images_in_the_media_library(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'image' => UploadedFile::fake()->image('brand-mark.png', 900, 600),
                'alt_text' => 'Brand mark',
            ])
            ->assertRedirect(route('admin.media.index'));

        $media = Media::query()->where('original_name', 'brand-mark.png')->firstOrFail();
        $this->assertSame('Brand mark', $media->alt_text);
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->thumbnail_path);

        $this->get(route('media.library'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $media->id)
            ->assertJsonPath('data.0.name', 'brand-mark.png')
            ->assertJsonPath('data.0.alt_text', 'Brand mark')
            ->assertJsonPath('data.0.url', $media->url());

        $this->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Media library')
            ->assertSee('brand-mark.png');
    }

    public function test_guests_and_non_admins_cannot_manage_the_media_library(): void
    {
        $this->get(route('admin.media.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.media.store'), [
            'image' => UploadedFile::fake()->image('not-allowed.png'),
        ])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.media.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.media.store'), [
                'image' => UploadedFile::fake()->image('not-allowed.png'),
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_media_alt_text_without_changing_its_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $media = $this->createMedia($admin, 'alt-edit');
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');

        $this->actingAs($admin)
            ->put(route('admin.media.update', $media), ['alt_text' => 'A descriptive landscape'])
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('status', 'Image alternative text updated.');

        $this->assertSame('A descriptive landscape', $media->fresh()->alt_text);
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
    }

    public function test_media_alt_text_update_rejects_too_long_text_and_non_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $media = $this->createMedia($admin, 'protected-alt');

        $this->actingAs($admin)
            ->from(route('admin.media.index'))
            ->put(route('admin.media.update', $media), ['alt_text' => str_repeat('a', 256)])
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHasErrors('alt_text');

        $this->actingAs($user)
            ->put(route('admin.media.update', $media), ['alt_text' => 'Unauthorized'])
            ->assertForbidden();

        $this->assertNull($media->fresh()->alt_text);
    }

    public function test_admin_can_delete_an_unused_media_item_and_its_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $media = $this->createMedia($admin, 'unused');
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
        Storage::disk('public')->assertMissing($media->thumbnail_path);
    }

    public function test_admin_cannot_delete_media_used_by_a_post(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $media = $this->createMedia($admin, 'in-use');
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');
        $post = $admin->posts()->create([
            'title' => 'Post with media',
            'content' => 'Content using an image.',
            'featured_media_id' => $media->id,
            'featured_image' => $media->path,
            'featured_image_thumbnail' => $media->thumbnail_path,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect()
            ->assertSessionHas('status', 'This image is in use by a post and cannot be deleted.');

        $this->assertModelExists($media);
        $this->assertModelExists($post);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_admin_cannot_delete_media_embedded_in_post_content(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $media = $this->createMedia($admin, 'inline');
        Storage::disk('public')->put($media->path, 'image');
        Storage::disk('public')->put($media->thumbnail_path, 'thumbnail');
        $admin->posts()->create([
            'title' => 'Post with inline image',
            'content' => '<p><img src="/storage/'.$media->path.'"></p>',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect()
            ->assertSessionHas('status', 'This image is in use by a post and cannot be deleted.');

        $this->assertModelExists($media);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_library_requires_authentication(): void
    {
        $this->get(route('media.library'))
            ->assertRedirect(route('login'));
    }

    private function createMedia(User $user, string $name): Media
    {
        return Media::query()->create([
            'user_id' => $user->id,
            'path' => "images/library/{$name}.webp",
            'thumbnail_path' => "images/library/thumbnails/{$name}.webp",
            'original_name' => "{$name}.webp",
            'mime_type' => 'image/webp',
            'size' => 128,
        ]);
    }
}
