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

                    /*
                     * A PATIENT IS NEVER DELETED.
                     *
                     * 89 tables cascade-delete from `patients` — diagnoses,
                     * visits, allergy_records, consent_grants and
                     * medical_id_access_events among them. Deleting the row
                     * would take a person's clinical record AND the audit
                     * trail of who opened it, which is precisely what this
                     * platform's immutability rule exists to prevent: the
                     * evidence of access would vanish along with the evidence
                     * of care.
                     *
                     * `entered_in_error` is the platform's own word for a
                     * record that should never have existed — the same status
                     * a voided immunisation carries. The row stays, the
                     * history stays, and every screen that already reads the
                     * enum reports it correctly.
                     */
                    if ($table === 'patients') {
                        $query->update([
                            'identity_status' => 'entered_in_error',
                            'updated_at'      => now(),
                        ]);
                        $counts[$table . ':entered_in_error'] = ($counts[$table . ':entered_in_error'] ?? 0) + 1;
                        continue;
                    }

                    if (Schema::hasColumn($table, 'deleted_at')) {
                        $query->update(['deleted_at' => now()]);
                        $counts[$table . ':soft_deleted'] = ($counts[$table . ':soft_deleted'] ?? 0) + 1;
                    } else {
                        // Reference and catalogue rows only — an import writes
                        // insurance_providers, service_prices and
                        // inventory_items, none of which carry clinical history.
                        $query->delete();
                        $counts[$table . ':deleted'] = ($counts[$table . ':deleted'] ?? 0) + 1;
                    }
                }

                /*
                 * The links are KEPT. They are the record of what this import
                 * created and what the rollback reversed; deleting them would
                 * leave a retracted patient with nothing explaining why they
                 * are marked entered-in-error.
                 */

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
