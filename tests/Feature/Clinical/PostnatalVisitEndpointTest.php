<?php

namespace Tests\Feature\Clinical;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PostnatalVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /v1/postnatal-visits
 *
 * The controller was complete — facility guard, fourteen validated fields, PNC
 * certificate issuance — and called MaternityService::recordPostnatalVisit(),
 * which existed nowhere in app/. Every request threw BadMethodCallException.
 * There was also no table: the controller shipped, the persistence layer never
 * did.
 *
 * MaternityTest covers the service. These cover the HTTP path, which is where
 * the break actually was, so a missing service method or a dropped route
 * cannot pass unnoticed again.
 */
class PostnatalVisitEndpointTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'X-Client-ID'     => 'test_client_id',
        'X-Client-Secret' => 'test_client_secret',
    ];

    private Facility $facility;
    private Patient $patient;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->facility = Facility::factory()->create();
        $this->patient  = Patient::factory()->create();
        $this->provider = User::factory()->create();
    }

    /** The test bypass in VerifyIntegrationClient takes facility_id from the body. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'facility_id'          => $this->facility->id,
            'patient_id'           => $this->patient->id,
            'provider_id'          => $this->provider->id,
            'visit_date'           => now()->subDays(3)->toDateString(),
            'days_postpartum'      => 3,
            'bp_systolic'          => 118,
            'bp_diastolic'         => 76,
            'weight_kg'            => 68.40,
            'lochia'               => 'rubra',
            'wound_healing'        => 'normal',
            'breastfeeding_status' => 'exclusive',
            'infant_weight_grams'  => 3200,
            'notes'                => 'Mother and infant well. No pyrexia.',
            'next_visit_plan'      => 'Review at day 7.',
        ], $overrides);
    }

    public function test_records_a_postnatal_visit(): void
    {
        $res = $this->postJson('/v1/postnatal-visits', $this->payload(), $this->headers);

        $this->assertContains($res->status(), [200, 201], 'endpoint should succeed, got ' . $res->status());

        $this->assertDatabaseHas('postnatal_visits', [
            'patient_id'      => $this->patient->id,
            'facility_id'     => $this->facility->id,
            'days_postpartum' => 3,
            'lochia'          => 'rubra',
        ]);

        $visit = PostnatalVisit::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($visit);
        $this->assertSame(3200, $visit->infant_weight_grams);
        $this->assertSame('exclusive', $visit->breastfeeding_status);
    }

    public function test_optional_clinical_fields_may_be_omitted(): void
    {
        // Postnatal care continues after a pregnancy record closes, and a
        // community visit may capture very little. Only the identifying fields
        // and days_postpartum are required.
        $res = $this->postJson('/v1/postnatal-visits', [
            'facility_id'     => $this->facility->id,
            'patient_id'      => $this->patient->id,
            'provider_id'     => $this->provider->id,
            'visit_date'      => now()->toDateString(),
            'days_postpartum' => 42,
        ], $this->headers);

        $this->assertContains($res->status(), [200, 201]);
        $this->assertDatabaseHas('postnatal_visits', [
            'patient_id'      => $this->patient->id,
            'days_postpartum' => 42,
            'lochia'          => null,
        ]);
    }

    public function test_rejects_an_unknown_patient(): void
    {
        $this->postJson('/v1/postnatal-visits', $this->payload([
            'patient_id' => fake()->uuid(),
        ]), $this->headers)->assertStatus(422);
    }

    public function test_rejects_a_value_outside_the_allowed_vocabulary(): void
    {
        $this->postJson('/v1/postnatal-visits', $this->payload([
            'lochia' => 'purple',
        ]), $this->headers)->assertStatus(422);
    }

    public function test_rejects_a_future_visit_date(): void
    {
        $this->postJson('/v1/postnatal-visits', $this->payload([
            'visit_date' => now()->addWeek()->toDateString(),
        ]), $this->headers)->assertStatus(422);
    }
}
