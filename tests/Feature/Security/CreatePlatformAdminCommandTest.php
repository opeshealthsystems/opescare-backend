<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreatePlatformAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_real_platform_admin_with_a_random_password_shown_once(): void
    {
        Role::query()->firstOrCreate(['name' => 'platform_admin'], ['display_name' => 'Platform admin']);

        $this->artisan('opescare:create-admin', ['email' => 'Owner@Example.org', '--name' => 'Owner'])
            ->expectsOutputToContain('Platform administrator created: owner@example.org')
            ->assertSuccessful();

        $user = User::withoutGlobalScopes()->where('email', 'owner@example.org')->firstOrFail();
        $this->assertSame('platform_admin', $user->role->name);
        $this->assertSame('active', $user->status);
        $this->assertFalse((bool) $user->is_demo);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$argon'), 'password is hashed');
    }

    public function test_it_never_touches_an_existing_account(): void
    {
        Role::query()->firstOrCreate(['name' => 'platform_admin'], ['display_name' => 'Platform admin']);
        $existing = User::factory()->create(['email' => 'taken@example.org', 'password' => Hash::make('original-pass')]);

        $this->artisan('opescare:create-admin', ['email' => 'taken@example.org'])
            ->expectsOutputToContain('already exists')
            ->assertFailed();

        $this->assertTrue(Hash::check('original-pass', $existing->fresh()->password));
    }

    public function test_it_refuses_a_non_platform_role(): void
    {
        $this->artisan('opescare:create-admin', ['email' => 'x@example.org', '--role' => 'patient'])
            ->expectsOutputToContain('is not a platform role')
            ->assertFailed();
    }
}
