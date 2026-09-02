<?php

namespace App\Console\Commands;

use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use Illuminate\Console\Command;

/**
 * Re-publishes `blood_availability` from `blood_inventories`.
 *
 * Blood was recorded twice with no arrow between the two tables, so the
 * operational record and the patient-facing signal simply drifted apart — one
 * facility held 5 units of O+ whole blood while the Blood Finder advertised
 * "20+". Every write through BloodInventoryService now projects automatically;
 * this is the one-shot backfill for rows written before that, and the manual
 * re-sync for after a bulk import or a bridge-agent replay.
 *
 * Idempotent and non-destructive: it only ever updates rows, and a facility
 * with no operational record keeps its self-reported availability untouched.
 *
 * Not scheduled — routes/console.php is sealed, and projection is event-driven
 * on write rather than on a timer.
 */
class SyncBloodAvailability extends Command
{
    protected $signature = 'blood:sync-availability
                            {--facility= : Only this tenant facility id (facilities.id)}';

    protected $description = 'Re-publish patient-facing blood availability from the operational blood inventory';

    public function handle(BloodAvailabilityProjector $projector): int
    {
        if ($facilityId = $this->option('facility')) {
            $rows = $projector->projectFacility((string) $facilityId);

            $this->info("blood:sync-availability: {$rows} availability row(s) re-published for facility {$facilityId}.");

            if ($rows === 0) {
                $this->line('  Nothing projected — the facility has no blood inventory, or no public listing linked to it.');
            }

            return self::SUCCESS;
        }

        $result = $projector->projectAll();

        $this->info(sprintf(
            'blood:sync-availability: %d availability row(s) re-published across %d facility(ies).',
            $result['rows'],
            $result['facilities'],
        ));

        return self::SUCCESS;
    }
}
