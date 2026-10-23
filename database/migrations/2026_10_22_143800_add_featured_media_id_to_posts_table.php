<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('featured_media_id')
                ->nullable()
                ->after('featured_image_thumbnail')
                ->constrained('media')
                ->nullOnDelete();
        });

        DB::table('posts')
            ->whereNotNull('featured_image')
            ->select(['id', 'user_id', 'featured_image', 'featured_image_thumbnail'])
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    $path = $post->featured_image;
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mimeType = match ($extension) {
                        'gif' => 'image/gif',
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        default => 'image/webp',
                    };
                    $timestamp = now();

                    DB::table('media')->insertOrIgnore([
                        'user_id' => $post->user_id,
                        'path' => $path,
                        'thumbnail_path' => $post->featured_image_thumbnail ?: $path,
                        'original_name' => basename($path),
                        'mime_type' => $mimeType,
                        'size' => 0,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    $mediaId = DB::table('media')->where('path', $path)->value('id');

                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update(['featured_media_id' => $mediaId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_media_id');
        });
    }
};
