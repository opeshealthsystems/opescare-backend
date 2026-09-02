<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Appointment;
use App\Models\BloodAvailability;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The journeys a real person actually takes, end to end.
 *
 * The unit and feature tests around these flows pass on each step in isolation.
 * These walk the whole path, because that is where this platform has repeatedly
 * been broken: a finder that could never return a row, a sync endpoint that
 * answered "synced" and stored nothing, a prescribing chain with no first link.
 * Each step here is a place a patient would otherwise be stranded.
 */
class CareJourneyTest extends TestCase
{
    use RefreshDatabase;
    use WithMobileAuth;

    private function facility(string $name, float $lat, float $lon): array
    {
        $tenant = Facility::forceCreate(['name' => $name, 'type' => 'hospital', 'is_demo' => false]);

        $listing = CareFacility::forceCreate([
            'facility_id'         => $tenant->id,
            'facility_name'       => $name,
            'facility_type'       => 'hospital',
            'country_code'        => 'CM',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => '1 Rue de la Sante',
            'latitude'            => $lat,
            'longitude'           => $lon,
            'phone_primary'       => '+237670000001',
            'verification_status' => 'unverified',
            'listing_status'      => 'active',
            'integration_status'  => 'none',
        ]);

        return [$tenant, $listing];
    }

    private function staffAt(Facility $facility, string $role = 'doctor'): User
    {
        return User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => $facility->id,
            'role_id'             => Role::firstOrCreate(['name' => $role], ['display_name' => $role])->id,
        ]);
    }

    private function asStaff(User $user)
    {
        return $this->actingAs($user)->withSession([
            'mfa.verified'       => true,
            'active_facility_id' => $user->primary_facility_id,
        ]);
    }

    // ── Journey 1: a patient looks for blood and asks for it ────────────────

    public function test_a_patient_can_find_a_blood_bank_and_request_from_it(): void
    {
        [$tenant, $listing] = $this->facility('Hopital Laquintinie', 4.0480, 9.6960);
        $patient = Patient::factory()->create();

        // A blood bank publishes stock, the way the staff screen does.
        BloodAvailability::create([
            'facility_id'         => $listing->id,
            'blood_group'         => BloodGroup::ONegative->value,
            'component_type'      => BloodComponentType::WholeBlood->value,
            'availability_status' => 'available',
            'freshness_status'    => 'fresh',
            'last_updated_at'     => now(),
            'source_system'       => 'portal',
        ]);

        // The patient finds it.
        $found = $this->getJson('/api/v1/care-map/blood/search?blood_group=' . urlencode('O-'))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($found, 'a published blood bank must be findable');

        // And can then request from it.
        $this->mobilePostJson($patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $listing->id,
            'blood_group'    => 'O-',
            'component_type' => 'whole_blood',
            'units'          => 1,
            'urgency'        => 'urgent',
            'contact_phone'  => '+237670000009',
        ])->assertStatus(201);
    }

    /** Findable and orderable must be the same set. */
    public function test_a_patient_cannot_order_blood_the_finder_withholds(): void
    {
        [$tenant, $listing] = $this->facility('Seeded Bank', 4.0500, 9.7000);
        $patient = Patient::factory()->create();

        BloodAvailability::create([
            'facility_id'         => $listing->id,
            'blood_group'         => BloodGroup::ONegative->value,
            'component_type'      => BloodComponentType::WholeBlood->value,
            'availability_status' => 'available',
            'freshness_status'    => 'fresh',
            'last_updated_at'     => now(),
            'source_system'       => 'demo_seed',
        ]);

        $this->assertSame(
            [],
            $this->getJson('/api/v1/care-map/blood/search?blood_group=' . urlencode('O-'))->json('data'),
            'seeded stock must never be published'
        );

        $this->mobilePostJson($patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $listing->id,
            'blood_group'    => 'O-',
            'component_type' => 'whole_blood',
            'units'          => 1,
            'urgency'        => 'urgent',
            'contact_phone'  => '+237670000009',
        ])->assertStatus(409);
    }

    // ── Journey 2: find a facility, book, and have the facility confirm ─────

    public function test_a_patient_finds_a_facility_books_and_the_facility_confirms(): void
    {
        [$tenant, $listing] = $this->facility('Clinique du Littoral', 4.0510, 9.7010);

        // The patient finds it on the map.
        $nearby = $this->getJson('/api/v1/care-map/nearby?latitude=4.05&longitude=9.70&radius=25')
            ->assertOk()->json('data');
        $this->assertNotEmpty($nearby, 'a facility with coordinates must be findable nearby');

        // The patient books.
        $patient = Patient::factory()->create();
        $patientUser = User::factory()->create([
            'status'     => 'active',
            'patient_id' => $patient->id,
            'role_id'    => Role::firstOrCreate(['name' => 'patient'], ['display_name' => 'Patient'])->id,
        ]);

        $this->actingAs($patientUser)->withSession(['mfa.verified' => true])
            ->post('/portals/patient/appointments/book', [
                'facility_id'      => $tenant->id,
                'appointment_type' => $this->firstAppointmentType(),
                'scheduled_at'     => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason'           => 'Routine review',
            ])->assertRedirect();

        $appointment = Appointment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($appointment, 'the booking must persist');
        $this->assertSame('requested', $appointment->status);

        // The facility confirms it.
        $this->asStaff($this->staffAt($tenant))
            ->post("/portals/staff/appointments/{$appointment->id}/confirm")
            ->assertRedirect();

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    /** A staff user must not be able to act on another facility's appointment. */
    public function test_staff_cannot_confirm_another_facilitys_appointment(): void
    {
        [$mine]    = $this->facility('My Clinic', 4.05, 9.70);
        [$theirs]  = $this->facility('Their Clinic', 4.06, 9.71);

        $appointment = Appointment::create([
            'patient_id'       => Patient::factory()->create()->id,
            'facility_id'      => $theirs->id,
            'appointment_type' => $this->firstAppointmentType(),
            'status'           => 'requested',
            'scheduled_at'     => now()->addDay(),
            'booked_by_type'   => 'patient',
        ]);

        $this->asStaff($this->staffAt($mine))
            ->post("/portals/staff/appointments/{$appointment->id}/confirm")
            ->assertNotFound();

        $this->assertSame(
            'requested',
            $appointment->fresh()->status,
            "confirming another facility's appointment tells someone else's patient their slot is booked"
        );
    }

    /** The facility must come from the session, never from the request body. */
    public function test_staff_cannot_book_an_appointment_into_another_facility(): void
    {
        [$mine]   = $this->facility('My Clinic', 4.05, 9.70);
        [$theirs] = $this->facility('Their Clinic', 4.06, 9.71);
        $patient  = Patient::factory()->create();

        $this->asStaff($this->staffAt($mine))
            ->post('/portals/staff/appointments', [
                'patient_id'       => $patient->id,
                'facility_id'      => $theirs->id,          // ignored on purpose
                'appointment_type' => $this->firstAppointmentType(),
                'scheduled_at'     => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])->assertRedirect();

        $created = Appointment::where('patient_id', $patient->id)->first();

        $this->assertNotNull($created);
        $this->assertSame(
            $mine->id,
            $created->facility_id,
            'a facility id posted in the body must never decide where an appointment lands'
        );
    }

    private function firstAppointmentType(): string
    {
        return 'consultation';
    }
}
