<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;

/**
 * Registering a walk-in from the front desk app: a provisional Health ID
 * through PatientIdentityService, and a 409 — never a second identity — when
 * the MPI already knows the person.
 */
class FrontDeskWalkInTest extends FrontDeskTestCase
{
    private function walkIn(array $overrides = []): array
    {
        return array_merge([
            'first_name'    => 'Blaise',
            'last_name'     => 'Tchoua',
            'date_of_birth' => '1988-06-14',
            'sex'           => 'male',
            'phone'         => '+237 690 000 111',
        ], $overrides);
    }

    public function test_registering_a_walk_in_mints_a_provisional_health_id(): void
    {
        $response = $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn())
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Blaise')
            ->assertJsonPath('data.last_name', 'Tchoua')
            ->assertJsonPath('data.full_name', 'Blaise Tchoua')
            ->assertJsonPath('data.date_of_birth', '1988-06-14')
            ->assertJsonPath('data.sex', 'male')
            ->assertJsonStructure(['data' => ['id', 'health_id', 'first_name', 'last_name', 'full_name', 'date_of_birth', 'sex', 'blood_group']]);

        // The ONE professional-facing patient shape (ProviderPatientSummary) — nothing more.
        $this->assertSame(['id', 'health_id', 'first_name', 'last_name', 'full_name', 'date_of_birth', 'sex', 'blood_group'], array_keys($response->json('data')));

        $healthId = $response->json('data.health_id');
        $this->assertMatchesRegularExpression('/^CM-HID-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $healthId);

        $patient = Patient::withoutGlobalScopes()->where('health_id', $healthId)->firstOrFail();
        $this->assertSame('provisional', $patient->identity_status->value);
        $this->assertSame('CM', $patient->country_code);
        $this->assertSame((bool) config('demo.enabled'), (bool) $patient->is_demo);
        $this->assertSame('+237 690 000 111', $patient->phone_number);

        // Created THROUGH the identity service: its audit row names the desk and the facility.
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->users['receptionist']->id,
            'facility_id'   => $this->facility->id,
            'patient_id'    => $patient->id,
            'action_type'   => 'create',
            'resource_type' => 'patient',
            'source_system' => 'identity',
        ]);

        // And the desk can book them a second later, by that Health ID.
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => $healthId, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertCreated()->assertJsonPath('data.patient.id', $patient->id);
    }

    public function test_a_person_who_already_holds_a_health_id_is_a_409_and_never_a_second_identity(): void
    {
        // Same name and date of birth as the fixture patient — the MPI's
        // demographic match. Amara Ndi's Health ID must NOT appear in the answer.
        $dob = $this->patient->date_of_birth->toDateString();

        $response = $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn([
            'first_name' => 'Amara', 'last_name' => 'Ndi', 'date_of_birth' => $dob, 'sex' => $this->patient->sex, 'phone' => null,
        ]))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'duplicate_identity')
            ->assertJsonPath('message', __('desk_api.duplicate_identity'))
            ->assertJsonMissingPath('data');

        $this->assertStringNotContainsString(self::HEALTH_ID, $response->getContent());
        $this->assertSame(1, Patient::withoutGlobalScopes()->count(), 'no second identity minted');

        // A phone number that is already on file is a match on its own.
        $this->patient->forceFill(['phone_number' => '+237 655 123 456'])->save();

        $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn(['phone' => '+237 655 123 456']))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'duplicate_identity');

        $this->assertSame(1, Patient::withoutGlobalScopes()->count());
    }

    public function test_registration_validates_its_input(): void
    {
        $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'date_of_birth', 'sex']);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn(['sex' => 'other']))
            ->assertStatus(422)->assertJsonValidationErrors(['sex']);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn(['date_of_birth' => now()->addDay()->toDateString()]))
            ->assertStatus(422)->assertJsonValidationErrors(['date_of_birth']);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn(['date_of_birth' => '14/06/1988']))
            ->assertStatus(422)->assertJsonValidationErrors(['date_of_birth']);

        $this->assertSame(1, Patient::withoutGlobalScopes()->count());
    }

    public function test_a_walk_in_at_a_demo_facility_joins_the_demo_partition(): void
    {
        $this->facility->forceFill(['is_demo' => true])->save();

        $healthId = $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn())
            ->assertCreated()
            ->json('data.health_id');

        $this->assertTrue((bool) Patient::withoutGlobalScopes()->where('health_id', $healthId)->value('is_demo'));
    }

    public function test_the_duplicate_refusal_carries_nothing_but_the_code_and_the_sentence(): void
    {
        $response = $this->hit('receptionist', 'POST', '/api/mobile/desk/patients', $this->walkIn([
            'first_name'    => 'Amara',
            'last_name'     => 'Ndi',
            'date_of_birth' => $this->patient->date_of_birth->toDateString(),
            'sex'           => $this->patient->sex,
            'phone'         => null,
        ]))->assertStatus(409);

        // Exactly these two keys, in this order: no match count, no Health ID,
        // no name — the desk learns that a match exists, not who or how many.
        $this->assertSame([
            'error_code' => 'duplicate_identity',
            'message'    => __('desk_api.duplicate_identity'),
        ], $response->json());
        $this->assertArrayNotHasKey('matches', $response->json());

        $this->assertSame(1, Patient::withoutGlobalScopes()->count());
    }
}
