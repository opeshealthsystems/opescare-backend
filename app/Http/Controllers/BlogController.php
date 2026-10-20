<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Services\Blog\BlogImageGenerator;
use App\Services\Blog\BlogRepository;
use App\Support\FinderSeo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The public OpesCare blog: index, category, article, RSS.
 *
 * Posts are files (content/blog/{en,fr}/*.md, see BlogRepository). Locale
 * follows the rest of the site: English on the clean URL, French on ?lang=fr.
 * A post URL is deterministic for crawlers: /blog/{slug} always renders that
 * post in the language it was written in, whatever the visitor's session
 * says; only an EXPLICIT ?lang= that names the other language redirects to
 * the linked translation.
 */
class BlogController extends Controller
{
    public function __construct(private BlogRepository $posts)
    {
    }

    /** URL of a post in its own language. */
    public static function postUrl(array $post): string
    {
        return route('blog.show', $post['slug']) . ($post['locale'] === 'en' ? '' : '?lang=' . $post['locale']);
    }

    public static function localised(string $url, string $locale): string
    {
        return $locale === 'en' ? $url : $url . (str_contains($url, '?') ? '&' : '?') . 'lang=' . $locale;
    }

    /** Slug of a byline ("Jude Nshome" → "jude-nshome"). */
    public static function authorSlug(string $name): string
    {
        return Str::slug($name);
    }

    /** Author page URL for a byline, or null when the author has no page. */
    public static function authorUrl(string $name, string $locale): ?string
    {
        $slug = self::authorSlug($name);

        return isset(config('blog.authors', [])[$slug])
            ? self::localised(route('blog.author', $slug), $locale)
            : null;
    }

    /**
     * schema.org node for a byline. A named person is a Person with the
     * author page as url (sameAs only from config, never invented); the house
     * byline IS the site Organization, referenced by the same @id and naming
     * as the shared #organization node (name OpesCare, legal name Opes Health
     * Systems Sarl), so the article does not introduce a second entity.
     */
    public static function authorSchema(string $name, string $bio = ''): array
    {
        $slug = self::authorSlug($name);
        $cfg = config('blog.authors', [])[$slug] ?? null;
        $base = FinderSeo::base();

        if (($cfg['type'] ?? 'Person') === 'Organization') {
            return [
                '@type'     => 'Organization',
                '@id'       => $base . '/#organization',
                'name'      => 'OpesCare',
                'legalName' => 'Opes Health Systems Sarl',
                'url'       => $base,
            ];
        }

        $url = $cfg ? FinderSeo::absolute(route('blog.author', $slug)) : null;

        return array_filter([
            '@type'       => 'Person',
            '@id'         => $url ? $url . '#person' : null,
            'name'        => $name,
            'description' => $bio !== '' ? $bio : null,
            'jobTitle'    => $cfg ? (__('blog.authors.' . $slug . '.role') ?: null) : null,
            'url'         => $url,
            'worksFor'    => ['@id' => $base . '/#organization'],
            'sameAs'      => array_values($cfg['same_as'] ?? []) ?: null,
        ], fn ($v) => $v !== null);
    }

    /** /blog/author/{author}: the author's bio and their posts in this language. */
    public function author(Request $request, string $author)
    {
        $cfg = config('blog.authors', [])[$author] ?? null;
        if ($cfg === null) {
            abort(404);
        }

        $locale = App::getLocale();
        $posts = array_values(array_filter(
            $this->posts->all($locale),
            fn ($p) => self::authorSlug((string) $p['author']) === $author
        ));

        return view('blog.author', [
            'author'     => $author,
            'name'       => $cfg['name'],
            'type'       => $cfg['type'] ?? 'Person',
            'posts'      => $posts,
            'categories' => $this->posts->categories($locale),
            'recent'     => array_slice($this->posts->all($locale), 0, 5),
        ]);
    }

    public function index(Request $request)
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, string $category)
    {
        $locale = App::getLocale();
        $categories = $this->posts->categories($locale);
        if (! isset($categories[$category])) {
            abort(404);
        }

        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?string $category)
    {
        $locale = App::getLocale();
        $query = trim(mb_substr((string) $request->query('q', ''), 0, 100));
        $all = $this->posts->search($locale, $query, $category);

        $filtering = $query !== '' || $category !== null;
        $page = max(1, (int) $request->query('page', 1));
        // The featured post leads page 1 and is left out of the paginated grid.
        $featured = ! $filtering ? $this->posts->featured($all) : null;
        $rest = $featured ? array_values(array_filter($all, fn ($p) => $p['slug'] !== $featured['slug'])) : $all;
        if ($page > 1) {
            $featured = null;
        }

        $perPage = (int) config('blog.per_page', 9);
        $paginator = new LengthAwarePaginator(
            array_slice($rest, ($page - 1) * $perPage, $perPage),
            count($rest),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => array_filter(['q' => $query ?: null, 'lang' => $locale === 'en' ? null : $locale])]
        );

        if ($page > 1 && $paginator->isEmpty()) {
            abort(404);
        }

        return view('blog.index', [
            'featured'   => $featured,
            'posts'      => $paginator,
            'categories' => $this->posts->categories($locale),
            'category'   => $category,
            'categoryLabel' => $category ? ($this->posts->categories($locale)[$category] ?? $category) : null,
            'query'      => $query,
            'total'      => count($all),
            'recent'     => array_slice($this->posts->all($locale), 0, 5),
        ]);
    }

    public function show(Request $request, string $slug, BlogImageGenerator $images)
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            abort(404);
        }

        $current = App::getLocale();
        $post = $this->posts->findAnywhere($slug, $current);
        if ($post === null) {
            abort(404);
        }

        if ($post['locale'] !== $current) {
            $explicit = $request->query('lang') === $current;
            $translation = $this->posts->translation($post, $current);
            if ($explicit && $translation) {
                return redirect()->to(self::postUrl($translation), 301);
            }
            // Render in the language the post was written in.
            App::setLocale($post['locale']);
            \Carbon\Carbon::setLocale($post['locale']);
        }

        if (! is_file(public_path($post['image']))) {
            try {
                $images->generate($post);
            } catch (Throwable $e) {
                Log::warning('blog: hero image not generated', ['slug' => $slug, 'error' => $e->getMessage()]);
            }
        }

        $translations = [];
        foreach (BlogRepository::LOCALES as $l) {
            if ($t = $this->posts->translation($post, $l)) {
                $translations[$l] = $t;
            }
        }

        $comments = BlogComment::query()
            ->where('post_slug', $post['slug'])
            ->where('locale', $post['locale'])
            ->whereNull('parent_id')
            ->approved()
            ->with(['replies' => fn ($q) => $q->approved()->orderBy('created_at')])
            ->orderBy('created_at')
            ->get(['id', 'author_name', 'body', 'created_at', 'parent_id']);

        $approvedCount = BlogComment::query()
            ->where('post_slug', $post['slug'])->where('locale', $post['locale'])->approved()->count();

        return view('blog.show', [
            'post'          => $post,
            // Canonical on the app.url host, never the request host (audit F1).
            'url'           => FinderSeo::absolute(self::postUrl($post)),
            'translations'  => $translations,
            'related'       => $this->posts->related($post, 3),
            'adjacent'      => $this->posts->adjacent($post),
            'comments'      => $comments,
            'commentCount'  => $approvedCount,
            'imageExists'   => is_file(public_path($post['image'])),
            'formToken'     => Crypt::encryptString((string) now()->getTimestamp()),
            // Desktop sidebar: topics and the latest posts in this language.
            'categories'    => $this->posts->categories($post['locale']),
            'recent'        => array_values(array_filter(
                array_slice($this->posts->all($post['locale']), 0, 6),
                fn ($p) => $p['slug'] !== $post['slug']
            )),
        ]);
    }

    /**
     * The post as a typeset PDF (front matter downloadable: true).
     *
     * A blog post is public content, so the PDF is cacheable: it is rendered
     * on the first request for a given version of the post and kept under
     * storage/app/blog-pdf, keyed by a fingerprint of the post, the PDF view
     * and the diagram labels. Editing any of those produces a new file; old
     * ones are removed when the new one is written. Nothing here touches
     * patient data.
     */
    public function download(string $slug)
    {
        $post = $this->posts->findAnywhere($slug, App::getLocale());
        if ($post === null || empty($post['downloadable'])) {
            abort(404);
        }

        App::setLocale($post['locale']);
        \Carbon\Carbon::setLocale($post['locale']);

        $fingerprint = md5(json_encode([
            $post['slug'], $post['locale'], $post['updated'], md5($post['html']), $post['title'], $post['description'],
            $post['faq'], $post['sources'], $post['key_takeaways'], $post['author'], $post['author_bio'] ?? '',
            @filemtime(resource_path('views/pdf/blog-article.blade.php')),
            @filemtime(resource_path('views/blog/diagrams/board.blade.php')),
            @filemtime(lang_path($post['locale'] . '/blog_diagrams.php')),
            @filemtime(lang_path($post['locale'] . '/blog.php')),
            @filemtime(app_path('Support/Blog/DiagramPdf.php')),
        ]));
        $dir = storage_path('app/blog-pdf');
        $file = $dir . DIRECTORY_SEPARATOR . $post['slug'] . '-' . $fingerprint . '.pdf';

        if (! is_file($file)) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $bytes = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.blog-article', [
                'post' => $post,
                'url'  => self::postUrl($post),
            ])
                ->setPaper('a4')
                // `true` merges with the package defaults (keeps the chroot at base_path()).
                ->setOptions([
                    'defaultFont'             => 'DejaVu Sans',
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'isRemoteEnabled'         => false,
                    'dpi'                     => 96,
                ], true)
                ->output();

            foreach (glob($dir . DIRECTORY_SEPARATOR . $post['slug'] . '-*.pdf') ?: [] as $old) {
                @unlink($old);
            }
            file_put_contents($file, $bytes);
        }

        $name = self::pdfFilename($post);

        return response()->file($file, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control'       => 'public, max-age=86400',
            'ETag'                => '"' . $fingerprint . '"',
            'X-Robots-Tag'        => 'noindex',
        ]);
    }

    /** "OpesCare-The-Record-Is-the-Product-Jude-Nshome.pdf" — ASCII, title words joined by hyphens. */
    public static function pdfFilename(array $post): string
    {
        $title = trim(preg_split('/[:—–|]/u', $post['title'])[0]);
        $clean = fn (string $s) => trim(preg_replace('/[^A-Za-z0-9]+/', '-', \Illuminate\Support\Str::ascii($s)), '-');

        return implode('-', array_filter(['OpesCare', $clean($title), $clean($post['author'])])) . '.pdf';
    }

    /** RSS 2.0 feed of the current locale's posts. */
    public function feed(Request $request): Response
    {
        $locale = App::getLocale();
        $posts = array_slice($this->posts->all($locale), 0, 30);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $rss = $doc->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');
        $doc->appendChild($rss);

        $channel = $doc->createElement('channel');
        $rss->appendChild($channel);

        $add = function (\DOMElement $parent, string $name, string $text) use ($doc) {
            $el = $doc->createElement($name);
            $el->appendChild($doc->createTextNode($text));
            $parent->appendChild($el);

            return $el;
        };

        $selfUrl = self::localised(route('blog.feed'), $locale);
        $add($channel, 'title', __('blog.feed_title'));
        $add($channel, 'link', self::localised(route('blog.index'), $locale));
        $add($channel, 'description', __('blog.meta_description'));
        $add($channel, 'language', $locale === 'fr' ? 'fr-CM' : 'en-CM');
        $add($channel, 'copyright', 'OPES Health Systems');
        $atom = $doc->createElementNS('http://www.w3.org/2005/Atom', 'atom:link');
        $atom->setAttribute('href', $selfUrl);
        $atom->setAttribute('rel', 'self');
        $atom->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atom);
        if ($posts !== []) {
            $add($channel, 'lastBuildDate', \Illuminate\Support\Carbon::parse(max(array_column($posts, 'updated')))->toRfc2822String());
        }

        foreach ($posts as $post) {
            $item = $doc->createElement('item');
            $url = self::postUrl($post);
            $add($item, 'title', $post['title']);
            $add($item, 'link', $url);
            $guid = $add($item, 'guid', $url);
            $guid->setAttribute('isPermaLink', 'true');
            $add($item, 'description', $post['description']);
            $add($item, 'pubDate', \Illuminate\Support\Carbon::parse($post['date'])->toRfc2822String());
            if ($post['category'] !== '') {
                $add($item, 'category', $post['category']);
            }
            $add($item, 'author', 'noreply@opescare.com (OPES Health Systems)');
            $channel->appendChild($item);
        }

        return response($doc->saveXML(), 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
