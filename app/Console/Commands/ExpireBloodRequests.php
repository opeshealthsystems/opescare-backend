<?php

namespace App\Console\Commands;

use App\Enums\BloodRequestStatus;
use App\Models\BloodRequest;
use App\Modules\CareMap\Services\BloodRequestService;
use Illuminate\Console\Command;

/**
 * Lapses blood-unit requests the facility never acted on.
 *
 * A request is a 24-hour hold (BloodRequestService::HOLD_HOURS) and
 * App\Enums\BloodRequestStatus documents the transition
 * `pending|confirmed|ready → expired (scheduler)` — but the scheduler did not
 * exist. `expires_at` was written on every row and never read, so nothing ever
 * left the open set: a patient with five unanswered holds hit
 * TOO_MANY_OPEN_REQUESTS for ever and the Blood Finder was, for them,
 * permanently dead. This is that missing sweep.
 *
 * Forward-only: an expired request is never deleted and a terminal row is never
 * touched, so the blood bank's history stays intact.
 *
 * Scheduled hourly from bootstrap/app.php (routes/console.php is sealed).
 */
class ExpireBloodRequests extends Command
{
    protected $signature = 'blood:expire-requests
                            {--dry-run : Report what would lapse without writing}';

    protected $description = 'Move blood requests past their hold window to expired';

    public function handle(BloodRequestService $requests): int
    {
        $due = BloodRequest::query()
            ->open()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        if ($this->option('dry-run')) {
            $this->info("blood:expire-requests [dry-run]: {$due} request(s) would move to "
                . BloodRequestStatus::Expired->value . '.');

            return self::SUCCESS;
        }

        $expired = $requests->expireLapsed();

        $this->info("blood:expire-requests: {$expired} request(s) expired. "
            . 'Still open: ' . BloodRequest::query()->open()->count() . '.');

        return self::SUCCESS;
    }
}
