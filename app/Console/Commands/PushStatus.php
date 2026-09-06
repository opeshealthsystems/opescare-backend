<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\PushDeviceToken;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

/**
 * opescare:push-status — one screen that says whether this host can push,
 * whether the receipt pass is scheduled, how many phones are reachable, and
 * what became of the last pushes.
 *
 * Prints whether an Expo access token is present, never its value.
 */
class PushStatus extends Command
{
    protected $signature = 'opescare:push-status
                            {--recent=20 : How many recent deliveries to list}';

    protected $description = 'Show patient push configuration, reachable device counts and the most recent deliveries.';

    public function handle(ExpoPushService $expo): int
    {
        $this->components->twoColumnDetail('Expo push', $expo->isConfigured() ? '<info>enabled</info>' : '<error>DISABLED</error> (EXPO_PUSH_ENABLED=false)');
        $this->components->twoColumnDetail('Expo access token', $expo->hasAccessToken() ? 'present' : 'not set (only needed with enhanced push security)');
        $this->components->twoColumnDetail('Receipt pass', $this->receiptSchedule());
        $this->line('');

        $this->deviceCounts();
        $this->line('');

        $this->deliveryCounts();
        $this->line('');

        $this->recentDeliveries(max(1, (int) $this->option('recent')));

        return self::SUCCESS;
    }

    private function receiptSchedule(): string
    {
        try {
            foreach (app(Schedule::class)->events() as $event) {
                if (str_contains((string) $event->command, 'push:check-receipts')) {
                    return 'scheduled ('.$event->expression.' — push:check-receipts)';
                }
            }
        } catch (\Throwable) {
            // Fall through: better to say "not found" than to crash a status screen.
        }

        return '<comment>NOT scheduled</comment> — push:check-receipts is missing from routes/console.php';
    }

    private function deviceCounts(): void
    {
        $active = PushDeviceToken::query()->active()->get(['platform', 'push_token']);

        $revoked = PushDeviceToken::query()
            ->where(fn ($q) => $q->where('is_active', false)->orWhereNotNull('revoked_at'))
            ->select('platform', DB::raw('count(*) as n'))
            ->groupBy('platform')
            ->pluck('n', 'platform');

        $platforms = collect($active->pluck('platform'))->merge($revoked->keys())->unique()->sort()->values();

        $rows = [];

        foreach ($platforms as $platform) {
            $onPlatform = $active->where('platform', $platform);
            $rows[] = [
                $platform,
                $onPlatform->filter(fn ($t) => ExpoPushService::isExpoPushToken($t->push_token))->count(),
                $onPlatform->reject(fn ($t) => ExpoPushService::isExpoPushToken($t->push_token))->count(),
                (int) ($revoked[$platform] ?? 0),
            ];
        }

        $this->line('<info>Device tokens</info>');

        if ($rows === []) {
            $this->line('  none registered');

            return;
        }

        $this->table(['Platform', 'Active, deliverable', 'Active, fingerprint-only', 'Revoked'], $rows);
    }

    private function deliveryCounts(): void
    {
        $since = now()->subDay();

        $pending = PushDelivery::query()->pending()->count();
        $ok      = PushDelivery::query()->where('status', PushDelivery::STATUS_OK)->where('sent_at', '>=', $since)->count();
        $error   = PushDelivery::query()->where('status', PushDelivery::STATUS_ERROR)->where('sent_at', '>=', $since)->count();

        $this->line('<info>Deliveries</info>');
        $this->components->twoColumnDetail('Pending receipt', (string) $pending);
        $this->components->twoColumnDetail('Delivered (last 24h)', (string) $ok);
        $this->components->twoColumnDetail('Failed (last 24h)', (string) $error);

        $codes = PushDelivery::query()
            ->where('status', PushDelivery::STATUS_ERROR)
            ->where('sent_at', '>=', $since)
            ->select('error_code', DB::raw('count(*) as n'))
            ->groupBy('error_code')
            ->orderByDesc('n')
            ->limit(5)
            ->pluck('n', 'error_code');

        foreach ($codes as $code => $n) {
            $this->components->twoColumnDetail('  '.($code ?: 'Unknown'), (string) $n);
        }
    }

    private function recentDeliveries(int $limit): void
    {
        $rows = PushDelivery::query()
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();

        $this->line("<info>Last {$limit} deliveries</info>");

        if ($rows->isEmpty()) {
            $this->line('  none yet');

            return;
        }

        $this->table(
            ['Sent at', 'Type', 'Platform', 'Status', 'Error', 'Ticket', 'Receipt checked'],
            $rows->map(fn (PushDelivery $d) => [
                $d->sent_at?->toDateTimeString() ?? '-',
                $d->type ?? '-',
                $d->platform ?? '-',
                $d->status,
                $d->error_code ?? '',
                $d->ticket_id !== null ? substr($d->ticket_id, 0, 8).'…' : '-',
                $d->receipt_checked_at?->toDateTimeString() ?? '-',
            ])->all(),
        );
    }
}
