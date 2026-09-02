<?php
namespace Tests\Feature\Lab;

use App\Models\DicomStudy;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\Lab\DicomWebService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DicomStudyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_dicom_study(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $study = DicomStudy::create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'study_uid'    => '1.2.840.10008.5.1.4.1.1.2.1',
            'modality'     => 'CT',
            'body_part'    => 'Chest',
            'study_date'   => '2026-06-01',
            'accession_no' => 'ACC-2026-001',
            'status'       => 'available',
        ]);

        $this->assertEquals('CT', $study->modality);
        $this->assertEquals('available', $study->status);
    }

    public function test_dicomweb_service_builds_wado_url(): void
    {
        $service = new DicomWebService(
            wadoBaseUrl: 'https://pacs.hospital.cm/wado',
            stowBaseUrl: 'https://pacs.hospital.cm/stow',
        );

        $url = $service->buildWadoUrl('1.2.840.10008.5.1.4.1.1.2.1');

        $this->assertStringContainsString('1.2.840.10008.5.1.4.1.1.2.1', $url);
        $this->assertStringContainsString('wado', $url);
    }
}
