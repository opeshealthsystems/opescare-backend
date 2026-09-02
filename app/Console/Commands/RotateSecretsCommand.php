<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RotateSecretsCommand extends Command
{
    protected $signature = 'opescare:rotate-secrets
                            {--check : Report rotation status without making any changes}';

    protected $description = 'Report secrets rotation status. Use --check to see which secrets need rotation.';

    private array $thresholds = [
        'app_key'          => 90,
        'db_password'      => 60,
        'api_key_mtn_momo' => 180,
        'api_key_orange'   => 180,
        'kms_data_keys'    => 365,
    ];

    public function handle(): int
    {
        if (!$this->option('check')) {
            $this->warn('No action taken.');
            $this->line('');
            $this->line('This command does NOT auto-rotate secrets.');
            $this->line('To check rotation status:  php artisan opescare:rotate-secrets --check');
            $this->line('For rotation steps:        see docs/secrets-rotation-runbook.md');
            return self::SUCCESS;
        }

        $this->info('Checking secrets rotation status...');
        $this->line('');

        $rows         = [];
        $overdueCount = 0;

        foreach ($this->thresholds as $secret => $maxDays) {
            $lastRotated = Cache::get("secrets.last_rotated.{$secret}");

            if ($lastRotated === null) {
                $status    = 'UNKNOWN — never recorded';
                $daysSince = '?';
            } else {
                try {
                    $dt        = Carbon::parse($lastRotated);
                    $daysSince = (int) $dt->diffInDays(now());
                    $overdue   = $daysSince >= $maxDays;
                } catch (\Throwable $e) {
                    $status    = 'ERROR — cached value unreadable';
                    $daysSince = '?';
                    $rows[]    = [$secret, $maxDays . 'd', $daysSince, $status];
                    continue;
                }

                if ($overdue) {
                    $status = "OVERDUE ({$daysSince} / {$maxDays} days)";
                    $overdueCount++;
                } else {
                    $remaining = $maxDays - $daysSince;
                    $status    = "OK — rotates in {$remaining} days";
                }
            }

            $rows[] = [$secret, $maxDays . 'd', $daysSince, $status];
        }

        $this->table(['Secret', 'Max Age', 'Days Since', 'Status'], $rows);
        $this->line('');

        if ($overdueCount > 0) {
            $this->error("{$overdueCount} secret(s) need rotation. See docs/secrets-rotation-runbook.md.");
            Log::warning('opescare:rotate-secrets: overdue secrets detected', ['count' => $overdueCount]);
        } else {
            $this->info('All tracked secrets are within rotation policy.');
        }

        return self::SUCCESS;
    }
}
