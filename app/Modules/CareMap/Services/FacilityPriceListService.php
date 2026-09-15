<?php

namespace App\Modules\CareMap\Services;

use App\Models\CareFacility;
use App\Models\FacilityCatalogueItem;
use App\Services\Lab\LabCatalogueProjector;
use Illuminate\Support\Collection;

/**
 * The public price list a linked facility has published in its service
 * catalogue — procedures (ICHI), services, specialties — for the Finder profile.
 *
 * Before this, a dental practice could add "Extraction of tooth" at 5000 FCFA
 * to /facility/catalogue and the catalogue screen told it a price is
 * "information for patients", yet no public page read the row. Lab tests
 * (LOINC) already reach patients through the lab finder projection, so they
 * are excluded here to avoid showing the same row twice.
 *
 * Read-only, public directory data, no patient rows. Bounded: a price list is
 * a menu, not a register.
 */
class FacilityPriceListService
{
    public const LIMIT = 100;

    /**
     * @return Collection<int, array{name: string, system: string, code: string, price_xaf: ?int}>
     */
    public function forListing(CareFacility $listing): Collection
    {
        if ($listing->facility_id === null || $listing->listing_status !== 'active') {
            return collect();
        }

        $items = FacilityCatalogueItem::query()
            ->active()
            ->where('facility_id', $listing->facility_id)
            ->where('system', '!=', LabCatalogueProjector::LAB_TEST_SYSTEM)
            ->orderBy('system')
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get();

        $dictionary = FacilityCatalogueItem::dictionaryFor($items);

        return $items->map(fn (FacilityCatalogueItem $item) => [
            'name'      => $item->displayName($dictionary[$item->dictionaryKey()] ?? null),
            'system'    => (string) $item->system,
            'code'      => (string) $item->code,
            'price_xaf' => $item->price_xaf,
        ])->values();
    }
}
