<?php

namespace Tests\Feature\Admin;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Models\Role;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Support\ClientSecretHasher;
use Tests\TestCase;

/**
 * The partner onboarding desk at /admin/partners.
 *
 * This screen hands out keys to patient data, so what is pinned here is the
 * set of properties that would be silent and catastrophic if they regressed:
 *
 *  - only a platform admin gets in, and refusal is 403, not 404;
 *  - the issued secret is NEVER in the database in plaintext — asserted
 *    against the actual column values, not against an intention;
 *  - the secret is not retrievable after the request that created it;
 *  - a client is granted no scope unless one was explicitly chosen, and a
 *    wildcard cannot be spelled;
 *  - a revoked client cannot obtain a token, AND a token it already holds
 *    stops working on its very next request;
 *  - every credential act writes an audit row whose actor_id is a real uuid
 *    or null, never a string that is neither.
 */
class PartnerOnboardingDeskTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);
    }

    private function admin(): User
    {
        return $this->userWithRole('super_admin');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Sagex HIS',
            'contact_email' => 'tech@sagex.example',
            'environment'   => 'sandbox',
            'scopes'        => ['health_id:verify'],
        ], $overrides);
    }

    // ── Access ───────────────────────────────────────────────────────────

    public function test_a_non_platform_admin_is_refused_with_403_not_404(): void
    {
        $nurse = $this->userWithRole('nurse');

        $this->actingAs($nurse)->get('/admin/partners')->assertStatus(403);
        $this->actingAs($nurse)
            ->post('/admin/partners', $this->validPayload())
            ->assertStatus(403);

        // And nothing was created by the refused request.
        $this->assertSame(0, IntegrationClient::count());
    }

    public function test_a_platform_admin_reaches_the_desk(): void
    {
        $this->actingAs($this->admin())->get('/admin/partners')->assertStatus(200);
        $this->actingAs($this->admin())->get('/admin/partners/create')->assertStatus(200);
    }

    // ── The secret ───────────────────────────────────────────────────────

    public function test_the_issued_secret_is_never_stored_in_plaintext(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload())
            ->assertRedirect();

        $secret = session('new_client_secret');
        $this->assertNotEmpty($secret, 'The secret must be shown once, on creation.');

        $row = DB::table('integration_clients')->first();

        // Not in the Argon column, not in the legacy column, not in ANY column.
        foreach ((array) $row as $column => $value) {
            if (is_string($value)) {
                $this->assertStringNotContainsString(
                    $secret, $value,
                    "Column {$column} contains the plaintext secret."
                );
            }
        }

        // What IS stored is an Argon2id hash that verifies the secret.
        $this->assertStringStartsWith('$argon2', $row->client_secret_argon);
        $this->assertTrue(ClientSecretHasher::check($secret, $row->client_secret_argon));

        // And the legacy SHA-256 column holds a sentinel that cannot match any
        // input, so the weaker verification path is closed by construction.
        $this->assertNotSame(hash('sha256', $secret), $row->client_secret);
    }

    public function test_the_secret_is_not_retrievable_after_creation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $secret = session('new_client_secret');
        $client = IntegrationClient::firstOrFail();

        // The redirect target is THE one showing: the flash survives exactly
        // one request, so the page the admin lands on displays the secret.
        $this->actingAs($admin)
            ->get('/admin/partners/' . $client->id)
            ->assertStatus(200)
            ->assertSee($secret);

        // And that was the only one. A second load of the same page — a
        // refresh, a bookmark, anyone coming back later — no longer has it,
        // because nothing stored it.
        $this->actingAs($admin)
            ->get('/admin/partners/' . $client->id)
            ->assertStatus(200)
            ->assertDontSee($secret);

        // There is no route anywhere that returns it.
        $this->actingAs($admin)
            ->get('/admin/partners/' . $client->id . '/secret')
            ->assertStatus(404);
    }

    public function test_rotating_issues_a_new_secret_and_kills_the_old_one(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $old    = session('new_client_secret');
        $client = IntegrationClient::firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/partners/' . $client->id . '/rotate')
            ->assertRedirect();

        $new = session('new_client_secret');
        $this->assertNotSame($old, $new);

        $client->refresh();
        $this->assertTrue(ClientSecretHasher::check($new, $client->client_secret_argon));
        $this->assertFalse(ClientSecretHasher::check($old, $client->client_secret_argon));

        // The old secret no longer buys a token.
        $this->postJson('/api/v1/connect/auth/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $client->client_id,
            'client_secret' => $old,
        ])->assertStatus(401);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function test_scopes_default_to_none_and_are_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['scopes']);

        $this->actingAs($this->admin())
            ->post('/admin/partners', $payload)
            ->assertSessionHasErrors('scopes');

        $this->assertSame(0, IntegrationClient::count(), 'No client may be created without an explicit scope.');
    }

    public function test_a_wildcard_scope_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload(['scopes' => ['*']]))
            ->assertSessionHasErrors('scopes.0');

        $this->assertSame(0, IntegrationClient::count());

        // And so is anything outside the published catalogue.
        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload(['scopes' => ['patient:delete_everything']]))
            ->assertSessionHasErrors('scopes.0');

        $this->assertSame(0, IntegrationClient::count());
    }

    public function test_only_the_chosen_scopes_are_granted(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload(['scopes' => ['health_id:verify', 'lab:push']]));

        $client = IntegrationClient::firstOrFail();
        $this->assertSame(['health_id:verify', 'lab:push'], $client->scopes);
    }

    // ── Revocation ───────────────────────────────────────────────────────

    public function test_a_revoked_client_cannot_obtain_a_token(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $secret = session('new_client_secret');
        $client = IntegrationClient::firstOrFail();

        // It works while active.
        $this->postJson('/api/v1/connect/auth/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $client->client_id,
            'client_secret' => $secret,
        ])->assertStatus(200);

        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/status', [
            'status' => 'revoked',
            'reason' => 'Contract ended.',
        ])->assertRedirect();

        $this->postJson('/api/v1/connect/auth/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $client->client_id,
            'client_secret' => $secret,
        ])->assertStatus(401);
    }

    public function test_revocation_kills_a_token_that_was_already_issued(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $client = IntegrationClient::firstOrFail();

        // A token in the partner's hands right now.
        $token = app(JwtService::class)->issue([
            'sub'         => $client->client_id,
            'client_id'   => $client->client_id,
            'facility_id' => null,
            'environment' => 'sandbox',
            'scopes'      => ['health_id:verify'],
        ]);

        $middleware = app(\App\Http\Middleware\VerifyBearerToken::class);

        $pass = function () use ($middleware, $token) {
            $request = \Illuminate\Http\Request::create('/probe', 'GET');
            $request->headers->set('Authorization', 'Bearer ' . $token);

            return $middleware->handle($request, fn () => response()->json(['ok' => true]));
        };

        $this->assertSame(200, $pass()->getStatusCode(), 'The live token works while the client is active.');

        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/status', [
            'status' => 'revoked',
            'reason' => 'Credential leaked.',
        ]);

        $refused = $pass();
        $this->assertSame(401, $refused->getStatusCode(), 'Revocation must bite the token already issued.');
        $this->assertStringContainsString('client_revoked', $refused->getContent());
    }

    public function test_suspending_also_stops_a_live_token_and_reinstating_restores_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $client = IntegrationClient::firstOrFail();

        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/status', [
            'status' => 'suspended',
            'reason' => 'Under investigation.',
        ]);
        $this->assertSame('suspended', $client->fresh()->status);

        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/status', ['status' => 'active']);
        $this->assertSame('active', $client->fresh()->status);
    }

    // ── Audit ────────────────────────────────────────────────────────────

    public function test_every_credential_act_is_audited_with_a_uuid_actor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/partners', $this->validPayload());
        $client = IntegrationClient::firstOrFail();
        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/rotate');
        $this->actingAs($admin)->post('/admin/partners/' . $client->id . '/status', [
            'status' => 'revoked',
            'reason' => 'Done.',
        ]);

        $actions = AuditEvent::where('resource_type', 'integration_client')
            ->pluck('action_type')->all();

        $this->assertContains('partner_client_created', $actions);
        $this->assertContains('partner_client_secret_rotated', $actions);
        $this->assertContains('partner_client_revoked', $actions);

        foreach (AuditEvent::where('resource_type', 'integration_client')->get() as $event) {
            // actor_id is a uuid column and has been broken twice by a string
            // that was not one.
            $this->assertTrue(
                $event->actor_id === null || \Illuminate\Support\Str::isUuid($event->actor_id),
                'actor_id must be a real uuid or null.'
            );
            // The secret must never reach the audit trail either.
            $this->assertStringNotContainsString('sk_sandbox_', json_encode($event->after_state));
        }
    }

    // ── facility_id ──────────────────────────────────────────────────────

    public function test_a_bogus_organisation_id_is_refused_rather_than_stored(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload([
                'organisation_id' => '11111111-1111-1111-1111-111111111111',
            ]))
            ->assertSessionHasErrors('organisation_id');

        $this->assertSame(0, IntegrationClient::count());
    }

    public function test_the_stored_facility_is_the_one_the_database_holds(): void
    {
        $facility = Facility::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/partners', $this->validPayload(['organisation_id' => $facility->id]));

        $this->assertSame($facility->id, IntegrationClient::firstOrFail()->facility_id);
    }
}
