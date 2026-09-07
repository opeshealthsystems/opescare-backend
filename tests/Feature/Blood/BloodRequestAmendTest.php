<?php

namespace Tests\Feature\Blood;

use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Reading and amending one blood request (PM-BLOOD-05, PM-BLOOD-06).
 *
 * The two rules worth pinning are about what CANNOT change and who cannot see:
 * an amendment may not move the group, component or facility, because those
 * three are what was checked against stock when the hold was taken; and another
 * patient's request is not found, never merely forbidden.
 */
class BloodRequestAmendTest extends TestCase
{
    use RefreshDatabase;

    private function actingPatient(): array
    {
        $patient = Patient::factory()->create();
        $plain = 'test_' . Str::random(40);

        PatientAccessToken::create([
            'patient_id'        => $patient->id,
            'token_prefix'      => substr($plain, 0, 12),
            'token_hash'        => Hash::make($plain),
            'token_lookup_hash' => hash('sha256', $plain),
            'name'              => 'test',
            'expires_at'        => now()->addHour(),
        ]);

        return [$patient, ['Authorization' => 'Bearer ' . $plain, 'Accept' => 'application/json']];
    }

    private function makeRequest(Patient $patient, string $status = 'pending'): BloodRequest
    {
        // `care_facility_id` is NOT NULL: a blood request is always a request
        // AT somewhere, which is exactly why an amendment may not move it.
        $listing = CareFacility::create([
            'facility_name'  => 'Yaounde Central Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'city'           => 'Yaounde',
            'region'         => 'Centre',
            'country_code'   => 'CM',
            'address'        => 'Rue de la Reunification',
            'phone_primary'  => '+237222234000',
        ]);

        return BloodRequest::create([
            'care_facility_id' => $listing->id,
            'reference'      => 'BLD-' . Str::upper(Str::random(8)),
            'patient_id'     => $patient->id,
            'blood_group'    => 'O+',
            'component_type' => 'whole_blood',
            'quantity'       => 2,
            'urgency'        => 'routine',
            'status'         => $status,
            'contact_phone'  => '+237600000000',
        ]);
    }

    public function test_a_patient_reads_their_own_request(): void
    {
        [$patient, $headers] = $this->actingPatient();
        $request = $this->makeRequest($patient);

        $this->getJson('/api/mobile/blood/requests/' . $request->id, $headers)
            ->assertOk()
            ->assertJsonPath('data.reference', $request->reference)
            ->assertJsonPath('data.quantity', 2);
    }

    public function test_another_patients_request_is_not_found_rather_than_forbidden(): void
    {
        [$owner] = $this->actingPatient();
        $request = $this->makeRequest($owner);

        [, $stranger] = $this->actingPatient();

        // 404, not 403: learning that an id exists but belongs to someone else
        // is itself a disclosure.
        $this->getJson('/api/mobile/blood/requests/' . $request->id, $stranger)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'REQUEST_NOT_FOUND');

        $this->patchJson('/api/mobile/blood/requests/' . $request->id, ['quantity' => 5], $stranger)
            ->assertNotFound();
    }

    public function test_an_amendment_changes_what_it_may(): void
    {
        [$patient, $headers] = $this->actingPatient();
        $request = $this->makeRequest($patient);

        $this->patchJson('/api/mobile/blood/requests/' . $request->id, [
            'quantity'      => 3,
            'urgency'       => 'urgent',
            'note'          => 'Ward changed to ER.',
            'contact_phone' => '+237600000999',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.quantity', 3)
            ->assertJsonPath('data.urgency', 'urgent')
            ->assertJsonPath('data.patient_note', 'Ward changed to ER.');
    }

    public function test_an_amendment_cannot_move_the_group_component_or_facility(): void
    {
        [$patient, $headers] = $this->actingPatient();
        $request = $this->makeRequest($patient);

        $this->patchJson('/api/mobile/blood/requests/' . $request->id, [
            'blood_group'      => 'AB-',
            'component_type'   => 'platelets',
            'care_facility_id' => (string) Str::uuid(),
            'quantity'         => 4,
        ], $headers)->assertOk();

        $request->refresh();

        // The three that define what was reserved are untouched; only the
        // quantity moved.
        $this->assertSame('O+', $request->blood_group->value);
        $this->assertSame('whole_blood', $request->component_type->value);
        $this->assertSame(4, $request->quantity);
    }

    public function test_a_closed_request_can_no_longer_be_amended(): void
    {
        [$patient, $headers] = $this->actingPatient();
        $request = $this->makeRequest($patient, 'fulfilled');

        $this->patchJson('/api/mobile/blood/requests/' . $request->id, ['quantity' => 4], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'REQUEST_NOT_EDITABLE');

        $this->assertSame(2, $request->refresh()->quantity);
    }
}
