<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostContentImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_author_can_upload_a_content_image(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();

        $response = $this->actingAs($author)
            ->postJson(route('posts.content-images.store'), [
                'image' => UploadedFile::fake()->image('illustration.png'),
            ]);

        $response->assertOk()->assertJsonStructure(['location']);

        $files = Storage::disk('public')->allFiles('images/content/'.$author->id);
        $this->assertCount(2, $files);
        $media = Media::query()->where('user_id', $author->id)->firstOrFail();
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
        $this->assertStringEndsWith('.webp', $media->path);
        $response->assertJsonPath('location', Storage::disk('public')->url($media->path));
    }

    public function test_authenticated_author_can_upload_a_gif_content_image(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $gif = $this->createGifContents();

        $response = $this->actingAs($author)
            ->postJson(route('posts.content-images.store'), [
                'image' => UploadedFile::fake()->createWithContent('animation.gif', $gif),
            ]);

        $response->assertOk()->assertJsonStructure(['location']);
        $files = Storage::disk('public')->allFiles('images/content/'.$author->id);
        $this->assertCount(2, $files);
        $media = Media::query()->where('user_id', $author->id)->firstOrFail();
        $this->assertStringEndsWith('.gif', $media->path);
        $this->assertSame($gif, Storage::disk('public')->get($media->path));
        Storage::disk('public')->assertExists($media->thumbnail_path);
        $response->assertJsonPath('location', Storage::disk('public')->url($media->path));
    }

    public function test_guest_cannot_upload_a_content_image(): void
    {
        Storage::fake('public');

        $this->post(route('posts.content-images.store'), [
            'image' => UploadedFile::fake()->image('illustration.png'),
        ])->assertRedirect(route('login'));

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_content_image_upload_rejects_non_raster_files(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();

        $this->actingAs($author)
            ->postJson(route('posts.content-images.store'), [
                'image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function createGifContents(): string
    {
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagegif($image);
        imagedestroy($image);
        $contents = ob_get_clean();

        $this->assertIsString($contents);

        return $contents;
    }
}
