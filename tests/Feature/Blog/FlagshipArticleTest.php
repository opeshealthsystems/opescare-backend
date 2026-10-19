<?php

namespace Tests\Feature\Blog;

use App\Services\Blog\MarkdownRenderer;
use App\Support\Blog\BlogDiagrams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The flagship essay and the blog features it introduced: whitelisted diagram
 * embeds, a Person author in the schema, the featured flag and the PDF download.
 */
class FlagshipArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        File::delete(glob(storage_path('app/blog-pdf/*.pdf')) ?: []);
        File::deleteDirectory(storage_path('app/blog-pdf/svg'));
        parent::tearDown();
    }

    public function test_only_whitelisted_diagram_names_become_markers(): void
    {
        $r = app(MarkdownRenderer::class);

        $ok = $r->render("Intro\n\n{{diagram:consent}}\n\nOutro")['html'];
        $this->assertStringContainsString('<!--opescare-diagram:consent-->', $ok);
        $this->assertStringNotContainsString('{{diagram', $ok);

        foreach (['nope', '../../etc/passwd', 'blog.diagrams.board', '<script>alert(1)</script>'] as $bad) {
            $html = $r->render("A\n\n{{diagram:$bad}}\n\nB")['html'];
            $this->assertStringNotContainsString('opescare-diagram', $html, $bad);
            $this->assertStringNotContainsString('<script', $html, $bad);
        }

        // A post cannot forge the marker with raw HTML: it is escaped.
        $forged = $r->render("<!--opescare-diagram:consent-->\n\n<svg onload=alert(1)>")['html'];
        $this->assertSame([], array_filter(BlogDiagrams::segments($forged), fn ($s) => isset($s['diagram'])));
        $this->assertStringNotContainsString('<svg', $forged);

        // Unknown markers never render, even if one reached segments().
        $this->assertSame('', BlogDiagrams::render('nope'));
        $this->assertSame([['html' => 'x']], BlogDiagrams::segments('x<!--opescare-diagram:nope-->'));
    }

    public function test_flagship_renders_diagrams_and_person_author(): void
    {
        $res = $this->get('/blog/the-record-is-the-product')->assertOk();
        $html = $res->getContent();

        $this->assertStringNotContainsString('{{diagram', $html);
        $this->assertSame(6, substr_count($html, '<svg class="dg-svg'));
        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('js/blog-diagrams.js', $html);
        $this->assertStringContainsString('/blog/the-record-is-the-product/download.pdf', $html);

        preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $ld = json_decode($m[1], true);
        $this->assertSame('https://schema.org', $ld['@context']);
        $post = collect($ld['@graph'])->firstWhere('@type', 'BlogPosting');
        $this->assertSame('Person', $post['author']['@type']);
        $this->assertSame('Jude Nshome', $post['author']['name']);
        $this->assertSame('Organization', $post['publisher']['@type']);

        $this->get('/blog/le-dossier-est-le-produit')->assertOk()->assertSee('Jude Nshome');
    }

    public function test_house_byline_stays_an_organization(): void
    {
        $html = $this->get('/blog/patient-consent-who-sees-your-record')->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $post = collect(json_decode($m[1], true)['@graph'])->firstWhere('@type', 'BlogPosting');
        $this->assertSame('Organization', $post['author']['@type']);
        $this->assertStringNotContainsString('download.pdf', $html);
    }

    public function test_pdf_download(): void
    {
        $res = $this->get('/blog/the-record-is-the-product/download.pdf')->assertOk();
        $this->assertStringStartsWith('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('filename="OpesCare-The-Record-Is-the-Product-Jude-Nshome.pdf"', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', file_get_contents($res->baseResponse->getFile()->getPathname()));

        $this->get('/blog/patient-consent-who-sees-your-record/download.pdf')->assertNotFound();
        $this->get('/blog/no-such-post/download.pdf')->assertNotFound();
    }

    public function test_featured_flag_takes_the_featured_slot(): void
    {
        $html = $this->get('/blog')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#class="bl-featured".*?The Record Is the Product#s', $html);
        $this->assertSame(1, substr_count($html, 'href="' . url('/blog/the-record-is-the-product') . '"') >= 1 ? 1 : 0);

        $this->get('/blog?lang=fr')->assertOk()->assertSee('Le dossier est le produit', false);
    }
}
