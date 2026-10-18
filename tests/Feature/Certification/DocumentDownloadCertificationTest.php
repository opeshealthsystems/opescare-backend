<?php

namespace Tests\Feature\Certification;

use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Services\Pharmacy\PharmacyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 2 functional certification: every PDF a seeded patient (or the
 * pharmacy desk) can download, fetched over HTTP through its real route and
 * middleware, must come back 200 as a well-formed PDF.
 */
class DocumentDownloadCertificationTest extends TestCase
{
    use CertificationSupport;
    use RefreshDatabase;

    private LabOrder $lab;

    private Prescription $rx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $facility = $this->facilityOfType('hospital');
        $patient  = $this->fixturePatient();

        $this->lab = LabOrder::create([
            'id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'test_name' => 'Full Blood Count', 'status' => 'resulted',
            'ordered_at' => now()->subDay(), 'resulted_at' => now(),
        ]);
        $this->rx = Prescription::create([
            'id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'reference' => 'RX-CERT-0001', 'status' => 'active', 'prescribed_at' => now(),
        ]);
    }

    private function assertPdf(TestResponse $response, string $what): void
    {
        $this->record('documents', ['document' => $what, 'status' => $response->getStatusCode()]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'), $what);
        $body = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            ? $response->streamedContent()
            : (string) $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body, "{$what} is not a PDF");
        $this->assertStringContainsString('%%EOF', substr($body, -1024), "{$what} PDF is truncated");
    }

    private function asPatient(): void
    {
        $this->actingAs($this->patientUser())->withSession(['mfa.verified' => true]);
    }

    public function test_health_id_card_pdf(): void
    {
        $this->asPatient();
        $this->assertPdf($this->get(route('portals.patient.health-id-card')), 'health-id-card');
    }

    public function test_patient_portal_record_documents(): void
    {
        $this->asPatient();
        $this->assertPdf($this->get("/patient/documents/laboratory_report/{$this->lab->id}.pdf"), 'laboratory_report');
        $this->assertPdf($this->get("/patient/documents/prescription/{$this->rx->id}.pdf"), 'prescription');
    }

    public function test_patient_portal_immunisation_record(): void
    {
        $this->asPatient();
        $this->assertPdf($this->get('/patient/documents/immunisation_record.pdf'), 'immunisation_record');
    }

    public function test_mobile_signed_document_downloads(): void
    {
        $patientId = (string) $this->fixturePatient()->id;

        foreach ([['laboratory_report', $this->lab->id], ['prescription', $this->rx->id], ['immunisation_record', null]] as [$type, $record]) {
            $url = URL::temporarySignedRoute('mobile.documents.download', now()->addMinutes(15),
                array_filter(['type' => $type, 'patient' => $patientId, 'record' => $record]));
            $this->assertPdf($this->get($url), "mobile {$type}");
        }

        $url = URL::temporarySignedRoute('mobile.labs.report.download', now()->addMinutes(15),
            ['id' => (string) $this->lab->id, 'patient' => $patientId]);
        $this->assertPdf($this->get($url), 'mobile lab report');
    }

    public function test_unsigned_mobile_download_is_refused(): void
    {
        $this->get(route('mobile.documents.download', ['type' => 'prescription', 'patient' => $this->fixturePatient()->id, 'record' => $this->rx->id]))
            ->assertForbidden();
    }

    public function test_pharmacy_report_pdfs(): void
    {
        $user = $this->staffUser('pharmacy_manager', 'pharmacy');
        $this->actingAs($user)->withSession(['active_facility_id' => $this->certFacility->id, 'mfa.verified' => true]);

        foreach (PharmacyReportService::REPORTS as $report) {
            $this->assertPdf($this->get(route('portals.pharmacy.reports.pdf', $report)), "pharmacy {$report}");
        }
        $this->assertPdf($this->get(route('portals.pharmacy.controlled.register')), 'controlled register');
    }
}
