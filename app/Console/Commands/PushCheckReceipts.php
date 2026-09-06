<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * push:check-receipts — the scheduled half of patient push delivery.
 *
 * A send gets a ticket from Expo; a ticket only means "queued". The receipt,
 * published a few minutes later, is where `DeviceNotRegistered` — the app was
 * uninstalled, notifications were revoked at the OS — actually shows up, and
 * it is the only signal that lets the platform stop pushing to a dead token.
 * This command reads the receipts for every pending push_deliveries row,
 * marks it ok/error, retires the token behind a DeviceNotRegistered, and
 * leaves every other error on the row for `opescare:push-status` to show.
 *
 * Scheduled every 15 minutes in routes/console.php. Safe to run by hand.
 */
class PushCheckReceipts extends Command
{
    protected $signature = 'push:check-receipts
                            {--min-age=5 : Only look at tickets sent at least this many minutes ago (Expo needs a few minutes to produce a receipt)}
                            {--limit=1000 : Maximum pending tickets to reconcile in one run}
                            {--prune-days=90 : Delete resolved ledger rows older than this many days (0 keeps them forever)}';

    protected $description = 'Read Expo push receipts for pending deliveries, mark them ok/error and retire dead device tokens.';

    public function handle(ExpoPushService $expo): int
    {
        if (! $expo->isConfigured()) {
            $this->info('Expo push is disabled (EXPO_PUSH_ENABLED=false) — no receipts were requested.');

            return self::SUCCESS;
        }

        $minAge = max(0, (int) $this->option('min-age'));
        $limit  = max(1, (int) $this->option('limit'));

        $summary = $expo->reconcileReceipts($minAge, $limit);

        $this->line(sprintf(
            'Checked %d pending ticket(s): %d ok, %d error, %d token(s) retired, %d closed as receipt unavailable, %d still pending.',
            $summary['checked'],
            $summary['ok'],
            $summary['error'],
            $summary['retired'],
            $summary['unavailable'],
            $summary['still_pending'],
        ));

        if ($summary['lookup_failed']) {
            $this->warn('At least one receipt lookup failed (Expo unreachable or rejected the request); those rows stay pending and will be retried next run.');
        }

        $pruned = $this->prune((int) $this->option('prune-days'));

        if ($pruned > 0) {
            $this->line("Pruned {$pruned} resolved ledger row(s) older than {$this->option('prune-days')} days.");
        }

        Log::info('push_receipts_reconciled', $summary + ['pruned' => $pruned]);

        return $summary['lookup_failed'] ? self::FAILURE : self::SUCCESS;
    }

    /**
     * The ledger is evidence, not history: once a row has been resolved and
     * is older than the window an operator would ever look at, it goes.
     * Pending rows are never pruned — reconcileReceipts() closes them.
     */
    private function prune(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        try {
            return PushDelivery::query()
                ->where('status', '!=', PushDelivery::STATUS_PENDING)
                ->where('sent_at', '<', now()->subDays($days))
                ->delete();
        } catch (\Throwable $e) {
            $this->warn('Could not prune old ledger rows: '.$e->getMessage());

            return 0;
        }
    }
}
