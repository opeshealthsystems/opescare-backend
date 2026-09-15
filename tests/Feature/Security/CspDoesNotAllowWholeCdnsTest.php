<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Security scan 2026-09-15, item 6. The CSP allowed all of cdn.jsdelivr.net for
 * scripts and styles although nothing loads from it; a whole public CDN in
 * script-src lets any published file run as script and bypasses the policy.
 */
class CspDoesNotAllowWholeCdnsTest extends TestCase
{
    public function test_html_pages_do_not_allow_jsdelivr(): void
    {
        $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertNotSame('', $csp, 'HTML pages carry a CSP');
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }
}
