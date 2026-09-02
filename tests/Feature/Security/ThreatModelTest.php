<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class ThreatModelTest extends TestCase
{
    public function test_threat_model_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/threat-model.md'));
    }

    public function test_threat_model_covers_stride_categories(): void
    {
        $content = file_get_contents(base_path('docs/threat-model.md'));

        $this->assertStringContainsString('Spoofing', $content);
        $this->assertStringContainsString('Tampering', $content);
        $this->assertStringContainsString('Repudiation', $content);
        $this->assertStringContainsString('Information Disclosure', $content);
        $this->assertStringContainsString('Denial of Service', $content);
        $this->assertStringContainsString('Elevation of Privilege', $content);
    }

    public function test_threat_model_references_opescare_components(): void
    {
        $content = file_get_contents(base_path('docs/threat-model.md'));

        $this->assertStringContainsString('Patient', $content);
        $this->assertStringContainsString('API', $content);
        $this->assertStringContainsString('Authentication', $content);
    }
}
