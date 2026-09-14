<?php

namespace App\Console\Commands;

use App\Services\Appointments\AppointmentWaitlistService;
use Illuminate\Console\Command;

/**
 * Move `appointment_waitlists` rows whose preferred window has passed from
 * `waiting` to `expired`. Nothing is deleted; `notified`, `booked` and
 * `cancelled` rows are never touched. Scheduled daily in routes/console.php.
 */
class ExpireWaitlistEntries extends Command
{
    protected $signature = 'waitlist:expire';

    protected $description = 'Expire appointment waitlist entries whose preferred latest date has passed';

    public function handle(AppointmentWaitlistService $service): int
    {
        $count = $service->expireOldEntries();

        $this->info(__('waitlist.expired_count', ['count' => $count]));

        return self::SUCCESS;
    }
}
