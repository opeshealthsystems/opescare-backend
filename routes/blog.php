<?php

// Registered from AppServiceProvider (inside the 'web' middleware group).
//
// The public blog (file-based posts in content/blog/{en,fr}) and its comment
// moderation desk. /blog/feed.xml is declared before /blog/{slug} so the feed
// is never read as a post slug.

use App\Http\Controllers\Admin\BlogCommentModerationController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap-blog.xml', [\App\Http\Controllers\SeoController::class, 'sitemapBlog'])->name('seo.sitemap.blog');

Route::get('/blog',[BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/category/{category}', [BlogController::class, 'category'])
    ->where('category', '[a-z0-9-]+')->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')->name('blog.show');
Route::post('/blog/{slug}/comments', [BlogCommentController::class, 'store'])
    ->where('slug', '[a-z0-9-]+')->middleware('throttle:30,1')->name('blog.comments.store');

Route::middleware(['auth', 'mfa.verified', 'portal.access', 'platform.admin', 'throttle:portal'])->group(function () {
    Route::get('/admin/blog/comments', [BlogCommentModerationController::class, 'index'])->name('portals.admin.blog.comments');
    Route::post('/admin/blog/comments/{comment}/status', [BlogCommentModerationController::class, 'updateStatus'])
        ->whereUuid('comment')->name('portals.admin.blog.comments.status');
    Route::delete('/admin/blog/comments/{comment}', [BlogCommentModerationController::class, 'destroy'])
        ->whereUuid('comment')->name('portals.admin.blog.comments.destroy');
});
