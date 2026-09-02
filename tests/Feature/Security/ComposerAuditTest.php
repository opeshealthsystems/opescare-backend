<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class ComposerAuditTest extends TestCase
{
    public function test_composer_lock_exists(): void
    {
        $this->assertFileExists(base_path('composer.lock'));
    }

    public function test_composer_json_declares_php_constraint(): void
    {
        $json = json_decode(file_get_contents(base_path('composer.json')), true);

        $this->assertArrayHasKey('require', $json);
        $this->assertArrayHasKey('php', $json['require']);
    }
}
