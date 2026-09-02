<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\ProfessionalLicense;
use App\Models\ProviderCredential;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Mobile clinician/specialist directory — GET /api/mobile/facilities/{id}/providers
 * and GET /api/mobile/providers/{id}.
 *
 * Covers the directory contract, the facility scoping (only active clinical
 * staff at the routed facility), the enumeration guard on provider detail,
 * and the provider-filtered slot listing.
 */
class MobileProviderDirectoryTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private CareFacility $careFacility;
    private Facility $facility;
    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'City Medical Centre',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->careFacility = CareFacility::create([
            'facility_name'      => 'City Medical Centre',
            'facility_type'      => 'hospital',
            'listing_status'     => 'active',
            'city'               => 'Yaounde',
            'country_code'       => 'CM',
            'address'            => '12 Independence Ave',
            'phone_primary'      => '+237600000001',
            'integration_status' => 'none',
            'facility_id'        => $this->facility->id,
        ]);

        // ── The clinician the patient should see ────────────────────────────
        $this->doctor = User::factory()->create(['name' => 'Ibrahim Sow']);

        $profile = StaffProfile::create([
            'user_id'         => $this->doctor->id,
            'facility_id'     => $this->facility->id,
            'employee_number' => 'EMP-SPE-001',
            'first_name'      => 'Ibrahim',
            'last_name'       => 'Sow',
            'job_title'       => 'Cardiologist',
            'department'      => 'Cardiology',
            'staff_category'  => 'clinical',
            'employment_type' => 'full_time',
            'status'          => 'active',
        ]);

        ProfessionalLicense::create([
            'staff_profile_id' => $profile->id,
            'profession'       => 'doctor',
            'license_number'   => 'CM-DOC-4411',
            'issuing_body'     => 'Medical Council of Cameroon',
            'status'           => 'active',
        ]);

        ProviderCredential::create([
            'provider_id'       => $this->doctor->id,
            'credential_type'   => 'specialist_cert',
            'issuing_body'      => 'Medical Council of Cameroon',
            'credential_number' => 'SPEC-9912',
            'issued_date'       => '2020-04-01',
            'status'            => 'active',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8888-0001-01',
            'first_name'    => 'Alice',
            'last_name'     => 'Patient',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'is_demo'       => false,
        ]);
    }

    /** Non-clinical, inactive, and other-facility staff must never be listed. */
    private function seedNoiseStaff(): array
    {
        $admin = User::factory()->create(['name' => 'Marie Admin']);
        StaffProfile::create([
            'user_id'        => $admin->id,
            'facility_id'    => $this->facility->id,
            'first_name'     => 'Marie',
            'last_name'      => 'Admin',
            'staff_category' => 'administrative',
            'status'         => 'active',
        ]);

        $retired = User::factory()->create(['name' => 'Paul Retired']);
        StaffProfile::create([
            'user_id'        => $retired->id,
            'facility_id'    => $this->facility->id,
            'first_name'     => 'Paul',
            'last_name'      => 'Retired',
            'staff_category' => 'clinical',
            'status'         => 'terminated',
        ]);

        $otherFacility = Facility::create([
            'name'   => 'Other Hospital',
            'type'   => 'hospital',
            'status' => 'active',
        ]);
        $elsewhere = User::factory()->create(['name' => 'Jean Elsewhere']);
        StaffProfile::create([
            'user_id'        => $elsewhere->id,
            'facility_id'    => $otherFacility->id,
            'first_name'     => 'Jean',
            'last_name'      => 'Elsewhere',
            'staff_category' => 'clinical',
            'status'         => 'active',
        ]);

        return compact('admin', 'retired', 'elsewhere');
    }

    // ── GET /api/mobile/facilities/{id}/providers ───────────────────────────

    public function test_facility_providers_lists_active_clinicians_only(): void
    {
        $noise = $this->seedNoiseStaff();

        $response = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/facilities/' . $this->careFacility->id . '/providers',
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'facility_id',
                'care_facility_id',
                'facility_name',
                'data' => [[
                    'id', 'name', 'job_title', 'department', 'profession',
                    'facility_id', 'care_facility_id', 'facility_name',
                    'credentials', 'licenses',
                ]],
            ]);

        $this->assertSame($this->facility->id, $response->json('facility_id'));
        $this->assertSame($this->careFacility->id, $response->json('care_facility_id'));

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$this->doctor->id], $ids, 'Only the active clinical staff member should be listed');

        $doctor = $response->json('data.0');
        $this->assertSame('Ibrahim Sow', $doctor['name']);
        $this->assertSame('Cardiologist', $doctor['job_title']);
        $this->assertSame('Cardiology', $doctor['department']);
        $this->assertSame('doctor', $doctor['profession']);
        $this->assertSame('specialist_cert', $doctor['credentials'][0]['type']);

        // Explicit exclusions — the noise staff must not leak into the directory.
        foreach ($noise as $user) {
            $this->assertNotContains($user->id, $ids);
        }
    }

    public function test_facility_providers_returns_empty_for_unlinked_directory_entry(): void
    {
        $unlinked = CareFacility::create([
            'facility_name'  => 'Unlinked Clinic',
            'facility_type'  => 'clinic',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => '9 Unlinked Road',
            'phone_primary'  => '+237600000003',
        ]);

        $response = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/facilities/' . $unlinked->id . '/providers',
        );

        $response->assertStatus(200)
            ->assertExactJson([
                'facility_id'      => null,
                'care_facility_id' => $unlinked->id,
                'facility_name'    => 'Unlinked Clinic',
                'data'             => [],
            ]);
    }

    public function test_facility_providers_requires_authentication(): void
    {
        $this->getJson('/api/mobile/facilities/' . $this->careFacility->id . '/providers')
            ->assertStatus(401);
    }

    // ── GET /api/mobile/providers/{id} ──────────────────────────────────────

    public function test_provider_detail_returns_profile_and_next_slots(): void
    {
        $slot = AppointmentSlot::create([
            'facility_id'  => $this->facility->id,
            'provider_id'  => $this->doctor->id,
            'starts_at'    => now()->addDay()->setTime(9, 0),
            'ends_at'      => now()->addDay()->setTime(9, 30),
            'capacity'     => 2,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        // Another clinician's slot at the same facility must not appear.
        $otherDoctor = User::factory()->create(['name' => 'Amara Diallo']);
        StaffProfile::create([
            'user_id'        => $otherDoctor->id,
            'facility_id'    => $this->facility->id,
            'first_name'     => 'Amara',
            'last_name'      => 'Diallo',
            'staff_category' => 'clinical',
            'status'         => 'active',
        ]);
        $otherSlot = AppointmentSlot::create([
            'facility_id'  => $this->facility->id,
            'provider_id'  => $otherDoctor->id,
            'starts_at'    => now()->addDay()->setTime(10, 0),
            'ends_at'      => now()->addDay()->setTime(10, 30),
            'capacity'     => 1,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $this->doctor->id);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'id', 'name', 'job_title', 'department', 'profession',
                'facility_id', 'care_facility_id', 'facility_name',
                'credentials', 'licenses', 'next_slots', 'messaging_appointment_id',
            ]]);

        $data = $response->json('data');
        $this->assertSame($this->doctor->id, $data['id']);
        $this->assertSame('Ibrahim Sow', $data['name']);
        $this->assertSame($this->careFacility->id, $data['care_facility_id']);
        $this->assertSame('City Medical Centre', $data['facility_name']);

        $slotIds = collect($data['next_slots'])->pluck('id')->all();
        $this->assertSame([$slot->id], $slotIds, 'Only this provider\'s slots should be returned');
        $this->assertNotContains($otherSlot->id, $slotIds);
        $this->assertSame(2, $data['next_slots'][0]['available_count']);
        $this->assertSame($this->doctor->id, $data['next_slots'][0]['provider_id']);

        // No prior appointment with this clinician yet → no messaging context.
        $this->assertNull($data['messaging_appointment_id']);
    }

    public function test_provider_detail_exposes_callers_own_appointment_for_messaging(): void
    {
        $appointment = Appointment::create([
            'patient_id'       => $this->patient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->doctor->id,
            'appointment_type' => 'consultation',
            'status'           => 'booked',
            'scheduled_at'     => now()->addDay(),
        ]);

        // Another patient's appointment with the same clinician must not leak.
        $otherPatient = Patient::create([
            'health_id'     => 'OC-TST-8888-0002-01',
            'first_name'    => 'Bob',
            'last_name'     => 'Other',
            'sex'           => 'male',
            'date_of_birth' => '1985-06-15',
            'is_demo'       => false,
        ]);
        $otherAppointment = Appointment::create([
            'patient_id'       => $otherPatient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->doctor->id,
            'appointment_type' => 'consultation',
            'status'           => 'booked',
            'scheduled_at'     => now()->addDays(3),
        ]);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $this->doctor->id);

        $response->assertStatus(200);
        $this->assertSame($appointment->id, $response->json('data.messaging_appointment_id'));
        $this->assertNotSame($otherAppointment->id, $response->json('data.messaging_appointment_id'));
    }

    public function test_provider_detail_404s_for_non_clinical_user(): void
    {
        $noise = $this->seedNoiseStaff();

        // An administrative staff account is a real users row — but it is not a
        // listed clinician, so probing its id must not reveal anything.
        $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $noise['admin']->id)
            ->assertStatus(404)
            ->assertJsonFragment(['error_code' => 'PROVIDER_NOT_FOUND']);

        // Same for a terminated clinician and a bare user with no staff profile.
        $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $noise['retired']->id)
            ->assertStatus(404);

        $stranger = User::factory()->create();
        $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $stranger->id)
            ->assertStatus(404);
    }

    public function test_provider_detail_404s_when_facility_is_not_publicly_listed(): void
    {
        $this->careFacility->update(['listing_status' => 'suspended']);

        $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $this->doctor->id)
            ->assertStatus(404);
    }

    public function test_provider_detail_requires_authentication(): void
    {
        $this->getJson('/api/mobile/providers/' . $this->doctor->id)
            ->assertStatus(401);
    }

    // ── Booking carries the clinician through ───────────────────────────────

    /**
     * Regression guard: MobileAppointmentController::book() used to drop the
     * slot's provider_id, so every patient-booked appointment came back with
     * provider_name null — which also made provider messaging unreachable,
     * since starting a thread requires an appointment with a provider.
     */
    public function test_booking_a_slot_records_its_clinician_on_the_appointment(): void
    {
        $slot = AppointmentSlot::create([
            'facility_id'  => $this->facility->id,
            'provider_id'  => $this->doctor->id,
            'starts_at'    => now()->addDay()->setTime(9, 0),
            'ends_at'      => now()->addDay()->setTime(9, 30),
            'capacity'     => 1,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        $response = $this->mobilePostJson($this->patient, '/api/mobile/appointments', [
            'facility_id'         => $this->facility->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['provider_name' => 'Ibrahim Sow']);

        $this->assertDatabaseHas('appointments', [
            'patient_id'          => $this->patient->id,
            'appointment_slot_id' => $slot->id,
            'provider_id'         => $this->doctor->id,
        ]);

        // …and that appointment is now valid messaging context for this clinician.
        $detail = $this->mobileGetJson($this->patient, '/api/mobile/providers/' . $this->doctor->id);
        $this->assertNotNull($detail->json('data.messaging_appointment_id'));
    }
}
