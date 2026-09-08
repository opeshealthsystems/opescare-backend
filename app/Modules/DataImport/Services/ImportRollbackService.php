<?php

namespace App\Modules\DataImport\Services;

use App\Models\ImportJob;
use App\Models\ImportRollback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * ImportRollbackService — reverse a completed import batch.
 *
 * Ported from the main platform, with two changes for the Finder:
 *
 *  1. Every rollback now opens an `ImportRollback` row (pending → processing →
 *     completed) so the reversal itself has a durable, queryable record with a
 *     reason and a per-table log. The main platform only wrote an audit event.
 *
 *  2. Reversal prefers a SOFT delete: if the target table carries `deleted_at`
 *     the row is stamped rather than removed, so nothing is destroyed. Only
 *     tables with no soft-delete column fall back to a delete — and then only
 *     for rows this import itself created, which is exactly what
 *     `import_record_links` records.
 *
 * Nothing outside `import_record_links` is ever touched: an import can only
 * un-do what it did.
 */
class ImportRollbackService
{
    public function __construct(
        private ImportService $importService,
    ) {}

    public function rollback(ImportJob $job, string $actorId, ?string $reason = null): ImportJob
    {
        if (! $job->canBeRolledBack()) {
            throw new RuntimeException("Import job cannot be rolled back from status: {$job->status}");
        }

        $rollback = ImportRollback::create([
            'import_job_id' => $job->id,
            'initiated_by'  => $actorId,
            'reason'        => $reason,
            'status'        => 'pending',
        ]);
        $rollback->markStarted();

        try {
            [$reverted, $log] = DB::transaction(function () use ($job) {
                $links = DB::table('import_record_links')
                    ->where('import_job_id', $job->id)
                    ->get();

                $counts = [];

                foreach ($links as $link) {
                    $table = $link->target_table;

                    // A link may name a table this deployment does not carry.
                    if (! Schema::hasTable($table)) {
                        $counts[$table . ':missing_table'] = ($counts[$table . ':missing_table'] ?? 0) + 1;
                        continue;
                    }

                    $query = DB::table($table)->where('id', $link->record_id);

                    if (Schema::hasColumn($table, 'deleted_at')) {
                        $query->update(['deleted_at' => now()]);
                        $counts[$table . ':soft_deleted'] = ($counts[$table . ':soft_deleted'] ?? 0) + 1;
                    } else {
                        $query->delete();
                        $counts[$table . ':deleted'] = ($counts[$table . ':deleted'] ?? 0) + 1;
                    }
                }

                DB::table('import_record_links')->where('import_job_id', $job->id)->delete();

                $job->forceFill(['status' => 'rolled_back'])->save();

                return [$links->count(), $counts];
            });
        } catch (\Throwable $e) {
            $rollback->update(['status' => 'failed', 'rollback_log' => $e->getMessage()]);
            throw $e;
        }

        $rollback->markCompleted($reverted, json_encode($log, JSON_UNESCAPED_UNICODE) ?: '');

        $this->importService->audit($job, 'rolled_back', $actorId, [
            'reason'              => $reason,
            'previously_imported' => $job->imported_rows,
            'reverted_records'    => $reverted,
            'rollback_id'         => $rollback->id,
            'per_table'           => $log,
        ]);

        return $job->fresh();
    }
}
