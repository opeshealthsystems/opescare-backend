<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Modules\DataImport\Services\ImportService;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ExecuteImportJob — runs an APPROVED import job.
 *
 * Ported from the main platform and re-pointed at the tables this app actually
 * has. The main version wrote straight into `drugs`, `lab_tests` and
 * `drug_stock_items`, none of which exist in this schema, and it inserted the
 * import's *system field names* as if they were database columns. Both would
 * have failed at runtime, so this port:
 *
 *  - only claims the three catalog tables this schema really carries, and
 *  - translates system field -> real column through COLUMN_MAP, dropping
 *    anything the table does not have.
 *
 * Every type without a mapping is parked at `requires_manual_review` rather
 * than half-imported. Every row created is recorded in `import_record_links`
 * so ImportRollbackService can reverse exactly this batch and nothing else.
 */use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;

class ExecuteImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /** import_type -> target table (only types this schema can actually receive). */
    private const TABLE_MAP = [
        'insurance_providers' => 'insurance_providers',
        'price_lists'         => 'service_prices',
        'inventory_items'     => 'inventory_items',
    ];

    /** import_type -> [system field => real column on the target table]. */
    private const COLUMN_MAP = [
        'insurance_providers' => [
            'provider_name' => 'name',
            'provider_code' => 'code',
            'contact_email' => 'contact_email',
            'contact_phone' => 'contact_phone',
            'country'       => 'country_code',
        ],
        'price_lists' => [
            'item_name' => 'service_name',
            'item_code' => 'service_code',
            'item_type' => 'service_category',
            'amount'    => 'base_price',
            'currency'  => 'currency',
        ],
        'inventory_items' => [
            'item_name'     => 'name',
            'item_code'     => 'code',
            'category'      => 'category',
            'unit'          => 'unit',
            'reorder_level' => 'reorder_level',
            'notes'         => 'description',
        ],
    ];

    public function __construct(
        public readonly string $importJobId,
        public readonly string $actorId,
    ) {}

    public function handle(ImportService $svc): void
    {
        $job = ImportJob::findOrFail($this->importJobId);

        if ($job->status !== 'approved_for_import') {
            Log::warning('ExecuteImportJob: unexpected status', ['id' => $this->importJobId, 'status' => $job->status]);

            return;
        }

        $job->forceFill(['status' => 'importing', 'import_started_at' => now()])->save();
        $svc->audit($job, 'import_started', $this->actorId);

        try {
            $importedCount = $this->executeImport($job, $svc);

            // A deferred type parks itself; do not overwrite that with "completed".
            if ($job->fresh()->status === 'importing') {
                $job->forceFill([
                    'status'              => 'completed',
                    'imported_rows'       => $importedCount,
                    'import_completed_at' => now(),
                ])->save();

                $svc->audit($job, 'completed', $this->actorId, ['imported' => $importedCount]);
            }
        } catch (\Throwable $e) {
            $job->forceFill(['status' => 'failed', 'error_message' => $e->getMessage()])->save();
            $svc->audit($job, 'failed', $this->actorId, ['error' => $e->getMessage()]);
            Log::error('ExecuteImportJob failed', ['id' => $this->importJobId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function executeImport(ImportJob $job, ImportService $svc): int
    {
        // Patients have a dedicated path: MPI dedup, canonical Health ID, provenance.
        if ($job->import_type === 'patients') {
            return $this->importPatients($job, $svc);
        }

        $table = self::TABLE_MAP[$job->import_type] ?? null;

        if (! $table || ! Schema::hasTable($table)) {
            $job->forceFill(['status' => 'requires_manual_review'])->save();
            $svc->audit($job, 'deferred_to_manual', $this->actorId, [
                'reason' => "Import type '{$job->import_type}' has no automated target in this deployment.",
            ]);

            return 0;
        }

        $rows        = $svc->readRows($job->stored_path, $job->file_extension, $job->detected_headers ?? []);
        $mapping     = $job->mapping ?? [];
        $columnMap   = self::COLUMN_MAP[$job->import_type] ?? [];
        $hasFacility = Schema::hasColumn($table, 'facility_id');
        $count       = 0;

        foreach (array_chunk($rows, 200) as $chunk) {
            $inserts = [];
            $links   = [];

            foreach ($chunk as $raw) {
                $id  = (string) Str::uuid();
                $row = ['id' => $id, 'created_at' => now(), 'updated_at' => now()];

                if ($hasFacility) {
                    $row['facility_id'] = $job->facility_id;
                }

                $mapped = 0;
                foreach ($mapping as $csvCol => $systemField) {
                    if (! $systemField || ! array_key_exists($csvCol, $raw)) {
                        continue;
                    }
                    $column = $columnMap[$systemField] ?? null;
                    if (! $column || ! Schema::hasColumn($table, $column)) {
                        continue;
                    }
                    $row[$column] = ($raw[$csvCol] === '' ? null : $raw[$csvCol]);
                    $mapped++;
                }

                if ($mapped === 0) {
                    continue;
                }

                $inserts[] = $row;
                $links[]   = [
                    'id'            => (string) Str::uuid(),
                    'import_job_id' => $job->id,
                    'target_table'  => $table,
                    'record_id'     => $id,
                    'created_at'    => now(),
                ];
            }

            if ($inserts !== []) {
                DB::transaction(function () use ($table, $inserts, $links) {
                    DB::table($table)->insert($inserts);
                    DB::table('import_record_links')->insert($links);
                });
                $count += count($inserts);
            }
        }

        return $count;
    }

    /**
     * Patient import: create real patients with dedup + provenance.
     *
     * Each row goes through PatientIdentityService::createPatientCandidate,
     * which runs MPI dedup, mints a canonical Health ID and writes an audit
     * event. Nothing merges on its own; uncertain matches surface at the MPI
     * desk. Successful creations are linked back to this job so the batch can
     * be reversed cleanly.
     */
    private function importPatients(ImportJob $job, ImportService $svc): int
    {
        $rows     = $svc->readRows($job->stored_path, $job->file_extension, $job->detected_headers ?? []);
        $mapping  = $job->mapping ?? [];
        $identity = app(PatientIdentityService::class);

        $created = 0;
        $dupes   = 0;
        $failed  = 0;

        foreach ($rows as $raw) {
            $f = [];
            foreach ($mapping as $csvCol => $field) {
                if ($field && isset($raw[$csvCol])) {
                    $f[$field] = $raw[$csvCol] !== '' ? $raw[$csvCol] : null;
                }
            }

            if (empty($f['first_name']) || empty($f['last_name'])) {
                $failed++;
                continue;
            }

            $data = [
                'first_name'      => $f['first_name'],
                'last_name'       => $f['last_name'],
                'middle_name'     => $f['middle_name'] ?? null,
                'date_of_birth'   => $f['date_of_birth'] ?? null,
                'sex'             => $f['gender'] ?? $f['sex'] ?? null,
                'phone_number'    => $f['phone'] ?? $f['phone_number'] ?? null,
                'address'         => $f['address'] ?? null,
                'identity_status' => 'provisional',
            ];

            try {
                $patient = $identity->createPatientCandidate($data, $this->actorId, $job->facility_id);

                DB::table('import_record_links')->insert([
                    'id'            => (string) Str::uuid(),
                    'import_job_id' => $job->id,
                    'target_table'  => 'patients',
                    'record_id'     => $patient->id,
                    'created_at'    => now(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                if ($e instanceof DuplicateIdentityFound) {
                    $dupes++;
                } else {
                    $failed++;
                    Log::warning('ExecuteImportJob: patient row failed', [
                        'import_job_id' => $job->id,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }

        $job->forceFill([
            'duplicate_rows' => $dupes,
            'failed_rows'    => $failed,
        ])->save();

        return $created;
    }
}
