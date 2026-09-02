<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredDataCommand extends Command
{
    protected $signature = 'opescare:purge-expired-data {--dry-run : Preview what would be deleted without deleting}';

    protected $description = 'Purge expired data per OpesCare retention policy';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No data will be deleted.');
        }

        $this->info('Starting data retention purge — ' . Carbon::now()->toDateTimeString());

        $results = [];

        // 1. API usage logs
        // api_usage_logs has no created_at — its timestamp column is logged_at.
        $results['api_usage_logs'] = $this->purgeTable(
            table: 'api_usage_logs',
            column: 'logged_at',
            days: (int) config('data_retention.api_usage_logs_days', 180),
            dryRun: $dryRun,
        );

        // 2. USSD sessions
        $results['ussd_sessions'] = $this->purgeTable(
            table: 'ussd_sessions',
            column: 'last_active_at',
            days: (int) config('data_retention.ussd_sessions_days', 30),
            dryRun: $dryRun,
        );

        // 3. Export files
        $results['export_files'] = $this->purgeExportFiles(
            hoursOld: (int) config('data_retention.export_files_hours', 24),
            dryRun: $dryRun,
        );

        // Summary
        $this->newLine();
        $this->info('Purge Summary:');
        $this->table(
            ['Target', 'Records/Files Affected'],
            collect($results)->map(fn ($count, $target) => [$target, $count])->values()->toArray(),
        );

        Log::info('opescare:purge-expired-data completed', array_merge(
            ['dry_run' => $dryRun],
            $results,
        ));

        return self::SUCCESS;
    }

    private function purgeTable(
        string $table,
        string $column,
        int $days,
        bool $dryRun,
    ): int {
        $cutoff = Carbon::now()->subDays($days);

        try {
            $count = DB::table($table)->where($column, '<', $cutoff)->count();

            if (! $dryRun && $count > 0) {
                DB::table($table)->where($column, '<', $cutoff)->delete();
            }

            $verb = $dryRun ? 'Would delete' : 'Deleted';
            $this->line("  {$verb} {$count} record(s) from {$table} (older than {$days} days).");
            return $count;
        } catch (\Throwable $e) {
            Log::error("PurgeExpiredDataCommand: failed to purge {$table}", [
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed to purge {$table}: {$e->getMessage()}");
            return 0;
        }
    }

    private function purgeExportFiles(int $hoursOld, bool $dryRun): int
    {
        $exportDir = 'exports/medical-records';
        $cutoff    = Carbon::now()->subHours($hoursOld)->timestamp;
        $deleted   = 0;

        try {
            $files = Storage::disk('local')->files($exportDir);

            foreach ($files as $file) {
                $lastModified = Storage::disk('local')->lastModified($file);

                if ($lastModified < $cutoff) {
                    if (! $dryRun) {
                        Storage::disk('local')->delete($file);
                    }
                    $deleted++;
                }
            }

            $verb = $dryRun ? 'Would delete' : 'Deleted';
            $this->line("  {$verb} {$deleted} export file(s) older than {$hoursOld} hour(s).");
        } catch (\Throwable $e) {
            Log::error('PurgeExpiredDataCommand: failed to purge export files', [
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed to purge export files: {$e->getMessage()}");
        }

        return $deleted;
    }
}
