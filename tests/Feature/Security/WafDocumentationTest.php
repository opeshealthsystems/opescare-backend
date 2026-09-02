<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class WafDocumentationTest extends TestCase
{
    public function test_waf_configuration_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/waf-configuration.md'));
    }

    public function test_waf_doc_contains_required_sections(): void
    {
        $content = file_get_contents(base_path('docs/waf-configuration.md'));

        $this->assertStringContainsString('Rate Limiting', $content);
        $this->assertStringContainsString('Bot Management', $content);
        $this->assertStringContainsString('IP Allowlist', $content);
        $this->assertStringContainsString('OWASP', $content);
    }
}
