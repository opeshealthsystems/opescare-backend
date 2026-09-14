<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Services\Lab\LabCatalogueProjector;
use Illuminate\Console\Command;

/**
 * Re-publishes `lab_test_availability` from `facility_catalogue_items`.
 *
 * Every catalogue write on /facility/catalogue projects automatically through
 * LabCatalogueProjector; this is the one-shot backfill for rows curated before
 * that existed, and the nightly safety net (routes/console.php) for a listing
 * that was linked AFTER its catalogue was filled — the link is an admin
 * action and does not itself trigger a projection.
 *
 * Idempotent and non-destructive: one row per (listing, LOINC code), rows are
 * updated in place, retired items are marked unavailable, nothing is deleted.
 */
class PublishLabCatalogue extends Command
{
    protected $signature = 'lab:publish-catalogue
                            {--facility= : Only this tenant facility id (facilities.id)}';

    protected $description = 'Publish every facility service catalogue (LOINC items, prices) to the public lab finder';

    public function handle(LabCatalogueProjector $projector): int
    {
        $ids = ($facilityId = $this->option('facility'))
            ? [(string) $facilityId]
            : $projector->tenantIdsWithLabCatalogue();

        $tenants = 0;
        $rows    = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            // The demo partition scope must not hide a real tenant on the
            // demo-mode host: this is a system pass over every catalogue.
            $tenant = Facility::withoutGlobalScope('isolate_demo')->find($id);

            if (! $tenant) {
                $this->warn("  {$id}: no such facility.");
                continue;
            }

            $tenants++;

            if (! $projector->isLinked($tenant)) {
                $skipped++;
                $this->line("  {$tenant->name} ({$id}): not linked to a public listing — nothing published.");
                continue;
            }

            $rows += $projector->syncListing($tenant);
        }

        $this->info("lab:publish-catalogue: {$rows} test row(s) published for {$tenants} facility(ies); {$skipped} unlinked.");

        return self::SUCCESS;
    }
}
