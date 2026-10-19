<?php

namespace Tests\Feature\Blog;

use App\Models\BlogComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The file-based blog: rendering in both languages, safe Markdown, SEO head,
 * RSS, sitemap, share links, and moderated comments.
 */
class BlogTest extends TestCase
{
    use RefreshDatabase;

    private const EN = '/blog/find-medicine-test';
    private const FR = '/blog/trouver-medicament-test?lang=fr';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'blog.path'       => __DIR__ . '/fixtures/blog',
            'blog.images_dir' => 'blog-images/_test',
        ]);
        Cache::flush();
        RateLimiter::clear('x');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('blog-images/_test'));
        parent::tearDown();
    }

    private function token(int $secondsAgo = 10): string
    {
        return Crypt::encryptString((string) (now()->getTimestamp() - $secondsAgo));
    }

    private function comment(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/blog/find-medicine-test/comments', array_merge([
            'locale'       => 'en',
            'ts'           => $this->token(),
            'author_name'  => 'Amina',
            'author_email' => 'amina@example.com',
            'body'         => 'Very useful, <b>thanks</b> https://spam.example',
            'consent'      => '1',
        ], $overrides));
    }

    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $out = [];
        foreach ($m[1] as $block) {
            $decoded = json_decode($block, true);
            $this->assertIsArray($decoded, 'JSON-LD block is not valid JSON');
            $out[] = $decoded;
        }

        return $out;
    }

    public function test_index_renders_in_english_and_french(): void
    {
        $this->get('/blog')->assertOk()
            ->assertSee('How to find a medicine near you')
            ->assertDontSee('Comment trouver un médicament')
            ->assertSee('CollectionPage', false);

        $this->get('/blog?lang=fr')->assertOk()
            ->assertSee('Comment trouver un médicament près de chez vous')
            ->assertSee('Guides santé pour le Cameroun');
    }

    public function test_category_and_search(): void
    {
        $this->get('/blog/category/guides')->assertOk()->assertSee('How to find a medicine near you');
        $this->get('/blog/category/nope')->assertNotFound();
        $this->get('/blog?q=zzzzz')->assertOk()->assertSee(__('blog.empty_search'))->assertSee('noindex', false);
    }

    public function test_article_renders_in_both_languages_with_translation_links(): void
    {
        $html = $this->get(self::EN)->assertOk()
            ->assertSee('<h1>How to find a medicine near you</h1>', false)
            ->assertSee('Search by medicine name.')
            ->assertSee('Is the Medicine Finder free?')
            ->assertSee('WHO Model List of Essential Medicines')
            ->assertSee('href="#step-one"', false)
            ->assertSee('hreflang="fr" href="' . url('/blog/trouver-medicament-test') . '?lang=fr"', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'exactly one h1');
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('article:published_time" content="2026-09-01"', $html);
        $this->assertStringContainsString('article:modified_time" content="2026-09-15"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . url(self::EN) . '">', $html);
        $this->assertStringContainsString('blog-images/_test/find-medicine-test.png', $html);
        $this->assertFileExists(public_path('blog-images/_test/find-medicine-test.png'));
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('blog-images/_test/find-medicine-test.png')), 0, 2));

        $this->get(self::FR)->assertOk()
            ->assertSee('Comment trouver un médicament près de chez vous')
            ->assertSee('lang="fr"', false)
            ->assertSee('Lire en anglais');

        // Explicit ?lang=fr on the English slug goes to the translation.
        $this->get('/blog/find-medicine-test?lang=fr')->assertRedirect(url(self::FR));
    }

    public function test_unknown_slug_is_404(): void
    {
        $this->get('/blog/no-such-post')->assertNotFound();
        $this->get('/blog/Bad_Slug')->assertNotFound();
    }

    public function test_markdown_html_is_escaped_and_unsafe_links_dropped(): void
    {
        $html = $this->get(self::EN)->assertOk()->getContent();

        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringNotContainsString('nofollow', $html);
        // The body h1 is demoted; the internal link is not marked external.
        $this->assertStringContainsString('<h2 id="stray-top-heading">', $html);
        $this->assertMatchesRegularExpression('#<a href="/network/medicine-finder">#', $html);
    }

    public function test_json_ld_is_valid_and_complete(): void
    {
        $blocks = $this->jsonLd($this->get(self::EN)->getContent());
        $graph = collect($blocks)->firstWhere('@graph', '!=', null)['@graph'] ?? [];
        $article = collect($blocks)->pluck('@graph')->flatten(1)->firstWhere('@type', 'BlogPosting');

        $this->assertNotNull($article);
        foreach ($blocks as $b) {
            if (isset($b['@context'])) {
                $this->assertSame('https://schema.org', $b['@context']);
            }
        }
        $this->assertSame('How to find a medicine near you', $article['headline']);
        $this->assertSame('2026-09-01', $article['datePublished']);
        $this->assertSame('2026-09-15', $article['dateModified']);
        $this->assertSame('Organization', $article['author']['@type']);
        $this->assertSame('en', $article['inLanguage']);
        $this->assertArrayHasKey('logo', $article['publisher']);

        $types = collect($blocks)->pluck('@graph')->flatten(1)->pluck('@type')->all();
        $this->assertContains('BreadcrumbList', $types);
        $this->assertContains('FAQPage', $types);
        $this->assertNotEmpty($graph);
    }

    public function test_share_links_present_and_no_third_party_scripts(): void
    {
        $html = $this->get(self::EN)->getContent();
        foreach (['wa.me/?text=', 'facebook.com/sharer', 'twitter.com/intent/tweet', 'linkedin.com/sharing', 't.me/share', 'mailto:?subject=', 'data-blog-copy', 'data-blog-share'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }

        preg_match_all('#<script[^>]*\bsrc="([^"]+)"#', $html, $m);
        foreach ($m[1] as $src) {
            $this->assertStringStartsWith(url('/'), $src, "third-party script: {$src}");
        }
        $this->assertDoesNotMatchRegularExpression('/<[a-z][^>]*\son(click|load|error|submit)=/i', $html);
    }

    public function test_rss_is_valid_xml(): void
    {
        $res = $this->get('/blog/feed.xml')->assertOk();
        $this->assertStringStartsWith('application/rss+xml', $res->headers->get('Content-Type'));
        $xml = simplexml_load_string($res->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('2.0', (string) $xml['version']);
        $this->assertSame('How to find a medicine near you', (string) $xml->channel->item[0]->title);

        $fr = simplexml_load_string($this->get('/blog/feed.xml?lang=fr')->getContent());
        $this->assertSame('Comment trouver un médicament près de chez vous', (string) $fr->channel->item[0]->title);
    }

    public function test_sitemap_lists_posts_with_hreflang(): void
    {
        $index = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/sitemap-blog.xml', $index);

        $xml = $this->get('/sitemap-blog.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('<loc>' . url('/blog/find-medicine-test') . '</loc>', $xml);
        $this->assertStringContainsString('hreflang="fr" href="' . url('/blog/trouver-medicament-test') . '?lang=fr"', $xml);
        $this->assertStringContainsString('hreflang="x-default" href="' . url('/blog/find-medicine-test') . '"', $xml);

        $pages = $this->get('/sitemap-pages.xml')->getContent();
        $this->assertStringContainsString('<loc>' . url('/blog') . '</loc>', $pages);
        $this->assertStringContainsString('<loc>' . url('/faq') . '</loc>', $pages);
        $this->assertStringNotContainsString('feed.xml', $pages);
    }

    public function test_comment_is_pending_until_approved(): void
    {
        $this->comment()->assertRedirect()->assertSessionHas('blog_comment_status', 'pending');

        $c = BlogComment::sole();
        $this->assertSame('pending', $c->status);
        $this->assertNotSame('', (string) $c->ip_hash);

        $this->get(self::EN)->assertDontSee('Very useful');

        $c->update(['status' => 'approved']);
        $html = $this->get(self::EN)->assertSee('Very useful')->assertSee('1 comment')->getContent();
        // escaped, not auto-linked, email never shown
        $this->assertStringContainsString('&lt;b&gt;thanks&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('href="https://spam.example', $html);
        $this->assertStringNotContainsString('amina@example.com', $html);
    }

    public function test_signed_in_users_are_still_moderated(): void
    {
        $this->actingAs(User::factory()->create());
        $this->comment();
        $this->assertSame('pending', BlogComment::sole()->status);
        $this->assertNotNull(BlogComment::sole()->user_id);
    }

    public function test_honeypot_time_trap_and_validation_reject(): void
    {
        $this->comment(['website' => 'http://bot'])->assertSessionHas('blog_comment_status', 'pending');
        $this->comment(['ts' => $this->token(0)]);
        $this->comment(['ts' => 'forged']);
        $this->assertSame(0, BlogComment::count());

        $this->comment(['consent' => null])->assertSessionHasErrors('consent');
        $this->comment(['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->assertSame(0, BlogComment::count());
    }

    public function test_throttle_limits_comments_per_minute(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->comment(['body' => "comment {$i}"]);
        }
        $this->assertSame(3, BlogComment::count());
    }

    public function test_moderation_requires_platform_admin(): void
    {
        $this->get('/admin/blog/comments')->assertRedirect();

        $this->actingAs(User::factory()->create(['status' => 'active']))
            ->withSession(['mfa.verified' => true])
            ->get('/admin/blog/comments')
            ->assertStatus(403);
    }

    public function test_platform_admin_can_moderate(): void
    {
        $this->comment();
        $c = BlogComment::sole();
        $admin = User::factory()->create([
            'status'  => 'active',
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin'])->id,
        ]);

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->get('/admin/blog/comments')->assertOk()->assertSee('Very useful');

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->post("/admin/blog/comments/{$c->id}/status", ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $c->fresh()->status);
        $this->get(self::EN)->assertSee('Very useful');

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->delete("/admin/blog/comments/{$c->id}")->assertRedirect();
        $this->assertSame(0, BlogComment::count());
    }

    public function test_nav_links_to_blog(): void
    {
        $this->get('/faq')->assertOk()->assertSee('href="' . route('blog.index') . '"', false);
    }
}
