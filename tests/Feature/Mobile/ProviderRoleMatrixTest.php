<?php

namespace Tests\Feature\Mobile;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use App\Support\StaffCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The clinician API is role-gated by `capability:` (config/staff_capabilities.php).
 *
 * One provider session per demo role, then for a representative endpoint of
 * every capability: roles that lack it get 403 {error_code: role_forbidden}
 * and roles that hold it get PAST the gate — 2xx, 404 or 422 are all fine,
 * because the gate is what is under test, not the feature behind it. Writes a
 * refused role attempts leave no row behind.
 *
 * The map is keyed on `roles.name` (the users.role_id row), which is what the
 * middleware reads today; these tests are role-based, not posting-based.
 */
class ProviderRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** The five demo staff roles plus billing, which holds no clinical capability at all. */
    private const ROLES = ['doctor', 'nurse', 'pharmacist', 'receptionist', 'labtech', 'cashier'];

    private const HEALTH_ID = 'CM-HID-TEST-MATRIX-0001';

    private Facility $facility;

    private Patient $patient;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, string> */
    private array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();

        foreach (self::ROLES as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'matrix');
        }

        $this->patient = Patient::factory()->create([
            'health_id' => self::HEALTH_ID,
            'is_demo'   => (bool) config('demo.enabled'),
        ]);

        // Chart basis 5: the patient has granted THIS facility access, so a
        // clinician's chart read is a real 200 and the only 403 left on the
        // chart is the role gate.
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity', 'allergies', 'problems', 'encounters', 'vitals', 'prescriptions', 'diagnoses', 'orders'],
            'status'            => 'active',
            'expires_at'        => now()->addDay(),
        ]);
    }

    /**
     * A user whose role passes isProfessional(): a dashboard profile outside
     * the excluded set. Same recipe as ProviderScanTest.
     */
    private function professional(string $role, ?string $dashboardKey = null): User
    {
        $dashboardKey ??= $role;

        DB::table('dashboard_profiles')->updateOrInsert(['key' => $dashboardKey], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $dashboardKey])->save(); // not mass-assignable

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    private function hit(string $role, string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"])
            ->json($method, $path, $body);
    }

    /**
     * The matrix assertion: every role in $allowed gets past the gate, every
     * other demo role is refused with the role_forbidden shape naming $required.
     * Returns every response by role so a caller can look closer at one
     * without spending another request (throttled routes have a budget).
     *
     * @param  list<string>  $allowed
     * @param  list<string>  $required
     * @return array<string, TestResponse>
     */
    private function assertGate(string $method, string $path, array $allowed, array $required, array $body = []): array
    {
        $responses = [];

        foreach (self::ROLES as $role) {
            $response = $responses[$role] = $this->hit($role, $method, $path, $body);
            $label    = "{$role} {$method} {$path}";

            if (in_array($role, $allowed, true)) {
                $this->assertNotSame(403, $response->status(), "{$label} should pass the role gate, got 403: " . $response->getContent());
                $this->assertNotSame('role_forbidden', $response->json('error_code'), "{$label} was refused by the role gate");
            } else {
                $this->assertSame(403, $response->status(), "{$label} should be refused, got {$response->status()}: " . $response->getContent());
                $response->assertJsonPath('error_code', 'role_forbidden')
                    ->assertJsonPath('required', $required)
                    ->assertJsonPath('message', __('api.capability_forbidden'));
            }
        }

        return $responses;
    }

    // ── identify_patient ──────────────────────────────────────────────────

    public function test_identifying_a_patient_is_for_every_counter_and_clinical_role_but_not_billing(): void
    {
        $allowed = ['doctor', 'nurse', 'pharmacist', 'receptionist', 'labtech'];

        $this->assertGate('GET', '/api/mobile/doctor/patients/lookup?health_id=' . self::HEALTH_ID, $allowed, ['identify_patient']);
        $this->assertGate('POST', '/api/mobile/doctor/patients/scan', $allowed, ['identify_patient'], ['code' => 'not-a-health-id-code']);
    }

    // ── clinical_chart ────────────────────────────────────────────────────

    public function test_the_chart_is_for_clinicians_only(): void
    {
        $allowed = ['doctor', 'nurse'];

        $this->assertGate('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID, $allowed, ['clinical_chart']);
        $this->assertGate('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/allergies', $allowed, ['clinical_chart']);
        $this->assertGate('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vitals', $allowed, ['clinical_chart']);

        // With the consent grant in place the clinician's read is a genuine
        // 200, so the 403 the other roles got above is the gate and nothing else.
        $this->hit('doctor', 'GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID)
            ->assertOk()
            ->assertJsonPath('data.health_id', self::HEALTH_ID);
    }

    // ── prescribe / dispense ──────────────────────────────────────────────

    public function test_the_prescription_list_is_for_prescribers_and_the_pharmacy_counter(): void
    {
        $this->assertGate('GET', '/api/mobile/doctor/prescriptions', ['doctor', 'pharmacist'], ['prescribe', 'dispense']);
    }

    public function test_writing_and_voiding_a_prescription_is_for_prescribers_only(): void
    {
        // An empty body: the prescriber reaches validation (422); nobody else
        // reaches the controller at all.
        $this->assertGate('POST', '/api/mobile/doctor/prescriptions', ['doctor'], ['prescribe']);
        $this->assertGate('POST', '/api/mobile/doctor/prescriptions/' . Str::uuid() . '/void', ['doctor'], ['prescribe']);

        $this->hit('doctor', 'POST', '/api/mobile/doctor/prescriptions')->assertStatus(422);
    }

    public function test_a_non_prescriber_with_a_complete_script_leaves_no_prescription_behind(): void
    {
        $script = [
            'patient_id' => $this->patient->id,
            'items'      => [['drug_name' => 'Paracetamol 500 mg', 'dose' => '1 tablet', 'frequency' => 'TDS', 'duration_days' => 3]],
        ];

        foreach (['nurse', 'pharmacist', 'receptionist', 'labtech', 'cashier'] as $role) {
            $this->hit($role, 'POST', '/api/mobile/doctor/prescriptions', $script)
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden');

            $this->assertDatabaseMissing('prescriptions', ['prescribed_by' => $this->users[$role]->id]);
        }

        $this->assertDatabaseCount('prescriptions', 0);
    }

    // ── request_access ────────────────────────────────────────────────────

    public function test_consent_requests_are_for_clinicians_and_the_pharmacy(): void
    {
        $allowed = ['doctor', 'nurse', 'pharmacist'];

        $this->assertGate('GET', '/api/mobile/doctor/consent-requests', $allowed, ['request_access']);
        $this->assertGate('GET', '/api/mobile/doctor/consent-requests/options', $allowed, ['request_access']);
        $this->assertGate('POST', '/api/mobile/doctor/consent-requests', $allowed, ['request_access']);

        // Front desk, lab and billing cannot ask a patient in the facility's
        // name — even with a complete request nothing is recorded.
        $ask = ['health_id' => self::HEALTH_ID, 'purpose' => 'treatment', 'scope' => ['allergies'], 'duration_hours' => 24];

        foreach (['receptionist', 'labtech', 'cashier'] as $role) {
            $this->hit($role, 'POST', '/api/mobile/doctor/consent-requests', $ask)->assertForbidden();
        }

        $this->assertDatabaseCount('consent_requests', 0);
    }

    // ── schedule ──────────────────────────────────────────────────────────

    public function test_the_diary_is_for_clinicians_and_the_front_desk(): void
    {
        $allowed = ['doctor', 'nurse', 'receptionist'];

        $this->assertGate('GET', '/api/mobile/doctor/schedule', $allowed, ['schedule']);
        $this->assertGate('GET', '/api/mobile/doctor/appointment-requests', $allowed, ['schedule']);
        $this->assertGate('POST', '/api/mobile/doctor/appointment-requests/' . Str::uuid() . '/accept', $allowed, ['schedule']);
        $this->assertGate('POST', '/api/mobile/doctor/appointments/' . Str::uuid() . '/check-in', $allowed, ['schedule']);

        // A uuid nobody owns: the gate lets the front desk through to the 404.
        $this->hit('receptionist', 'POST', '/api/mobile/doctor/appointment-requests/' . Str::uuid() . '/accept')->assertNotFound();
    }

    // ── clinical_chart, lab_results ───────────────────────────────────────

    public function test_the_results_inbox_is_for_clinicians_and_the_lab(): void
    {
        $allowed = ['doctor', 'nurse', 'labtech'];

        $this->assertGate('GET', '/api/mobile/doctor/results-inbox', $allowed, ['clinical_chart', 'lab_results']);
        $this->assertGate('GET', '/api/mobile/doctor/results-inbox/counts', $allowed, ['clinical_chart', 'lab_results']);
        $this->assertGate('GET', '/api/mobile/doctor/orders/' . Str::uuid() . '/results', $allowed, ['clinical_chart', 'lab_results']);
    }

    // ── messaging ─────────────────────────────────────────────────────────

    public function test_messaging_is_for_everyone_who_talks_to_patients_but_not_billing(): void
    {
        $allowed = ['doctor', 'nurse', 'pharmacist', 'receptionist', 'labtech'];

        $this->assertGate('GET', '/api/mobile/doctor/messages/threads', $allowed, ['messaging']);
        $this->assertGate('GET', '/api/mobile/doctor/messages/unread-count', $allowed, ['messaging']);
        $this->assertGate('GET', '/api/mobile/doctor/messages/threads/1', $allowed, ['messaging']);
    }

    public function test_the_unread_count_badge_is_served_by_messaging_not_by_the_results_inbox(): void
    {
        // The URI was registered twice and the results-inbox handler used to
        // win. The app parses it as the MESSAGING badge.
        $this->hit('doctor', 'GET', '/api/mobile/doctor/messages/unread-count')
            ->assertOk()
            ->assertJsonStructure(['data' => ['unread_threads', 'unread_messages']])
            ->assertJsonMissingPath('data.unacknowledged_results');

        // A pharmacist holds messaging but neither clinical_chart nor
        // lab_results: the badge answers, the result counts refuse. That can
        // only be true if the two paths are gated by different files.
        $this->hit('pharmacist', 'GET', '/api/mobile/doctor/messages/unread-count')->assertOk();
        $this->hit('pharmacist', 'GET', '/api/mobile/doctor/results-inbox/counts')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');
    }

    // ── emergency_access ──────────────────────────────────────────────────

    public function test_break_glass_is_for_doctors_and_nurses_only(): void
    {
        $allowed = ['doctor', 'nurse'];

        // Break-glass is throttled 6/min and Laravel's middleware priority runs
        // that throttle BEFORE the role gate, so the six attempts above are
        // the whole budget for this test: read the clinician's answer from the
        // matrix rather than asking again. An empty body means the clinician
        // reached validation; nobody else reached the controller at all.
        $responses = $this->assertGate('POST', '/api/mobile/doctor/emergency-access', $allowed, ['emergency_access']);
        $responses['doctor']->assertStatus(422);
        $responses['nurse']->assertStatus(422);

        $this->assertGate('GET', '/api/mobile/doctor/emergency-access', $allowed, ['emergency_access']);
        $this->assertGate('GET', '/api/mobile/doctor/emergency-access/' . Str::uuid(), $allowed, ['emergency_access']);
    }

    public function test_a_receptionist_cannot_break_the_glass_even_with_a_complete_request(): void
    {
        $this->hit('receptionist', 'POST', '/api/mobile/doctor/emergency-access', [
            'patient'      => self::HEALTH_ID,
            'reason'       => 'Unconscious patient brought in by ambulance, no next of kin reachable.',
            'acknowledged' => true,
        ])->assertForbidden()->assertJsonPath('error_code', 'role_forbidden');

        $this->assertDatabaseMissing('emergency_access_events', ['patient_id' => $this->patient->id]);
        $this->assertDatabaseCount('emergency_access_events', 0);
    }

    // ── open to every professional ────────────────────────────────────────

    public function test_the_landing_and_own_account_routes_stay_open_to_every_professional(): void
    {
        foreach ([
            '/api/mobile/doctor/me',
            '/api/mobile/doctor/home',
            '/api/mobile/doctor/profile',
            '/api/mobile/provider/facilities',
            '/api/mobile/auth/provider/me',
        ] as $path) {
            $this->assertGate('GET', $path, self::ROLES, []);
        }
    }

    // ── the middleware's provider branch ──────────────────────────────────

    public function test_the_gate_reads_the_role_from_the_provider_token_not_from_a_web_session(): void
    {
        // No actingAs(): the only identity on the request is the bearer token,
        // so the refusal can only have come from provider_user_id.
        $this->hit('pharmacist', 'POST', '/api/mobile/doctor/prescriptions')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required', ['prescribe']);

        $this->hit('doctor', 'POST', '/api/mobile/doctor/prescriptions')->assertStatus(422);

        // A doctor's web session alongside a pharmacist's token: the token is
        // the actor on this surface, so the refusal stands.
        $this->actingAs($this->users['doctor'])
            ->withHeaders(['Authorization' => "Bearer {$this->bearer['pharmacist']}"])
            ->postJson('/api/mobile/doctor/prescriptions')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');
    }

    // ── login hardening ───────────────────────────────────────────────────

    public function test_login_refuses_a_professional_whose_role_holds_no_capability(): void
    {
        // insurance_reviewer passes isProfessional() (its dashboard profile is
        // not in the excluded set) but appears in no capability at all.
        $reviewer = $this->professional('insurance_reviewer');
        $reviewer->forceFill(['primary_facility_id' => $this->facility->id])->save();

        $this->assertSame([], StaffCapabilities::forRole('insurance_reviewer'));

        $this->postJson('/api/mobile/auth/provider/login', ['email' => $reviewer->email, 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('message', __('api.provider_role_not_clinical'));

        $this->assertDatabaseMissing('provider_access_tokens', ['user_id' => $reviewer->id]);
    }

    public function test_login_still_admits_any_role_that_holds_a_capability(): void
    {
        // Billing-only: no clinical route will admit a cashier, but the role
        // holds `billing`, so who may sign in is otherwise unchanged.
        foreach (['doctor', 'cashier'] as $role) {
            $user = $this->users[$role];
            $user->forceFill(['primary_facility_id' => $this->facility->id])->save();

            $this->postJson('/api/mobile/auth/provider/login', ['email' => $user->email, 'password' => 'password'])
                ->assertOk()
                ->assertJsonPath('provider.role', $role);
        }
    }
}
