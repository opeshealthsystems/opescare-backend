<?php
namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class RotateSecretsCommandTest extends TestCase
{
    public function test_check_flag_runs_without_error(): void
    {
        $this->artisan('opescare:rotate-secrets --check')
             ->assertExitCode(0);
    }

    public function test_check_flag_does_not_modify_app_key(): void
    {
        $original = config('app.key');
        $this->artisan('opescare:rotate-secrets --check')->assertExitCode(0);
        $this->assertSame($original, config('app.key'));
    }

    public function test_command_outputs_secret_names(): void
    {
        $this->artisan('opescare:rotate-secrets --check')
             ->expectsOutputToContain('app_key')
             ->assertExitCode(0);
    }

    public function test_command_without_check_flag_shows_usage_instructions(): void
    {
        $this->artisan('opescare:rotate-secrets')
             ->expectsOutputToContain('--check')
             ->assertExitCode(0);
    }

    public function test_overdue_secret_is_reported(): void
    {
        Cache::put('secrets.last_rotated.app_key', now()->subDays(100)->toIso8601String());
        $this->artisan('opescare:rotate-secrets --check')
             ->expectsOutputToContain('OVERDUE')
             ->assertExitCode(0);
        Cache::forget('secrets.last_rotated.app_key');
    }
}
