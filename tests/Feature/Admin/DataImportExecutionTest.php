<?php

namespace Tests\Feature\Admin;

use App\Jobs\ExecuteImportJob;
use App\Models\AuditEvent;
use App\Models\DrugFormulary;
use App\Models\Facility;
use App\Models\ImportAuditEvent;
use App\Models\ImportJob;
use App\Models\ImportRollback;
use App\Models\Patient;
use App\Models\User;
use App\Modules\DataImport\Services\ImportRollbackService;
use App\Modules\DataImport\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What an approved import actually does, and what rolling it back actually
 * un-does.
 *
 * The two invariants worth the most here: a patient row is created through
 * PatientIdentityService (so dedup, a canonical Health ID and an audit event
 * all happen) rather than by a raw insert; and a rollback touches exactly the
 * rows recorded in import_record_links for that one job — never a row from
 * another job, never a row that was already there.
 */
class DataImportExecutionTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->facility = Facility::factory()->create();

        // The desk passes the authenticated user's UUID as the actor, and
        // audit_events.actor_id is a uuid column — so the actor must be one.
        $this->actor = User::factory()->create();
    }

    private function jobWithCsv(string $type, string $csv, array $mapping, array $attrs = []): ImportJob
    {
        $path = 'imports/private/import_'.Str::uuid().'.csv';
        Storage::disk('local')->put($path, $csv);

        $headers = str_getcsv(strtok($csv, "\n"));

        return ImportJob::create(array_merge([
            'facility_id' => $this->facility->id,
            'import_type' => $type,
            'status' => 'approved_for_import',
            'original_filename' => 'upload.csv',
            'stored_path' => $path,
            'file_extension' => 'csv',
            'file_size_bytes' => strlen($csv),
            'detected_headers' => $headers,
            'mapping' => $mapping,
            'created_by' => 'tester',
        ], $attrs));
    }

    private function execute(ImportJob $job, ?string $actor = null): void
    {
        (new ExecuteImportJob($job->id, $actor ?? $this->actor->id))->handle(app(ImportService::class));
    }

    // -- Patient import goes through the identity service ----------------------

    public function test_a_patient_row_is_created_through_the_identity_service_as_provisional(): void
    {
        $csv = "first_name,last_name,date_of_birth,gender\nAmina,Ngassa,1991-04-02,female\n";

        $job = $this->jobWithCsv('patients', $csv, [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'date_of_birth' => 'date_of_birth',
            'gender' => 'gender',
        ]);

        $this->execute($job);

        $this->assertSame(1, Patient::count());
        $patient = Patient::firstOrFail();

        // Provisional, not verified: an imported identity is a claim, not proof.
        $this->assertSame('provisional', $patient->identity_status->value);

        // A canonical Health ID was minted — a raw insert would not have one.
        $this->assertNotEmpty($patient->health_id);

        // The identity service writes this audit event; nothing else does.
        $this->assertSame(1, AuditEvent::where('patient_id', $patient->id)
            ->where('action_type', 'create')
            ->where('resource_type', 'patient')
            ->where('source_system', 'identity')
            ->where('actor_id', $this->actor->id)
            ->count());

        // Linked back to this job so the batch is reversible.
        $this->assertDatabaseHas('import_record_links', [
            'import_job_id' => $job->id,
            'target_table' => 'patients',
            'record_id' => $patient->id,
        ]);

        $job->refresh();
        $this->assertSame('completed', $job->status);
        $this->assertSame(1, $job->imported_rows);
    }

    /**
     * A person already holding a Health ID is never given a second one, and
     * nothing merges on its own.
     *
     * This test found a real defect and now guards the fix. The catch used to
     * classify by message —
     *     str_contains($e->getMessage(), 'Duplicate candidate')
     * — while DuplicateIdentityFound actually says
     *     'PATIENT_IDENTITY_DUPLICATE: <health ids>'.
     * The substring never matched, so duplicate_rows was always 0 and every
     * correctly-deduped patient was reported to the operator as a FAILURE.
     * The catch now matches the exception TYPE, which cannot drift when
     * somebody rewords a message.
     */
    public function test_a_duplicate_candidate_is_never_created_twice(): void
    {
        $csv = "first_name,last_name,date_of_birth,gender\nAmina,Ngassa,1991-04-02,female\n";
        $mapping = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'date_of_birth' => 'date_of_birth',
            'gender' => 'gender',
        ];

        $this->execute($this->jobWithCsv('patients', $csv, $mapping));
        $this->assertSame(1, Patient::count());

        // The same person again: MPI refuses a second candidate rather than
        // creating a silent duplicate or auto-merging.
        $second = $this->jobWithCsv('patients', $csv, $mapping);
        $this->execute($second);

        // The invariant that matters: no second identity, no link row.
        $this->assertSame(1, Patient::count());
        $second->refresh();
        $this->assertSame(0, $second->imported_rows);
        $this->assertSame(0, DB::table('import_record_links')->where('import_job_id', $second->id)->count());

        // Reported as what it is: a duplicate, not a failure.
        $this->assertSame(1, $second->duplicate_rows, 'a deduped patient must be counted as a duplicate');
        $this->assertSame(0, $second->failed_rows, 'a deduped patient must not be reported to the operator as a failure');
    }

    public function test_a_row_missing_a_name_is_failed_not_imported(): void
    {
        $csv = "first_name,last_name,date_of_birth,gender\n,Ngassa,1991-04-02,female\n";

        $job = $this->jobWithCsv('patients', $csv, [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'date_of_birth' => 'date_of_birth',
            'gender' => 'gender',
        ]);

        $this->execute($job);

        $this->assertSame(0, Patient::count());
        $this->assertSame(1, $job->fresh()->failed_rows);
    }

    // -- Types with no target table park, they do not throw --------------------

    public function test_an_import_type_with_no_target_table_parks_at_requires_manual_review(): void
    {
        // pharmacy_stock is a supported upload type but this deployment has no
        // automated target for it.
        $csv = "drug_name,quantity,unit\nParacetamol,100,tablet\n";
        $job = $this->jobWithCsv('pharmacy_stock', $csv, [
            'drug_name' => 'drug_name',
            'quantity' => 'quantity',
            'unit' => 'unit',
        ]);

        $this->execute($job);

        $job->refresh();
        $this->assertSame('requires_manual_review', $job->status);
        $this->assertSame(0, $job->imported_rows);
        $this->assertSame(1, ImportAuditEvent::where('import_job_id', $job->id)
            ->where('action', 'deferred_to_manual')->count());

        // Parked, never claimed as completed.
        $this->assertSame(0, ImportAuditEvent::where('import_job_id', $job->id)
            ->where('action', 'completed')->count());
        $this->assertSame(0, DB::table('import_record_links')->where('import_job_id', $job->id)->count());
    }

    public function test_a_job_that_is_not_approved_is_ignored_by_the_worker(): void
    {
        $csv = "provider_name,provider_code\nActa,ACT\n";
        $job = $this->jobWithCsv('insurance_providers', $csv, [
            'provider_name' => 'provider_name',
            'provider_code' => 'provider_code',
        ], ['status' => 'validated']);

        $this->execute($job);

        $this->assertSame('validated', $job->fresh()->status);
        $this->assertSame(0, DB::table('insurance_providers')->count());
    }

    // -- Catalog import + rollback --------------------------------------------

    public function test_a_catalog_import_writes_rows_and_links_them_to_the_job(): void
    {
        $csv = "provider_name,provider_code\nActa Assurance,ACT\nBeneficia,BEN\n";
        $job = $this->jobWithCsv('insurance_providers', $csv, [
            'provider_name' => 'provider_name',
            'provider_code' => 'provider_code',
        ]);

        $this->execute($job);

        $this->assertSame(2, DB::table('insurance_providers')->count());
        $this->assertSame(2, DB::table('import_record_links')
            ->where('import_job_id', $job->id)->where('target_table', 'insurance_providers')->count());
        $this->assertSame('completed', $job->fresh()->status);
    }

    // -- Rollback -------------------------------------------------------------

    private function completedJob(): ImportJob
    {
        return ImportJob::create([
            'facility_id' => $this->facility->id,
            'import_type' => 'insurance_providers',
            'status' => 'completed',
            'original_filename' => 'x.csv',
            'stored_path' => 'imports/private/x.csv',
            'file_extension' => 'csv',
            'file_size_bytes' => 1,
            'imported_rows' => 1,
            'created_by' => 'tester',
        ]);
    }

    private function link(ImportJob $job, string $table, string $recordId): void
    {
        DB::table('import_record_links')->insert([
            'id' => (string) Str::uuid(),
            'import_job_id' => $job->id,
            'target_table' => $table,
            'record_id' => $recordId,
            'created_at' => now(),
        ]);
    }

    private function provider(string $name): string
    {
        $id = (string) Str::uuid();
        DB::table('insurance_providers')->insert([
            'id' => $id, 'name' => $name, 'country_code' => 'CM', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_rollback_touches_only_the_rows_this_job_created(): void
    {
        $mine = $this->completedJob();
        $other = $this->completedJob();

        $minesRow = $this->provider('Mine');
        $othersRow = $this->provider('Theirs');
        $preExisting = $this->provider('Pre-existing');

        $this->link($mine, 'insurance_providers', $minesRow);
        $this->link($other, 'insurance_providers', $othersRow);

        app(ImportRollbackService::class)->rollback($mine, 'tester', 'wrong file');

        // Only my row is gone.
        $this->assertSame(0, DB::table('insurance_providers')->where('id', $minesRow)->count());
        $this->assertSame(1, DB::table('insurance_providers')->where('id', $othersRow)->count());
        $this->assertSame(1, DB::table('insurance_providers')->where('id', $preExisting)->count());

        // Links are KEPT on both sides. They are the record of what each import
        // created and what the rollback reversed — deleting them would leave a
        // retracted row with nothing explaining why.
        $this->assertSame(1, DB::table('import_record_links')->where('import_job_id', $mine->id)->count());
        $this->assertSame(1, DB::table('import_record_links')->where('import_job_id', $other->id)->count());

        $this->assertSame('rolled_back', $mine->fresh()->status);
        $this->assertSame('completed', $other->fresh()->status);
    }

    /**
     * A patient is NEVER hard-deleted by a rollback.
     *
     * 89 tables cascade-delete from `patients` — diagnoses, visits,
     * allergy_records, consent_grants and medical_id_access_events among
     * them. A DELETE here would take a person's clinical record and the audit
     * trail of who opened it, which is exactly what the platform's
     * immutability rule exists to prevent. The row is marked
     * `entered_in_error` instead: the platform's own word for a record that
     * should never have existed.
     */
    public function test_rollback_retracts_a_patient_rather_than_destroying_them(): void
    {
        $job = $this->completedJob();

        $patient = Patient::factory()->create(['identity_status' => 'provisional']);
        $this->link($job, 'patients', $patient->id);

        app(ImportRollbackService::class)->rollback($job, 'tester', 'wrong file');

        // The row survives — nothing cascaded.
        $this->assertSame(1, DB::table('patients')->where('id', $patient->id)->count());

        // And it reads as what it is.
        $this->assertSame(
            'entered_in_error',
            DB::table('patients')->where('id', $patient->id)->value('identity_status'),
        );

        // The trace of why survives too.
        $this->assertDatabaseHas('import_record_links', [
            'import_job_id' => $job->id,
            'target_table'  => 'patients',
            'record_id'     => $patient->id,
        ]);
    }

    public function test_rollback_prefers_a_soft_delete_where_the_table_carries_deleted_at(): void
    {
        $job = $this->completedJob();

        // drug_formularies has deleted_at; insurance_providers does not.
        $soft = DrugFormulary::factory()->create();
        $hard = $this->provider('Hard');

        $this->link($job, 'drug_formularies', $soft->id);
        $this->link($job, 'insurance_providers', $hard);

        app(ImportRollbackService::class)->rollback($job, 'tester', null);

        $softRow = DB::table('drug_formularies')->where('id', $soft->id)->first();
        $this->assertNotNull($softRow, 'a soft-deletable row must be stamped, not destroyed');
        $this->assertNotNull($softRow->deleted_at);

        $this->assertSame(0, DB::table('insurance_providers')->where('id', $hard)->count());

        $rollback = ImportRollback::where('import_job_id', $job->id)->firstOrFail();
        $this->assertSame('completed', $rollback->status);
        $this->assertSame(2, $rollback->rows_rolled_back);
        $this->assertStringContainsString('drug_formularies:soft_deleted', (string) $rollback->rollback_log);
        $this->assertStringContainsString('insurance_providers:deleted', (string) $rollback->rollback_log);
    }

    public function test_a_link_naming_a_table_this_deployment_does_not_carry_is_survived_not_thrown(): void
    {
        $job = $this->completedJob();
        $row = $this->provider('Real');

        $this->link($job, 'a_table_that_does_not_exist', (string) Str::uuid());
        $this->link($job, 'insurance_providers', $row);

        app(ImportRollbackService::class)->rollback($job, 'tester', null);

        $this->assertSame(0, DB::table('insurance_providers')->where('id', $row)->count());
        $this->assertSame('rolled_back', $job->fresh()->status);

        $rollback = ImportRollback::where('import_job_id', $job->id)->firstOrFail();
        $this->assertSame('completed', $rollback->status);
        $this->assertStringContainsString('missing_table', (string) $rollback->rollback_log);
    }

    public function test_a_job_that_never_completed_cannot_be_rolled_back(): void
    {
        $job = $this->completedJob();
        $job->forceFill(['status' => 'validated'])->save();
        $row = $this->provider('Untouched');
        $this->link($job, 'insurance_providers', $row);

        $this->expectException(\RuntimeException::class);

        try {
            app(ImportRollbackService::class)->rollback($job, 'tester', null);
        } finally {
            $this->assertSame(1, DB::table('insurance_providers')->where('id', $row)->count());
            $this->assertSame(0, ImportRollback::where('import_job_id', $job->id)->count());
        }
    }

    public function test_rollback_records_a_reason_and_an_audit_event(): void
    {
        $job = $this->completedJob();
        $this->link($job, 'insurance_providers', $this->provider('Gone'));

        app(ImportRollbackService::class)->rollback($job, 'officer@example.test', 'duplicate upload');

        $rollback = ImportRollback::where('import_job_id', $job->id)->firstOrFail();
        $this->assertSame('duplicate upload', $rollback->reason);
        $this->assertSame('officer@example.test', $rollback->initiated_by);

        $audit = ImportAuditEvent::where('import_job_id', $job->id)->where('action', 'rolled_back')->firstOrFail();
        $this->assertSame('officer@example.test', $audit->actor_id);
        $this->assertSame('duplicate upload', $audit->details['reason']);
        $this->assertSame(1, $audit->details['reverted_records']);
    }
}
