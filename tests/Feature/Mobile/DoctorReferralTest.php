<?php

namespace Tests\Feature\Mobile;

use App\Enums\PatientReferralRequestStatus;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PatientReferralRequest;
use App\Models\ReferralAccessGrant;
use App\Models\ReferralCase;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * MobileDoctorReferralController — the clinician-to-clinician referral surface.
 *
 * A referral is a disclosure decision, so the tests below are mostly about who
 * decided what, and on whose authority:
 *
 *   • the referring facility and the referring clinician come from the token
 *     and from nowhere else — a `referring_facility_id` in the body is not read;
 *   • the RECEIVING facility's own ConsentGrant is the only thing that can open
 *     the record package, and the referring facility's grant never stands in
 *     for it;
 *   • the package is the intersection of what the clinician asked for and what
 *     the patient granted, and the access grant never outlives the consent;
 *   • status only moves along TRANSITIONS, an illegal move is 409, and nothing
 *     is ever deleted.
 */
class DoctorReferralTest extends TestCase
{
    use RefreshDatabase;

    private const HEALTH_ID = 'CM-HID-TEST-REFR-0001';

    /** The token's facility — the referring side. */
    private Facility $facilityA;

    /** The receiving side. */
    private Facility $facilityB;

    /** A facility this session has nothing to do with. */
    private Facility $facilityC;

    private Patient $patient;

    private User $doctorA;

    private User $doctorB;

    private string $bearerA;

    private string $bearerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facilityA = Facility::factory()->create(['name' => 'Referring District Hospital']);
        $this->facilityB = Facility::factory()->create(['name' => 'Receiving Regional Hospital']);
        $this->facilityC = Facility::factory()->create(['name' => 'Unrelated Clinic']);

        $this->doctorA = $this->professional('doctor');
        $this->doctorB = $this->professional('doctor');

        $this->bearerA = app(ProviderSessionService::class)->mint($this->doctorA, $this->facilityA->id, 'referral-a');
        $this->bearerB = app(ProviderSessionService::class)->mint($this->doctorB, $this->facilityB->id, 'referral-b');

        $this->patient = Patient::factory()->create([
            'health_id' => self::HEALTH_ID,
            'is_demo'   => (bool) config('demo.enabled'),
        ]);

        // The patient has granted the REFERRING facility access, which is what
        // makes the chart openable at all. It is deliberately NOT a grant to
        // the receiving facility — that distinction is the point of several
        // tests below.
        $this->grant($this->facilityA->id, [
            'patient.summary', 'allergies.read', 'problems.read', 'encounters.read',
            'vitals.read', 'medications.read', 'diagnoses.read', 'orders.read',
        ]);
    }

    // ── fixtures ─────────────────────────────────────────────────────────

    /** Same recipe as ProviderRoleMatrixTest: a user whose role is a professional one. */
    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    /**
     * @param  list<string>  $scope
     */
    private function grant(string $facilityId, array $scope, ?\DateTimeInterface $expiresAt = null): ConsentGrant
    {
        return ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $facilityId,
            'authorizing_actor' => 'patient',
            'scope'             => $scope,
            'status'            => 'active',
            'expires_at'        => $expiresAt ?? now()->addDays(90),
        ]);
    }

    private function asA(string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearerA}"])->json($method, $path, $body);
    }

    private function asB(string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearerB}"])->json($method, $path, $body);
    }

    /**
     * Raise a draft through the API, so every test starts from a referral the
     * controller itself created.
     *
     * @param  list<string>  $recordTypes
     */
    private function draft(array $overrides = [], array $recordTypes = []): ReferralCase
    {
        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', array_merge([
            'receiving_facility_id' => $this->facilityB->id,
            'reason'                => 'Suspected rheumatic heart disease; needs echocardiography.',
            'included_record_types' => $recordTypes,
        ], $overrides));

        $response->assertStatus(201);

        return ReferralCase::findOrFail($response->json('data.id'));
    }

    // ══════════════════════════════════════════════════════════════════
    // The role gate
    // ══════════════════════════════════════════════════════════════════

    public function test_a_role_without_the_chart_capability_is_refused_on_every_referral_route(): void
    {
        $pharmacist = $this->professional('pharmacist');
        $bearer     = app(ProviderSessionService::class)->mint($pharmacist, $this->facilityA->id, 'referral-rx');

        $referral = $this->draft();

        $routes = [
            ['GET',  '/api/mobile/doctor/referrals'],
            ['GET',  '/api/mobile/doctor/referrals/' . $referral->id],
            ['POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send'],
            ['POST', '/api/mobile/doctor/referrals/' . $referral->id . '/accept'],
            ['POST', '/api/mobile/doctor/referrals/' . $referral->id . '/reject'],
            ['POST', '/api/mobile/doctor/referrals/' . $referral->id . '/complete'],
            ['POST', '/api/mobile/doctor/referrals/' . $referral->id . '/cancel'],
            ['POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals'],
            ['GET',  '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referral-requests'],
        ];

        foreach ($routes as [$method, $path]) {
            $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
                ->json($method, $path, ['reason' => 'anything at all'])
                ->assertStatus(403)
                ->assertJsonPath('error_code', 'role_forbidden');
        }

        // The refusal is the gate, not a hidden 404 — and it left nothing behind.
        $this->assertSame('draft', $referral->refresh()->status);
        $this->assertDatabaseCount('referral_cases', 1);
    }

    // ══════════════════════════════════════════════════════════════════
    // Identity comes from the token
    // ══════════════════════════════════════════════════════════════════

    public function test_a_referring_facility_posted_in_the_body_is_ignored_entirely(): void
    {
        $stranger = $this->professional('doctor');

        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'receiving_facility_id' => $this->facilityB->id,
            'reason'                => 'Needs a surgical opinion.',
            // Every one of these is a lie the API must not accept.
            'referring_facility_id' => $this->facilityC->id,
            'facility_id'           => $this->facilityC->id,
            'referring_provider_id' => $stranger->id,
            'created_by_id'         => $stranger->id,
            'status'                => 'sent',
        ]);

        $response->assertStatus(201);

        $referral = ReferralCase::findOrFail($response->json('data.id'));

        $this->assertSame($this->facilityA->id, $referral->referring_facility_id);
        $this->assertSame($this->doctorA->id, $referral->referring_provider_id);
        $this->assertSame($this->doctorA->id, $referral->created_by_id);
        // A referral cannot be born sent, whatever the body says.
        $this->assertSame('draft', $referral->status);

        $this->assertDatabaseMissing('referral_cases', ['referring_facility_id' => $this->facilityC->id]);
    }

    public function test_no_token_means_no_referral_book(): void
    {
        $this->json('GET', '/api/mobile/doctor/referrals')->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════════════════
    // No enumeration, no cross-facility reads
    // ══════════════════════════════════════════════════════════════════

    public function test_a_patient_resolves_only_by_an_exact_health_id(): void
    {
        // A prefix of the real Health ID. If anything here were a LIKE, this
        // would find the patient.
        $prefix = substr(self::HEALTH_ID, 0, 12);

        $this->asA('GET', '/api/mobile/doctor/patients/' . $prefix . '/referral-requests')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'NOT_FOUND');

        $this->asA('POST', '/api/mobile/doctor/patients/' . $prefix . '/referrals', [
            'receiving_specialty' => 'Cardiology',
            'reason'              => 'Anything.',
        ])->assertStatus(404);

        // The exact one resolves.
        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referral-requests')->assertOk();
    }

    public function test_a_referral_between_two_other_facilities_answers_404_not_403(): void
    {
        $other = ReferralCase::create([
            'patient_id'            => $this->patient->id,
            'referring_facility_id' => $this->facilityC->id,
            'receiving_facility_id' => $this->facilityB->id,
            'status'                => 'sent',
            'urgency'               => 'routine',
            'reason'                => 'Not this facility’s business.',
        ]);

        // A 403 would confirm the referral exists, which is itself a disclosure.
        $this->asA('GET', '/api/mobile/doctor/referrals/' . $other->id)
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'NOT_FOUND');

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $other->id . '/cancel', ['reason' => 'trying'])
            ->assertStatus(404);

        $this->assertSame('sent', $other->refresh()->status);
    }

    public function test_the_referral_book_is_this_facility_only(): void
    {
        $mine = $this->draft();

        ReferralCase::create([
            'patient_id'            => $this->patient->id,
            'referring_facility_id' => $this->facilityC->id,
            'receiving_facility_id' => $this->facilityB->id,
            'status'                => 'sent',
            'urgency'               => 'routine',
            'reason'                => 'Somebody else’s referral.',
        ]);

        $response = $this->asA('GET', '/api/mobile/doctor/referrals')->assertOk();

        $this->assertSame([$mine->id], $response->json('data.*.id'));
        $this->assertSame($this->facilityA->id, $response->json('meta.facility_id'));
    }

    // ══════════════════════════════════════════════════════════════════
    // Consent — the sharpest part
    // ══════════════════════════════════════════════════════════════════

    public function test_the_referring_facilitys_own_grant_never_authorises_the_receiving_facility(): void
    {
        // Facility A holds a full-scope grant (setUp). Facility B holds none.
        $referral = $this->draft(recordTypes: ['allergies.read', 'medications.read']);

        $response = $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        // Care is never blocked: the referral still sends.
        $response->assertJsonPath('data.status', 'sent')
            // But it carries nothing, and says so.
            ->assertJsonPath('data.included_record_types', [])
            ->assertJsonPath('data.has_record_access', false)
            ->assertJsonPath('record_access.granted', false)
            ->assertJsonPath('record_access.basis', 'no_consent')
            ->assertJsonPath('record_access.record_types', [])
            ->assertJsonPath('record_access.required_action', 'request_consent');

        $referral->refresh();
        $this->assertSame([], $referral->included_record_types);
        $this->assertNull($referral->consent_grant_id);

        // No key was cut.
        $this->assertDatabaseCount('referral_access_grants', 0);
    }

    public function test_the_package_is_the_intersection_of_the_request_and_the_grant(): void
    {
        // The patient granted the RECEIVING facility two things only.
        $grant = $this->grant($this->facilityB->id, ['allergies.read', 'vitals.read']);

        $referral = $this->draft(recordTypes: ['allergies.read', 'medications.read', 'orders.read']);

        $response = $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        $response->assertJsonPath('record_access.granted', true)
            ->assertJsonPath('record_access.basis', 'consent_grant');

        $this->assertSame(['allergies.read'], $response->json('record_access.record_types'));
        $this->assertSame(['allergies.read'], $response->json('data.included_record_types'));

        // Neither the union nor the clinician's unchecked list.
        $this->assertNotContains('medications.read', $response->json('data.included_record_types'));
        $this->assertNotContains('orders.read', $response->json('data.included_record_types'));
        // Nor everything the patient granted.
        $this->assertNotContains('vitals.read', $response->json('data.included_record_types'));

        $this->assertSame($grant->id, $referral->refresh()->consent_grant_id);

        $access = ReferralAccessGrant::where('referral_case_id', $referral->id)->sole();
        $this->assertSame(['allergies.read'], $access->allowed_scopes);
        $this->assertSame($this->facilityB->id, $access->granted_to_facility_id);
        $this->assertSame($this->doctorA->id, $access->granted_by_id);
    }

    public function test_a_grant_that_excludes_the_whole_package_opens_nothing(): void
    {
        $this->grant($this->facilityB->id, ['vitals.read']);

        $referral = $this->draft(recordTypes: ['medications.read']);

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')
            ->assertOk()
            ->assertJsonPath('record_access.granted', false)
            ->assertJsonPath('record_access.basis', 'consent_scope_excludes_package')
            ->assertJsonPath('data.included_record_types', [])
            ->assertJsonPath('data.has_record_access', false);

        $this->assertDatabaseCount('referral_access_grants', 0);
    }

    public function test_a_referral_to_a_facility_off_the_platform_opens_nothing(): void
    {
        $referral = $this->draft(
            ['receiving_facility_id' => null, 'receiving_specialty' => 'Cardiology'],
            ['allergies.read'],
        );

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')
            ->assertOk()
            ->assertJsonPath('record_access.granted', false)
            ->assertJsonPath('record_access.basis', 'external_destination');

        $this->assertDatabaseCount('referral_access_grants', 0);
    }

    public function test_the_access_grant_expires_with_the_consent_when_the_consent_ends_sooner(): void
    {
        $consentExpiry = now()->addDays(2);
        $this->grant($this->facilityB->id, ['allergies.read'], $consentExpiry);

        $referral = $this->draft(recordTypes: ['allergies.read']);

        // A 90-day referral window against a 2-day consent.
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send', ['expires_in_days' => 90])
            ->assertOk()
            ->assertJsonPath('record_access.granted', true);

        $access = ReferralAccessGrant::where('referral_case_id', $referral->id)->sole();

        $this->assertTrue(
            $access->expires_at->lessThanOrEqualTo($consentExpiry->copy()->addMinute()),
            'The access grant outlived the consent behind it: ' . $access->expires_at->toIso8601String(),
        );
        // The referral window itself is unchanged — only the key is clamped.
        $this->assertTrue($referral->refresh()->expires_at->greaterThan(now()->addDays(80)));
    }

    public function test_the_access_grant_expires_with_the_referral_when_the_referral_ends_sooner(): void
    {
        $this->grant($this->facilityB->id, ['allergies.read'], now()->addDays(365));

        $referral = $this->draft(recordTypes: ['allergies.read']);

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send', ['expires_in_days' => 3])
            ->assertOk();

        $access = ReferralAccessGrant::where('referral_case_id', $referral->id)->sole();

        $this->assertTrue(
            $access->expires_at->lessThanOrEqualTo(now()->addDays(4)),
            'The access grant outlived the referral window: ' . $access->expires_at->toIso8601String(),
        );
    }

    public function test_an_expired_consent_is_not_a_consent(): void
    {
        $this->grant($this->facilityB->id, ['allergies.read'], now()->subDay());

        $referral = $this->draft(recordTypes: ['allergies.read']);

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')
            ->assertOk()
            ->assertJsonPath('record_access.basis', 'no_consent')
            ->assertJsonPath('data.included_record_types', []);

        $this->assertDatabaseCount('referral_access_grants', 0);
    }

    // ══════════════════════════════════════════════════════════════════
    // The state machine
    // ══════════════════════════════════════════════════════════════════

    public function test_an_illegal_transition_is_409_and_changes_nothing(): void
    {
        $referral = $this->draft();

        // draft cannot be accepted — and B is the right party, so this is the
        // transition rule refusing, not the party rule.
        $this->asB('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/accept')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ILLEGAL_TRANSITION')
            ->assertJsonPath('current_status', 'draft')
            ->assertJsonPath('attempted', 'accepted')
            ->assertJsonPath('allowed', ['sent', 'cancelled']);

        $this->assertSame('draft', $referral->refresh()->status);
    }

    public function test_a_referral_cannot_be_sent_twice(): void
    {
        $referral = $this->draft();

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ILLEGAL_TRANSITION');

        $this->assertSame('sent', $referral->refresh()->status);
    }

    public function test_the_wrong_party_is_refused_with_403_not_409(): void
    {
        $referral = $this->draft();
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        // Accept is the RECEIVING facility's move. A is a party to the referral
        // and already knows it exists, so this is 403, not 404.
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/accept')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'WRONG_PARTY');

        // And cancel is the REFERRING facility's move.
        $this->asB('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/cancel', ['reason' => 'not mine to cancel'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'WRONG_PARTY');

        $this->assertSame('sent', $referral->refresh()->status);
    }

    public function test_a_sent_referral_past_its_date_reads_as_expired_and_cannot_be_accepted(): void
    {
        $referral = $this->draft();
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        $referral->forceFill(['expires_at' => now()->subDay()])->save();

        $this->asB('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/accept')
            ->assertStatus(409)
            ->assertJsonPath('current_status', 'expired');

        // The stored status is reported honestly rather than rewritten.
        $this->asA('GET', '/api/mobile/doctor/referrals/' . $referral->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'expired')
            ->assertJsonPath('data.stored_status', 'sent')
            ->assertJsonPath('data.is_expired', true);

        $this->assertSame('sent', $referral->refresh()->status);
    }

    public function test_rejecting_revokes_the_record_key_without_deleting_anything(): void
    {
        $this->grant($this->facilityB->id, ['allergies.read']);

        $referral = $this->draft(recordTypes: ['allergies.read']);
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        $access = ReferralAccessGrant::where('referral_case_id', $referral->id)->sole();
        $this->assertSame('active', $access->status);

        $this->asB('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/reject', [
            'reason' => 'No cardiology cover this month.',
        ])->assertOk()->assertJsonPath('data.status', 'rejected');

        $access->refresh();
        $this->assertSame('revoked', $access->status);
        $this->assertSame('referral_rejected', $access->revocation_reason);
        $this->assertNotNull($access->revoked_at);

        // Revoked, not deleted — on both tables.
        $this->assertDatabaseCount('referral_access_grants', 1);
        $this->assertDatabaseHas('referral_cases', ['id' => $referral->id, 'status' => 'rejected', 'deleted_at' => null]);
    }

    public function test_completing_revokes_the_record_key_and_cancelling_does_too(): void
    {
        $this->grant($this->facilityB->id, ['allergies.read']);

        // accepted → completed
        $completed = $this->draft(recordTypes: ['allergies.read']);
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $completed->id . '/send')->assertOk();
        $this->asB('POST', '/api/mobile/doctor/referrals/' . $completed->id . '/accept')->assertOk();
        $this->asB('POST', '/api/mobile/doctor/referrals/' . $completed->id . '/complete', [
            'feedback' => 'Echocardiography performed; report issued to the referring team.',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertSame(
            'revoked',
            ReferralAccessGrant::where('referral_case_id', $completed->id)->sole()->status,
        );

        // sent → cancelled
        $cancelled = $this->draft(recordTypes: ['allergies.read']);
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $cancelled->id . '/send')->assertOk();
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $cancelled->id . '/cancel', [
            'reason' => 'Patient seen locally instead.',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(
            'revoked',
            ReferralAccessGrant::where('referral_case_id', $cancelled->id)->sole()->status,
        );

        // Nothing was destroyed on the way.
        $this->assertDatabaseCount('referral_access_grants', 2);
        $this->assertSame(0, ReferralCase::onlyTrashed()->count());
    }

    public function test_there_is_no_delete_or_update_route_for_a_referral(): void
    {
        $referral = $this->draft();

        foreach (['DELETE', 'PUT', 'PATCH'] as $method) {
            $status = $this->asA($method, '/api/mobile/doctor/referrals/' . $referral->id)->status();
            $this->assertContains($status, [404, 405], "{$method} on a referral answered {$status}");
        }

        $this->assertDatabaseHas('referral_cases', ['id' => $referral->id, 'deleted_at' => null]);
    }

    public function test_reject_and_cancel_both_require_a_reason(): void
    {
        $referral = $this->draft();
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send')->assertOk();

        $this->asB('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/reject')->assertStatus(422);
        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/cancel')->assertStatus(422);

        $this->assertSame('sent', $referral->refresh()->status);
    }

    // ══════════════════════════════════════════════════════════════════
    // Raising one
    // ══════════════════════════════════════════════════════════════════

    public function test_a_referral_must_be_addressed_to_somebody(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'reason' => 'Needs an opinion.',
        ])->assertStatus(422)->assertJsonPath('error_code', 'DESTINATION_REQUIRED');

        $this->assertDatabaseCount('referral_cases', 0);
    }

    public function test_a_facility_cannot_refer_a_patient_to_itself(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'receiving_facility_id' => $this->facilityA->id,
            'reason'                => 'Needs an opinion.',
        ])->assertStatus(422)->assertJsonPath('error_code', 'SELF_REFERRAL');

        $this->assertDatabaseCount('referral_cases', 0);
    }

    public function test_a_record_type_outside_the_consent_vocabulary_is_refused(): void
    {
        // The package vocabulary is the same one the consent ledger speaks. A
        // type it does not speak could never be checked against a grant.
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'receiving_facility_id' => $this->facilityB->id,
            'reason'                => 'Needs an opinion.',
            'included_record_types' => ['everything'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('referral_cases', 0);
    }

    public function test_a_referral_window_has_a_ceiling(): void
    {
        $referral = $this->draft();

        $this->asA('POST', '/api/mobile/doctor/referrals/' . $referral->id . '/send', ['expires_in_days' => 3650])
            ->assertStatus(422);

        $this->assertSame('draft', $referral->refresh()->status);
    }

    public function test_a_draft_discloses_nothing(): void
    {
        // Even with a live grant to the receiving facility, a draft opens no key.
        $this->grant($this->facilityB->id, ['allergies.read']);

        $referral = $this->draft(recordTypes: ['allergies.read']);

        $this->assertNull($referral->consent_grant_id);
        $this->assertNull($referral->expires_at);
        $this->assertDatabaseCount('referral_access_grants', 0);
    }

    public function test_a_clinician_without_a_care_relationship_is_refused_the_chart_write(): void
    {
        $stranger       = $this->professional('doctor');
        $strangerBearer = app(ProviderSessionService::class)->mint($stranger, $this->facilityC->id, 'referral-c');

        $this->withHeaders(['Authorization' => "Bearer {$strangerBearer}"])
            ->json('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
                'receiving_facility_id' => $this->facilityB->id,
                'reason'                => 'No relationship with this patient.',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP');

        $this->assertDatabaseCount('referral_cases', 0);
    }

    // ══════════════════════════════════════════════════════════════════
    // The patient's own asks
    // ══════════════════════════════════════════════════════════════════

    public function test_answering_a_patients_request_links_it_and_closes_it(): void
    {
        $ask = PatientReferralRequest::create([
            'reference'           => 'OC-RR-' . strtoupper(Str::random(8)),
            'patient_id'          => $this->patient->id,
            'requested_specialty' => 'Cardiology',
            'reason'              => 'Breathlessness on climbing stairs.',
            'reported_urgency'    => 'soon',
        ]);

        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referral-requests')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ask->id)
            ->assertJsonPath('data.0.is_open', true)
            // The patient's own sense of urgency is labelled as theirs, never
            // as clinical triage.
            ->assertJsonPath('data.0.patient_reported_urgency', 'soon')
            ->assertJsonMissingPath('data.0.urgency');

        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'receiving_facility_id'       => $this->facilityB->id,
            'reason'                      => 'For cardiology assessment.',
            'patient_referral_request_id' => $ask->id,
        ])->assertStatus(201);

        $ask->refresh();
        $this->assertSame(PatientReferralRequestStatus::Accepted, $ask->status);
        $this->assertSame($response->json('data.id'), $ask->referral_case_id);
        $this->assertSame($this->doctorA->id, $ask->reviewed_by_id);
    }

    public function test_a_clinician_cannot_answer_another_patients_request(): void
    {
        $other = Patient::factory()->create([
            'health_id' => 'CM-HID-TEST-REFR-0002',
            'is_demo'   => (bool) config('demo.enabled'),
        ]);

        $ask = PatientReferralRequest::create([
            'reference'  => 'OC-RR-' . strtoupper(Str::random(8)),
            'patient_id' => $other->id,
            'reason'     => 'Somebody else’s ask.',
        ]);

        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/referrals', [
            'receiving_facility_id'       => $this->facilityB->id,
            'reason'                      => 'For cardiology assessment.',
            'patient_referral_request_id' => $ask->id,
        ])->assertStatus(422)->assertJsonPath('error_code', 'REQUEST_NOT_ACTIONABLE');

        $this->assertDatabaseCount('referral_cases', 0);
        $this->assertNull($ask->refresh()->referral_case_id);
    }
}
