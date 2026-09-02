<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Notifications\HealthIdExpiryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * NotifyExpiringHealthIds
 *
 * Scans the patients table for Health IDs that are entering their renewal
 * window (renewal_required_at <= now) and have not yet expired, then sends
 * an in-app (database) notification to the patient's linked user account.
 *
 * Scheduling (add to app/Console/Kernel.php or Schedule::call in
 * AppServiceProvider for Laravel 11+):
 *
 *     $schedule->command('health-id:notify-expiring')->daily();
 *
 * Dry-run support:
 *
 *     php artisan health-id:notify-expiring --dry-run
 *
 * MINSANTE Digital Health Strategy 2026–2030: patient IDs should carry a
 * 10-year validity period. This command handles the notification lifecycle;
 * actual invalidation (setting `verification_status = expired`) is handled
 * separately by PurgeExpiredDataCommand or a dedicated expiry command.
 */
class NotifyExpiringHealthIds extends Command
{
    protected $signature = 'health-id:notify-expiring
                            {--dry-run : List patients that would be notified, without sending}
                            {--days=90 : Notify patients whose IDs expire within this many days}
                            {--chunk=100 : Process patients in chunks of this size}';

    protected $description = 'Notify patients whose Health IDs are approaching expiry.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days   = (int) $this->option('days');
        $chunk  = (int) $this->option('chunk');
        $now    = now();
        $cutoff = $now->copy()->addDays($days);

        $this->info(sprintf(
            '[%s] Scanning for Health IDs expiring within %d days%s…',
            $now->toDateTimeString(),
            $days,
            $dryRun ? ' (DRY RUN)' : '',
        ));

        $notified = 0;
        $skipped  = 0;
        $errors   = 0;

        // Select only patients whose renewal window has opened and who have
        // not yet expired. Chunked to avoid loading thousands of Eloquent
        // models into memory simultaneously.
        Patient::query()
            ->whereNotNull('renewal_required_at')
            ->whereNotNull('expires_at')
            ->where('renewal_required_at', '<=', $now)
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $cutoff)
            ->with('user') // eager-load the linked user account
            ->chunkById($chunk, function ($patients) use ($dryRun, &$notified, &$skipped, &$errors) {
                foreach ($patients as $patient) {
                    if (! $patient->user_id || ! $patient->user) {
                        $this->line("  SKIP  {$patient->health_id} — no linked user account.");
                        $skipped++;
                        continue;
                    }

                    $daysLeft = (int) now()->diffInDays($patient->expires_at, false);

                    if ($dryRun) {
                        $this->line("  DRY   {$patient->health_id} — expires in {$daysLeft} days.");
                        $notified++;
                        continue;
                    }

                    try {
                        $patient->user->notify(new HealthIdExpiryNotification(
                            healthId:  $patient->health_id,
                            name:      trim($patient->first_name . ' ' . $patient->last_name),
                            expiresAt: $patient->expires_at->toDateString(),
                            daysLeft:  $daysLeft,
                        ));

                        $this->line("  SENT  {$patient->health_id} — expires in {$daysLeft} days.");
                        $notified++;

                        Log::info('health_id_expiry_notification_sent', [
                            'patient_id' => $patient->id,
                            'health_id'  => $patient->health_id,
                            'expires_at' => $patient->expires_at->toIso8601String(),
                            'days_left'  => $daysLeft,
                        ]);
                    } catch (\Throwable $e) {
                        $this->warn("  ERROR {$patient->health_id} — {$e->getMessage()}");
                        $errors++;

                        Log::error('health_id_expiry_notification_failed', [
                            'patient_id' => $patient->id,
                            'health_id'  => $patient->health_id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info(sprintf(
            'Done. Notified: %d | Skipped (no account): %d | Errors: %d',
            $notified,
            $skipped,
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
