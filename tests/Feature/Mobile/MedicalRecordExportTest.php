<?php

namespace Tests\Feature\Mobile;

use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\DocumentVerification;
use App\Models\Visit;
use App\Services\Patient\DocumentVerificationService;
use App\Support\QrSvg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The record export sits on the OpesCare Secure sheet: background art, the
 * security seal, a header band and a footer on every page, in the patient's
 * language, listing medicines by name rather than by prescription id.
 */
class MedicalRecordExportTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->patient = Patient::factory()->create([
            'health_id'   => 'CM-HID-TEST-EXPO-0001',
            'first_name'  => 'Amara',
            'last_name'   => 'Ndi',
            'sex'         => 'female',
            'blood_group' => 'O+',
            'is_demo'     => (bool) config('demo.enabled'),
        ]);

        $this->token = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($this->token),
            'token_prefix' => substr($this->token, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);
    }

    private function seedRecord(): Prescription
    {
        AllergyRecord::factory()->create(['patient_id' => $this->patient->id, 'substance' => 'Penicillin', 'severity' => 'severe']);
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'status' => 'active']);
        PrescriptionItem::factory()->create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Amlodipine',
            'dose'            => '5mg',
            'frequency'       => 'Once daily',
            'duration_days'   => 30,
            'status'          => 'dispensed',
        ]);

        // diagnoses.visit_id is NOT NULL with a real FK on Postgres, so the
        // diagnosis needs a visit — the factory's random uuid only works on SQLite.
        $visit = Visit::create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $rx->facility_id,
            'provider_id' => $rx->prescribed_by,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => now()->subMonth(),
        ]);
        Diagnosis::factory()->create(['patient_id' => $this->patient->id, 'visit_id' => $visit->id, 'provider_id' => $rx->prescribed_by, 'display_name' => 'Essential hypertension', 'code' => 'I10']);
        LabResult::factory()->create(['patient_id' => $this->patient->id, 'parameter_name' => 'Haemoglobin', 'value' => '11.2', 'unit' => 'g/dL', 'flag' => 'L']);
        ImmunizationRecord::create([
            'patient_id'      => $this->patient->id,
            'facility_id'     => $rx->facility_id,
            'vaccine_code'    => 'HEPB',
            'vaccine_name'    => 'Hepatitis B',
            'administered_at' => now()->subYear(),
            'dose_number'     => 1,
            'lot_number'      => 'HB-2025-17',
            'status'          => 'completed',
        ]);

        return $rx;
    }

    private function renderView(string $locale): string
    {
        app()->setLocale($locale);

        $patient = Patient::with([
            'allergies', 'diagnoses', 'prescriptions.items', 'prescriptions.facility',
            'vitals', 'labResults', 'immunizations',
        ])->findOrFail($this->patient->id);

        $issued = app(DocumentVerificationService::class)->issue($patient, DocumentVerificationService::TYPE_MEDICAL_RECORD);

        return view('exports.medical-record', [
            'patient'      => $patient,
            'issuedAt'     => now(),
            'verification' => $issued['record'],
            'verifyQr'     => QrSvg::pngDataUri($issued['verify_url']),
            'options'  => array_fill_keys(['include_vitals', 'include_diagnoses', 'include_medications', 'include_labs', 'include_immunizations'], true),
        ])->render();
    }

    public function test_export_endpoint_returns_a_pdf(): void
    {
        $this->seedRecord();

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/mobile/medical-records/export/pdf', [])
            ->assertOk()
            ->assertJsonStructure(['filename', 'mime_type', 'file_base64']);

        $pdf = base64_decode($response->json('file_base64'), true);

        $this->assertNotFalse($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(100_000, strlen($pdf), 'The sheet art (background, seal, band) should be embedded in the PDF.');

        // The export issued exactly one verifiable document and sealed the file's fingerprint onto it.
        $verification = DocumentVerification::where('patient_id', $this->patient->id)->sole();
        $this->assertSame(DocumentVerificationService::TYPE_MEDICAL_RECORD, $verification->document_type);
        $this->assertSame(hash('sha256', $pdf), $verification->sha256);
        $this->assertMatchesRegularExpression('/^OC-DOC-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $verification->reference);
    }

    public function test_record_sits_on_the_secure_sheet_with_the_seal(): void
    {
        $rx   = $this->seedRecord();
        $html = $this->renderView('en');

        foreach (['document-sheet-a4.jpg', 'sheet-band.jpg', 'sheet-seal.png'] as $asset) {
            $this->assertStringContainsString($asset, $html, "Sheet asset {$asset} is missing.");
        }

        $this->assertStringContainsString('OpesCare Secure document', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html, 'The verification QR is missing from the sheet.');
        $this->assertStringContainsString('Scan to verify', $html);
        $this->assertMatchesRegularExpression('/OC-DOC-[A-Z2-9]{4}-[A-Z2-9]{4}/', $html);
        $this->assertStringContainsString('CM-HID-TEST-EXPO-0001', $html);
        $this->assertStringContainsString('Amlodipine', $html);
        $this->assertStringContainsString('30 days', $html);
        $this->assertStringContainsString('Essential hypertension', $html);
        $this->assertStringContainsString('Penicillin', $html);
        $this->assertStringContainsString('Haemoglobin', $html);
        $this->assertStringContainsString('Hepatitis B', $html);
        $this->assertStringNotContainsString((string) $rx->id, $html, 'Medicines must be listed by name, not by prescription id.');
    }

    public function test_sheet_and_record_read_in_french_when_the_patient_does(): void
    {
        $this->seedRecord();
        $html = $this->renderView('fr');

        $this->assertStringContainsString('Document sécurisé OpesCare', $html);
        $this->assertStringContainsString('Dossier médical', $html);
        $this->assertStringContainsString('Traitements en cours', $html);
        $this->assertStringContainsString('30 jours', $html);
        $this->assertStringNotContainsString('Medical Record', $html);
    }

    public function test_verification_shield_only_appears_for_a_verified_patient(): void
    {
        $this->seedRecord();

        $this->patient->forceFill(['verification_status' => 'provisional'])->save();
        $this->assertStringNotContainsString('sheet-shield.png', $this->renderView('en'));

        $this->patient->forceFill(['verification_status' => 'verified'])->save();
        $this->assertStringContainsString('sheet-shield.png', $this->renderView('en'));
    }
}
