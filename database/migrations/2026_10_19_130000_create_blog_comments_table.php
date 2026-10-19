<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Reader comments on blog posts. Posts themselves are files (content/blog);
 * only comments live here. Every comment starts 'pending' and is shown only
 * once a platform admin approves it. The email is stored for moderation
 * contact and is never rendered; IP and user agent are kept only as keyed
 * hashes (for throttling and spam patterns), never in the clear.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('post_slug', 160);
            $table->string('locale', 5);
            $table->uuid('parent_id')->nullable();
            $table->string('author_name', 80);
            $table->string('author_email', 190);
            $table->text('body');
            $table->string('status', 16)->default('pending');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestamps();

            $table->index(['post_slug', 'locale', 'status']);
            $table->index(['status', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Self-reference added after the table (and its primary key) exist.
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('blog_comments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
