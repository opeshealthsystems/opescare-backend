<?php

namespace Tests\Feature\Security;

use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Cross-facility isolation on the mobile-booking surface.
 *
 * The bug shape covered: the facility taken from request input, and record
 * lookups by id with no facility filter — resolving the right facility buys
 * nothing if the next query is a bare findOrFail().
 *
 * A patient legitimately chooses which facility to attend, so facility_id
 * stays a request field here. What it must not do is decide where the
 * appointment lands: the booked slot belongs to exactly one facility.
 *
 * (The lab, health-organisation, Lite and facility-clinical variants of these
 * tests were removed with those portals in the Finder extraction.)
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

    // ── Mobile patient booking ────────────────────────────────────────────

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
