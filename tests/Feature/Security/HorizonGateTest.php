<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class HorizonGateTest extends TestCase
{
    // Creates users on fixed emails ('ops@opescare.test') with no isolation,
    // so it wrote into whatever state it found and left the rows behind.
    // Serial ordering hid that; `--parallel` — which CI runs — surfaced it
    // as a duplicate-key violation on users_email_unique.
    use RefreshDatabase;

    public function test_email_allowlist_grants_and_denies(): void
    {
        config(['horizon.admin_emails' => 'ops@opescare.test, lead@opescare.test']);

        $allowed = User::factory()->create(['email' => 'ops@opescare.test']);
        $denied  = User::factory()->create(['email' => 'random@opescare.test']);

        $this->assertTrue(Gate::forUser($allowed)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($denied)->allows('viewHorizon'));
    }

    public function test_no_allowlist_does_not_throw_and_denies_non_admin(): void
    {
        // Reproduces the prod bug: env() is empty under config:cache, so the gate
        // fell back to $user->hasRole() — a method User does not have — and threw.
        config(['horizon.admin_emails' => '']);

        $user = User::factory()->create(); // role-less -> roleName() null

        // Must NOT throw, and must deny a non-platform user.
        $this->assertFalse(Gate::forUser($user)->allows('viewHorizon'));
    }

    public function test_jwt_ttl_is_read_from_config(): void
    {
        $this->assertIsInt(config('services.opescare_jwt.ttl'));
        $this->assertGreaterThan(0, config('services.opescare_jwt.ttl'));
    }
}
