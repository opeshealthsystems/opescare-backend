<?php

namespace App\Console\Commands;

use App\Models\FacilityCatalogueItem;
use App\Services\CareMap\PublicServicesSync;
use Illuminate\Console\Command;

/**
 * Backfill / safety net: derive every linked listing's public services list
 * from its tenant's service catalogue (see PublicServicesSync). Idempotent —
 * a second run writes nothing. Also catches a listing linked AFTER its
 * catalogue was filled (linking does not itself trigger a sync).
 */
class SyncPublicServices extends Command
{
    protected $signature = 'caremap:sync-public-services
                            {--facility= : Only this tenant facility id (facilities.id)}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Derive the public Finder services list of every linked listing from the facility service catalogue';

    public function handle(PublicServicesSync $sync): int
    {
        $dry = (bool) $this->option('dry-run');

        $ids = ($facilityId = $this->option('facility'))
            ? collect([$facilityId])
            : FacilityCatalogueItem::query()->select('facility_id')->distinct()->pluck('facility_id');

        $totals = ['listings' => 0, 'upserted' => 0, 'removed' => 0, 'manual_removed' => 0];

        foreach ($ids as $id) {
            foreach ($sync->syncTenant((string) $id, $dry) as $k => $v) {
                $totals[$k] += $v;
            }
        }

        $this->info(sprintf(
            '%sTenants: %d, listings: %d, rows written: %d, catalogue rows removed: %d, manual rows replaced: %d',
            $dry ? '[dry-run] ' : '',
            $ids->count(), $totals['listings'], $totals['upserted'], $totals['removed'], $totals['manual_removed'],
        ));

        return self::SUCCESS;
    }
}
