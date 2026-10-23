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
            $table->boolean('comments_enabled')->default(true);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('comments')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Guest comments must be removed before rolling back this migration.');
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['author_name', 'author_email']);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('comments_enabled');
        });
    }
};
