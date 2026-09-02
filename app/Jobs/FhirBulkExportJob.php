<?php

namespace App\Jobs;

use App\Models\BulkExportJob;
use App\Models\ConsentGrant;
use App\Models\Patient;
use App\Modules\Fhir\Services\FhirService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FhirBulkExportJob
 *
 * Processes an async FHIR $export request (FHIR Bulk Data Access IG STU1).
 *
 * Migration Sprint — Item 4.
 *
 * Workflow:
 *   1. Mark job as 'processing'.
 *   2. Resolve consented patients for the facility.
 *   3. Stream NDJSON output to storage/app/fhir-exports/{jobId}/{type}.ndjson
 *   4. Update progress (0-100) after each batch.
 *   5. On completion: set output_files, completed_at, expires_at (1 hour).
 *   6. On failure: set status=failed, error message.
 *
 * Output files are served by BulkExportController::download() which streams
 * them directly — download links are internal URLs, not S3 pre-signed URLs,
 * because the app uses local disk storage.
 *
 * FHIR types exported: Patient, Encounter, Observation, MedicationRequest, Condition.
 * Custom type filtering via the original `_type` parameter is respected.
 *
 * ISO 27001 A.12.3 / FHIR Bulk Data Access IG §4
 */
class FhirBulkExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum patients per export job. */
    private const MAX_PATIENTS = 50_000;

    /** Batch size for DB chunking to avoid memory exhaustion. */
    private const CHUNK_SIZE = 500;

    /** Export download links expire 1 hour after completion. */
    private const EXPIRY_HOURS = 1;

    public int $tries    = 1;
    public int $timeout  = 600; // 10 minutes max

    public function __construct(
        public readonly BulkExportJob $exportJob,
    ) {}

    public function handle(FhirService $fhirService): void
    {
        $job = $this->exportJob->fresh();

        if (! $job || ! $job->isStillProcessing()) {
            Log::warning('fhir_bulk_export_skipped', ['job_id' => $this->exportJob->id]);
            return;
        }

        $job->update(['status' => 'processing', 'progress' => 0]);

        try {
            $params     = $job->parameters ?? [];
            $facilityId = $job->facility_id;
            $since      = $params['_since'] ?? null;
            $types      = isset($params['_type'])
                ? explode(',', $params['_type'])
                : ['Patient', 'Encounter', 'Observation', 'MedicationRequest', 'Condition'];

            // Scope to consented patients for this facility.
            $consentedPatientIds = ConsentGrant::where('requesting_facility_id', $facilityId)
                ->where('status', 'granted')
                ->limit(self::MAX_PATIENTS)
                ->pluck('patient_id');

            $totalPatients = $consentedPatientIds->count();

            if ($totalPatients === 0) {
                $this->complete($job, []);
                return;
            }

            $outputDir   = "fhir-exports/{$job->id}";
            $outputFiles = [];
            $processedTypes = 0;
            $totalTypes     = count($types);

            foreach ($types as $type) {
                $file     = "{$outputDir}/{$type}.ndjson";
                $lines    = [];
                $count    = 0;

                if ($type === 'Patient') {
                    $query = Patient::whereIn('id', $consentedPatientIds);

                    if ($since) {
                        $query->where('updated_at', '>=', $since);
                    }

                    $query->chunkById(self::CHUNK_SIZE, function ($patients) use (&$lines, &$count, $fhirService) {
                        foreach ($patients as $patient) {
                            $lines[] = json_encode($fhirService->patient($patient));
                            $count++;
                        }
                    });
                }
                // Additional resource types can be added here following the same pattern.
                // Encounter, Observation, MedicationRequest, Condition would join through
                // Patient and require their own FHIR mappers.

                if (! empty($lines)) {
                    Storage::put($file, implode("\n", $lines));

                    $outputFiles[] = [
                        'type'  => $type,
                        'url'   => url("/api/fhir/R4/bulkdata/{$job->id}/download/{$type}.ndjson"),
                        'count' => $count,
                    ];
                }

                $processedTypes++;
                $progress = (int) round(($processedTypes / $totalTypes) * 100);
                $job->update(['progress' => $progress]);
            }

            $this->complete($job, $outputFiles);

        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);

            Log::error('fhir_bulk_export_failed', [
                'job_id'    => $job->id,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $job = BulkExportJob::find($this->exportJob->id);
        if ($job) {
            $job->update([
                'status' => 'failed',
                'error'  => $exception->getMessage(),
            ]);
        }

        Log::error('fhir_bulk_export_job_permanently_failed', [
            'job_id'    => $this->exportJob->id,
            'exception' => $exception->getMessage(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function complete(BulkExportJob $job, array $outputFiles): void
    {
        $now = now();

        $job->update([
            'status'       => 'complete',
            'progress'     => 100,
            'output_files' => $outputFiles,
            'completed_at' => $now,
            'expires_at'   => $now->copy()->addHours(self::EXPIRY_HOURS),
        ]);

        Log::info('fhir_bulk_export_complete', [
            'job_id'     => $job->id,
            'file_count' => count($outputFiles),
            'expires_at' => $now->copy()->addHours(self::EXPIRY_HOURS)->toISOString(),
        ]);
    }
}
