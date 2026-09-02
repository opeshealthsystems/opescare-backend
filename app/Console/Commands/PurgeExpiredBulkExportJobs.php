<?php

namespace App\Console\Commands;

use App\Models\BulkExportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PurgeExpiredBulkExportJobs
 *
 * Nightly cleanup of expired async bulk export jobs and their NDJSON output files.
 *
 * A BulkExportJob is eligible for purge when:
 *   - status = 'expired', OR
 *   - expires_at < now() (download window closed), OR
 *   - status = 'failed' AND created_at < 7 days ago
 *   - status = 'queued'/'processing' AND created_at < 1 day ago (stale/hung jobs)
 *
 * For each purged job the corresponding NDJSON output directory
 * (storage/app/fhir-exports/{jobId}/) is deleted from disk.
 *
 * Schedule: nightly at 01:00.
 *
 * Dry run:
 *     php artisan health-id:purge-bulk-exports --dry-run
 */
class PurgeExpiredBulkExportJobs extends Command
{
    protected $signature = 'health-id:purge-bulk-exports
                            {--dry-run : Count eligible rows without deleting}';

    protected $description = 'Remove expired FHIR bulk export jobs and their NDJSON output files.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Expired by TTL or explicitly marked expired.
        $expired = BulkExportJob::where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere('expires_at', '<', now()->subMinutes(5));
        });

        // Failed jobs older than 7 days (keep recent ones for debugging).
        $failedStale = BulkExportJob::where('status', 'failed')
            ->where('created_at', '<', now()->subDays(7));

        // Hung jobs (queued/processing for more than 24 hours — worker likely crashed).
        $hung = BulkExportJob::whereIn('status', ['queued', 'processing'])
            ->where('created_at', '<', now()->subDay());

        $total = $expired->count() + $failedStale->count() + $hung->count();

        if ($dryRun) {
            $this->info("Would purge {$total} job record(s) ({$expired->count()} expired, {$failedStale->count()} stale-failed, {$hung->count()} hung). No changes made.");
            return self::SUCCESS;
        }

        $deleted     = 0;
        $filesRemoved = 0;

        foreach ([$expired, $failedStale, $hung] as $query) {
            $query->each(function (BulkExportJob $job) use (&$deleted, &$filesRemoved) {
                // Remove output files from disk.
                $dir = "fhir-exports/{$job->id}";
                if (Storage::exists($dir)) {
                    Storage::deleteDirectory($dir);
                    $filesRemoved++;
                }

                $job->delete();
                $deleted++;
            });
        }

        Log::info('bulk_export_jobs_purged', [
            'deleted'       => $deleted,
            'dirs_removed'  => $filesRemoved,
        ]);

        $this->info("Purged {$deleted} bulk export job(s) and {$filesRemoved} output director(ies).");

        return self::SUCCESS;
    }
}
