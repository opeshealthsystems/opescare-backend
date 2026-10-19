<?php

namespace App\Services\Blog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The public FAQ, read from content/faq/{locale}.yaml: a list of
 * {category, q, a, link}. Category values are English keys in both files;
 * the page translates them via blog.faq_categories.{slug}.
 *
 * Returns [] when the file is missing or unreadable, so the page falls back
 * to the questions in lang/{locale}/finder_story.php.
 */
class FaqRepository
{
    /** @return list<array{category:string,category_slug:string,q:string,a:string,link:?string}> */
    public function items(string $locale): array
    {
        $file = rtrim((string) config('blog.faq_path'), '/\\') . DIRECTORY_SEPARATOR . $locale . '.yaml';
        if (! is_file($file)) {
            return [];
        }

        $key = 'faq.items.v1.' . md5($file . '|' . filesize($file) . '|' . filemtime($file));

        return Cache::remember($key, 86400, function () use ($file) {
            try {
                $rows = MiniYaml::parse((string) file_get_contents($file));
            } catch (Throwable $e) {
                Log::warning('faq: unreadable yaml', ['file' => $file, 'error' => $e->getMessage()]);

                return [];
            }

            $out = [];
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['q']) || empty($row['a'])) {
                    continue;
                }
                $link = trim((string) ($row['link'] ?? ''));
                // Same-site paths or http(s) only.
                if ($link !== '' && ! str_starts_with($link, '/') && ! preg_match('#^https?://#i', $link)) {
                    $link = '';
                }
                $category = trim((string) ($row['category'] ?? ''));
                $out[] = [
                    'category'      => $category,
                    'category_slug' => Str::slug($category),
                    'q'             => trim((string) $row['q']),
                    'a'             => trim((string) $row['a']),
                    'link'          => $link === '' ? null : $link,
                ];
            }

            return $out;
        });
    }

    /** Items grouped by category, in first-seen order. */
    public function grouped(string $locale): array
    {
        $groups = [];
        foreach ($this->items($locale) as $item) {
            $groups[$item['category_slug']] ??= ['category' => $item['category'], 'slug' => $item['category_slug'], 'items' => []];
            $groups[$item['category_slug']]['items'][] = $item;
        }

        return array_values($groups);
    }
}
