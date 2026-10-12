<?php

namespace Tests\Unit;

use App\Modules\Legal\Services\LegalHtmlSanitizer;
use Tests\TestCase;

/**
 * Legal document HTML is rendered unescaped on the PUBLIC legal pages, so a
 * pasted script in an admin's upload would be stored XSS for every visitor.
 * The sanitiser runs at publish time; this pins what it must strip and keep.
 */
class LegalHtmlSanitizerTest extends TestCase
{
    private LegalHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new LegalHtmlSanitizer();
    }

    public function test_strips_scripts_handlers_and_javascript_urls(): void
    {
        $dirty = '<h2>Terms</h2><script>alert(1)</script>'
            . '<p onclick="steal()">Hello <a href="javascript:alert(1)">click</a></p>'
            . '<img src="x" onerror="alert(1)"><iframe src="https://evil.example"></iframe>'
            . '<p style="background:url(javascript:1)">styled</p>';

        $clean = $this->sanitizer->clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringContainsString('<h2>Terms</h2>', $clean);
        $this->assertStringContainsString('Hello', $clean);
    }

    public function test_keeps_document_markup(): void
    {
        $html = '<h1>Privacy</h1><p>We <strong>never</strong> sell data.</p>'
            . '<ul><li>One</li><li>Two</li></ul>'
            . '<table><thead><tr><th scope="col">A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'
            . '<p><a href="https://opescare.cloud/contact">Contact</a> or <a href="mailto:help@opescare.cloud">email</a>.</p>';

        $clean = $this->sanitizer->clean($html);

        foreach (['<h1>Privacy</h1>', '<strong>never</strong>', '<li>Two</li>', '<th scope="col">A</th>', 'href="https://opescare.cloud/contact"', 'href="mailto:help@opescare.cloud"'] as $expected) {
            $this->assertStringContainsString($expected, $clean);
        }
    }
}
