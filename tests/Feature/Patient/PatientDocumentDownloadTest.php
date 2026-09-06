<?php

namespace Tests\Feature\Patient;

use App\Models\Admission;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Patient\PatientDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A patient can take their documents away, and none of them is ever stored.
 *
 * The second half is the part worth a test. Before this, the download handlers
 * read `official_documents.pdf_path` — a column nothing in the codebase has
 * ever written — so every Download button returned "no report file". The fix
 * was to render on the way out, and the risk of that fix is that somebody later
 * "optimises" it by caching the bytes to disk. That would put copies of medical
 * records on a filesystem, going stale and needing guarding, which is exactly
 * what the design avoids. So the test asserts the disk stays empty.
 */
class PatientDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private string $facilityId;

    protected function setUp(): void
    {
        parent::setUp();

        // lab_orders.facility_id and prescriptions.facility_id are NOT NULL
        // with no default.
        $this->facilityId = (string) \App\Models\Facility::factory()->create()->id;

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-DOCS-0001',
            'first_name' => 'Ayo',
            'last_name'  => 'Mbeki',
        ]);
    }

    public function test_a_released_lab_panel_can_be_downloaded_and_nothing_is_written_to_disk(): void
    {
        Storage::fake('local');

        $order = LabOrder::create([
            'id'          => (string) Str::uuid(),
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facilityId,
            'test_name'   => 'Full Blood Count',
            'status'      => 'resulted',
            'ordered_at'  => now()->subDay(),
            'resulted_at' => now(),
        ]);

        [$bytes, $filename] = app(PatientDocumentService::class)
            ->render($this->patient, PatientDocumentService::LABORATORY_REPORT, $order->id);

        $this->assertStringStartsWith('%PDF', $bytes, 'the download must be a PDF');
        $this->assertStringContainsString('laboratory-report', $filename);

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles(),
            'a document must never be written to disk — it is rendered on the way out'
        );
    }

    public function test_an_unreleased_panel_is_not_offered_as_a_report(): void
    {
        $order = LabOrder::create([
            'id'         => (string) Str::uuid(),
            'patient_id' => $this->patient->id,
            'facility_id' => $this->facilityId,
            'test_name'  => 'Renal Function Test',
            'status'     => 'ordered',
            'ordered_at' => now(),
        ]);

        // Handing over values no clinician has released is what the release
        // step exists to prevent.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(PatientDocumentService::class)
            ->render($this->patient, PatientDocumentService::LABORATORY_REPORT, $order->id);
    }

    public function test_one_patient_cannot_render_another_patients_record(): void
    {
        $other = Patient::factory()->create(['health_id' => 'CM-HID-DOCS-0002']);

        $theirs = Prescription::create([
            'id'            => (string) Str::uuid(),
            'patient_id'    => $other->id,
            'facility_id'   => $this->facilityId,
            'reference'     => 'RX-OTHER-0001',
            'status'        => 'active',
            'prescribed_at' => now(),
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(PatientDocumentService::class)
            ->render($this->patient, PatientDocumentService::PRESCRIPTION, $theirs->id);
    }

    public function test_an_open_admission_has_no_discharge_summary(): void
    {
        // admissions.bed_id is NOT NULL with a foreign key, so a stay cannot
        // be recorded without a ward and a bed to put it in.
        $wardId = (string) Str::uuid();
        \Illuminate\Support\Facades\DB::table('wards')->insert([
            'id' => $wardId, 'facility_id' => $this->facilityId,
            'name' => 'Test Ward', 'ward_type' => 'general',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $bedId = (string) Str::uuid();
        \Illuminate\Support\Facades\DB::table('beds')->insert([
            'id' => $bedId, 'ward_id' => $wardId, 'bed_number' => 'A-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $admission = Admission::create([
            'id'            => (string) Str::uuid(),
            'facility_id'   => $this->facilityId,
            'patient_id'    => $this->patient->id,
            'bed_id'        => $bedId,
            'admitted_by'   => (string) \App\Models\User::factory()->create()->id,
            'admitted_at'   => now()->subDays(2),
            'discharged_at' => null,
            'status'        => 'active',
        ]);

        // A discharge summary for someone who has not been discharged reads as
        // a discharge.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(PatientDocumentService::class)
            ->render($this->patient, PatientDocumentService::DISCHARGE_SUMMARY, $admission->id);
    }

    public function test_the_available_list_matches_what_can_actually_be_rendered(): void
    {
        LabOrder::create([
            'id'          => (string) Str::uuid(),
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facilityId,
            'test_name'   => 'Fasting Blood Glucose',
            'status'      => 'resulted',
            'ordered_at'  => now()->subDay(),
            'resulted_at' => now(),
        ]);

        Prescription::create([
            'id'            => (string) Str::uuid(),
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->facilityId,
            'reference'     => 'RX-DOCS-0001',
            'status'        => 'active',
            'prescribed_at' => now(),
        ]);

        ImmunizationRecord::create([
            'id'              => (string) Str::uuid(),
            'patient_id'      => $this->patient->id,
            'facility_id'     => \App\Models\Facility::factory()->create()->id,
            'vaccine_name'    => 'Yellow Fever',
            'vaccine_code'    => 'YF',
            'administered_at' => now()->subYear(),
            'status'          => 'completed',
        ]);

        $service   = app(PatientDocumentService::class);
        $available = $service->available($this->patient);

        $this->assertCount(3, $available);

        // The promise the list makes is that every row can be produced. A list
        // that offers a document the download then refuses is the bug this
        // whole change exists to remove.
        foreach ($available as $item) {
            [$bytes] = $service->render($this->patient, $item['type'], $item['record']);
            $this->assertStringStartsWith('%PDF', $bytes, "{$item['type']} could not be rendered");
        }
    }

    public function test_a_patient_with_no_records_is_offered_nothing(): void
    {
        $this->assertSame([], app(PatientDocumentService::class)->available($this->patient));
    }
}
