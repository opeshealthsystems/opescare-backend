<?php

namespace Tests\Feature\Mobile;

use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Structural validity of the patient FHIR R4 export
 * (POST /api/mobile/medical-records/export/fhir). Mirrors what the official
 * HL7 validator checks, see docs/certification/FHIR_EXPORT_VALIDATION.md.
 */
class FhirExportStructureTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-TEST-FHIR-0001',
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'sex'        => 'female',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        $this->token = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($this->token),
            'token_prefix' => substr($this->token, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);
    }

    private function export(): array
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/mobile/medical-records/export/fhir', []);
        $response->assertOk();

        $json = $response->json();

        return $json['data'] ?? $json;
    }

    private function seedRecord(): void
    {
        AllergyRecord::factory()->create(['patient_id' => $this->patient->id, 'substance' => 'Penicillin', 'status' => 'active']);
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'status' => 'dispensed']);
        PrescriptionItem::factory()->create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Amlodipine',
            'dose'            => '5mg',
            'frequency'       => 'Once daily',
            'duration_days'   => 30,
        ]);
        $visit = Visit::create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $rx->facility_id,
            'provider_id' => $rx->prescribed_by,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => now()->subMonth(),
        ]);
        Diagnosis::factory()->create([
            'patient_id'   => $this->patient->id,
            'visit_id'     => $visit->id,
            'provider_id'  => $rx->prescribed_by,
            'code_system'  => 'ICD-10',
            'code'         => 'I10',
            'display_name' => 'Essential hypertension',
            'status'       => 'chronic',
        ]);
    }

    public function test_bundle_is_structurally_valid_fhir_r4(): void
    {
        $this->seedRecord();
        $bundle = $this->export();

        $this->assertSame('Bundle', $bundle['resourceType']);
        $this->assertSame('collection', $bundle['type']);
        $this->assertArrayNotHasKey('total', $bundle, 'bdl-1: total only on searchset/history');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $bundle['timestamp']);

        $fullUrls = array_column($bundle['entry'], 'fullUrl');
        $this->assertCount(count($bundle['entry']), $fullUrls, 'every entry has a fullUrl');
        $this->assertSame($fullUrls, array_unique($fullUrls), 'fullUrls are unique');

        foreach ($bundle['entry'] as $entry) {
            $r = $entry['resource'];
            $this->assertNotEmpty($r['resourceType']);
            $this->assertNotEmpty($r['id']);
            $this->assertSame('urn:uuid:' . $r['id'], $entry['fullUrl']);
        }

        // Every reference anywhere in the bundle resolves to an entry.
        $refs = [];
        array_walk_recursive($bundle['entry'], function ($v, $k) use (&$refs) {
            if ($k === 'reference') {
                $refs[] = $v;
            }
        });
        $this->assertNotEmpty($refs);
        foreach ($refs as $ref) {
            $this->assertContains($ref, $fullUrls, "reference {$ref} resolves in-bundle");
        }

        $byType = [];
        foreach ($bundle['entry'] as $e) {
            $byType[$e['resource']['resourceType']][] = $e['resource'];
        }
        $this->assertCount(1, $byType['Patient']);
        $this->assertSame('female', $byType['Patient'][0]['gender']);

        $condition = $byType['Condition'][0];
        $this->assertSame('http://hl7.org/fhir/sid/icd-10', $condition['code']['coding'][0]['system']);
        $this->assertSame('I10', $condition['code']['coding'][0]['code']);
        $this->assertSame('http://terminology.hl7.org/CodeSystem/condition-clinical', $condition['clinicalStatus']['coding'][0]['system']);
        $this->assertSame('active', $condition['clinicalStatus']['coding'][0]['code']);
        $this->assertArrayNotHasKey('onsetDateTime', $condition, 'we do not hold an onset date');

        $allergy = $byType['AllergyIntolerance'][0];
        $this->assertSame('http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical', $allergy['clinicalStatus']['coding'][0]['system']);

        $med = $byType['MedicationRequest'][0];
        $this->assertSame('completed', $med['status']);
        $this->assertStringContainsString('Amlodipine', $med['medicationCodeableConcept']['text']);

        // Code systems used anywhere are absolute URIs, never bare labels.
        $systems = [];
        array_walk_recursive($bundle, function ($v, $k) use (&$systems) {
            if ($k === 'system') {
                $systems[] = $v;
            }
        });
        foreach ($systems as $s) {
            $this->assertMatchesRegularExpression('#^(https?://|urn:)#', $s);
        }
    }

    public function test_empty_record_exports_only_the_patient(): void
    {
        $bundle = $this->export();

        $this->assertSame('collection', $bundle['type']);
        $this->assertCount(1, $bundle['entry']);
        $this->assertSame('Patient', $bundle['entry'][0]['resource']['resourceType']);
    }
}
