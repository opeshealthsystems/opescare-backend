<?php

namespace Tests\Feature\Developer;

use App\Models\ApiUsageLog;
use App\Models\DeveloperAccount;
use App\Models\Facility;
use App\Models\FhirIngestEvent;
use App\Models\IntegrationClient;
use App\Models\Role;
use App\Models\User;
use App\Models\WebhookSubscription;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The developer portal after docs/DEVELOPER_DASHBOARD_AUDIT.md.
 *
 * D-1: every developer page 500'd because the sidebar linked a route that
 *      needs a {clientId} without one. Pinned here as a 200 on the dashboard,
 *      which EveryRoleReachesItsOwnDashboardTest (not-302, not-403) let through.
 * D-2: a partner can register a webhook endpoint, sees its secret once.
 * D-3: a partner sees its own fhir_ingest_events — and only its own.
 * D-4: the quickstart carries the partner's real client_id, never a secret.
 */
class DeveloperDashboardCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    private function developer(string $roleName = 'developer'): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);
        $user->role_id = $role->id;
        $user->save();

        DeveloperAccount::create([
            'display_name'      => 'Acme Health Systems',
            'email'             => $user->email,
            'status'            => 'active',
            'email_verified_at' => now(),
            'api_terms_accepted'=> true,
            'sandbox_only'      => true,
        ]);

        return $user->fresh();
    }

    private function appFor(User $developer, array $attrs = []): IntegrationClient
    {
        return IntegrationClient::factory()->create(array_merge([
            'created_by'  => $developer->email,
            'facility_id' => null,
            'environment' => 'sandbox',
        ], $attrs));
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa.verified' => true]);
    }

    // ── D-1 ────────────────────────────────────────────────────────────────

    public function test_every_developer_page_renders_200_for_the_developer_role(): void
    {
        $dev = $this->developer();
        $this->appFor($dev);

        foreach (['/developer', '/developer/apps', '/developer/webhooks', '/developer/activity', '/developer/quickstart', '/developer/analytics', '/developer/production-requests'] as $path) {
            $this->as($dev)->get($path)->assertOk();
        }
    }

    public function test_webhook_manager_and_partner_technical_reach_the_new_pages(): void
    {
        foreach (['webhook_manager', 'partner_technical', 'public_health_api'] as $role) {
            $dev = $this->developer($role);
            $this->as($dev)->get('/developer')->assertOk();
            $this->as($dev)->get('/developer/webhooks')->assertOk();
            $this->as($dev)->get('/developer/activity')->assertOk();
        }
    }

    public function test_owned_sidebars_never_link_the_per_app_route_without_its_parameter(): void
    {
        foreach (['developer', 'webhook_manager'] as $partial) {
            $blade = file_get_contents(resource_path("views/partials/sidebars/{$partial}.blade.php"));
            $this->assertStringNotContainsString("route('portals.developer.webhook_deliveries')", $blade, $partial);
            $this->assertStringContainsString("route('portals.developer.webhooks')", $blade, $partial);
        }
    }

    public function test_a_clinical_role_is_bounced_from_the_developer_pages(): void
    {
        $role = Role::where('name', 'doctor')->firstOrFail();
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => Facility::factory()->create()->id]);
        $user->role_id = $role->id;
        $user->save();

        foreach (['/developer/webhooks', '/developer/activity', '/developer/quickstart'] as $path) {
            $response = $this->as($user->fresh())->get($path);
            $this->assertContains($response->getStatusCode(), [302, 403], $path);
        }
    }

    // ── D-2 webhooks ───────────────────────────────────────────────────────

    public function test_partner_registers_an_https_endpoint_and_sees_the_secret_exactly_once(): void
    {
        $dev = $this->developer();
        $app = $this->appFor($dev);

        $response = $this->as($dev)->post('/developer/webhooks', [
            'integration_client_id' => $app->id,
            'callback_url'          => 'https://partner.example.com/hooks/opescare',
            'events'                => ['consent.granted', 'lab_result.released'],
        ]);

        $response->assertRedirect(route('portals.developer.webhooks'));

        $sub = WebhookSubscription::where('client_id', $app->client_id)->firstOrFail();
        $this->assertSame('https://partner.example.com/hooks/opescare', $sub->callback_url);
        $this->assertSame(['consent.granted', 'lab_result.released'], $sub->subscribed_events);
        $this->assertStringStartsWith('whsec_', $sub->webhook_secret);

        // Shown once on the redirect target ...
        $this->as($dev)->get('/developer/webhooks')->assertSee($sub->webhook_secret);
        // ... and never again.
        $this->as($dev)->get('/developer/webhooks')->assertDontSee($sub->webhook_secret);
    }

    public function test_plain_http_endpoints_and_unknown_events_are_refused(): void
    {
        $dev = $this->developer();
        $app = $this->appFor($dev);

        $this->as($dev)->from('/developer/webhooks')->post('/developer/webhooks', [
            'integration_client_id' => $app->id,
            'callback_url'          => 'http://partner.example.com/hooks',
            'events'                => ['consent.granted'],
        ])->assertSessionHasErrors('callback_url');

        $this->as($dev)->from('/developer/webhooks')->post('/developer/webhooks', [
            'integration_client_id' => $app->id,
            'callback_url'          => 'https://partner.example.com/hooks',
            'events'                => ['patient.deleted'],
        ])->assertSessionHasErrors('events.0');

        $this->assertSame(0, WebhookSubscription::count());
    }

    public function test_an_endpoint_cannot_be_registered_on_another_developers_app(): void
    {
        $dev   = $this->developer();
        $other = $this->developer();
        $theirs = $this->appFor($other);

        $this->as($dev)->post('/developer/webhooks', [
            'integration_client_id' => $theirs->id,
            'callback_url'          => 'https://partner.example.com/hooks',
            'events'                => ['consent.granted'],
        ])->assertNotFound();

        $this->assertSame(0, WebhookSubscription::count());
    }

    public function test_pause_and_resume_only_touch_the_partners_own_subscription(): void
    {
        $dev   = $this->developer();
        $other = $this->developer();
        $mine   = WebhookSubscription::create(['client_id' => $this->appFor($dev)->client_id, 'callback_url' => 'https://a.example.com/h', 'webhook_secret' => 'whsec_a', 'subscribed_events' => ['consent.granted'], 'status' => 'active']);
        $theirs = WebhookSubscription::create(['client_id' => $this->appFor($other)->client_id, 'callback_url' => 'https://b.example.com/h', 'webhook_secret' => 'whsec_b', 'subscribed_events' => ['consent.granted'], 'status' => 'active']);

        $this->as($dev)->post("/developer/webhooks/{$mine->id}/toggle")->assertRedirect();
        $this->assertSame('paused', $mine->fresh()->status);

        $this->as($dev)->post("/developer/webhooks/{$mine->id}/toggle")->assertRedirect();
        $this->assertSame('active', $mine->fresh()->status);

        $this->as($dev)->post("/developer/webhooks/{$theirs->id}/toggle")->assertNotFound();
        $this->assertSame('active', $theirs->fresh()->status);

        // The other developer's endpoint is not listed on my page either.
        $this->as($dev)->get('/developer/webhooks')->assertSee('https://a.example.com/h')->assertDontSee('https://b.example.com/h');
    }

    // ── D-3 activity ───────────────────────────────────────────────────────

    public function test_activity_shows_the_partners_own_ingest_events_with_the_operation_outcome(): void
    {
        $dev   = $this->developer();
        $other = $this->developer();
        $mine   = $this->appFor($dev);
        $theirs = $this->appFor($other);

        FhirIngestEvent::create([
            'integration_client_id'   => $mine->client_id,
            'resource_type'           => 'Observation',
            'outcome'                 => FhirIngestEvent::OUTCOME_REFUSED,
            'refusal_code'            => 'consent_required',
            'refusal_detail'          => 'no active consent grant from patient to facility abc',
            'http_status'             => 403,
            'subject_identifier_hash' => FhirIngestEvent::hashIdentifier('OC-TST-001'),
            'created_at'              => now(),
        ]);
        FhirIngestEvent::create([
            'integration_client_id' => $mine->client_id,
            'resource_type'         => 'Immunization',
            'outcome'               => FhirIngestEvent::OUTCOME_ACCEPTED,
            'http_status'           => 201,
            'created_at'            => now(),
        ]);
        FhirIngestEvent::create([
            'integration_client_id' => $theirs->client_id,
            'resource_type'         => 'DiagnosticReport',
            'outcome'               => FhirIngestEvent::OUTCOME_REFUSED,
            'refusal_code'          => 'unknown_patient',
            'refusal_detail'        => 'SOMEONE-ELSES-DETAIL',
            'http_status'           => 404,
            'created_at'            => now(),
        ]);
        ApiUsageLog::create([
            'integration_client_id' => $mine->client_id,
            'endpoint'              => 'api/fhir/R4/Observation',
            'method'                => 'POST',
            'response_status'       => 403,
            'response_time_ms'      => 41,
        ]);

        $page = $this->as($dev)->get('/developer/activity')->assertOk();

        $page->assertSee('consent_required')
            ->assertSee('no active consent grant from patient to facility abc')
            ->assertSee('Immunization')
            ->assertSee('OperationOutcome')
            ->assertSee('&quot;code&quot;: &quot;forbidden&quot;', false)
            ->assertSee('api/fhir/R4/Observation')
            ->assertDontSee('SOMEONE-ELSES-DETAIL')
            ->assertDontSee('DiagnosticReport');

        // The hashed identifier is not the Health ID and the Health ID is not on the page.
        $page->assertDontSee('OC-TST-001');
    }

    public function test_activity_filter_by_a_foreign_app_id_is_a_404(): void
    {
        $dev    = $this->developer();
        $other  = $this->developer();
        $mine   = $this->appFor($dev);
        $theirs = $this->appFor($other);

        $this->as($dev)->get('/developer/activity?app=' . $mine->id)->assertOk();
        $this->as($dev)->get('/developer/activity?app=' . $theirs->id)->assertNotFound();
        $this->as($dev)->get('/developer/quickstart?app=' . $theirs->id)->assertNotFound();
    }

    // ── D-4 quickstart ─────────────────────────────────────────────────────

    public function test_quickstart_uses_the_partners_own_sandbox_client_id_and_never_a_secret(): void
    {
        $dev = $this->developer();
        $app = $this->appFor($dev, ['client_id' => 'sandbox_quickstart_abc123', 'client_secret' => hash('sha256', 'sk_sandbox_PLAINTEXT')]);

        $page = $this->as($dev)->get('/developer/quickstart')->assertOk();

        $page->assertSee('sandbox_quickstart_abc123')
            ->assertSee(url('/api/v1/connect/auth/token'))
            ->assertSee(url('/api/fhir/R4/metadata'))
            ->assertSee('YOUR_CLIENT_SECRET')
            ->assertDontSee('sk_sandbox_PLAINTEXT')
            ->assertDontSee($app->client_secret)
            // Honest about what a facility-less sandbox app can and cannot do.
            ->assertSee('no_facility_in_token')
            ->assertSee('system/Observation.write')
            ->assertSee('/openapi.json')
            ->assertSee('/opescare.postman_collection.json');
    }

    public function test_quickstart_without_an_app_points_at_app_creation(): void
    {
        $dev = $this->developer();

        $this->as($dev)->get('/developer/quickstart')
            ->assertOk()
            ->assertSee(route('portals.developer.apps.create'));
    }

    public function test_new_routes_are_wired_and_named(): void
    {
        foreach ([
            'portals.developer.webhooks'        => '/developer/webhooks',
            'portals.developer.webhooks.store'  => '/developer/webhooks',
            'portals.developer.activity'        => '/developer/activity',
            'portals.developer.quickstart'      => '/developer/quickstart',
        ] as $name => $path) {
            $this->assertSame(url($path), route($name), $name);
        }
        $this->assertStringContainsString('/developer/webhooks/', route('portals.developer.webhooks.toggle', 'x'));
    }
}
