<?php

namespace App\Console\Commands;

use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\MaintenanceWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMaintenanceWindows
 *
 * Runs every minute via the scheduler. Handles two transitions:
 *
 *  1. Auto-ACTIVATE: windows where starts_at <= now AND is_active = false
 *     AND (ends_at IS NULL OR ends_at > now).
 *
 *  2. Auto-EXPIRE: windows where is_active = true AND ends_at <= now.
 *
 * Both transitions flush the maintenance cache so enforcement reflects the
 * change within seconds without waiting for the 5-minute cache TTL.
 */
class ProcessMaintenanceWindows extends Command
{
    protected $signature   = 'maintenance:process';
    protected $description = 'Auto-activate and auto-expire scheduled maintenance windows';

    public function handle(): int
    {
        $activated = $this->activateDueWindows();
        $expired   = $this->expireEndedWindows();

        if ($activated > 0 || $expired > 0) {
            CheckMaintenanceMode::flushCache();
        }

        $this->line("Activated: {$activated}  |  Expired: {$expired}");
        return self::SUCCESS;
    }

    private function activateDueWindows(): int
    {
        $due = MaintenanceWindow::where('is_active', false)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->get();

        foreach ($due as $window) {
            $window->forceFill(['is_active' => true])->save();
            Log::info('ProcessMaintenanceWindows: window activated', [
                'id'    => $window->id,
                'title' => $window->title,
            ]);
        }

        return $due->count();
    }

    private function expireEndedWindows(): int
    {
        $ended = MaintenanceWindow::where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($ended as $window) {
            $window->forceFill(['is_active' => false])->save();
            Log::info('ProcessMaintenanceWindows: window expired', [
                'id'    => $window->id,
                'title' => $window->title,
            ]);
        }

        return $ended->count();
    }
}
