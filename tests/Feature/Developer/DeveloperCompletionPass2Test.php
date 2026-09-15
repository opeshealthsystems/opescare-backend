<?php

namespace Tests\Feature\Developer;

use App\Http\Controllers\MedicalId\DeveloperExtController;
use App\Jobs\SendWebhookJob;
use App\Models\ApiUsageLog;
use App\Models\ConsentGrant;
use App\Models\DeveloperAccount;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ProductionAccessRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookEvent;
use App\Models\WebhookSubscription;
use App\Modules\Governance\Services\ConsentService;
use App\Services\Clinical\PrescriptionService;
use App\Services\JwtService;
use App\Services\Lab\LabDeskService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Developer portal, pass 2 (docs/DEVELOPER_DASHBOARD_AUDIT.md, "Pass 2"):
 *
 *   - LogApiUsage attributes from the bearer context, never from a header
 *   - analytics counts the partner's own calls only, and says "no calls yet"
 *   - the three documented webhook events fire from their domain writes,
 *     queued and HMAC-signed, and a failing endpoint can never fail the write
 *   - replay re-queues a failed delivery, own subscriptions only
 *   - production approval binds a facility (D-5) and keeps the requested
 *     scopes when approved_scopes is omitted (the json_encode(null) bug)
 *   - the consents page lists grants for the app's facility only; a foreign
 *     app id is a 404
 */
class DeveloperCompletionPass2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    // ── fixtures ───────────────────────────────────────────────────────────

    private function userWithRole(string $roleName, ?string $facilityId = null): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => $facilityId,
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $user->fresh();
    }

    private function developer(): User
    {
        $user = $this->userWithRole('developer', Facility::factory()->create()->id);

        DeveloperAccount::create([
            'display_name'       => 'Acme Health Systems',
            'email'              => $user->email,
            'status'             => 'active',
            'email_verified_at'  => now(),
            'api_terms_accepted' => true,
            'sandbox_only'       => true,
        ]);

        return $user;
    }

    private function appFor(User $developer, array $attrs = []): IntegrationClient
    {
        return IntegrationClient::factory()->create(array_merge([
            'created_by'  => $developer->email,
            'facility_id' => null,
            'environment' => 'sandbox',
        ], $attrs));
    }

    private function subscribe(IntegrationClient $app, array $events, string $url = 'https://partner.example.com/hooks'): WebhookSubscription
    {
        return WebhookSubscription::create([
            'client_id'         => $app->client_id,
            'facility_id'       => $app->facility_id,
            'callback_url'      => $url,
            'webhook_secret'    => 'whsec_' . Str::random(40),
            'subscribed_events' => $events,
            'status'            => 'active',
        ]);
    }

    private function patient(string $healthId): Patient
    {
        return Patient::factory()->create(['health_id' => $healthId, 'is_demo' => false]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa.verified' => true]);
    }

    private function bearerFor(IntegrationClient $client, array $scopes = ['inventory:write']): string
    {
        return app(JwtService::class)->issue([
            'sub'         => $client->client_id,
            'client_id'   => $client->client_id,
            'facility_id' => $client->facility_id,
            'environment' => 'sandbox',
            'scopes'      => $scopes,
        ]);
    }

    /** Validates the HMAC the job puts on the wire, exactly as a partner would. */
    private function signatureIsValid(ClientRequest $request, string $secret): bool
    {
        $sig = $request->header('X-OpesCare-Signature')[0] ?? '';
        if (! preg_match('/^t=(\d+),v1=([0-9a-f]{64})$/', $sig, $m)) {
            return false;
        }
        $expected = hash_hmac('sha256', $m[1] . '.' . $request->body(), $secret);

        return hash_equals($expected, $m[2]);
    }

    // ── 1. attribution ─────────────────────────────────────────────────────

    public function test_usage_is_attributed_from_the_bearer_token_and_a_spoofed_header_is_ignored(): void
    {
        // Usage rows are written after the response, outside this test's
        // transaction, so rows from earlier tests can already be in the table.
        // Judge only what this test's requests logged.
        $since = now()->subSecond();

        $facility = Facility::factory()->create();
        $client   = IntegrationClient::factory()->create([
            'facility_id' => $facility->id,
            'scopes'      => ['inventory:write'],
            'status'      => 'active',
        ]);

        $this->withHeaders([
            'Authorization'           => 'Bearer ' . $this->bearerFor($client),
            'X-Integration-Client-Id' => 'somebody_else',
            'X-Facility-Id'           => (string) Str::uuid(),
        ])->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', []);

        $this->assertDatabaseHas('api_usage_logs', [
            'integration_client_id' => $client->client_id,
            'facility_id'           => $facility->id,
        ]);
        $this->assertDatabaseMissing('api_usage_logs', ['integration_client_id' => 'somebody_else']);

        // No token → nothing to attribute to → nothing logged, whatever the header says.
        $this->withHeaders(['X-Integration-Client-Id' => 'somebody_else'])
            ->postJson('/api/v1/connect/inventory/pharmacy-stock/sync', []);

        $this->assertDatabaseMissing('api_usage_logs', ['integration_client_id' => 'somebody_else']);
        // Every row that exists belongs to the token's client — nothing was attributed elsewhere.
        $this->assertSame(0, ApiUsageLog::where('logged_at', '>=', $since)->where('integration_client_id', '!=', $client->client_id)->count());
        $this->assertGreaterThanOrEqual(1, ApiUsageLog::where('integration_client_id', $client->client_id)->count());
    }

    // ── 2. analytics ───────────────────────────────────────────────────────

    public function test_analytics_counts_only_the_partners_own_calls(): void
    {
        $dev  = $this->developer();
        $mine = $this->appFor($dev);
        $them = IntegrationClient::factory()->create(['created_by' => 'other@example.com']);

        foreach ([200, 200, 500] as $status) {
            ApiUsageLog::create(['integration_client_id' => $mine->client_id, 'endpoint' => 'api/fhir/R4/Observation', 'method' => 'POST', 'response_status' => $status, 'response_time_ms' => 120]);
        }
        foreach (range(1, 5) as $i) {
            ApiUsageLog::create(['integration_client_id' => $them->client_id, 'endpoint' => 'api/fhir/R4/Observation', 'method' => 'POST', 'response_status' => 200, 'response_time_ms' => 10]);
        }

        $response = $this->as($dev)->get('/developer/analytics')->assertOk();

        $totals = $response->viewData('totals');
        $this->assertSame(3, $totals['requests']);
        $this->assertSame(1, $totals['errors']);
        $this->assertSame(1, $totals['server_errors']);
        $this->assertSame(120, $totals['p50_ms']);
        $this->assertSame(120, $totals['p95_ms']);
        $this->assertSame(33.3, $totals['error_rate']);

        $byApp = $response->viewData('byApp');
        $this->assertSame(3, (int) $byApp[$mine->client_id]);
        $this->assertArrayNotHasKey($them->client_id, $byApp->all());

        // A foreign app id is a 404, never a 403 (the id must not be confirmed).
        $this->as($dev)->get('/developer/analytics?app=' . $them->id)->assertNotFound();
    }

    public function test_analytics_with_no_calls_says_so_instead_of_inventing_numbers(): void
    {
        $dev = $this->developer();
        $this->appFor($dev);

        $response = $this->as($dev)->get('/developer/analytics')->assertOk();
        $response->assertSee(__('developer_ext.an_empty'));

        $totals = $response->viewData('totals');
        $this->assertSame(0, $totals['requests']);
        $this->assertNull($totals['error_rate']);
        $this->assertNull($totals['p50_ms']);
        $this->assertNull($totals['p95_ms']);
    }

    // ── 3. webhook dispatch from the domain writes ─────────────────────────

    public function test_a_consent_grant_enqueues_a_webhook_for_the_facilitys_subscriber_only(): void
    {
        Queue::fake();

        $facility = Facility::factory()->create();
        $other    = Facility::factory()->create();
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['facility_id' => $facility->id, 'environment' => 'production']);
        $sub      = $this->subscribe($app, ['consent.granted']);

        $elsewhere = $this->subscribe(
            IntegrationClient::factory()->create(['facility_id' => $other->id]),
            ['consent.granted'],
            'https://elsewhere.example.com/hooks'
        );

        $patient = $this->patient('OC-WH-0001');
        $user    = User::factory()->create();

        $consents = app(ConsentService::class);
        $request  = $consents->requestConsent($patient->id, $facility->id, $user->id, 'treatment', ['patient.summary'], 60);
        $grant    = $consents->approveConsent($request->id, $user->id);

        Queue::assertPushedOn('webhooks', SendWebhookJob::class, function (SendWebhookJob $job) use ($sub, $grant) {
            return $job->subscriptionId === $sub->id
                && $job->payload['event_type'] === 'consent.granted'
                && $job->payload['resource']['grant_id'] === (string) $grant->id
                && $job->payload['resource']['health_id'] === 'OC-WH-0001'
                && ! empty($job->payload['event_id']);
        });
        Queue::assertNotPushed(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->subscriptionId === $elsewhere->id);

        $this->assertDatabaseHas('webhook_events', ['event_type' => 'consent.granted', 'facility_id' => $facility->id]);
    }

    public function test_a_prescription_issue_enqueues_prescription_issued(): void
    {
        Queue::fake();

        $facility = Facility::factory()->create();
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['facility_id' => $facility->id, 'environment' => 'production']);
        $sub      = $this->subscribe($app, ['prescription.issued']);
        $patient  = $this->patient('OC-WH-0002');
        $doctor   = User::factory()->create();

        $rx = app(PrescriptionService::class)->issue([
            'patient_id'    => $patient->id,
            'facility_id'   => $facility->id,
            'prescribed_by' => $doctor->id,
            'items'         => [['drug_name' => 'Paracetamol 500mg', 'dose' => '500mg', 'frequency' => 'TDS', 'duration_days' => 3, 'quantity' => 9]],
        ], $doctor->id);

        Queue::assertPushedOn('webhooks', SendWebhookJob::class, fn (SendWebhookJob $job) =>
            $job->subscriptionId === $sub->id
            && $job->payload['event_type'] === 'prescription.issued'
            && $job->payload['resource']['prescription_id'] === (string) $rx->id
            && $job->payload['resource']['health_id'] === 'OC-WH-0002'
            && $job->payload['resource']['item_count'] === 1
        );
    }

    public function test_a_lab_release_enqueues_lab_result_released(): void
    {
        Queue::fake();

        $facility = Facility::factory()->create();
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['facility_id' => $facility->id, 'environment' => 'production']);
        $sub      = $this->subscribe($app, ['lab_result.released']);
        $patient  = $this->patient('OC-WH-0003');
        $tech     = User::factory()->create(['primary_facility_id' => $facility->id]);

        $order = LabOrder::factory()->create([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'status'      => 'in_progress',
            'resulted_at' => null,
            'is_demo'     => false,
        ]);
        LabResult::create([
            'lab_order_id'   => $order->id,
            'patient_id'     => $patient->id,
            'parameter_name' => 'Haemoglobin',
            'value'          => '13.2',
            'unit'           => 'g/dL',
            'resulted_at'    => now(),
            'is_demo'        => false,
        ]);

        app(LabDeskService::class)->release($order, $tech->id);

        Queue::assertPushedOn('webhooks', SendWebhookJob::class, fn (SendWebhookJob $job) =>
            $job->subscriptionId === $sub->id
            && $job->payload['event_type'] === 'lab_result.released'
            && $job->payload['resource']['lab_order_id'] === (string) $order->id
            && $job->payload['resource']['health_id'] === 'OC-WH-0003'
        );
    }

    public function test_delivery_is_hmac_signed_with_the_subscription_secret(): void
    {
        Http::fake(['https://partner.example.com/*' => Http::response('ok', 200)]);

        $facility = Facility::factory()->create();
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['facility_id' => $facility->id]);
        $sub      = $this->subscribe($app, ['consent.granted']);

        $payload = ['event_id' => (string) Str::uuid(), 'event_type' => 'consent.granted', 'schema_version' => 'v1', 'resource' => ['grant_id' => 'x']];
        (new SendWebhookJob($sub->id, $payload))->handle();

        Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://partner.example.com/hooks'
            && $this->signatureIsValid($r, $sub->webhook_secret)
            && $r->header('X-OpesCare-Event-Id')[0] === $payload['event_id']);

        $this->assertDatabaseHas('webhook_delivery_logs', [
            'event_id'                => $payload['event_id'],
            'webhook_subscription_id' => $sub->id,
            'status'                  => 'delivered',
            'http_status_code'        => 200,
        ]);
    }

    public function test_a_failing_endpoint_logs_a_failed_delivery_and_never_fails_the_write(): void
    {
        // QUEUE_CONNECTION=sync in phpunit.xml: the job runs inline, inside the write.
        Http::fake([
            'https://down.example.com/*' => Http::response('boom', 500),
            'https://gone.example.com/*' => fn () => throw new ConnectionException('refused'),
        ]);

        $facility = Facility::factory()->create();
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['facility_id' => $facility->id, 'environment' => 'production']);
        $down     = $this->subscribe($app, ['prescription.issued'], 'https://down.example.com/hooks');
        $gone     = $this->subscribe($app, ['prescription.issued'], 'https://gone.example.com/hooks');
        $patient  = $this->patient('OC-WH-0004');
        $doctor   = User::factory()->create();

        $rx = app(PrescriptionService::class)->issue([
            'patient_id'    => $patient->id,
            'facility_id'   => $facility->id,
            'prescribed_by' => $doctor->id,
            'items'         => [['drug_name' => 'Amoxicillin 500mg', 'dose' => '500mg', 'frequency' => 'TDS', 'duration_days' => 5]],
        ], $doctor->id);

        // The clinical record stands.
        $this->assertTrue(Prescription::whereKey($rx->id)->exists());
        $this->assertSame('active', $rx->fresh()->status);

        // Both failures are on the log, neither surfaced to the caller.
        $this->assertDatabaseHas('webhook_delivery_logs', ['webhook_subscription_id' => $down->id, 'event_type' => 'prescription.issued', 'status' => 'failed', 'http_status_code' => 500]);
        $this->assertDatabaseHas('webhook_delivery_logs', ['webhook_subscription_id' => $gone->id, 'event_type' => 'prescription.issued', 'status' => 'failed']);
    }

    // ── 4. replay ──────────────────────────────────────────────────────────

    public function test_replay_requeues_a_failed_delivery_for_the_partners_own_subscription_only(): void
    {
        Queue::fake();

        $dev  = $this->developer();
        $app  = $this->appFor($dev, ['facility_id' => Facility::factory()->create()->id]);
        $sub  = $this->subscribe($app, ['consent.granted']);
        $evId = (string) Str::uuid();
        WebhookEvent::create(['event_type' => 'consent.granted', 'schema_version' => 'v1', 'payload' => ['event_type' => 'consent.granted', 'resource' => []], 'facility_id' => $app->facility_id]);

        $failed = WebhookDeliveryLog::create([
            'event_id'                => $evId,
            'webhook_subscription_id' => $sub->id,
            'endpoint_url'            => $sub->callback_url,
            'event_type'              => 'consent.granted',
            'payload'                 => ['event_id' => $evId, 'event_type' => 'consent.granted', 'schema_version' => 'v1', 'resource' => ['grant_id' => 'g1']],
            'status'                  => 'failed',
            'attempts'                => 3,
        ]);

        // The button is on the page for a failed row.
        $this->as($dev)->get('/developer/webhooks')->assertOk()
            ->assertSee(route('portals.developer.webhooks.replay', $failed->id), false);

        $this->as($dev)->post(route('portals.developer.webhooks.replay', $failed->id))
            ->assertRedirect(route('portals.developer.webhooks'));

        Queue::assertPushedOn('webhooks', SendWebhookJob::class, fn (SendWebhookJob $job) =>
            $job->subscriptionId === $sub->id && $job->payload['event_id'] === $evId
        );
        $this->assertSame('pending', $failed->fresh()->status);
        $this->assertDatabaseHas('webhook_replays', ['webhook_event_id' => $evId, 'webhook_endpoint_id' => $sub->id]);
        $this->assertDatabaseHas('audit_events', ['action_type' => 'developer_webhook_replayed', 'resource_id' => $failed->id]);

        // Somebody else's failed delivery: 404, and nothing queued for it.
        $theirs = $this->subscribe(IntegrationClient::factory()->create(['created_by' => 'other@example.com']), ['consent.granted'], 'https://theirs.example.com/h');
        $theirLog = WebhookDeliveryLog::create([
            'event_id' => (string) Str::uuid(), 'webhook_subscription_id' => $theirs->id, 'endpoint_url' => $theirs->callback_url,
            'event_type' => 'consent.granted', 'payload' => ['resource' => []], 'status' => 'failed', 'attempts' => 3,
        ]);
        $this->as($dev)->post(route('portals.developer.webhooks.replay', $theirLog->id))->assertNotFound();
        Queue::assertNotPushed(SendWebhookJob::class, fn (SendWebhookJob $job) => $job->subscriptionId === $theirs->id);

        // A delivered row cannot be replayed.
        $ok = WebhookDeliveryLog::create([
            'event_id' => (string) Str::uuid(), 'webhook_subscription_id' => $sub->id, 'endpoint_url' => $sub->callback_url,
            'event_type' => 'consent.granted', 'payload' => ['resource' => []], 'status' => 'delivered', 'attempts' => 1,
        ]);
        $this->as($dev)->post(route('portals.developer.webhooks.replay', $ok->id))
            ->assertRedirect(route('portals.developer.webhooks'))
            ->assertSessionHas('error');
    }

    // ── 5. admin approval (D-5 + the approved_scopes null-write) ───────────

    private function pendingRequest(User $dev, IntegrationClient $app, array $scopes): ProductionAccessRequest
    {
        return ProductionAccessRequest::create([
            'developer_account_id'  => DeveloperAccount::where('email', $dev->email)->firstOrFail()->id,
            'integration_client_id' => $app->client_id,
            'use_case'              => 'Push lab results from our LIS',
            'requested_scopes'      => $scopes,
            'handles_patient_data'  => true,
            'terms_accepted'        => true,
            'terms_version'         => '1.0',
            'status'                => 'pending',
        ]);
    }

    public function test_approval_binds_the_facility_and_keeps_the_requested_scopes_when_none_are_given(): void
    {
        $facility = Facility::factory()->create(['name' => 'Hôpital Laquintinie de Douala']);
        $dev      = $this->developer();
        $app      = $this->appFor($dev, ['scopes' => ['health_id:read']]);
        $sub      = $this->subscribe($app, ['consent.granted']); // registered while facility-less
        $scopes   = ['system/Observation.write', 'system/DiagnosticReport.write'];
        $req      = $this->pendingRequest($dev, $app, $scopes);
        $admin    = $this->userWithRole('super_admin');

        $this->as($admin)
            ->from('/admin/developer/production-requests')
            ->post(route('portals.admin.developer.production_requests.approve', $req->id), [
                'facility_name' => 'Hôpital Laquintinie de Douala',
                // approved_scopes deliberately omitted
            ])
            ->assertRedirect('/admin/developer/production-requests')
            ->assertSessionHas('success');

        $app->refresh();
        $this->assertSame('production', $app->environment);
        $this->assertSame($facility->id, $app->facility_id);
        $this->assertSame($scopes, DeveloperExtController::scopesOf($app));
        $this->assertNotSame('null', $app->getRawOriginal('scopes'));

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertSame($scopes, $req->approved_scopes);

        // The facility-less subscription followed the app into its facility.
        $this->assertSame($facility->id, $sub->fresh()->facility_id);
    }

    public function test_approval_with_explicit_scopes_uses_them(): void
    {
        $facility = Facility::factory()->create(['name' => 'Clinique Bastos']);
        $dev      = $this->developer();
        $app      = $this->appFor($dev);
        $req      = $this->pendingRequest($dev, $app, ['system/Observation.write', 'system/Immunization.write']);
        $admin    = $this->userWithRole('super_admin');

        $this->as($admin)->from('/admin/developer/production-requests')
            ->post(route('portals.admin.developer.production_requests.approve', $req->id), [
                'facility_name'   => 'Clinique Bastos',
                'approved_scopes' => ['system/Observation.write'],
            ])->assertSessionHas('success');

        $this->assertSame(['system/Observation.write'], DeveloperExtController::scopesOf($app->fresh()));
        $this->assertSame($facility->id, $app->fresh()->facility_id);
    }

    public function test_approval_refuses_an_unknown_or_ambiguous_facility_and_changes_nothing(): void
    {
        Facility::factory()->count(2)->create(['name' => 'Centre Médical Twin']);
        $dev   = $this->developer();
        $app   = $this->appFor($dev);
        $req   = $this->pendingRequest($dev, $app, ['system/Observation.write']);
        $admin = $this->userWithRole('super_admin');

        // Missing → validation error.
        $this->as($admin)->from('/admin/developer/production-requests')
            ->post(route('portals.admin.developer.production_requests.approve', $req->id), [])
            ->assertSessionHasErrors('facility_name');

        // Unknown → refused with a flash.
        $this->as($admin)->from('/admin/developer/production-requests')
            ->post(route('portals.admin.developer.production_requests.approve', $req->id), ['facility_name' => 'Nowhere Clinic'])
            ->assertSessionHas('error', __('developer_ext.flash_approve_facility_not_found'));

        // Ambiguous → refused with a flash.
        $this->as($admin)->from('/admin/developer/production-requests')
            ->post(route('portals.admin.developer.production_requests.approve', $req->id), ['facility_name' => 'Centre Médical Twin'])
            ->assertSessionHas('error', __('developer_ext.flash_approve_facility_ambiguous'));

        $this->assertSame('pending', $req->fresh()->status);
        $this->assertSame('sandbox', $app->fresh()->environment);
        $this->assertNull($app->fresh()->facility_id);
    }

    public function test_the_admin_approval_page_is_reachable_from_the_admin_nav(): void
    {
        $dev = $this->developer();
        $this->pendingRequest($dev, $this->appFor($dev), ['system/Observation.write']);

        $admin = $this->userWithRole('super_admin');
        $this->as($admin)->get('/admin/developer/production-requests')->assertOk()
            ->assertSee(route('portals.admin.developer.production_requests'), false)
            ->assertSee('name="facility_name"', false);
    }

    // ── 6. consents page ───────────────────────────────────────────────────

    public function test_consents_page_lists_grants_for_the_apps_facility_only_by_health_id(): void
    {
        $facility = Facility::factory()->create();
        $other    = Facility::factory()->create();
        $dev      = $this->developer();
        $bound    = $this->appFor($dev, ['facility_id' => $facility->id, 'environment' => 'production']);
        $sandbox  = $this->appFor($dev);

        $p1 = $this->patient('OC-CS-0001');
        $p2 = $this->patient('OC-CS-0002');
        $p3 = $this->patient('OC-CS-0003');

        $grant = fn (Patient $p, Facility $f, array $extra = []) => ConsentGrant::create(array_merge([
            'patient_id' => $p->id, 'facility_id' => $f->id, 'authorizing_actor' => 'patient',
            'scope' => ['clinical:write'], 'status' => 'active', 'expires_at' => now()->addMonth(), 'is_demo' => false,
        ], $extra));

        $grant($p1, $facility);
        $grant($p2, $facility, ['status' => 'revoked']);            // not active → hidden
        $grant($p3, $other);                                         // other facility → hidden

        $response = $this->as($dev)->get('/developer/consents')->assertOk();
        $response->assertSee('OC-CS-0001');
        $response->assertDontSee('OC-CS-0002');
        $response->assertDontSee('OC-CS-0003');
        $response->assertSee(__('developer_ext.cs_facilityless_title'));   // the sandbox app's explainer
        $response->assertSee(route('docs.api') . '#consent', false);

        // Filtering to the sandbox app shows the explainer only, no grants.
        $this->as($dev)->get('/developer/consents?app=' . $sandbox->id)->assertOk()
            ->assertDontSee('OC-CS-0001')
            ->assertSee(__('developer_ext.cs_facilityless_title'));

        // A foreign app id is a 404.
        $foreign = IntegrationClient::factory()->create(['created_by' => 'other@example.com', 'facility_id' => $other->id]);
        $this->as($dev)->get('/developer/consents?app=' . $foreign->id)->assertNotFound();
    }

    public function test_new_routes_are_wired_named_and_in_the_sidebars(): void
    {
        $this->assertSame('/developer/consents', route('portals.developer.consents', [], false));
        $this->assertSame('/developer/webhooks/deliveries/abc/replay', route('portals.developer.webhooks.replay', 'abc', false));

        foreach (['developer', 'webhook_manager', 'api_partner_admin'] as $partial) {
            $blade = file_get_contents(resource_path("views/partials/sidebars/{$partial}.blade.php"));
            $this->assertStringContainsString("route('portals.developer.consents')", $blade, $partial);
        }

        $nav = file_get_contents(resource_path('views/portals/admin/_admin_nav.blade.php'));
        $this->assertStringContainsString("route('portals.admin.developer.production_requests')", $nav);
    }
}
