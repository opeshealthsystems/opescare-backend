<?php

namespace App\Services\Blog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads blog posts from content/blog/{locale}/*.md.
 *
 * Front matter contract (YAML between two --- lines):
 *   title, slug, description, date, updated, author, category, tags[],
 *   translation_of, hero_alt, reading_minutes, key_takeaways[],
 *   faq[{q,a}], sources[{title,publisher,url,accessed}]
 * Optional: draft: true (never published).
 *
 * A file that fails to parse or lacks title/slug/date is skipped and logged,
 * never half-rendered. Parsed posts are cached as PLAIN ARRAYS (see the
 * never-cache-objects rule: a cached object comes back as
 * __PHP_Incomplete_Class), keyed by a fingerprint of every file's path, size
 * and mtime, so editing or adding a post invalidates the cache by itself.
 */
class BlogRepository
{
    public const LOCALES = ['en', 'fr'];

    public function __construct(private MarkdownRenderer $renderer)
    {
    }

    /** All published posts for a locale, newest first. */
    public function all(string $locale): array
    {
        $today = now()->toDateString();

        // A post dated in the future is scheduled, not published.
        return array_values(array_filter($this->load()[$locale] ?? [], fn ($p) => $p['date'] <= $today));
    }

    public function find(string $locale, string $slug): ?array
    {
        foreach ($this->all($locale) as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }

        return null;
    }

    /** A post in any locale, current locale first. */
    public function findAnywhere(string $slug, string $preferred): ?array
    {
        $order = array_unique(array_merge([$preferred], self::LOCALES));
        foreach ($order as $locale) {
            if ($post = $this->find($locale, $slug)) {
                return $post;
            }
        }

        return null;
    }

    /** The same post in the other locale, if one is linked by translation_of (either direction). */
    public function translation(array $post, string $locale): ?array
    {
        if ($locale === $post['locale']) {
            return $post;
        }

        foreach ($this->all($locale) as $other) {
            if (($post['translation_of'] && $other['slug'] === $post['translation_of'])
                || ($other['translation_of'] && $other['translation_of'] === $post['slug'])
                || ($post['translation_of'] && $other['translation_of'] === $post['translation_of'])) {
                return $other;
            }
        }

        return null;
    }

    /** @return array<string,string> category slug => label */
    public function categories(string $locale): array
    {
        $out = [];
        foreach ($this->all($locale) as $post) {
            if ($post['category'] !== '') {
                $out[$post['category_slug']] = $post['category'];
            }
        }
        ksort($out);

        return $out;
    }

    public function related(array $post, int $limit = 3): array
    {
        $scored = [];
        foreach ($this->all($post['locale']) as $other) {
            if ($other['slug'] === $post['slug']) {
                continue;
            }
            $score = ($other['category_slug'] === $post['category_slug'] ? 3 : 0)
                + count(array_intersect($other['tags'], $post['tags']));
            $scored[] = [$score, $other['date'], $other];
        }
        usort($scored, fn ($a, $b) => [$b[0], $b[1]] <=> [$a[0], $a[1]]);

        return array_slice(array_column($scored, 2), 0, $limit);
    }

    /** @return array{prev:?array,next:?array} prev = older, next = newer */
    public function adjacent(array $post): array
    {
        $all = $this->all($post['locale']);
        foreach ($all as $i => $p) {
            if ($p['slug'] === $post['slug']) {
                return ['prev' => $all[$i + 1] ?? null, 'next' => $all[$i - 1] ?? null];
            }
        }

        return ['prev' => null, 'next' => null];
    }

    public function search(string $locale, string $query, ?string $category = null): array
    {
        $needle = Str::lower(Str::ascii(trim($query)));

        return array_values(array_filter($this->all($locale), function ($p) use ($needle, $category) {
            if ($category !== null && $p['category_slug'] !== $category) {
                return false;
            }
            if ($needle === '') {
                return true;
            }
            $hay = Str::lower(Str::ascii($p['title'] . ' ' . $p['description'] . ' ' . implode(' ', $p['tags']) . ' ' . $p['category']));

            return str_contains($hay, $needle);
        }));
    }

    public function imagePath(string $slug): string
    {
        return trim((string) config('blog.images_dir', 'blog-images'), '/') . '/' . $slug . '.png';
    }

    public function flush(): void
    {
        Cache::forget($this->cacheKey());
    }

    // ── loading ────────────────────────────────────────────────────────────

    private function files(): array
    {
        $root = rtrim((string) config('blog.path'), '/\\');
        $out = [];
        foreach (self::LOCALES as $locale) {
            foreach (glob($root . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . '*.md') ?: [] as $file) {
                $out[] = [$locale, $file];
            }
        }

        return $out;
    }

    private function cacheKey(): string
    {
        $fp = '';
        foreach ($this->files() as [$locale, $file]) {
            $fp .= $file . '|' . @filesize($file) . '|' . @filemtime($file) . ';';
        }

        return 'blog.posts.v1.' . md5((string) config('blog.path') . $fp);
    }

    private function load(): array
    {
        return Cache::remember($this->cacheKey(), (int) config('blog.cache_ttl', 86400), function () {
            $byLocale = array_fill_keys(self::LOCALES, []);

            foreach ($this->files() as [$locale, $file]) {
                try {
                    $post = $this->parseFile($locale, $file);
                } catch (Throwable $e) {
                    Log::warning('blog: skipped unreadable post', ['file' => $file, 'error' => $e->getMessage()]);
                    continue;
                }
                if ($post === null) {
                    continue;
                }
                if (isset($byLocale[$locale][$post['slug']])) {
                    Log::warning('blog: duplicate slug skipped', ['file' => $file, 'slug' => $post['slug']]);
                    continue;
                }
                $byLocale[$locale][$post['slug']] = $post;
            }

            foreach ($byLocale as $locale => $posts) {
                $posts = array_values($posts);
                usort($posts, fn ($a, $b) => [$b['date'], $b['slug']] <=> [$a['date'], $a['slug']]);
                $byLocale[$locale] = $posts;
            }

            return $byLocale;
        });
    }

    private function parseFile(string $locale, string $file): ?array
    {
        $source = (string) file_get_contents($file);
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        if (str_starts_with($source, "\u{FEFF}")) {
            $source = substr($source, 3);
        }

        if (! preg_match('/^---\n(.*?)\n---[ \t]*(?:\n|$)(.*)$/s', $source, $m)) {
            throw new \InvalidArgumentException('missing front matter');
        }

        $meta = MiniYaml::parse($m[1]);
        $body = $m[2];

        if (! empty($meta['draft'])) {
            return null;
        }

        $slug = (string) ($meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME));
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new \InvalidArgumentException("invalid slug '{$slug}'");
        }
        foreach (['title', 'date'] as $required) {
            if (empty($meta[$required])) {
                throw new \InvalidArgumentException("missing {$required}");
            }
        }

        $date = $this->date($meta['date']);
        if ($date === null) {
            throw new \InvalidArgumentException('invalid date');
        }
        $updated = $this->date($meta['updated'] ?? null) ?? $date;

        $rendered = $this->renderer->render($body);
        $category = trim((string) ($meta['category'] ?? ''));

        $list = fn ($v) => array_values(array_filter(array_map(fn ($x) => is_scalar($x) ? trim((string) $x) : null, (array) ($v ?? [])), fn ($x) => $x !== null && $x !== ''));

        $faq = [];
        foreach ((array) ($meta['faq'] ?? []) as $item) {
            if (is_array($item) && ! empty($item['q']) && ! empty($item['a'])) {
                $faq[] = ['q' => trim((string) $item['q']), 'a' => trim((string) $item['a'])];
            }
        }

        $sources = [];
        foreach ((array) ($meta['sources'] ?? []) as $item) {
            if (! is_array($item) || empty($item['title'])) {
                continue;
            }
            $url = trim((string) ($item['url'] ?? ''));
            if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
                $url = '';    // never link a javascript: or relative-path "source"
            }
            $sources[] = [
                'title'     => trim((string) $item['title']),
                'publisher' => trim((string) ($item['publisher'] ?? '')),
                'url'       => $url,
                'accessed'  => $this->date($item['accessed'] ?? null),
            ];
        }

        return [
            'locale'          => $locale,
            'slug'            => $slug,
            'title'           => trim((string) $meta['title']),
            'description'     => trim((string) ($meta['description'] ?? '')),
            'date'            => $date,
            'updated'         => max($updated, $date),
            'author'          => trim((string) ($meta['author'] ?? '')) ?: 'OPES Health Systems',
            'category'        => $category,
            'category_slug'   => $category === '' ? '' : Str::slug($category),
            'tags'            => $list($meta['tags'] ?? []),
            'translation_of'  => trim((string) ($meta['translation_of'] ?? '')),
            'hero_alt'        => trim((string) ($meta['hero_alt'] ?? '')) ?: trim((string) $meta['title']),
            'reading_minutes' => max(1, (int) ($meta['reading_minutes'] ?? 0) ?: (int) ceil($rendered['words'] / 200)),
            'key_takeaways'   => $list($meta['key_takeaways'] ?? []),
            'faq'             => $faq,
            'sources'         => $sources,
            'html'            => $rendered['html'],
            'toc'             => $rendered['toc'],
            'words'           => $rendered['words'],
            'image'           => $this->imagePath($slug),
        ];
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse(substr($value, 0, 10))->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
