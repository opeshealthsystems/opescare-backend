<?php

namespace Tests\Feature\Documents;

use App\Models\Admission;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\DocumentVerification;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\TriageVitalSign;
use App\Models\User;
use App\Services\Documents\PrescriptionSheetFacts;
use App\Services\Patient\PatientDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The existing documents on the OpesCare Secure sheet: each renders as a PDF
 * and carries the fields its spec (docs/specs/documents/) requires.
 *
 * Content is asserted on the rendered HTML (dompdf compresses the PDF text
 * streams); the PDF itself is asserted to render and to be sealed.
 */
class ExistingDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    private Patient $patient;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->facility = Facility::factory()->create([
            'name'           => 'Hopital Test Akwa',
            'type'           => 'hospital',
            'license_number' => 'LIC-HTA-777',
        ]);

        $role = Role::where('name', 'doctor')->firstOrFail();
        $this->doctor = User::factory()->create([
            'name'                => 'Dr Ngo Biyong',
            'role_id'             => $role->id,
            'primary_facility_id' => $this->facility->id,
            'status'              => 'active',
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $this->doctor->id, 'facility_id' => $this->facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->patient = Patient::factory()->create([
            'health_id'     => 'CM-HID-EXDOC-0001',
            'first_name'    => 'Ayo',
            'last_name'     => 'Mbeki',
            'sex'           => 'female',
            'date_of_birth' => '1990-04-12',
            'is_demo'       => (bool) config('demo.enabled'),
        ]);

        AllergyRecord::create(['patient_id' => $this->patient->id, 'provider_id' => $this->doctor->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'status' => 'active']);

        TriageVitalSign::create([
            'triage_record_id' => (string) Str::uuid(), 'recorded_by' => (string) Str::uuid(),
            'patient_id' => $this->patient->id,
            'weight_kg' => 64.5, 'recorded_at' => now()->subDays(3),
        ]);
    }

    private function prescription(string $status = 'active'): Prescription
    {
        $rx = Prescription::create([
            'id'            => (string) Str::uuid(),
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->facility->id,
            'prescribed_by' => $this->doctor->id,
            'reference'     => 'RX-EXDOC-0001',
            'status'        => $status,
            'notes'         => 'Take with food.',
            'prescribed_at' => now()->subHour(),
            'expires_at'    => now()->addDays(30),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Amoxicillin',
            'strength'        => '500 mg',
            'dosage_form'     => 'capsule',
            'dose'            => '1 capsule',
            'route'           => 'oral',
            'frequency'       => 'three times daily',
            'duration_days'   => 7,
            'quantity'        => 21,
        ]);

        return $rx->fresh(['items', 'facility']);
    }

    private function assertPdf(string $bytes): void
    {
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertGreaterThan(10_000, strlen($bytes));

        // Optional: DOC_DUMP_DIR=<dir> keeps each rendered PDF for a manual
        // pdftotext / visual check. Off by default; never in the repo.
        if ($dir = getenv('DOC_DUMP_DIR')) {
            @file_put_contents(rtrim($dir, '/\\') . '/' . $this->name() . '.pdf', $bytes);
        }
    }

    private function sheetHtml(string $view, array $data): string
    {
        $issued = app(\App\Services\Patient\DocumentVerificationService::class)->issue($this->patient, 'test');

        return view($view, $data + [
            'patient'      => $this->patient,
            'issuedAt'     => now(),
            'verification' => $issued['record'],
            'verifyQr'     => null,
        ])->render();
    }

    // ── Shared sheet ─────────────────────────────────────────────────────

    public function test_the_secure_sheet_prints_page_n_of_m_and_supports_the_toner_light_variant(): void
    {
        $rx   = $this->prescription();
        $html = $this->sheetHtml('documents.prescription', [
            'prescription' => $rx,
            'facts'        => app(PrescriptionSheetFacts::class)->for($rx),
        ]);

        $this->assertStringContainsString('counter(pages)', $html);
        $this->assertStringContainsString('class="tone-standard"', $html);
        $this->assertStringContainsString('document-sheet-a4.jpg', $html);

        $light = $this->sheetHtml('documents.prescription', [
            'prescription' => $rx,
            'facts'        => app(PrescriptionSheetFacts::class)->for($rx),
            'sheetTone'    => 'light',
        ]);

        $this->assertStringContainsString('class="tone-light"', $light);
        $this->assertStringNotContainsString('document-sheet-a4.jpg', $light, 'the toner-light sheet lays down no background art');
    }

    // ── Prescription ─────────────────────────────────────────────────────

    public function test_the_patient_prescription_pdf_renders_and_carries_the_who_elements(): void
    {
        Storage::fake('local');
        $rx = $this->prescription();

        [$bytes] = app(PatientDocumentService::class)->render($this->patient, PatientDocumentService::PRESCRIPTION, $rx->id);
        $this->assertPdf($bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $html = $this->sheetHtml('documents.prescription', [
            'prescription' => $rx,
            'facts'        => app(PrescriptionSheetFacts::class)->for($rx),
        ]);

        foreach (['Dr Ngo Biyong', 'LIC-HTA-777', 'Hopital Test Akwa', 'CM-HID-EXDOC-0001', 'Penicillin',
                  '64.5 kg', 'Amoxicillin', '500 mg capsule', '1 capsule', 'three times daily', '7 days', '21',
                  'Take with food.', 'Registration no.', 'Prescriber signature and stamp'] as $needle) {
            $this->assertStringContainsString($needle, $html, "prescription PDF is missing: {$needle}");
        }
    }

    public function test_a_voided_prescription_carries_a_watermark(): void
    {
        $rx   = $this->prescription('voided');
        $html = $this->sheetHtml('documents.prescription', [
            'prescription' => $rx,
            'facts'        => app(PrescriptionSheetFacts::class)->for($rx),
        ]);

        $this->assertStringContainsString('class="watermark"', $html);
    }

    public function test_the_clinic_print_sheet_agrees_with_the_patient_pdf(): void
    {
        $rx = $this->prescription();
        Appointment::factory()->create([
            'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'provider_id' => null, 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->doctor)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get(route('portals.prescriber.print', $rx->id))
            ->assertOk()
            ->assertSee('Penicillin')
            ->assertSee('64.5 kg')
            ->assertSee('500 mg capsule')
            ->assertSee('LIC-HTA-777');
    }

    // ── Laboratory report ───────────────────────────────────────────────

    public function test_the_lab_report_prints_collection_validation_and_the_indication_under_its_own_name(): void
    {
        $order = LabOrder::create([
            'id'                  => (string) Str::uuid(),
            'patient_id'          => $this->patient->id,
            'facility_id'         => $this->facility->id,
            'ordered_by'          => $this->doctor->id,
            'verified_by'         => $this->doctor->id,
            'test_name'           => 'Full Blood Count',
            'clinical_indication' => 'Fatigue for two weeks',
            'status'              => 'resulted',
            'ordered_at'          => now()->subDay(),
            'collected_at'        => now()->subHours(20),
            'resulted_at'         => now(),
        ]);
        LabResult::create([
            'lab_order_id' => $order->id, 'patient_id' => $this->patient->id,
            'parameter_name' => 'Haemoglobin', 'value' => '10.1', 'unit' => 'g/dL',
            'reference_range' => '12.0-16.0', 'flag' => 'L', 'notes' => 'Repeat in 4 weeks',
        ]);

        [$bytes] = app(PatientDocumentService::class)->render($this->patient, PatientDocumentService::LABORATORY_REPORT, $order->id);
        $this->assertPdf($bytes);

        $html = $this->sheetHtml('documents.laboratory-report', ['order' => $order->fresh(['results', 'facility'])]);

        foreach (['Collected', 'Validated by', 'Dr Ngo Biyong', 'g/dL', '12.0-16.0', 'Repeat in 4 weeks',
                  'Clinical indication (reason for the test)', 'Fatigue for two weeks'] as $needle) {
            $this->assertStringContainsString($needle, $html, "lab report is missing: {$needle}");
        }
        $this->assertStringNotContainsString('>Interpretation<', $html);
    }

    // ── Discharge summary ──────────────────────────────────────────────

    public function test_the_discharge_summary_prints_real_diagnoses_and_leaves_empty_sections_empty(): void
    {
        $wardId = (string) Str::uuid();
        DB::table('wards')->insert(['id' => $wardId, 'facility_id' => $this->facility->id, 'name' => 'Medical Ward B', 'ward_type' => 'general', 'created_at' => now(), 'updated_at' => now()]);
        $bedId = (string) Str::uuid();
        DB::table('beds')->insert(['id' => $bedId, 'ward_id' => $wardId, 'bed_number' => 'B-02', 'created_at' => now(), 'updated_at' => now()]);

        $visitId = (string) Str::uuid();
        DB::table('visits')->insert(['id' => $visitId, 'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'visit_type' => 'inpatient', 'status' => 'closed', 'started_at' => now()->subDays(4), 'created_at' => now(), 'updated_at' => now()]);

        $admission = Admission::create([
            'visit_id' => $visitId,
            'id' => (string) Str::uuid(), 'facility_id' => $this->facility->id, 'patient_id' => $this->patient->id,
            'bed_id' => $bedId, 'admitted_by' => $this->doctor->id, 'attending_physician_id' => $this->doctor->id,
            'admission_reason' => 'Severe malaria', 'admitted_at' => now()->subDays(4),
            'discharged_at' => now()->subHour(), 'discharge_reason' => 'Recovered', 'status' => 'discharged',
        ]);

        Diagnosis::create([
            'patient_id' => $this->patient->id, 'visit_id' => $visitId, 'provider_id' => $this->doctor->id, 'code_system' => 'icd10',
            'code' => 'B50.9', 'display_name' => 'Plasmodium falciparum malaria', 'status' => 'active', 'is_primary' => true,
        ]);

        [$bytes] = app(PatientDocumentService::class)->render($this->patient, PatientDocumentService::DISCHARGE_SUMMARY, $admission->id);
        $this->assertPdf($bytes);

        $gather = new \ReflectionMethod(PatientDocumentService::class, 'dischargeData');
        $data   = $gather->invoke(app(PatientDocumentService::class), $this->patient, $admission->id);
        $html   = $this->sheetHtml('documents.discharge-summary', $data);

        foreach (['Plasmodium falciparum malaria', 'B50.9', 'Severe malaria', 'Recovered', 'Dr Ngo Biyong',
                  'Hopital Test Akwa', 'Penicillin'] as $needle) {
            $this->assertStringContainsString($needle, $html, "discharge summary is missing: {$needle}");
        }
        // No discharge plan was recorded: the follow-up section says so and invents nothing.
        $this->assertStringContainsString('Nothing recorded for this stay.', $html);
    }

    // ── Certificates ───────────────────────────────────────────────────

    public function test_a_doctor_issues_a_sick_note_and_both_sides_can_print_it(): void
    {
        Appointment::factory()->create([
            'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'provider_id' => null, 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->doctor)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get(route('portals.certificates.index'))
            ->assertOk();

        $this->actingAs($this->doctor)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->post(route('portals.certificates.store'), [
                'patient_id'  => $this->patient->id,
                'type'        => 'sick_note',
                'examined_on' => now()->toDateString(),
                'rest_from'   => now()->toDateString(),
                'rest_to'     => now()->addDays(2)->toDateString(),
                'statement'   => 'Rest is medically required.',
            ])
            ->assertRedirect(route('portals.certificates.index'));

        $doc = OfficialDocument::where('patient_id', $this->patient->id)->where('document_type', 'sick_note')->sole();
        $this->assertSame((string) $this->facility->id, (string) $doc->facility_id);
        $this->assertSame(3, (int) $doc->payload_json['rest_days']);
        $this->assertSame('Dr Ngo Biyong', $doc->payload_json['issuer_name']);

        $response = $this->actingAs($this->doctor)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get(route('portals.certificates.pdf', $doc->id));
        $response->assertOk();
        $this->assertPdf($response->streamedContent());

        $html = $this->sheetHtml('documents.certificate', ['document' => $doc, 'facilityName' => $this->facility->name, 'facility' => $this->facility]);
        foreach (['Sick note (work stoppage)', 'Rest from', 'Rest until', '3 days', 'Dr Ngo Biyong', 'Rest is medically required.',
                  'does not disclose a diagnosis', 'Signature and facility stamp'] as $needle) {
            $this->assertStringContainsString($needle, $html, "sick note is missing: {$needle}");
        }
        $this->assertStringNotContainsString('Rest from' . "\n", ucfirst('rest_from'));
        $this->assertStringNotContainsString('Issuer name', $html, 'payload keys print translated, never raw');
    }

    public function test_a_non_clinician_cannot_open_the_certificate_desk(): void
    {
        $role = Role::where('name', 'receptionist')->firstOrFail();
        $clerk = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $this->facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create(['user_id' => $clerk->id, 'facility_id' => $this->facility->id, 'role_id' => $role->id, 'is_active' => true, 'assigned_at' => now()]);

        $this->actingAs($clerk)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get(route('portals.certificates.index'))
            ->assertForbidden();
    }

    // ── Immunisation record ────────────────────────────────────────────

    public function test_the_immunisation_record_renders(): void
    {
        ImmunizationRecord::create([
            'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'administered_by_id' => $this->doctor->id, 'vaccine_name' => 'Hepatitis B',
            'vaccine_code' => 'HepB', 'administered_at' => now()->subYear(), 'dose_number' => 1,
            'route' => 'intramuscular', 'site' => 'left deltoid', 'lot_number' => 'LOT-77', 'status' => 'completed',
        ]);

        [$bytes] = app(PatientDocumentService::class)->render($this->patient, PatientDocumentService::IMMUNISATION);
        $this->assertPdf($bytes);
    }

    // ── Record export ──────────────────────────────────────────────────

    public function test_the_record_export_service_stores_nothing(): void
    {
        Storage::fake('local');

        [$bytes, $filename] = app(\App\Services\Patient\MedicalRecordExportService::class)->render((string) $this->patient->id);

        $this->assertPdf($bytes);
        $this->assertStringContainsString('cm-hid-exdoc-0001', $filename);
        $this->assertStringNotContainsString('Mbeki', $filename);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'the record export must never be written to disk');
        $this->assertSame(hash('sha256', $bytes), DocumentVerification::where('patient_id', $this->patient->id)->latest('issued_at')->first()->sha256);
    }
}
