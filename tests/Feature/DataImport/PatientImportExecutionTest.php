<?php

namespace Tests\Feature\DataImport;

use App\Jobs\ExecuteImportJob;
use App\Models\Facility;
use App\Models\ImportJob;
use App\Models\Patient;
use App\Models\User;
use App\Modules\DataImport\Services\ImportRollbackService;
use App\Modules\DataImport\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GAP-005 — data-import execution for patient records.
 *
 * Patient imports must actually create patients (via PatientIdentityService:
 * dedup + canonical Health ID + audit provenance), record provenance links,
 * and be cleanly rollback-able.
 */
class PatientImportExecutionTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private string $actorId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->facility = Facility::factory()->create();
        $this->actorId  = (string) User::factory()->create()->id;
    }

    private function makeJob(string $csv, ?array $headers = null, ?array $mapping = null): ImportJob
    {
        Storage::disk('local')->put('imports/test.csv', $csv);

        $headers ??= ['first_name', 'last_name', 'date_of_birth', 'gender'];
        $mapping ??= [
            'first_name'    => 'first_name',
            'last_name'     => 'last_name',
            'date_of_birth' => 'date_of_birth',
            'gender'        => 'gender',
        ];

        return ImportJob::create([
            'facility_id'      => $this->facility->id,
            'import_type'      => 'patients',
            'status'           => 'approved_for_import',
            'original_filename' => 'patients.csv',
            'stored_path'      => 'imports/test.csv',
            'file_extension'   => 'csv',
            'detected_headers' => $headers,
            'mapping'          => $mapping,
            'created_by'       => null,
        ]);
    }

    private function runImport(ImportJob $job): void
    {
        (new ExecuteImportJob($job->id, $this->actorId))->handle(app(ImportService::class));
    }

    public function test_imports_patients_with_canonical_health_id_and_provenance(): void
    {
        $job = $this->makeJob(
            "first_name,last_name,date_of_birth,gender\n" .
            "Awa,Nkeng,1990-04-12,female\n" .
            "Junior,Fointama,1985-09-30,male\n"
        );

        $this->runImport($job);
        $job->refresh();

        $this->assertSame('completed', $job->status);
        $this->assertSame(2, Patient::whereIn('last_name', ['Nkeng', 'Fointama'])->count());

        // Canonical Cameroon Health ID format (CM-HID-XXXX-XXXX-XXXX), not OC-MVP.
        foreach (Patient::whereIn('last_name', ['Nkeng', 'Fointama'])->get() as $p) {
            $this->assertMatchesRegularExpression('/^CM-HID-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $p->health_id);
        }

        // Provenance links recorded for rollback.
        $this->assertSame(2, DB::table('import_record_links')
            ->where('import_job_id', $job->id)->where('target_table', 'patients')->count());
    }

    public function test_skips_duplicate_rows(): void
    {
        // Demographic dedup (Master Patient Index) keys on name + phone + DOB + sex,
        // so the duplicate rows carry a matching phone number.
        $job = $this->makeJob(
            "first_name,last_name,date_of_birth,gender,phone\n" .
            "Marie,Tabi,1992-01-01,female,+237699112233\n" .
            "Marie,Tabi,1992-01-01,female,+237699112233\n",
            ['first_name', 'last_name', 'date_of_birth', 'gender', 'phone'],
            [
                'first_name'    => 'first_name',
                'last_name'     => 'last_name',
                'date_of_birth' => 'date_of_birth',
                'gender'        => 'gender',
                'phone'         => 'phone',
            ],
        );

        $this->runImport($job);
        $job->refresh();

        $this->assertSame(1, Patient::where('last_name', 'Tabi')->count());
        $this->assertGreaterThanOrEqual(1, (int) $job->duplicate_rows);
    }

    public function test_rollback_deletes_imported_patients(): void
    {
        $job = $this->makeJob(
            "first_name,last_name,date_of_birth,gender\n" .
            "Paul,Achidi,1975-06-06,male\n"
        );
        $this->runImport($job);
        $job->refresh();
        $this->assertSame(1, Patient::where('last_name', 'Achidi')->count());

        app(ImportRollbackService::class)->rollback($job, $this->actorId, 'test rollback');

        $this->assertSame(0, Patient::where('last_name', 'Achidi')->count());
        $this->assertSame(0, DB::table('import_record_links')->where('import_job_id', $job->id)->count());
        $this->assertSame('rolled_back', $job->fresh()->status);
    }
}
