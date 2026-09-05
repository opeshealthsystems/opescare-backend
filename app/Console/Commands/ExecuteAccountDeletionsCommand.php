<?php

namespace App\Console\Commands;

use App\Services\Compliance\AccountDeletionService;
use Illuminate\Console\Command;

/**
 * Run the account closures whose grace period has elapsed, and purge the
 * retained records whose retention period has elapsed.
 *
 * Two clocks, one command, because they are two halves of the same promise
 * made on the deletion screen:
 *
 *   "your account closes on <date>"      → executeDue()
 *   "your record is kept until <date>"   → purgeExpiredRetention()
 *
 * A promise with no scheduled process behind it is a lie with a delay on it,
 * so both are implemented rather than only the first.
 *
 * REGISTRATION — add to routes/console.php, beside the other retention lines:
 *
 *     // Account closures whose 30-day grace period has elapsed, and retained
 *     // records whose statutory retention period has elapsed.
 *     Schedule::command('opescare:execute-account-deletions')
 *         ->dailyAt('02:30')
 *         ->withoutOverlapping();
 *
 * 02:30 rather than 02:00 so it does not contend with
 * `opescare:enforce-data-retention`.
 *
 * If that line is not registered, patients are still not left with a broken
 * promise: MobileAccountDeletionController::show() executes the CALLER'S OWN
 * overdue request before answering. That is a safety net for one account at a
 * time, not a replacement for the sweep — a patient who never opens the app
 * again is only reached by the schedule.
 */
class ExecuteAccountDeletionsCommand extends Command
{
    protected $signature = 'opescare:execute-account-deletions
                            {--dry-run : List what would run without changing anything}
                            {--limit=500 : Maximum closures to execute in one pass}
                            {--skip-purge : Execute due closures but do not purge expired retention}';

    protected $description = 'Execute account closures past their grace period and purge records past their retention period';

    public function handle(AccountDeletionService $deletion): int
    {
        $limit = max(1, (int) $this->option('limit'));

        if ($this->option('dry-run')) {
            return $this->dryRun($deletion);
        }

        $this->info('Executing account closures past their grace period...');
        $result = $deletion->executeDue(null, $limit);

        $this->line("  examined: {$result['examined']}");
        $this->line("  executed: {$result['executed']}");
        $this->line("  failed:   {$result['failed']}");

        foreach ($result['ids'] as $id) {
            $this->line("    closed: {$id}");
        }

        if (! $this->option('skip-purge')) {
            $this->newLine();
            $this->info('Purging retained records past their retention period...');
            $purge = $deletion->purgeExpiredRetention();

            $this->line("  examined: {$purge['examined']}");
            $this->line("  purged:   {$purge['purged']}");
            $this->line("  failed:   {$purge['failed']}");

            if ($purge['failed'] > 0) {
                return self::FAILURE;
            }
        }

        // A failure here is a promise not kept on time. Non-zero so the
        // scheduler's failure handling and Sentry both see it.
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function dryRun(AccountDeletionService $deletion): int
    {
        $due = \App\Models\AccountClosureRequest::query()
            ->where('status', \App\Models\AccountClosureRequest::STATUS_PENDING)
            ->whereNotNull('executes_after')
            ->where('executes_after', '<=', now())
            ->get();

        $expired = \App\Models\AccountClosureRequest::query()
            ->where('status', \App\Models\AccountClosureRequest::STATUS_COMPLETED)
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<=', now()->toDateString())
            ->get();

        $this->info("DRY RUN — nothing will be changed.");
        $this->line("Closures due for execution: {$due->count()}");

        foreach ($due as $request) {
            $this->line(sprintf(
                '  %s  patient=%s  due=%s',
                $request->id,
                $request->patient_id,
                $request->executes_after?->toIso8601String() ?? '?',
            ));
        }

        $this->line("Retained records due for purge: {$expired->count()}");

        foreach ($expired as $request) {
            $this->line(sprintf(
                '  %s  patient=%s  retention_until=%s',
                $request->id,
                $request->patient_id,
                $request->retention_until?->toDateString() ?? '?',
            ));
        }

        return self::SUCCESS;
    }
}
