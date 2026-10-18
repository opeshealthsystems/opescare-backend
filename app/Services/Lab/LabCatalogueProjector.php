<?php

namespace App\Services\Lab;

use App\Models\CareFacility;
use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\LabTestAvailability;
use App\Services\Imaging\ImagingExamSearch;
use Illuminate\Support\Facades\Log;

/**
 * LabCatalogueProjector — publishes a facility's service catalogue to the
 * public lab finder.
 *
 * The lab manager curates `facility_catalogue_items` (dictionary code, local
 * name, price in whole francs) at /facility/catalogue. The public finder —
 * GET /api/v1/care-map/labs/test-search → LabTestSearchService — reads
 * `lab_test_availability`, keyed on the PUBLIC listing (`care_facilities`).
 * Until this class existed nothing carried rows from one to the other, so no
 * laboratory's menu or prices ever reached a patient (audit item L-7).
 *
 * Same shape as the pharmacy's PharmacyStockOpsService::syncFinder():
 *
 *   - the tenant (`facilities`) must be linked to a listing through
 *     `care_facilities.facility_id`. An unlinked tenant projects NOTHING and
 *     is logged — linking is an admin decision, never done here;
 *   - one availability row per (listing, code). LOINC items are lab tests
 *     (or imaging exams, when the dictionary files them so — ImagingExamSearch
 *     reads those). Since 2026-10-13 (I-3) an ICHI diagnostic-imaging stem
 *     (action `BA`, ImagingExamSearch::isImagingProcedureCode) is projected
 *     too, into the same table, and reaches ONLY the imaging finder: the lab
 *     finder excludes it (LabTestAvailability::scopeExcludingImaging). Other
 *     ICHI procedures, HL7 service types and CVX vaccines stay in the
 *     catalogue and reach no finder;
 *   - a retired catalogue item marks its row `unavailable`. Never deleted:
 *     the price a patient was quoted last week stays accountable;
 *   - every row is stamped `source_system = 'portal'`, which is what the
 *     public read gate (LabTestAvailability::scopeReportedByRealSource)
 *     admits. Seeded and unclaimed rows stay invisible.
 *
 * Prices are information, never a gate. Nothing here reads a price to permit
 * or refuse anything.
 */
class LabCatalogueProjector
{
    /** The catalogue `system` that means "a laboratory test". */
    public const LAB_TEST_SYSTEM = 'LOINC';

    /** The catalogue systems this projector can publish (LOINC, and ICHI imaging stems only). */
    public const PUBLISHED_SYSTEMS = [self::LAB_TEST_SYSTEM, ImagingExamSearch::ICHI_SYSTEM];

    /**
     * Whether a catalogue item is one the finders can carry: any LOINC test,
     * or an ICHI diagnostic-imaging procedure. The controller asks this before
     * re-projecting after a write.
     */
    public static function publishes(FacilityCatalogueItem $item): bool
    {
        return $item->system === self::LAB_TEST_SYSTEM
            || ($item->system === ImagingExamSearch::ICHI_SYSTEM && ImagingExamSearch::isImagingProcedureCode($item->code));
    }

    /**
     * Publish every LOINC catalogue item — and every ICHI diagnostic-imaging
     * procedure — of this tenant to its public listing.
     *
     * @return int  Rows written (created or updated). 0 when unlinked or when
     *              the catalogue holds no lab test.
     */
    public function syncListing(Facility $tenant): int
    {
        $listing = $this->listingFor($tenant);

        if (! $listing) {
            Log::warning('lab catalogue not published: tenant has no linked public listing', [
                'facility_id' => $tenant->id,
            ]);

            return 0;
        }

        $items = FacilityCatalogueItem::query()
            ->where('facility_id', $tenant->id)
            ->whereIn('system', self::PUBLISHED_SYSTEMS)
            ->orderBy('code')
            ->get()
            ->filter(fn (FacilityCatalogueItem $item) => self::publishes($item))
            ->values();

        if ($items->isEmpty()) {
            return 0;
        }

        $dictionary = FacilityCatalogueItem::dictionaryFor($items);
        $now        = now();
        $written    = 0;

        foreach ($items as $item) {
            /** @var ClinicalCodeDictionary|null $entry */
            $entry = $dictionary[$item->dictionaryKey()] ?? null;

            $row = LabTestAvailability::query()
                ->where('facility_id', $listing->id)
                ->where('loinc_code', $item->code)
                ->first() ?? new LabTestAvailability([
                    'facility_id' => $listing->id,
                    'loinc_code'  => $item->code,
                ]);

            $row->fill([
                'test_name'           => $item->displayName($entry),
                'turnaround_time'     => $item->turnaround,
                // Whole francs, twice: the integer column is the truth, the
                // legacy decimal carries the same value for older readers.
                'price_xaf'           => $item->price_xaf,
                'price'               => $item->price_xaf,
                'currency'            => 'XAF',
                'availability_status' => $item->is_active
                    ? LabTestAvailability::STATUS_AVAILABLE
                    : LabTestAvailability::STATUS_UNAVAILABLE,
                'freshness_status'    => 'fresh',
                'source_system'       => LabTestAvailability::SOURCE_PORTAL,
                'last_updated_at'     => $now,
                'last_published_at'   => $now,
            ]);

            $row->save();
            $written++;
        }

        // Rows this listing published earlier for codes the catalogue no
        // longer holds at all (an item could only vanish by direct SQL, but
        // the finder must not keep advertising it).
        LabTestAvailability::query()
            ->where('facility_id', $listing->id)
            ->where('source_system', LabTestAvailability::SOURCE_PORTAL)
            ->whereNotIn('loinc_code', $items->pluck('code')->all())
            ->where('availability_status', LabTestAvailability::STATUS_AVAILABLE)
            ->update([
                'availability_status' => LabTestAvailability::STATUS_UNAVAILABLE,
                'last_updated_at'     => $now,
                'last_published_at'   => $now,
                'updated_at'          => $now,
            ]);

        $listing->forceFill(['last_availability_update_at' => $now])->save();

        return $written;
    }

    /** Whether this tenant has a public listing to publish to. */
    public function isLinked(Facility $tenant): bool
    {
        return CareFacility::query()->where('facility_id', $tenant->id)->exists();
    }

    public function listingFor(Facility $tenant): ?CareFacility
    {
        return CareFacility::query()->where('facility_id', $tenant->id)->first();
    }

    /**
     * Every tenant that has at least one lab-test catalogue item, for the
     * backfill command. Yields tenant ids, not models, so a national run
     * does not load 900 facilities at once.
     *
     * @return iterable<string>
     */
    public function tenantIdsWithLabCatalogue(): iterable
    {
        return FacilityCatalogueItem::query()
            ->whereIn('system', self::PUBLISHED_SYSTEMS)
            ->distinct()
            ->orderBy('facility_id')
            ->pluck('facility_id');
    }
}
