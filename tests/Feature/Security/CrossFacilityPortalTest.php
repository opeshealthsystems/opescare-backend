<?php

namespace Tests\Feature\Security;

use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\HealthOrgOutreachEvent;
use App\Models\LabOrder;
use App\Models\LiteConflict;
use App\Models\LiteDevice;
use App\Models\LiteOfflineEvent;
use App\Models\Patient;
use App\Models\PublicHealthReport;
use App\Models\PublicHealthSignal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Cross-facility isolation on the lab, health-organisation, OpesCare Lite,
 * facility-clinical and mobile-booking surfaces.
 *
 * Two bug shapes are covered:
 *
 *   (a) the facility resolved by a table scan — `?? Facility::value('id')`,
 *       whichever row Postgres returns first out of 345 in production. It read
 *       like a fallback; it silently substituted a stranger's facility whenever
 *       session context was missing, and none of these route groups carry
 *       RequireFacilityContext (which platform-admin roles bypass anyway).
 *
 *   (b) the facility taken from request input, and record lookups by id with no
 *       facility filter — the same hole wearing a different hat, since
 *       resolving the right facility buys nothing if the next query is a bare
 *       findOrFail().
 *
 * Every test here is written as facility A acting on facility B's row.
 */
class CrossFacilityPortalTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Facility $facilityA;
    private Facility $facilityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);

        // Two facilities, so no "there is only one, it must be that one"
        // shortcut is ever legitimate. A is created first, so it is also the
        // row `Facility::value('id')` would have handed out.
        $this->facilityA = Facility::create(['name' => 'Facility A', 'type' => 'hospital', 'status' => 'active']);
        $this->facilityB = Facility::create(['name' => 'Facility B', 'type' => 'hospital', 'status' => 'active']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function userWithRole(string $roleName, ?string $facilityId): User
    {
        $user = User::factory()->create(['primary_facility_id' => $facilityId]);
        $user->role_id = Role::where('name', $roleName)->value('id');
        $user->save();

        return $user->fresh();
    }

    /** Signed in, MFA satisfied, acting inside $facilityId (or with no facility at all). */
    private function actingAtFacility(User $user, ?string $facilityId = null)
    {
        $session = ['mfa.verified' => true];

        if ($facilityId !== null) {
            $session['active_facility_id'] = $facilityId;
        }

        return $this->actingAs($user)->withSession($session);
    }

    private function patientAt(string $facilityId, string $healthId): Patient
    {
        return Patient::create([
            'health_id'     => $healthId,
            'first_name'    => 'Test',
            'last_name'     => 'Patient',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'facility_id'   => $facilityId,
            'is_demo'       => false,
        ]);
    }

    private function labOrderAt(string $facilityId, string $testName): LabOrder
    {
        $patient = $this->patientAt($facilityId, 'OC-XF-' . strtoupper(substr(md5($testName), 0, 8)));

        return LabOrder::create([
            'patient_id'  => $patient->id,
            'facility_id' => $facilityId,
            'test_name'   => $testName,
            'urgency'     => 'routine',
            'status'      => 'pending',
            'ordered_at'  => now(),
        ]);
    }

    private function liteDeviceWithConflict(string $facilityId): LiteConflict
    {
        $device = LiteDevice::create([
            'facility_id'        => $facilityId,
            'device_name'        => 'Tablet ' . substr($facilityId, 0, 4),
            'device_fingerprint' => 'fp-' . md5($facilityId),
            'environment'        => 'production',
            'status'             => 'active',
        ]);

        $event = LiteOfflineEvent::create([
            'lite_device_id' => $device->id,
            'facility_id'    => $facilityId,
            'event_type'     => 'consultation',
            'client_id'      => 'cid-' . substr(md5($facilityId), 0, 12),
            'payload'        => ['note' => 'offline consultation'],
            'status'         => 'conflict',
            'captured_at'    => now()->subHour(),
        ]);

        return LiteConflict::create([
            'lite_device_id'        => $device->id,
            'lite_offline_event_id' => $event->id,
            'conflict_type'         => 'data_mismatch',
            'status'                => 'open',
        ]);
    }

    // ── Lab portal ────────────────────────────────────────────────────────

    public function test_lab_portal_cannot_progress_another_facilitys_order(): void
    {
        $orderB = $this->labOrderAt($this->facilityB->id, 'Malaria RDT');
        $labUserA = $this->userWithRole('lab_manager', $this->facilityA->id);

        $this->actingAtFacility($labUserA, $this->facilityA->id)
            ->post("/portals/lab/orders/{$orderB->id}/collect")
            ->assertNotFound();

        $this->assertSame('pending', $orderB->fresh()->status,
            "Facility A must not be able to move facility B's specimen through the lab workflow");
    }

    public function test_lab_portal_cannot_open_or_result_another_facilitys_order(): void
    {
        $orderB = $this->labOrderAt($this->facilityB->id, 'Full Blood Count');
        $orderB->update(['status' => 'collected']);

        $labUserA = $this->userWithRole('lab_manager', $this->facilityA->id);

        $this->actingAtFacility($labUserA, $this->facilityA->id)
            ->get("/portals/lab/orders/{$orderB->id}/result")
            ->assertNotFound();

        $this->actingAtFacility($labUserA, $this->facilityA->id)
            ->post("/portals/lab/orders/{$orderB->id}/result", [
                'parameter_name' => 'Haemoglobin',
                'value'          => '9.1',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('lab_results', 0);
        $this->assertSame('collected', $orderB->fresh()->status);
    }

    public function test_lab_portal_lists_only_its_own_facilitys_orders(): void
    {
        $orderA = $this->labOrderAt($this->facilityA->id, 'Urine Culture');
        $orderB = $this->labOrderAt($this->facilityB->id, 'Blood Culture');

        $response = $this->actingAtFacility(
            $this->userWithRole('lab_manager', $this->facilityA->id),
            $this->facilityA->id
        )->get('/portals/lab/orders');

        $response->assertOk();

        $ids = collect($response->viewData('orders')->items())->pluck('id')->all();
        $this->assertContains($orderA->id, $ids);
        $this->assertNotContains($orderB->id, $ids);
    }

    public function test_lab_portal_fails_closed_when_no_facility_is_resolved(): void
    {
        // No primary facility and nothing in session. The lab route group does
        // not carry RequireFacilityContext, so this reaches the controller —
        // which used to answer "which lab?" with Facility::value('id') and hand
        // over facility A's work queue.
        $this->labOrderAt($this->facilityA->id, 'Lipid Panel');

        $this->actingAtFacility($this->userWithRole('lab_manager', null))
            ->get('/portals/lab')
            ->assertStatus(409);
    }

    // ── Facility clinical register (/portals/admin/clinical/*) ─────────────

    public function test_clinical_register_shows_only_the_selected_facility(): void
    {
        $orderA = $this->labOrderAt($this->facilityA->id, 'Register Test A');
        $orderB = $this->labOrderAt($this->facilityB->id, 'Register Test B');

        // Everything under /portals/admin/* is platform tier (RequirePlatformAdmin),
        // and a platform admin has no facility of their own — they pick one.
        $admin = $this->userWithRole('super_admin', null);

        $response = $this->actingAtFacility($admin, $this->facilityA->id)
            ->get('/portals/admin/clinical/lab-orders');

        $response->assertOk();

        $ids = collect($response->viewData('orders')->items())->pluck('id')->all();
        $this->assertContains($orderA->id, $ids);
        $this->assertNotContains($orderB->id, $ids,
            "The clinical register must not leak another facility's lab orders");
    }

    public function test_clinical_register_fails_closed_with_no_facility_selected(): void
    {
        $this->labOrderAt($this->facilityA->id, 'Unselected Register');

        $this->actingAtFacility($this->userWithRole('super_admin', null))
            ->get('/portals/admin/clinical/prescriptions')
            ->assertStatus(409);
    }

    // ── Health organisation portal ────────────────────────────────────────

    public function test_health_org_cannot_complete_another_facilitys_outreach(): void
    {
        $eventB = HealthOrgOutreachEvent::create([
            'facility_id' => $this->facilityB->id,
            'title'       => 'Measles campaign',
            'status'      => 'planned',
        ]);

        $this->actingAtFacility(
            $this->userWithRole('ngo_admin', $this->facilityA->id),
            $this->facilityA->id
        )->post("/portals/healthorg/outreach/{$eventB->id}/complete", ['people_reached' => 4000])
            ->assertNotFound();

        $this->assertSame('planned', $eventB->fresh()->status);
        $this->assertNull($eventB->fresh()->people_reached);
    }

    public function test_health_org_cannot_submit_another_facilitys_report(): void
    {
        $typeId = DB::table('public_health_report_types')->value('id');
        $this->assertNotNull($typeId, 'report type catalogue should be seeded by migration');

        $reportB = PublicHealthReport::create([
            'report_type_id'         => $typeId,
            'facility_id'            => $this->facilityB->id,
            'reporting_period_start' => now()->subWeek()->toDateString(),
            'reporting_period_end'   => now()->toDateString(),
            'status'                 => 'draft',
            'sensitivity_level'      => 'routine',
            'data_classification'    => 'restricted',
            'generated_by_system'    => false,
            'requires_review'        => false,
            'requires_correction'    => false,
            'payload_json'           => ['notes' => ''],
        ]);

        $this->actingAtFacility(
            $this->userWithRole('ngo_admin', $this->facilityA->id),
            $this->facilityA->id
        )->post("/portals/healthorg/reports/{$reportB->id}/submit")
            ->assertNotFound();

        $this->assertSame('draft', $reportB->fresh()->status,
            "A MINSANTE submission must not be filed under another organisation's name");
    }

    public function test_health_org_signals_are_scoped_and_not_reviewable_across_facilities(): void
    {
        $signalB = PublicHealthSignal::create([
            'signal_type'    => 'disease_cluster',
            'status'         => 'new_signal',
            'scope_type'     => 'facility',
            'facility_id'    => $this->facilityB->id,
            'indicator_code' => 'CHOLERA_CASES',
            'detected_at'    => now(),
        ]);

        $signalA = PublicHealthSignal::create([
            'signal_type'    => 'disease_cluster',
            'status'         => 'new_signal',
            'scope_type'     => 'facility',
            'facility_id'    => $this->facilityA->id,
            'indicator_code' => 'MALARIA_CASES',
            'detected_at'    => now(),
        ]);

        $ngoA = $this->userWithRole('ngo_admin', $this->facilityA->id);

        $response = $this->actingAtFacility($ngoA, $this->facilityA->id)->get('/portals/healthorg/signals');
        $response->assertOk();

        $ids = collect($response->viewData('signals')->items())->pluck('id')->all();
        $this->assertContains($signalA->id, $ids);
        $this->assertNotContains($signalB->id, $ids);

        $this->actingAtFacility($ngoA, $this->facilityA->id)
            ->post("/portals/healthorg/signals/{$signalB->id}/review", ['action' => 'dismiss'])
            ->assertNotFound();

        $this->assertSame('new_signal', $signalB->fresh()->status);
        $this->assertDatabaseCount('public_health_signal_reviews', 0);
    }

    public function test_health_org_program_is_filed_under_the_acting_facility(): void
    {
        $this->actingAtFacility(
            $this->userWithRole('ngo_admin', $this->facilityB->id),
            $this->facilityB->id
        )->post('/portals/healthorg/programs', ['name' => 'Nutrition outreach'])
            ->assertRedirect();

        // Facility A is the row a table scan would have picked (created first).
        $this->assertDatabaseHas('health_org_programs', [
            'name'        => 'Nutrition outreach',
            'facility_id' => $this->facilityB->id,
        ]);
        $this->assertDatabaseMissing('health_org_programs', [
            'name'        => 'Nutrition outreach',
            'facility_id' => $this->facilityA->id,
        ]);
    }

    public function test_health_org_write_fails_closed_with_no_facility(): void
    {
        $this->actingAtFacility($this->userWithRole('ngo_admin', null))
            ->post('/portals/healthorg/programs', ['name' => 'Orphan program'])
            ->assertStatus(409);

        $this->assertDatabaseCount('health_org_programs', 0);
    }

    // ── OpesCare Lite portal ──────────────────────────────────────────────

    public function test_lite_cannot_check_in_another_facilitys_patient(): void
    {
        $patientB = $this->patientAt($this->facilityB->id, 'OC-XF-LITE-0001');

        $this->actingAtFacility(
            $this->userWithRole('lite_facility', $this->facilityA->id),
            $this->facilityA->id
        )->post('/portals/lite/checkin', ['patient_id' => $patientB->id])
            ->assertNotFound();

        $this->assertDatabaseCount('queue_tickets', 0);
    }

    public function test_lite_screens_do_not_open_another_facilitys_patient(): void
    {
        $patientB = $this->patientAt($this->facilityB->id, 'OC-XF-LITE-0002');

        $response = $this->actingAtFacility(
            $this->userWithRole('lite_facility', $this->facilityA->id),
            $this->facilityA->id
        )->get('/portals/lite/checkin?patient_id=' . $patientB->id);

        $response->assertOk();
        $this->assertNull($response->viewData('patient'),
            "A patient id in the query string must not open another facility's record");
    }

    public function test_lite_cannot_resolve_another_facilitys_sync_conflict(): void
    {
        $conflictB = $this->liteDeviceWithConflict($this->facilityB->id);

        $this->actingAtFacility(
            $this->userWithRole('lite_facility', $this->facilityA->id),
            $this->facilityA->id
        )->post("/portals/lite/conflicts/{$conflictB->id}/resolve", ['resolution' => 'dismiss'])
            ->assertForbidden();

        $this->assertSame('open', $conflictB->fresh()->status);
    }

    public function test_lite_registers_the_patient_into_the_acting_facility(): void
    {
        $this->actingAtFacility(
            $this->userWithRole('lite_facility', $this->facilityB->id),
            $this->facilityB->id
        )->post('/portals/lite/register-patient', [
            'first_name' => 'Amina',
            'last_name'  => 'Njoya',
        ])->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'first_name'  => 'Amina',
            'facility_id' => $this->facilityB->id,
        ]);
        $this->assertDatabaseMissing('patients', [
            'first_name'  => 'Amina',
            'facility_id' => $this->facilityA->id,
        ]);
    }

    public function test_lite_fails_closed_when_no_facility_is_resolved(): void
    {
        $this->actingAtFacility($this->userWithRole('lite_facility', null))
            ->post('/portals/lite/register-patient', [
                'first_name' => 'Orphan',
                'last_name'  => 'Registration',
            ])
            ->assertStatus(409);

        $this->assertDatabaseMissing('patients', ['first_name' => 'Orphan']);
    }

    // ── Mobile patient booking ────────────────────────────────────────────
    //
    // A patient legitimately chooses which facility to attend, so facility_id
    // stays a request field here. What it must not do is decide where the
    // appointment lands: the booked slot belongs to exactly one facility.

    private function bookableSlotAt(Facility $facility, array $overrides = []): AppointmentSlot
    {
        return AppointmentSlot::create(array_merge([
            'facility_id'  => $facility->id,
            'provider_id'  => User::factory()->create()->id,
            'starts_at'    => now()->addDay()->setTime(9, 0),
            'ends_at'      => now()->addDay()->setTime(9, 30),
            'capacity'     => 2,
            'booked_count' => 0,
            'status'       => 'open',
        ], $overrides));
    }

    private function listFacilityInDirectory(Facility $facility, string $status = 'active'): CareFacility
    {
        return CareFacility::create([
            'facility_name'  => $facility->name,
            'facility_type'  => 'hospital',
            'listing_status' => $status,
            'city'           => 'Yaounde',
            'country_code'   => 'CM',
            'address'        => '1 Test Road',
            'phone_primary'  => '+2376000000' . substr($facility->id, 0, 2),
            'facility_id'    => $facility->id,
        ]);
    }

    public function test_patient_can_still_book_at_the_facility_they_chose(): void
    {
        $this->listFacilityInDirectory($this->facilityB);
        $slot    = $this->bookableSlotAt($this->facilityB);
        $patient = $this->patientAt($this->facilityA->id, 'OC-XF-BOOK-0001');

        $this->mobilePostJson($patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facilityB->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
            'reason'              => 'Second opinion',
        ])->assertStatus(201)->assertJsonFragment(['status' => 'booked']);

        // Registered at A, treated at B — that is the platform working, not a bug.
        $this->assertDatabaseHas('appointments', [
            'patient_id'  => $patient->id,
            'facility_id' => $this->facilityB->id,
            'status'      => 'booked',
        ]);
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_booking_refuses_a_facility_id_that_is_not_the_slots_facility(): void
    {
        $this->listFacilityInDirectory($this->facilityB);
        $slot    = $this->bookableSlotAt($this->facilityB);
        $patient = $this->patientAt($this->facilityA->id, 'OC-XF-BOOK-0002');

        // One edited form field: book B's slot, file it against A.
        $this->mobilePostJson($patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facilityA->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
        ])->assertStatus(422)->assertJsonFragment(['error_code' => 'FACILITY_SLOT_MISMATCH']);

        $this->assertDatabaseCount('appointments', 0);
        $this->assertSame(0, $slot->fresh()->booked_count);
    }

    public function test_booking_refuses_a_facility_that_is_not_live(): void
    {
        $this->facilityB->update(['status' => 'suspended']);
        $slot    = $this->bookableSlotAt($this->facilityB);
        $patient = $this->patientAt($this->facilityA->id, 'OC-XF-BOOK-0003');

        $this->mobilePostJson($patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facilityB->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
        ])->assertStatus(422)->assertJsonFragment(['error_code' => 'FACILITY_NOT_BOOKABLE']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_refuses_a_facility_whose_public_listing_is_suspended(): void
    {
        $this->listFacilityInDirectory($this->facilityB, 'suspended');
        $slot    = $this->bookableSlotAt($this->facilityB);
        $patient = $this->patientAt($this->facilityA->id, 'OC-XF-BOOK-0004');

        $this->mobilePostJson($patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facilityB->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
        ])->assertStatus(422)->assertJsonFragment(['error_code' => 'FACILITY_NOT_BOOKABLE']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_refuses_a_closed_slot(): void
    {
        $this->listFacilityInDirectory($this->facilityB);
        $slot    = $this->bookableSlotAt($this->facilityB, ['status' => 'closed']);
        $patient = $this->patientAt($this->facilityA->id, 'OC-XF-BOOK-0005');

        $this->mobilePostJson($patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facilityB->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
        ])->assertStatus(422)->assertJsonFragment(['error_code' => 'SLOT_NOT_BOOKABLE']);

        $this->assertDatabaseCount('appointments', 0);
        $this->assertSame(0, $slot->fresh()->booked_count);
    }
}
