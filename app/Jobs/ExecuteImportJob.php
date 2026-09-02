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
use Illuminate\Support\Str;

/**
 * Executes an approved data import job by reading the validated file,
 * mapping columns, and inserting rows into the appropriate target table.
 *
 * Each import type maps to a database table; rows are upserted in chunks
 * of 200 to stay within memory bounds for large files.
 */
class ExecuteImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 2;
    public int $timeout = 600;

    // Map import_type → target table (only insertable types)
    private const TABLE_MAP = [
        'pharmacy_stock'      => 'drug_stock_items',
        'medicine_catalog'    => 'drugs',
        'lab_test_catalog'    => 'lab_tests',
        'insurance_providers' => 'insurance_providers',
        'price_lists'         => 'service_prices',
        'inventory_items'     => 'inventory_items',
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

            $job->forceFill([
                'status'              => 'completed',
                'imported_rows'       => $importedCount,
                'import_completed_at' => now(),
            ])->save();

            $svc->audit($job, 'completed', $this->actorId, ['imported' => $importedCount]);
        } catch (\Throwable $e) {
            $job->forceFill(['status' => 'failed'])->save();
            $svc->audit($job, 'failed', $this->actorId, ['error' => $e->getMessage()]);
            Log::error('ExecuteImportJob failed', ['id' => $this->importJobId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function executeImport(ImportJob $job, ImportService $svc): int
    {
        // Patient import has a dedicated path: dedup via the Master Patient Index,
        // canonical Health-ID generation, audit provenance, and rollback links.
        if ($job->import_type === 'patients') {
            return $this->importPatients($job, $svc);
        }

        $table = self::TABLE_MAP[$job->import_type] ?? null;

        // Staff / appointment imports touch complex domain models — they need
        // event dispatching, etc. For now mark as a manual review type so the UI
        // can guide staff through the model-specific import path.
        if (!$table) {
            $job->forceFill(['status' => 'requires_manual_review'])->save();
            $svc->audit($job, 'deferred_to_manual', $this->actorId, [
                'reason' => "Import type '{$job->import_type}' requires domain-specific processing.",
            ]);
            return 0;
        }

        $rows    = $svc->readRows($job->stored_path, $job->file_extension, $job->detected_headers ?? []);
        $mapping = $job->mapping ?? [];
        $count   = 0;

        // Process in chunks of 200 rows
        foreach (array_chunk($rows, 200) as $chunk) {
            $inserts = [];
            foreach ($chunk as $raw) {
                $row = ['facility_id' => $job->facility_id, 'created_at' => now(), 'updated_at' => now()];
                foreach ($mapping as $csvCol => $dbCol) {
                    if ($dbCol && isset($raw[$csvCol])) {
                        $row[$dbCol] = $raw[$csvCol] ?: null;
                    }
                }
                $inserts[] = $row;
            }
            if (!empty($inserts)) {
                DB::table($table)->insertOrIgnore($inserts);
                $count += count($inserts);
            }
        }

        return $count;
    }

    /**
     * Patient import: create real patients with dedup + provenance.
     *
     * Each row is routed through PatientIdentityService::createPatientCandidate,
     * which runs Master-Patient-Index dedup, generates a canonical Health ID, and
     * writes an audit event. Successful creations are linked back to this import
     * job in import_record_links so the batch can be rolled back cleanly.
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
            // Apply column mapping → system field names.
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
                if (str_contains($e->getMessage(), 'Duplicate candidate')) {
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
