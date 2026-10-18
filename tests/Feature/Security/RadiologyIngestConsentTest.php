<?php

namespace Tests\Feature\Security;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/v1/radiology/reports let any integration client write findings
 * for any patient UUID. It now needs the same consent grant FHIR ingest does,
 * and `ordered_by` must be staff at the client's facility.
 */
class RadiologyIngestConsentTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $patient;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->patient  = Patient::factory()->create(['is_demo' => false]);
        $this->doctor   = User::factory()->create([
            'role_id'             => Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor'])->id,
            'primary_facility_id' => $this->facility->id,
        ]);
    }

    private function headers(): array
    {
        return ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret', 'Accept' => 'application/json'];
    }

    private function body(array $overrides = []): array
    {
        return array_merge([
            'facility_id'         => $this->facility->id, // the testing bypass client's facility
            'patient_id'          => $this->patient->id,
            'ordered_by'          => $this->doctor->id,
            'reported_by'         => $this->doctor->id,
            'modality'            => 'xray',
            'body_part'           => 'Chest',
            'study_date'          => now()->toDateString(),
            'clinical_indication' => 'Cough',
            'findings'            => 'Clear lung fields.',
            'impression'          => 'Normal chest.',
        ], $overrides);
    }

    private function grantConsent(?Facility $facility = null): void
    {
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => ($facility ?? $this->facility)->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['clinical:write'],
            'status'            => 'active',
            'expires_at'        => now()->addYear(),
            'is_demo'           => false,
        ]);
    }

    public function test_with_consent_the_report_is_created(): void
    {
        $this->grantConsent();

        $this->postJson('/api/v1/radiology/reports', $this->body(), $this->headers())->assertCreated();
        $this->assertSame(1, RadiologyReport::where('patient_id', $this->patient->id)->count());
    }

    public function test_without_consent_the_write_is_refused_like_fhir(): void
    {
        $res = $this->postJson('/api/v1/radiology/reports', $this->body(), $this->headers());

        $res->assertNotFound(); // FHIR answers "no consent" as 404 too
        $this->assertSame('OperationOutcome', $res->json('resourceType'));
        $this->assertSame(0, RadiologyReport::count());
    }

    public function test_consent_to_another_facility_does_not_count(): void
    {
        $this->grantConsent(Facility::factory()->create());

        $this->postJson('/api/v1/radiology/reports', $this->body(), $this->headers())->assertNotFound();
        $this->assertSame(0, RadiologyReport::count());
    }

    public function test_unknown_patient_gets_the_same_refusal(): void
    {
        $res = $this->postJson('/api/v1/radiology/reports', $this->body(['patient_id' => (string) \Illuminate\Support\Str::uuid()]), $this->headers());
        $res->assertNotFound();
        $this->assertSame('OperationOutcome', $res->json('resourceType'));
    }

    public function test_ordered_by_must_be_staff_of_the_clients_facility(): void
    {
        $this->grantConsent();
        $outsider = User::factory()->create([
            'role_id'             => Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor'])->id,
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);

        $this->postJson('/api/v1/radiology/reports', $this->body(['ordered_by' => $outsider->id]), $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('ordered_by');
        $this->assertSame(0, RadiologyReport::count());
    }

    public function test_another_facilitys_report_is_404(): void
    {
        $this->grantConsent();
        $id = $this->postJson('/api/v1/radiology/reports', $this->body(), $this->headers())->assertCreated()->json('data.id');

        $other = Facility::factory()->create();
        $this->getJson('/api/v1/radiology/reports/' . $id . '?facility_id=' . $other->id, $this->headers())->assertNotFound();
    }
}
