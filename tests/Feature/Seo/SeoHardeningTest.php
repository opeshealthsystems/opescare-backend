<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * SEO/GEO audit 2026-10-20 fixes: www → apex, canonical host pinned to
 * app.url, author pages, single-escaped titles, docs head, WebP pictures.
 */
class SeoHardeningTest extends TestCase
{
    use RefreshDatabase;

    private string $blogDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixture blog: the shared fixtures plus one post by a named person.
        $this->blogDir = storage_path('framework/testing/seo-blog-' . uniqid());
        File::copyDirectory(base_path('tests/Feature/Blog/fixtures/blog'), $this->blogDir);
        File::put($this->blogDir . '/en/person-post-test.md', <<<'MD'
---
title: "A post by a person"
slug: person-post-test
description: "Written by a named author."
date: 2026-09-02
author: "Jude Nshome"
author_bio: "Jude Nshome is the founder of OpesCare."
category: Guides
tags: [records]
---
Body text.
MD);
        config([
            'blog.path'       => $this->blogDir,
            'blog.images_dir' => 'blog-images/_test',
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->blogDir);
        File::deleteDirectory(public_path('blog-images/_test'));
        parent::tearDown();
    }

    // ── F1: www duplicate host ─────────────────────────────────────────

    public function test_www_host_redirects_301_to_apex_keeping_path_and_query(): void
    {
        config(['app.url' => 'https://opescare.com']);

        $this->get('http://www.opescare.com/blog?q=x&lang=fr')
            ->assertStatus(301)
            ->assertRedirect('https://opescare.com/blog?q=x&lang=fr');
    }

    public function test_no_redirect_when_app_url_itself_is_www_or_host_is_apex(): void
    {
        config(['app.url' => 'https://www.opescare.com']);
        $this->get('http://www.opescare.com/privacy')->assertOk();

        config(['app.url' => 'https://opescare.com']);
        $this->get('http://opescare.com/privacy')->assertOk();
    }

    public function test_canonical_hreflang_and_og_url_use_app_url_not_request_host(): void
    {
        config(['app.url' => 'https://opescare.com']);

        // A host that is not www (so not redirected) but differs from app.url.
        $html = $this->get('http://other-host.test/privacy')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="https://opescare.com/privacy">', $html);
        $this->assertStringContainsString('hreflang="fr" href="https://opescare.com/privacy?lang=fr"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="https://opescare.com/privacy">', $html);
    }

    public function test_blog_article_canonical_uses_app_url_host(): void
    {
        config(['app.url' => 'https://opescare.com']);

        $html = $this->get('http://other-host.test/blog/find-medicine-test')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="https://opescare.com/blog/find-medicine-test">', $html);
    }

    // ── F6: /privacy title + og:title escaping ─────────────────────────

    public function test_privacy_title_has_the_brand_once_and_og_title_is_escaped_once(): void
    {
        $html = $this->get('/privacy')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Privacy Policy &amp; Patient Data Protection — OpesCare</title>', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Privacy Policy &amp; Patient Data Protection — OpesCare">', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertSame(1, substr_count(
            preg_replace('/.*<title>(.*?)<\/title>.*/s', '$1', $html), 'OpesCare'
        ));
    }

    // ── F7: docs head ──────────────────────────────────────────────────

    public function test_docs_use_the_shared_organization_graph_and_a_branded_title(): void
    {
        $html = $this->get(route('docs.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<meta property="og:title" content="OpesCare developer docs[^"]*">/', $html);
        $this->assertMatchesRegularExpression('/<title>OpesCare developer docs[^<]*<\/title>/', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('/#organization', $html);
        $this->assertStringContainsString('"@type":"TechArticle"', $html);
        $this->assertStringNotContainsString('MedicalOrganization', $html);
    }

    // ── F4 (audit) / F3 (task): author pages ───────────────────────────

    public function test_person_author_page_lists_their_posts_with_bio_and_person_schema(): void
    {
        $res = $this->get('/blog/author/jude-nshome')->assertOk();
        $html = $res->getContent();

        $res->assertSee('Jude Nshome')
            ->assertSee(__('blog.authors.jude-nshome.bio'))
            ->assertSee('A post by a person');
        $this->assertStringNotContainsString('"headline":"How to find a medicine near you"', $html);
        $this->assertStringContainsString('"@type":"ProfilePage"', $html);
        $this->assertStringContainsString('"@type":"Person"', $html);
        $this->assertStringContainsString('"url":"' . rtrim(config('app.url'), '/') . '/blog/author/jude-nshome"', $html);
        $this->assertStringNotContainsString('"sameAs"', $html);
    }

    public function test_author_page_is_translated(): void
    {
        $this->get('/blog/author/jude-nshome?lang=fr')->assertOk()
            ->assertSee(__('blog.authors.jude-nshome.role', [], 'fr'));
    }

    public function test_unknown_author_is_404(): void
    {
        $this->get('/blog/author/nobody-here')->assertNotFound();
    }

    public function test_article_links_the_byline_and_gives_the_person_a_url(): void
    {
        $html = $this->get('/blog/person-post-test')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('blog.author', 'jude-nshome') . '" rel="author"', $html);
        $this->assertMatchesRegularExpression('/"author":\{"@type":"Person","@id":"[^"]+\/blog\/author\/jude-nshome#person"/', $html);
    }

    public function test_house_byline_is_the_shared_organization_named_consistently(): void
    {
        $html = $this->get('/blog/find-medicine-test')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/"author":\{"@type":"Organization","@id":"[^"]+\/#organization","name":"OpesCare","legalName":"Opes Health Systems Sarl"/', $html);
        $this->assertStringContainsString('"publisher":{"@type":"Organization","@id"', $html);
    }

    public function test_blog_index_links_to_author_pages(): void
    {
        $this->get('/blog')->assertOk()
            ->assertSee(route('blog.author', 'jude-nshome'), false);
    }

    // ── F8/F9/F10: pictures, inline blog CSS, card alt ────────────────

    public function test_cards_use_picture_with_webp_when_present_and_meaningful_alt(): void
    {
        $dir = public_path('blog-images/_test');
        File::ensureDirectoryExists($dir);
        File::put($dir . '/person-post-test.png', 'x');
        File::put($dir . '/person-post-test.webp', 'x');

        $html = $this->get('/blog/author/jude-nshome')->assertOk()->getContent();

        $this->assertStringContainsString('<source srcset="' . asset('blog-images/_test/person-post-test.webp') . '" type="image/webp">', $html);
        $this->assertStringContainsString('alt="A post by a person"', $html);
    }

    public function test_article_inlines_blog_css_instead_of_blocking_link(): void
    {
        $html = $this->get('/blog/find-medicine-test')->assertOk()->getContent();

        $this->assertStringContainsString('<style data-inline="blog.css">', $html);
        $this->assertStringNotContainsString('css/blog.css', $html);
        $this->assertStringNotContainsString('url("../', $html);
    }
}
