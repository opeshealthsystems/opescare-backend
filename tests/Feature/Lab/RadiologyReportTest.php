<?php
namespace Tests\Feature\Lab;

use App\Models\DicomStudy;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_radiology_report(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $order = LabOrder::create([
            'patient_id'  => $patient->id,
            'ordered_by'  => $provider->id,
            'facility_id' => $facility->id,
            'test_name'   => 'Chest X-Ray',
            'urgency'     => 'routine',
            'status'      => 'resulted',
        ]);

        $study = DicomStudy::create([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'lab_order_id'=> $order->id,
            'study_uid'   => '1.2.3.4',
            'modality'    => 'CR',
            'study_date'  => '2026-06-01',
            'status'      => 'available',
        ]);

        $report = RadiologyReport::create([
            'patient_id'          => $patient->id,
            'facility_id'         => $facility->id,
            'dicom_study_id'      => $study->id,
            'ordered_by'          => $provider->id,
            'reported_by'         => $provider->id,
            'modality'            => 'xray',
            'body_part'           => 'Chest',
            'study_date'          => '2026-06-01 09:00:00',
            'clinical_indication' => 'Cough, fever',
            'findings'            => 'No active pulmonary disease. Heart size normal.',
            'impression'          => 'Normal chest radiograph.',
            'status'              => 'final',
        ]);

        $this->assertEquals('final', $report->status);
        $this->assertStringContainsString('Normal', $report->impression);
    }
}
