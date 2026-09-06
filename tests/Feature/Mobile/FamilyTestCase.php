<?php

namespace Tests\Feature\Mobile;

use App\Http\Middleware\ActAsDependent;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Shared fixtures for the mobile family / dependents suite.
 *
 * A "guardian" here is what the API needs one to be: a Patient (the bearer
 * token is issued to a patient) with a linked User (links hang off
 * users.patient_id). Rows are created on the current side of the demo
 * partition so every scoped query in the code under test can see them.
 */
abstract class FamilyTestCase extends TestCase
{
    use RefreshDatabase;

    protected function demoFlag(): bool
    {
        return (bool) config('demo.enabled');
    }

    protected function makePatient(array $attrs = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'identity_status' => 'verified',
            'is_demo'         => $this->demoFlag(),
        ], $attrs));
    }

    protected function makeChild(array $attrs = []): Patient
    {
        return $this->makePatient(array_merge([
            'health_id'     => 'CM-HID-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
            'first_name'    => 'Demo',
            'last_name'     => 'Boy',
            'sex'           => 'male',
            'date_of_birth' => '2017-03-12',
            'blood_group'   => 'B+',
        ], $attrs));
    }

    protected function makeUserFor(Patient $patient, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'patient_id' => $patient->id,
            'is_demo'    => $this->demoFlag(),
        ], $attrs));
    }

    /** @return array{patient: Patient, user: User, headers: array<string, string>} */
    protected function makeGuardian(array $patientAttrs = [], array $userAttrs = []): array
    {
        $patient = $this->makePatient(array_merge([
            'first_name'    => 'Marie',
            'last_name'     => 'Guardian',
            'sex'           => 'female',
            'date_of_birth' => '1985-06-02',
        ], $patientAttrs));
        $user = $this->makeUserFor($patient, array_merge(['name' => 'Marie Guardian'], $userAttrs));

        return ['patient' => $patient, 'user' => $user, 'headers' => $this->headersFor($patient)];
    }

    /** A valid mobile bearer for the patient, exactly as MobileAuthController issues one. */
    protected function headersFor(Patient $patient): array
    {
        $raw = 'pat_' . Str::random(40);

        PatientAccessToken::create([
            'patient_id'   => $patient->id,
            'token_hash'   => Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);

        return ['Authorization' => "Bearer {$raw}"];
    }

    protected function link(User $guardian, Patient $dependent, array $attrs = []): FamilyLink
    {
        return FamilyLink::factory()->create(array_merge([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'relationship'         => 'parent',
            'access_level'         => FamilyLink::ACCESS_FULL,
            'status'               => FamilyLink::STATUS_ACTIVE,
            'created_by'           => FamilyLink::CREATED_BY_SELF_REGISTERED,
        ], $attrs));
    }

    /**
     * Start a request as exactly these headers. Laravel's withHeaders() MERGES
     * into headers that persist for the whole test, so a previous call's
     * act-as header (or bearer) would silently ride along into the next one.
     */
    protected function as(array $headers): static
    {
        return $this->flushHeaders()->withHeaders($headers);
    }

    /** The guardian's own headers plus the act-as header for a dependent. */
    protected function actingFor(array $headers, Patient|string $dependent): array
    {
        return $headers + [ActAsDependent::HEADER => $dependent instanceof Patient ? $dependent->id : $dependent];
    }
}
