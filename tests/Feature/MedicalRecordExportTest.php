<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\Patient\MedicalRecordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

class MedicalRecordExportTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private MedicalRecordExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('barryvdh/laravel-dompdf not installed — run: composer require barryvdh/laravel-dompdf');
        }
        $this->service = app(MedicalRecordExportService::class);
        Storage::fake('local');
    }

    public function test_pdf_is_generated_and_file_exists(): void
    {
        $patient = Patient::factory()->create();

        $path = $this->service->generatePdf($patient->id, [
            'include_vitals'        => false,
            'include_diagnoses'     => true,
            'include_medications'   => true,
            'include_labs'          => false,
            'include_immunizations' => false,
        ]);

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.pdf', $path);
        // With fake storage, the path is a temporary path; just check it was "generated"
        Storage::disk('local')->assertExists('exports/medical-records/' . basename($path));
    }

    public function test_fhir_bundle_has_correct_resource_type(): void
    {
        $patient = Patient::factory()->create([
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'date_of_birth' => '1990-01-15',
        ]);

        $bundle = $this->service->generateFhirBundle($patient->id);

        $this->assertEquals('Bundle', $bundle['resourceType']);
        $this->assertEquals('collection', $bundle['type']);
        $this->assertArrayHasKey('entry', $bundle);
        $this->assertGreaterThanOrEqual(1, count($bundle['entry']));
    }

    public function test_fhir_bundle_contains_patient_resource(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
        ]);

        $bundle = $this->service->generateFhirBundle($patient->id);

        $patientResource = collect($bundle['entry'])
            ->firstWhere('resource.resourceType', 'Patient');

        $this->assertNotNull($patientResource);
        $this->assertEquals('Smith', $patientResource['resource']['name'][0]['family']);
    }

    public function test_cleanup_deletes_old_export_files(): void
    {
        $patient = Patient::factory()->create();

        // Generate two PDFs
        $this->service->generatePdf($patient->id);
        $this->service->generatePdf($patient->id);

        // With 0 hours threshold, all files are considered old
        $deleted = $this->service->cleanupExports(0);

        $this->assertGreaterThanOrEqual(2, $deleted);
    }

    /**
     * POST /api/mobile/medical-records/export/pdf returns the PDF inline as
     * base64 (mobile-expo's export-records.tsx decodes + shares it directly —
     * there is no client-reachable download endpoint for the storage path).
     */
    public function test_mobile_export_pdf_endpoint_returns_base64_file(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->mobilePostJson($patient, '/api/mobile/medical-records/export/pdf');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'filename', 'mime_type', 'file_base64']);

        $this->assertSame('application/pdf', $response->json('mime_type'));
        $this->assertStringEndsWith('.pdf', $response->json('filename'));

        $decoded = base64_decode($response->json('file_base64'), true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith('%PDF', $decoded);
    }

    /**
     * POST /api/mobile/medical-records/export/fhir returns the FHIR R4
     * Bundle JSON directly — mobile-expo writes the response body to a
     * .json file and shares it as-is.
     */
    public function test_mobile_export_fhir_endpoint_returns_bundle(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->mobilePostJson($patient, '/api/mobile/medical-records/export/fhir');

        $response->assertStatus(200)
            ->assertJson(['resourceType' => 'Bundle', 'type' => 'collection']);
        $this->assertArrayHasKey('entry', $response->json());
    }
}
