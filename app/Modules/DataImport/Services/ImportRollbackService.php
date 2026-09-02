<?php

namespace App\Modules\DataImport\Services;

use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportRollbackService
{
    public function __construct(
        private ImportService $importService,
    ) {}

    /**
     * Roll back a completed import batch.
     *
     * Deletes exactly the records the import created — tracked in
     * import_record_links — inside a transaction, then marks the job
     * rolled_back. Dependent rows (e.g. patient_identifiers) are removed by the
     * schema's ON DELETE CASCADE. Legacy jobs with no links are still marked
     * rolled_back.
     */
    public function rollback(ImportJob $job, string $actorId, ?string $reason = null): ImportJob
    {
        if (!$job->canBeRolledBack()) {
            throw new RuntimeException("Import job cannot be rolled back from status: {$job->status}");
        }

        $reverted = DB::transaction(function () use ($job) {
            $links = DB::table('import_record_links')
                ->where('import_job_id', $job->id)
                ->get();

            foreach ($links as $link) {
                DB::table($link->target_table)
                    ->where('id', $link->record_id)
                    ->delete();
            }

            DB::table('import_record_links')->where('import_job_id', $job->id)->delete();

            $job->forceFill(['status' => 'rolled_back'])->save();

            return $links->count();
        });

        $this->importService->audit($job, 'rolled_back', $actorId, [
            'reason'           => $reason,
            'previously_imported' => $job->imported_rows,
            'reverted_records' => $reverted,
        ]);

        return $job->fresh();
    }
}
