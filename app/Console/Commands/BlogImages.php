<?php

namespace App\Console\Commands;

use App\Services\Blog\BlogImageGenerator;
use App\Services\Blog\BlogRepository;
use Illuminate\Console\Command;
use Throwable;

/**
 * (Re)draw the branded 1200x630 hero / Open Graph card for every blog post.
 * The blog also draws a missing card on first view, so this is for refreshing
 * after a title change, or for warming the cards on deploy.
 */
class BlogImages extends Command
{
    protected $signature = 'blog:images {--force : Redraw cards that already exist} {--slug= : Only this post}';

    protected $description = 'Generate the branded hero/OG image for each blog post (public/blog-images/{slug}.png)';

    public function handle(BlogRepository $posts, BlogImageGenerator $images): int
    {
        $posts->flush();
        $done = 0;
        $failed = 0;

        foreach (BlogRepository::LOCALES as $locale) {
            foreach ($posts->all($locale) as $post) {
                if ($this->option('slug') && $post['slug'] !== $this->option('slug')) {
                    continue;
                }
                try {
                    $images->generate($post, (bool) $this->option('force'));
                    $this->line("  {$locale}  {$post['image']}");
                    $done++;
                } catch (Throwable $e) {
                    $this->error("  {$locale}  {$post['slug']}: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->info("{$done} image(s) ready, {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
