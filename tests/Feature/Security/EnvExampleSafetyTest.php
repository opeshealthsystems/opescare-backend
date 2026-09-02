<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class EnvExampleSafetyTest extends TestCase
{
    private string $envExample;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envExample = file_get_contents(base_path('.env.example'));
    }

    public function test_app_debug_is_false(): void
    {
        $this->assertStringContainsString('APP_DEBUG=false', $this->envExample,
            '.env.example must have APP_DEBUG=false');
    }

    public function test_db_connection_is_pgsql(): void
    {
        $this->assertStringContainsString('DB_CONNECTION=pgsql', $this->envExample,
            '.env.example must use DB_CONNECTION=pgsql (not sqlite)');
    }

    public function test_mail_mailer_is_smtp(): void
    {
        $this->assertStringContainsString('MAIL_MAILER=smtp', $this->envExample,
            '.env.example must have MAIL_MAILER=smtp (not log or null)');
    }

    public function test_demo_mode_is_false(): void
    {
        $this->assertStringContainsString('OPESCARE_DEMO_MODE=false', $this->envExample,
            '.env.example must have OPESCARE_DEMO_MODE=false');
    }

    public function test_log_level_is_warning(): void
    {
        $this->assertStringContainsString('LOG_LEVEL=warning', $this->envExample,
            '.env.example must have LOG_LEVEL=warning (not debug)');
    }

    public function test_session_encrypt_is_true(): void
    {
        $this->assertStringContainsString('SESSION_ENCRYPT=true', $this->envExample,
            '.env.example must have SESSION_ENCRYPT=true');
    }
}
