<?php

namespace App\Observers;

use App\Models\FacilityCatalogueItem;
use App\Services\CareMap\PublicServicesSync;

/**
 * Keeps the public services list (care_facility_services) in step with the
 * facility service catalogue — the single editor since 2026-10-20.
 *
 * An observer rather than a call in FacilityCatalogueController because the
 * catalogue has several writers (the catalogue screen, the lab manager desk,
 * seeders) and every one of them must reach the public profile.
 */
class FacilityCatalogueItemObserver
{
    public function __construct(private readonly PublicServicesSync $sync) {}

    public function saved(FacilityCatalogueItem $item): void
    {
        $this->sync->syncTenant((string) $item->facility_id);

        $original = $item->getOriginal('facility_id');
        if ($item->wasChanged('facility_id') && $original) {
            $this->sync->syncTenant((string) $original);
        }
    }

    public function deleted(FacilityCatalogueItem $item): void
    {
        $this->sync->syncTenant((string) $item->facility_id);
    }
}
