<?php

namespace Tests\Feature\Security;

use App\Console\Commands\DemoResetCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POST /api/demo/reset used to be anonymous and unthrottled, and the command
 * behind it deleted EVERY session with a user_id (logging out real users).
 */
class DemoResetEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/demo/reset';

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => true, 'demo.reset_token' => '']);
    }

    public function test_anonymous_caller_gets_404_when_no_token_is_configured(): void
    {
        Artisan::shouldReceive('call')->never();
        $this->postJson(self::URL)->assertNotFound();
    }

    public function test_demo_mode_off_is_404_even_with_the_right_token(): void
    {
        config(['demo.enabled' => false, 'demo.reset_token' => 'sekret-token-123']);
        Artisan::shouldReceive('call')->never();
        $this->postJson(self::URL, [], ['X-Demo-Reset-Token' => 'sekret-token-123'])->assertNotFound();
    }

    public function test_missing_or_wrong_token_is_403(): void
    {
        config(['demo.reset_token' => 'sekret-token-123']);
        Artisan::shouldReceive('call')->never();

        $this->postJson(self::URL)->assertForbidden();
        $this->postJson(self::URL, [], ['X-Demo-Reset-Token' => 'wrong'])->assertForbidden();
    }

    public function test_right_token_runs_the_reset(): void
    {
        config(['demo.reset_token' => 'sekret-token-123']);
        Artisan::shouldReceive('call')->once()->with('opescare:demo:reset')->andReturn(0);

        $this->postJson(self::URL, [], ['X-Demo-Reset-Token' => 'sekret-token-123'])
            ->assertOk()
            ->assertJson(['message' => 'Demo reset successfully']);
    }

    public function test_platform_admin_can_reset_without_token(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super admin'])->id,
        ]);
        Artisan::shouldReceive('call')->once()->with('opescare:demo:reset')->andReturn(0);

        $this->actingAs($admin)->postJson(self::URL)->assertOk();
    }

    public function test_endpoint_is_throttled(): void
    {
        config(['demo.reset_token' => 'sekret-token-123']);
        Artisan::shouldReceive('call')->never();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, [], ['X-Demo-Reset-Token' => 'wrong'])->assertForbidden();
        }
        $this->postJson(self::URL, [], ['X-Demo-Reset-Token' => 'wrong'])->assertStatus(429);
    }

    public function test_reset_revokes_only_demo_users_sessions(): void
    {
        $real = User::factory()->create();
        $demo = User::factory()->create();
        $demo->forceFill(['is_demo' => true])->save();

        foreach ([['real', $real->id], ['demo', $demo->id], ['guest', null]] as [$id, $userId]) {
            DB::table('sessions')->insert([
                'id' => 'sess_' . $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->assertSame(1, DemoResetCommand::revokeDemoSessions());

        $this->assertDatabaseHas('sessions', ['id' => 'sess_real']);
        $this->assertDatabaseHas('sessions', ['id' => 'sess_guest']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sess_demo']);
    }

    public function test_reset_is_not_advertised_in_the_public_spec(): void
    {
        $this->assertStringNotContainsString('/api/demo/reset', file_get_contents(public_path('openapi.json')));
        $this->assertStringNotContainsString('/api/demo/reset', file_get_contents(public_path('opescare.postman_collection.json')));
    }
}
