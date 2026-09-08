<?php

namespace Tests\Feature\Fhir;

use App\Enums\VitalProvenance;
use App\Http\Controllers\Api\Fhir\FhirIngestController;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\FhirIngestEvent;
use App\Models\IntegrationClient;
use App\Models\PartnerDiagnosticReport;
use App\Models\PartnerImmunization;
use App\Models\PartnerObservation;
use App\Models\Patient;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The FHIR R4 ingest door, proved against the rules it exists to enforce.
 *
 * Each test below corresponds to a way this endpoint could quietly corrupt a
 * national health record, and asserts it cannot.
 */
class FhirIngestTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Facility $otherFacility;
    private IntegrationClient $client;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create();
        $this->otherFacility = Facility::factory()->create();

        $this->client = IntegrationClient::factory()->create([
            'facility_id' => $this->facility->id,
            'name'        => 'Douala Reference Laboratory Middleware',
            'scopes'      => [
                FhirIngestController::SCOPE_OBSERVATION,
                FhirIngestController::SCOPE_DIAGNOSTIC_REPORT,
                FhirIngestController::SCOPE_IMMUNIZATION,
            ],
        ]);

        $this->patient = Patient::factory()->create([
            'health_id' => 'OC-TST-001',
            'is_demo'   => false,
        ]);

        $this->seedVocabulary();
    }

    /**
     * The three published codes these tests exercise.
     *
     * `clinical_code_dictionary` is import-populated (opescare:import-loinc,
     * the CVX import) and RefreshDatabase leaves it empty, so a test that
     * needs to prove a code IS recognised has to put it there. The values are
     * copied verbatim from the imported vocabulary in the development
     * database - none of them is invented, and the check digits are real.
     */
    private function seedVocabulary(): void
    {
        DB::table('clinical_code_dictionary')->insert([
            [
                'id' => (string) Str::uuid(), 'system' => 'LOINC', 'code' => '2339-0',
                'display' => 'Glucose [Mass/volume] in Blood', 'kind' => 'term',
                'is_common' => true, 'is_active' => true, 'source' => 'LOINC',
            ],
            [
                'id' => (string) Str::uuid(), 'system' => 'LOINC', 'code' => '718-7',
                'display' => 'Hemoglobin [Mass/volume] in Blood', 'kind' => 'term',
                'is_common' => true, 'is_active' => true, 'source' => 'LOINC',
            ],
            [
                'id' => (string) Str::uuid(), 'system' => 'CVX', 'code' => '03',
                'display' => 'measles, mumps and rubella virus vaccine', 'kind' => 'vaccine',
                'is_common' => true, 'is_active' => true, 'source' => 'CDC',
            ],
        ]);
    }

    // -- helpers -----------------------------------------------------------

    private function token(?array $scopes = null, ?string $facilityId = null): string
    {
        return app(JwtService::class)->issue([
            'client_id'   => $this->client->client_id,
            'sub'         => $this->client->client_id,
            'facility_id' => $facilityId ?? $this->facility->id,
            'scopes'      => $scopes ?? $this->client->scopes,
            'environment' => 'sandbox',
        ]);
    }

    private function headers(?string $key = null, ?array $scopes = null): array
    {
        return [
            'Authorization'   => 'Bearer ' . $this->token($scopes),
            'Idempotency-Key' => $key ?? ('idem_' . Str::random(24)),
            'Content-Type'    => 'application/fhir+json',
            'Accept'          => 'application/fhir+json',
        ];
    }

    private function grantConsent(?Patient $patient = null, ?Facility $facility = null): ConsentGrant
    {
        return ConsentGrant::create([
            'patient_id'        => ($patient ?? $this->patient)->id,
            'facility_id'       => ($facility ?? $this->facility)->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['clinical:write'],
            'status'            => 'active',
            'expires_at'        => now()->addYear(),
            'is_demo'           => false,
        ]);
    }

    private function subject(string $healthId = 'OC-TST-001'): array
    {
        return [
            'identifier' => [
                'system' => 'https://opescare.com/fhir/health-id',
                'value'  => $healthId,
            ],
        ];
    }

    /** A blood-pressure vital, valid in every respect. */
    private function vitalObservation(array $overrides = []): array
    {
        return array_replace_recursive([
            'resourceType' => 'Observation',
            'status'       => 'final',
            'category'     => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code'   => 'vital-signs',
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => '8867-4',
                    'display' => 'Heart rate',
                ]],
            ],
            'subject'           => $this->subject(),
            'effectiveDateTime' => now()->subHour()->toAtomString(),
            'valueQuantity'     => ['value' => 78, 'unit' => 'beats/minute', 'system' => 'http://unitsofmeasure.org', 'code' => '/min'],
        ], $overrides);
    }

    /** A laboratory Observation, valid in every respect. */
    private function labObservation(array $overrides = []): array
    {
        return array_replace_recursive([
            'resourceType' => 'Observation',
            'status'       => 'final',
            'category'     => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code'   => 'laboratory',
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => '2339-0',
                    'display' => 'Glucose [Mass/volume] in Blood',
                ]],
            ],
            'subject'           => $this->subject(),
            'effectiveDateTime' => now()->subHours(2)->toAtomString(),
            'valueQuantity'     => ['value' => 5.4, 'unit' => 'mmol/L'],
        ], $overrides);
    }

    // =====================================================================
    // 1. A PARTNER MAY NEVER CREATE A HEALTH ID
    // =====================================================================

    /**
     * The route simply does not exist. There is no POST /Patient, no PUT, no
     * conditional create — nothing on this surface writes a patient.
     */
    public function test_there_is_no_patient_write_route_at_all(): void
    {
        $patientRoutes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/fhir/R4')) {
                continue;
            }

            if (str_contains($route->uri(), 'Patient')) {
                $patientRoutes[] = implode('|', $route->methods()) . ' ' . $route->uri();
            }
        }

        $this->assertSame([], $patientRoutes, 'The FHIR ingest surface must expose no Patient route.');

        // And a partner probing for one gets a 404 from the router, not a handler.
        $this->postJson('/api/fhir/R4/Patient', ['resourceType' => 'Patient'], $this->headers())
            ->assertStatus(404);
    }

    /**
     * An unknown Health ID is the end of the request. It is emphatically NOT
     * a signal to create the patient.
     */
    public function test_an_unknown_health_id_is_refused_and_creates_no_patient(): void
    {
        $this->grantConsent();
        $before = Patient::withoutGlobalScopes()->count();

        $response = $this->postJson(
            '/api/fhir/R4/Observation',
            $this->vitalObservation(['subject' => $this->subject('OC-DOES-NOT-EXIST')]),
            $this->headers()
        );

        $response->assertStatus(404);
        $response->assertJsonPath('resourceType', 'OperationOutcome');
        $response->assertJsonPath('issue.0.code', 'not-found');
        $this->assertStringContainsString('does not create', $response->json('issue.0.diagnostics'));

        $this->assertSame($before, Patient::withoutGlobalScopes()->count(), 'No patient may be created by an ingest write.');
        $this->assertSame(0, PartnerObservation::count());

        $this->assertDatabaseHas('fhir_ingest_events', [
            'outcome'      => FhirIngestEvent::OUTCOME_REFUSED,
            'refusal_code' => 'patient_unresolvable',
            'http_status'  => 404,
        ]);
    }

    /** subject.reference is not a second door onto the register. */
    public function test_subject_reference_is_not_accepted_as_a_patient_lookup(): void
    {
        $this->grantConsent();

        $resource = $this->vitalObservation();
        $resource['subject'] = ['reference' => 'Patient/' . $this->patient->id];

        $this->postJson('/api/fhir/R4/Observation', $resource, $this->headers())
            ->assertStatus(400)
            ->assertJsonPath('issue.0.code', 'structure');

        $this->assertSame(0, PartnerObservation::count());
    }

    /** The CapabilityStatement says so out loud, and is public. */
    public function test_capability_statement_declares_patient_is_never_created(): void
    {
        $response = $this->getJson('/api/fhir/R4/metadata');

        $response->assertOk();
        $response->assertJsonPath('resourceType', 'CapabilityStatement');
        $response->assertJsonPath('fhirVersion', '4.0.1');

        $resources = collect($response->json('rest.0.resource'));
        $patient   = $resources->firstWhere('type', 'Patient');

        $this->assertNotNull($patient, 'Patient must be declared, so the absence of writes is explicit.');
        $this->assertSame([], $patient['interaction'], 'Patient must declare no interactions.');
        $this->assertStringContainsString('NEVER CREATED HERE', $patient['documentation']);

        foreach (['Observation', 'DiagnosticReport', 'Immunization'] as $type) {
            $this->assertSame(
                [['code' => 'create']],
                $resources->firstWhere('type', $type)['interaction'],
                $type . ' must declare create and nothing else.'
            );
        }
    }

    // =====================================================================
    // 2. NO CONSENT, NO WRITE
    // =====================================================================

    public function test_a_write_without_consent_is_refused_and_stores_nothing(): void
    {
        // Deliberately no grantConsent() call.
        $response = $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers());

        // Answered EXACTLY as an unknown Health ID is answered. A partner must
        // not be able to tell "no such person" from "that person has not
        // consented to you" — the difference is a membership disclosure about
        // the national register. The real reason is recorded server-side in
        // fhir_ingest_events, against the authenticated client.
        $response->assertStatus(404);
        $response->assertJsonPath('resourceType', 'OperationOutcome');
        $response->assertJsonPath('issue.0.code', 'not-found');
        $this->assertStringNotContainsString('CONSENT_REQUIRED', (string) $response->json('issue.0.diagnostics'));

        $this->assertSame(0, PartnerObservation::count());
        $this->assertSame(0, DB::table('fhir_partner_diagnostic_reports')->count());

        $this->assertDatabaseHas('fhir_ingest_events', [
            'outcome'      => FhirIngestEvent::OUTCOME_REFUSED,
            'refusal_code' => 'consent_required',
            'http_status'  => 404,
        ]);
    }

    /**
     * THE ENUMERATION ORACLE MUST STAY CLOSED.
     *
     * A partner posting at a Health ID that belongs to nobody, and a partner
     * posting at a real patient who has not consented to them, must get
     * byte-identical answers. If those ever diverge, one partner credential
     * becomes a probe for the national register: post a throwaway Observation
     * at a guessed identifier and read the reply to learn whether that person
     * is registered in the health system at all.
     *
     * This compares the WHOLE response body, not just the status, because a
     * differing `diagnostics` string leaks existence just as effectively as a
     * differing status code.
     */
    public function test_an_unknown_health_id_and_an_unconsented_patient_are_indistinguishable(): void
    {
        // (a) a real patient who has granted this partner nothing
        $unconsented = $this->postJson(
            '/api/fhir/R4/Observation',
            $this->vitalObservation(),
            $this->headers()
        );

        // (b) a Health ID that belongs to nobody
        $body = $this->vitalObservation();
        $body['subject']['identifier']['value'] = 'OC-NOSUCHPATIENT-0000';

        $unknown = $this->postJson('/api/fhir/R4/Observation', $body, $this->headers());

        $this->assertSame(
            $unconsented->getStatusCode(),
            $unknown->getStatusCode(),
            'A consent refusal and an unknown Health ID must share a status code.'
        );

        $this->assertSame(
            $unconsented->json(),
            $unknown->json(),
            'The two refusals must be identical — a differing diagnostic leaks existence too.'
        );

        // Nothing stored, and the SERVER still knows the real reason.
        $this->assertSame(0, PartnerObservation::count());
        $this->assertDatabaseHas('fhir_ingest_events', ['refusal_code' => 'consent_required']);
    }

    /** A grant issued to a DIFFERENT facility is not this partner's consent. */
    public function test_another_facilitys_consent_grant_does_not_authorise_this_partner(): void
    {
        $this->grantConsent($this->patient, $this->otherFacility);

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers())
            ->assertStatus(404)
            ->assertJsonPath('issue.0.code', 'not-found');

        $this->assertSame(0, PartnerObservation::count());
    }

    public function test_an_expired_consent_grant_does_not_authorise_a_write(): void
    {
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['clinical:write'],
            'status'            => 'active',
            'expires_at'        => now()->subDay(),
            'is_demo'           => false,
        ]);

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers())
            ->assertStatus(404);

        $this->assertSame(0, PartnerObservation::count());
    }

    // =====================================================================
    // 3. facility_id COMES FROM THE TOKEN AND NOWHERE ELSE
    // =====================================================================

    public function test_a_facility_in_the_request_body_or_headers_is_ignored(): void
    {
        $this->grantConsent();

        $resource = $this->vitalObservation([
            // Every plausible way a partner might try to name a facility.
            'performer' => [['reference' => 'Organization/' . $this->otherFacility->id]],
        ]);
        $resource['facility_id']  = $this->otherFacility->id;
        $resource['organization'] = ['reference' => 'Organization/' . $this->otherFacility->id];

        $this->postJson('/api/fhir/R4/Observation', $resource, array_merge($this->headers(), [
            'X-Facility-Id' => $this->otherFacility->id,
        ]))->assertStatus(201);

        $stored = PartnerObservation::sole();

        $this->assertSame($this->facility->id, $stored->facility_id, 'The facility must come from the token.');
        $this->assertNotSame($this->otherFacility->id, $stored->facility_id);
    }

    public function test_a_token_carrying_no_facility_cannot_write(): void
    {
        $this->grantConsent();

        $token = app(JwtService::class)->issue([
            'client_id'   => $this->client->client_id,
            'scopes'      => $this->client->scopes,
            'environment' => 'sandbox',
        ]);

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), [
            'Authorization'   => 'Bearer ' . $token,
            'Idempotency-Key' => 'idem_' . Str::random(20),
        ])->assertStatus(403)->assertJsonPath('issue.0.code', 'forbidden');

        $this->assertSame(0, PartnerObservation::count());
    }

    // =====================================================================
    // 4. IDEMPOTENCY
    // =====================================================================

    public function test_the_same_idempotency_key_twice_creates_exactly_one_record(): void
    {
        $this->grantConsent();

        $key      = 'idem_fixed_key_for_retry';
        $resource = $this->vitalObservation();

        $first = $this->postJson('/api/fhir/R4/Observation', $resource, $this->headers($key));
        $first->assertStatus(201);

        $second = $this->postJson('/api/fhir/R4/Observation', $resource, $this->headers($key));
        $second->assertStatus(201);

        $this->assertSame(1, PartnerObservation::count(), 'A retry must not create a second clinical event.');
        $this->assertSame($first->json('id'), $second->json('id'), 'The retry must replay the original response.');
        $this->assertSame('HIT', $second->headers->get('X-Cache-Idempotency'));
    }

    public function test_reusing_an_idempotency_key_with_a_different_body_is_a_conflict(): void
    {
        $this->grantConsent();

        $key = 'idem_reused_key';

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers($key))
            ->assertStatus(201);

        $this->postJson(
            '/api/fhir/R4/Observation',
            $this->vitalObservation(['valueQuantity' => ['value' => 140, 'unit' => 'beats/minute']]),
            $this->headers($key)
        )->assertStatus(409)->assertJsonPath('issue.0.code', 'conflict');

        $this->assertSame(1, PartnerObservation::count());
    }

    public function test_a_write_without_an_idempotency_key_is_refused(): void
    {
        $this->grantConsent();

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), [
            'Authorization' => 'Bearer ' . $this->token(),
        ])->assertStatus(400)->assertJsonPath('issue.0.code', 'required');

        $this->assertSame(0, PartnerObservation::count());
    }

    // =====================================================================
    // 5. TERMINOLOGY
    // =====================================================================

    /**
     * 70563-7 was offered in this codebase once for a malaria rapid test. It
     * fails its own LOINC check digit, so it is not a LOINC code at all.
     */
    public function test_a_loinc_code_that_fails_its_check_digit_is_refused(): void
    {
        $this->grantConsent();

        $response = $this->postJson(
            '/api/fhir/R4/Observation',
            $this->labObservation(['code' => ['coding' => [[
                'system' => 'http://loinc.org',
                'code'   => '70563-7',
            ]]]]),
            $this->headers()
        );

        $response->assertStatus(400);
        $response->assertJsonPath('issue.0.code', 'code-invalid');
        $this->assertStringContainsString('check digit', $response->json('issue.0.diagnostics'));

        $this->assertSame(0, PartnerObservation::count());
        $this->assertDatabaseHas('fhir_ingest_events', ['refusal_code' => 'code_check_digit_failed']);
    }

    /** Well-formed but not published: still refused, never stored. */
    public function test_a_well_formed_loinc_that_is_not_in_the_dictionary_is_refused(): void
    {
        $this->grantConsent();

        // 9999999-7 passes the mod-10 check digit but is not an imported term.
        $this->assertTrue(\App\Support\Terminology\ClinicalCode::isLoinc('9999999-7'));

        $this->postJson(
            '/api/fhir/R4/Observation',
            $this->labObservation(['code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '9999999-7']]]]),
            $this->headers()
        )->assertStatus(400)->assertJsonPath('issue.0.code', 'code-invalid');

        $this->assertSame(0, PartnerObservation::count());
    }

    public function test_an_unknown_cvx_code_is_refused(): void
    {
        $this->grantConsent();

        $this->postJson('/api/fhir/R4/Immunization', [
            'resourceType'       => 'Immunization',
            'status'             => 'completed',
            'vaccineCode'        => ['coding' => [['system' => 'http://hl7.org/fhir/sid/cvx', 'code' => '9999']]],
            'patient'            => $this->subject(),
            'occurrenceDateTime' => now()->subDay()->toAtomString(),
        ], $this->headers())->assertStatus(400)->assertJsonPath('issue.0.code', 'code-invalid');

        $this->assertSame(0, PartnerImmunization::count());
    }

    // =====================================================================
    // 6. PROVENANCE
    // =====================================================================

    /**
     * A partner-supplied vital must never be mistakable for one a nurse took.
     */
    public function test_a_partner_supplied_vital_is_distinguishable_from_a_facility_measurement(): void
    {
        $this->grantConsent();

        // The partner even tries to CLAIM the reading was measured at a facility.
        $resource = $this->vitalObservation();
        $resource['meta'] = [
            'security' => [['system' => 'https://opescare.com/fhir/CodeSystem/provenance', 'code' => 'facility_measured']],
        ];
        $resource['provenance'] = 'facility_measured';

        $response = $this->postJson('/api/fhir/R4/Observation', $resource, $this->headers());
        $response->assertStatus(201);

        $stored = PartnerObservation::sole();

        // 1. Structural: it is not in either facility-measurement table.
        $this->assertSame(0, DB::table('vital_signs')->count());
        $this->assertSame(0, DB::table('triage_vital_signs')->count());
        $this->assertSame(0, DB::table('patient_recorded_vitals')->count());

        // 2. The column says partner_reported, and the partner's claim to the
        //    contrary was ignored.
        $this->assertSame('partner_reported', DB::table('fhir_partner_observations')->value('provenance'));

        // 3. The typed answer every reader gets.
        $this->assertSame(VitalProvenance::PartnerReported, $stored->provenance());
        $this->assertFalse($stored->provenance()->isClinicallyVerified());
        $this->assertTrue(VitalProvenance::FacilityMeasured->isClinicallyVerified());

        // 4. And it says so on the wire too, naming the system that sent it.
        $response->assertJsonPath('meta.security.0.code', 'partner_reported');
        $this->assertStringContainsString('Douala Reference Laboratory', $response->json('meta.source'));
    }

    /** `provenance` is not fillable: mass assignment cannot move it. */
    public function test_provenance_cannot_be_mass_assigned(): void
    {
        $this->grantConsent();
        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers())->assertStatus(201);

        $observation = PartnerObservation::sole();
        $observation->fill(['provenance' => 'facility_measured']);

        $this->assertNotSame('facility_measured', $observation->provenance);
    }

    // =====================================================================
    // Scopes, immutability, reports, bundles
    // =====================================================================

    public function test_a_token_without_the_resource_scope_is_refused_with_403(): void
    {
        $this->grantConsent();

        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), [
            'Authorization'   => 'Bearer ' . $this->token([FhirIngestController::SCOPE_IMMUNIZATION]),
            'Idempotency-Key' => 'idem_' . Str::random(20),
        ])->assertStatus(403);

        $this->assertSame(0, PartnerObservation::count());
    }

    public function test_a_stored_observation_cannot_be_overwritten_or_deleted(): void
    {
        $this->grantConsent();
        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers())->assertStatus(201);

        $observation = PartnerObservation::sole();

        $observation->value_quantity = 999;
        $this->expectException(\RuntimeException::class);
        $observation->save();
    }

    public function test_a_stored_observation_cannot_be_deleted(): void
    {
        $this->grantConsent();
        $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers())->assertStatus(201);

        $this->expectException(\RuntimeException::class);
        PartnerObservation::sole()->delete();
    }

    public function test_a_diagnostic_report_stores_its_contained_observations(): void
    {
        $this->grantConsent();

        $response = $this->postJson('/api/fhir/R4/DiagnosticReport', [
            'resourceType' => 'DiagnosticReport',
            'status'       => 'final',
            'code'         => ['coding' => [['system' => 'http://loinc.org', 'code' => '2339-0']]],
            'subject'      => $this->subject(),
            'effectiveDateTime' => now()->subHours(3)->toAtomString(),
            'issued'       => now()->subHours(2)->toAtomString(),
            'conclusion'   => 'Fasting glucose within range.',
            'contained'    => [
                $this->labObservation(),
                $this->labObservation(['code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '718-7']]], 'valueQuantity' => ['value' => 13.1, 'unit' => 'g/dL']]),
            ],
        ], $this->headers());

        $response->assertStatus(201);
        $this->assertSame(1, PartnerDiagnosticReport::count());
        $this->assertSame(2, PartnerObservation::count());
        $this->assertCount(2, $response->json('result'));

        $report = PartnerDiagnosticReport::sole();
        $this->assertSame(2, $report->observations()->count());
        $this->assertSame($this->facility->id, $report->facility_id);
    }

    /**
     * All-or-nothing. One bad analyte in a five-analyte report must leave NO
     * report at all, not four analytes and a silent gap.
     */
    public function test_one_bad_analyte_rolls_back_the_whole_report(): void
    {
        $this->grantConsent();

        $this->postJson('/api/fhir/R4/DiagnosticReport', [
            'resourceType'      => 'DiagnosticReport',
            'status'            => 'final',
            'code'              => ['coding' => [['system' => 'http://loinc.org', 'code' => '2339-0']]],
            'subject'           => $this->subject(),
            'effectiveDateTime' => now()->subHours(3)->toAtomString(),
            'contained'         => [
                $this->labObservation(),
                // Fails its check digit.
                $this->labObservation(['code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '70563-7']]]]),
            ],
        ], $this->headers())->assertStatus(400);

        $this->assertSame(0, PartnerDiagnosticReport::count(), 'The report must not survive a refused analyte.');
        $this->assertSame(0, PartnerObservation::count(), 'No analyte may survive a refused sibling.');
    }

    public function test_a_transaction_bundle_is_all_or_nothing(): void
    {
        $this->grantConsent();

        $entry = fn (array $resource) => ['request' => ['method' => 'POST', 'url' => $resource['resourceType']], 'resource' => $resource];

        // Happy path first.
        $ok = $this->postJson('/api/fhir/R4', [
            'resourceType' => 'Bundle',
            'type'         => 'transaction',
            'entry'        => [
                $entry($this->vitalObservation()),
                $entry([
                    'resourceType'       => 'Immunization',
                    'status'             => 'completed',
                    'vaccineCode'        => ['coding' => [['system' => 'http://hl7.org/fhir/sid/cvx', 'code' => '03']]],
                    'subject'            => $this->subject(),
                    'occurrenceDateTime' => now()->subDays(2)->toAtomString(),
                ]),
            ],
        ], $this->headers());

        $ok->assertStatus(200);
        $ok->assertJsonPath('type', 'transaction-response');
        $this->assertSame(1, PartnerObservation::count());
        $this->assertSame(1, PartnerImmunization::count());

        // Now a Bundle whose second entry is bad: neither entry may land.
        $this->postJson('/api/fhir/R4', [
            'resourceType' => 'Bundle',
            'type'         => 'transaction',
            'entry'        => [
                $entry($this->vitalObservation(['effectiveDateTime' => now()->subMinutes(30)->toAtomString()])),
                $entry($this->labObservation(['code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '70563-7']]]])),
            ],
        ], $this->headers())->assertStatus(400);

        $this->assertSame(1, PartnerObservation::count(), 'The rolled-back Bundle must have added nothing.');
    }

    /** A Bundle is not a back door to creating a patient. */
    public function test_a_bundle_carrying_a_patient_resource_is_refused(): void
    {
        $this->grantConsent();
        $before = Patient::withoutGlobalScopes()->count();

        $this->postJson('/api/fhir/R4', [
            'resourceType' => 'Bundle',
            'type'         => 'transaction',
            'entry'        => [[
                'request'  => ['method' => 'POST', 'url' => 'Patient'],
                'resource' => ['resourceType' => 'Patient', 'name' => [['family' => 'Nobody']]],
            ]],
        ], $this->headers())->assertStatus(422);

        $this->assertSame($before, Patient::withoutGlobalScopes()->count());
    }

    public function test_a_supersede_keeps_the_original_and_marks_it_amended(): void
    {
        $this->grantConsent();

        $first = $this->postJson('/api/fhir/R4/Observation', $this->vitalObservation(), $this->headers());
        $first->assertStatus(201);
        $originalId = $first->json('id');

        $correction = $this->vitalObservation(['valueQuantity' => ['value' => 82, 'unit' => 'beats/minute']]);
        $correction['extension'] = [[
            'url'         => \App\Services\Fhir\FhirIngestService::SUPERSEDES_EXTENSION,
            'valueString' => $originalId,
        ]];

        $this->postJson('/api/fhir/R4/Observation', $correction, $this->headers())->assertStatus(201);

        $this->assertSame(2, PartnerObservation::count(), 'The original must still exist.');
        $this->assertSame('amended', PartnerObservation::findOrFail($originalId)->status);
        $this->assertSame($originalId, PartnerObservation::where('id', '!=', $originalId)->sole()->supersedes_id);
    }
}
