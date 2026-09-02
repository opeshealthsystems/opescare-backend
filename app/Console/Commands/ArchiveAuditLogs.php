<?php

namespace App\Console\Commands;

use App\Models\MedicalIdAccessEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ArchiveAuditLogs
 *
 * Archives old rows from `medical_id_access_events` to compressed JSONL files
 * in storage/app/audit-archive/{YYYY-MM}.jsonl and then deletes them from the
 * hot table.
 *
 * Retention tiers (per Cameroon Law No. 2010/012 Art. 28 and MINSANTE guidance):
 *   - Hot (queryable DB table): 12 months  [default, configurable]
 *   - Archive (cold storage JSONL): 7 years [legal minimum for medical records]
 *
 * Why JSONL not CSV:
 *   Each row preserves its full structure (including JSON columns like `notes`)
 *   without lossy serialisation. JSONL is also line-delimited so archive files
 *   can be streamed without loading everything into memory.
 *
 * Schedule: monthly on the 2nd at 03:00 (after MINSANTE report on the 1st)
 *
 *     $schedule->command('health-id:archive-audit-logs')->monthlyOn(2, '03:00');
 *
 * Dry run:
 *     php artisan health-id:archive-audit-logs --dry-run
 *
 * Custom retention:
 *     php artisan health-id:archive-audit-logs --months=6
 */
class ArchiveAuditLogs extends Command
{
    protected $signature = 'health-id:archive-audit-logs
                            {--months=12   : Archive rows older than this many months}
                            {--chunk=500   : Rows processed per batch}
                            {--dry-run     : Count eligible rows without archiving or deleting}';

    protected $description = 'Archive old audit log rows to cold storage and prune the hot table.';

    public function handle(): int
    {
        $months   = (int) $this->option('months');
        $chunk    = (int) $this->option('chunk');
        $dryRun   = $this->option('dry-run');
        $cutoff   = now()->subMonths($months)->startOfDay();

        $this->info(sprintf(
            '[%s] Archiving audit logs older than %s (%d months)%s…',
            now()->toDateTimeString(),
            $cutoff->toDateString(),
            $months,
            $dryRun ? ' [DRY RUN]' : '',
        ));

        if ($dryRun) {
            $count = MedicalIdAccessEvent::where('created_at', '<', $cutoff)->count();
            $this->info("Would archive and purge {$count} rows. No changes made.");
            return self::SUCCESS;
        }

        $totalArchived = 0;
        $totalDeleted  = 0;

        // Immutable archive target. In production this disk points at an S3
        // bucket with Object Lock (WORM) so written archives cannot be altered
        // or deleted within the retention window. See config/audit.php.
        $diskName = config('audit.archive_disk', 'local');
        $disk     = Storage::disk($diskName);

        // Group by year-month so each archive file covers one calendar month.
        // We query the oldest distinct months first to build files chronologically.
        // TO_CHAR is PostgreSQL syntax. DATE_FORMAT() is MySQL-only — this app uses PG.
        $months_present = MedicalIdAccessEvent::where('created_at', '<', $cutoff)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as ym")
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('ym');

        foreach ($months_present as $ym) {
            [$year, $month] = explode('-', $ym);
            $monthStart = \Carbon\Carbon::createFromDate((int)$year, (int)$month, 1)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $archivePath = "audit-archive/{$ym}.jsonl";
            $digestPath  = "{$archivePath}.sha256";

            // Refuse to overwrite an existing immutable archive object. On a WORM
            // store the put() would fail anyway; this makes the intent explicit
            // and avoids silently re-archiving a month.
            if ($disk->exists($archivePath)) {
                $this->warn("  Archive for {$ym} already exists on '{$diskName}' — skipping (immutable).");
                continue;
            }

            // Build the month's JSONL in memory from chunked reads, then write it
            // ONCE. Write-once (not append) is required for Object Lock / WORM.
            $buffer        = '';
            $rowsThisMonth = 0;
            MedicalIdAccessEvent::whereBetween('created_at', [$monthStart, $monthEnd])
                ->orderBy('created_at')
                ->chunkById($chunk, function ($rows) use (&$buffer, &$rowsThisMonth) {
                    foreach ($rows as $r) {
                        $buffer .= json_encode($r->toArray()) . "\n";
                        $rowsThisMonth++;
                    }
                });

            try {
                // Single write-once put for the archive, plus a SHA-256 digest
                // sidecar so any later tampering with the archive is detectable.
                $disk->put($archivePath, $buffer);
                $disk->put($digestPath, hash('sha256', $buffer));
            } catch (\Throwable $e) {
                $this->error("  Failed to write archive for {$archivePath}: " . $e->getMessage());
                $this->warn("  Skipping DB deletion for {$ym} due to write error.");
                continue;
            }

            // Verify the write before purging the hot table — never delete source
            // rows unless the immutable copy is present and matches.
            if (! $disk->exists($archivePath) || hash('sha256', $disk->get($archivePath)) !== hash('sha256', $buffer)) {
                $this->error("  Archive integrity check failed for {$ym} — keeping hot rows.");
                continue;
            }

            $deleted = MedicalIdAccessEvent::whereBetween('created_at', [$monthStart, $monthEnd])
                ->delete();

            $totalArchived += $rowsThisMonth;
            $totalDeleted  += $deleted;

            $this->line("  Archived {$rowsThisMonth} rows → {$diskName}:{$archivePath} (+digest) | Deleted: {$deleted}");
        }

        Log::info('audit_log_archival_complete', [
            'cutoff'         => $cutoff->toDateString(),
            'total_archived' => $totalArchived,
            'total_deleted'  => $totalDeleted,
        ]);

        $this->info("Done. Archived: {$totalArchived} | Deleted: {$totalDeleted}");

        return self::SUCCESS;
    }
}
