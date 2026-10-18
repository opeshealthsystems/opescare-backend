<?php

namespace Tests\Feature\Pilot;

use App\Models\AccessLog;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The pilot's core clinical path, end to end, over the real HTTP routes.
 *
 *   OpesCare approves a hospital lead  → the contact accepts the invite
 *   → the hospital admin invites a doctor → the doctor accepts and signs in
 *   → looks a patient up by Health ID (web desk and app) and sees nothing
 *   → asks for consent (web desk) → the PATIENT approves on the phone
 *   → the doctor opens the chart; the read is in the patient's access log
 *   → the patient revokes; the chart closes again
 *   → break-glass opens the emergency profile, is logged, reaches the patient.
 *
 * Setup that has no route (the platform admin, the patient's record) is made
 * directly; everything after it goes through the routes the product uses.
 * Recorded in docs/certification/PILOT_WALKTHROUGH_2026-10-18.md.
 */
class PilotClinicalJourneyTest extends TestCase
{
    use RefreshDatabase;
    use WithMobileAuth;

    private const PASSWORD = 'pilot-pass-2026';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();
        Http::fake();
    }

    public function test_a_new_doctor_at_a_new_hospital_reaches_a_patient_chart_only_through_consent(): void
    {
        // ── 0. Setup without a route: the approver and the patient ──────────
        $platformAdmin = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $platformAdmin->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $platformAdmin->save();

        $patient = Patient::create([
            'first_name'    => 'Esther',
            'last_name'     => 'Ngono',
            'sex'           => 'female',
            'date_of_birth' => '1988-04-12',
            'health_id'     => 'OC-CMR-PLT1-AB23-CD45',
        ]);
        User::factory()->create(['patient_id' => $patient->id, 'status' => 'active']);
        $patientAuth = $this->mobileAuthHeaders($patient);

        // ── 1. OpesCare approves the hospital lead ──────────────────────────
        $lead = Lead::create([
            'name' => 'Paul Ekane', 'email' => 'pilot.admin@example.test', 'phone' => '+237699000222',
            'organization_name' => 'Pilot Test Hospital Bonamoussadi', 'organization_type' => 'facility',
            'source' => 'request_demo', 'status' => 'qualified',
        ]);

        $this->actingAs($platformAdmin)->withSession(['mfa.verified' => true])
            ->post(route('portals.admin.leads.convert.store', $lead), [
                'facility_type' => 'hospital', 'name' => 'Pilot Test Hospital Bonamoussadi',
                'city' => 'Douala', 'region' => 'Littoral', 'address' => 'Carrefour Bonamoussadi',
            ])->assertSessionHasNoErrors()->assertSessionHas('invite_link');

        $adminInviteLink = session('invite_link');
        $hospital = Facility::withoutGlobalScopes()->findOrFail($lead->refresh()->converted_facility_id);
        $this->flushAuth();

        // ── 2. The hospital contact accepts and signs in ────────────────────
        $this->acceptInvite($adminInviteLink, 'Paul Ekane');
        $hospitalAdmin = User::where('email', 'pilot.admin@example.test')->firstOrFail();
        $this->assertSame('facility_admin', $hospitalAdmin->role?->name);
        $this->signIn('pilot.admin@example.test');

        // ── 3. The hospital admin invites a doctor through the team page ────
        $this->get(route('portals.facility.team'))->assertOk();
        $this->post(route('portals.facility.team.invite'), [
            'email' => 'dr.mbarga@example.test', 'name' => 'Dr Joel Mbarga', 'role' => 'doctor',
        ])->assertRedirect(route('portals.facility.team'))->assertSessionHas('invite_link');
        $doctorInviteLink = session('invite_link');
        $this->post(route('logout'));
        $this->flushAuth();

        // ── 4. The doctor accepts, signs in, lands without a selector ───────
        $this->acceptInvite($doctorInviteLink, 'Joel Mbarga');
        $doctor = User::where('email', 'dr.mbarga@example.test')->firstOrFail();
        $this->assertSame($hospital->id, $doctor->primary_facility_id);
        // The same account signs in to the phone app (provider token). Done
        // first: the app's login runs Auth::attempt + logout inside this one
        // test process, which would otherwise end the web session below.
        $providerToken = $this->postJson('/api/mobile/auth/provider/login', [
            'email' => 'dr.mbarga@example.test', 'password' => self::PASSWORD,
        ])->assertOk()->json('access_token');
        $provider = ['Authorization' => "Bearer {$providerToken}"];
        $this->signIn('dr.mbarga@example.test');

        $landing = $this->get(route('portals.staff'));
        $this->assertFalse($landing->isRedirect(route('select-facility')), 'one posting: no facility selector');
        $landing->assertOk();

        // The sidebar carries the entry point used below.
        $landing->assertSee(route('portals.chart.index'), false);

        // ── 5. Health ID lookup with no relationship: nothing is shown ──────
        $this->get(route('portals.chart.index', ['health_id' => $patient->health_id]))
            ->assertOk()
            ->assertSee(__('chart.not_found'))
            ->assertSee(route('portals.chart.consent_request'), false)
            ->assertDontSee('Ngono');
        $this->get(route('portals.chart.show', $patient->id))->assertNotFound();

        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $patient->id, 'facility_id' => $hospital->id, 'actor_id' => $doctor->id,
        ]);

        // The app's identify step confirms identity only — no chart.

        $this->api($provider, 'getJson', '/api/mobile/doctor/patients/lookup?health_id=' . urlencode($patient->health_id))
            ->assertOk()->assertJsonMissingPath('data.allergies');
        $this->api($provider, 'getJson', "/api/mobile/doctor/patients/{$patient->id}")
            ->assertForbidden();

        // ── 6. The doctor asks for consent from the desk ────────────────────
        $first = $this->post(route('portals.chart.consent_request'), [
            'health_id' => $patient->health_id, 'purpose' => 'treatment',
            'justification' => 'Seen in outpatients today.',
        ]);
        $this->assertNull(session('errors'), (string) session('errors'));
        $this->assertSame(route('portals.chart.index', ['health_id' => $patient->health_id]), $first->headers->get('Location'));
        $first->assertRedirect()->assertSessionHas('success', __('chart.consent_requested'));

        $ask = ConsentRequest::where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame($hospital->id, $ask->requesting_facility_id, 'facility comes from the session');
        $this->assertSame($doctor->id, $ask->requesting_user_id);

        // An unknown Health ID reads exactly the same and writes nothing.
        $this->post(route('portals.chart.consent_request'), [
            'health_id' => 'OC-CMR-ZZZZ-ZZZZ-ZZZZ', 'purpose' => 'treatment',
        ])->assertSessionHas('success', __('chart.consent_requested'));
        $this->assertSame(1, ConsentRequest::count());

        // The facility's outbox in the app shows the same ask.
        $this->api($provider, 'getJson', '/api/mobile/doctor/consent-requests')
            ->assertOk()->assertJsonPath('data.0.id', $ask->id);

        // ── 7. The patient approves on the phone ────────────────────────────
        $inbox = $this->api($patientAuth, 'getJson', '/api/mobile/consent-requests')->assertOk();
        $this->assertContains($ask->id, collect($inbox->json())->flatten()->all());

        $grantId = $this->api($patientAuth, 'postJson', "/api/mobile/consent-requests/{$ask->id}/approve")
            ->assertOk()->json('consent_grant_id');
        $this->assertSame('active', ConsentGrant::findOrFail($grantId)->status);

        // ── 8. The doctor opens the chart ───────────────────────────────────
        $this->get(route('portals.chart.index', ['health_id' => $patient->health_id]))
            ->assertRedirect(route('portals.chart.show', $patient->id));
        $this->get(route('portals.chart.show', $patient->id))->assertOk()->assertSee('Ngono');
        $this->get(route('portals.chart.show', [$patient->id, 'tab' => 'encounters']))->assertOk();
        $this->api($provider, 'getJson', "/api/mobile/doctor/patients/{$patient->id}")->assertOk();

        // ── 9. The read is in the patient's access log ──────────────────────
        $logs = $this->api($patientAuth, 'getJson', '/api/mobile/access-logs')->assertOk();
        $this->assertStringContainsString('Pilot Test Hospital Bonamoussadi', $logs->getContent());
        $this->assertTrue(AccessLog::where('patient_id', $patient->id)->where('actor_id', $doctor->id)
            ->where('purpose', 'treatment:consent_grant')->exists(), 'the read is logged under the consent basis');

        // ── 10. Revoke closes access ────────────────────────────────────────
        $this->api($patientAuth, 'postJson', "/api/mobile/consents/{$grantId}/revoke")->assertOk();
        $this->get(route('portals.chart.show', $patient->id))->assertNotFound();
        $this->api($provider, 'getJson', "/api/mobile/doctor/patients/{$patient->id}")->assertForbidden();

        // ── 11. Expiry closes access too ────────────────────────────────────
        $this->post(route('portals.chart.consent_request'), [
            'health_id' => $patient->health_id, 'purpose' => 'continuity_of_care',
        ])->assertSessionHas('success');
        $second = ConsentRequest::where('patient_id', $patient->id)->latest()->firstOrFail();
        $this->assertNotSame($ask->id, $second->id);
        $grant2 = $this->api($patientAuth, 'postJson', "/api/mobile/consent-requests/{$second->id}/approve")->assertOk()->json('consent_grant_id');
        $this->get(route('portals.chart.show', $patient->id))->assertOk();
        ConsentGrant::whereKey($grant2)->update(['expires_at' => now()->subMinute()]);
        $this->get(route('portals.chart.show', $patient->id))->assertNotFound();

        // ── 12. Break-glass: opens, is logged, reaches the patient ──────────
        $glass = $this->api($provider, 'postJson', '/api/mobile/doctor/emergency-access', [
            'patient'      => $patient->health_id,
            'reason'       => 'Unconscious on arrival, needs allergy and medication history now.',
            'acknowledged' => true,
        ]);
        $glass->assertSuccessful();
        $grantUuid = $glass->json('data.id') ?? $glass->json('data.grant.id') ?? $glass->json('grant.id');
        $this->assertNotNull($grantUuid, 'break-glass returns its grant: ' . $glass->getContent());

        $this->api($provider, 'getJson', "/api/mobile/doctor/emergency-access/{$grantUuid}/profile")->assertOk();

        $patientView = $this->api($patientAuth, 'getJson', '/api/mobile/emergency-access')->assertOk();
        $this->assertStringContainsString($grantUuid, $patientView->getContent(), 'the patient sees the override');

        // The patient is told (bell + push), and the override is on the record.
        Notification::assertSentTo($patient, \App\Notifications\EmergencyAccessAlertNotification::class);
        $this->assertTrue(AccessLog::where('patient_id', $patient->id)->where('actor_id', $doctor->id)
            ->where('purpose', 'like', '%emergency%')->exists(), 'break-glass is in the access log');
    }

    /** One API call with a bearer, without leaking the header into later web requests. */
    private function api(array $headers, string $method, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        $response = $this->withHeaders($headers)->{$method}($uri, $data);
        $this->flushHeaders();

        return $response;
    }

    private function acceptInvite(string $link, string $name): void
    {
        $token = (string) str($link)->afterLast('/');
        $this->get(route('invite.accept', $token))->assertOk();
        $this->post(route('invite.accept.submit', $token), [
            'name' => $name, 'phone' => '+237600000001',
            'password' => self::PASSWORD, 'confirm_password' => self::PASSWORD, 'accept_terms' => '1',
        ])->assertRedirect(route('login'));
    }

    private function signIn(string $email): void
    {
        $this->post(route('login.submit'), ['email' => $email, 'password' => self::PASSWORD])
            ->assertRedirect();
        $this->assertAuthenticated();
    }

    private function flushAuth(): void
    {
        auth()->logout();
        $this->flushSession();
    }
}
