<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blog_settings', function (Blueprint $table) {
            $table->text('site_description')->nullable();
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('hero_cta_label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_description',
                'hero_eyebrow',
                'hero_title',
                'hero_description',
                'hero_cta_label',
            ]);
        });
    }
};
