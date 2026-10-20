<?php

namespace App\Services\CareMap;

use App\Models\CareFacility;
use App\Models\CareFacilityService;
use App\Models\FacilityCatalogueItem;
use App\Services\Imaging\ImagingExamSearch;
use App\Services\Lab\LabCatalogueProjector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Derives the public services list (care_facility_services) of every listing
 * linked to a tenant facility from that tenant's ACTIVE catalogue items.
 *
 * 2026-10-20, owner's decision: the facility service catalogue
 * (/facility/catalogue, facility_catalogue_items) is the single editor of what
 * a facility offers. The services forms on /facility/settings and
 * /portals/listing are gone; this class is the only writer of catalogue rows.
 *
 * ── Rules ────────────────────────────────────────────────────────────────
 *  - One row per active non-lab (not LOINC) catalogue item per linked listing, marked
 *    source = 'catalogue' and catalogue_item_id = the item. Updated in place.
 *  - A retired (is_active = false) or deleted item loses its public row.
 *  - Legacy MANUAL rows (source = 'manual', typed in before the catalogue
 *    existed) are KEPT while the tenant's catalogue has no active item, so a
 *    facility that has not opened its catalogue yet does not lose its public
 *    list overnight. The moment the catalogue has at least one active item it
 *    is the source of truth and the manual rows are removed — each removal is
 *    audited in facility_update_audits with the old name, so it is
 *    accountable and recoverable.
 *  - No invented data: the name is the facility's own local_name, else the
 *    published dictionary display, else the bare code. price_range stays NULL
 *    — a catalogue price is whole francs and is shown on the profile's price
 *    list only when the facility published one (FacilityPriceListService);
 *    nothing here turns it into a low/medium/high guess.
 *  - Facility identity comes from the catalogue row's own facility_id (which
 *    the catalogue controller took from the session tenant), never a request.
 */
class PublicServicesSync
{
    public const AUDIT_SOURCE = 'catalogue_sync';

    /**
     * @return array{listings:int, upserted:int, removed:int, manual_removed:int}
     */
    public function syncTenant(string $tenantFacilityId, bool $dryRun = false): array
    {
        $result = ['listings' => 0, 'upserted' => 0, 'removed' => 0, 'manual_removed' => 0];

        $listings = CareFacility::query()->where('facility_id', $tenantFacilityId)->get();

        if ($listings->isEmpty()) {
            return $result;
        }

        $items = FacilityCatalogueItem::query()
            ->active()
            ->where('facility_id', $tenantFacilityId)
            // Lab tests (LOINC) reach patients through the lab test finder
            // (LabCatalogueProjector) and are left out here for the same
            // reason FacilityPriceListService leaves them out: a lab's
            // catalogue is hundreds of tests, not a services card.
            ->where('system', '!=', LabCatalogueProjector::LAB_TEST_SYSTEM)
            ->orderBy('system')
            ->orderBy('code')
            ->get();

        $dictionary = FacilityCatalogueItem::dictionaryFor($items);

        foreach ($listings as $listing) {
            $result['listings']++;
            $counts = $this->syncListing($listing, $items, $dictionary, $dryRun);
            foreach ($counts as $k => $v) {
                $result[$k] += $v;
            }
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FacilityCatalogueItem>  $items
     * @param  array<string, \App\Models\ClinicalCodeDictionary>  $dictionary
     * @return array{upserted:int, removed:int, manual_removed:int}
     */
    private function syncListing(CareFacility $listing, $items, array $dictionary, bool $dryRun): array
    {
        $existing = CareFacilityService::query()
            ->where('facility_id', $listing->id)
            ->get();

        $byItem = $existing->where('source', CareFacilityService::SOURCE_CATALOGUE)->keyBy('catalogue_item_id');
        $wanted = $items->keyBy('id');

        $stale  = $existing->where('source', CareFacilityService::SOURCE_CATALOGUE)
            ->filter(fn ($row) => ! $wanted->has($row->catalogue_item_id));
        $manual = $items->isNotEmpty()
            ? $existing->where('source', '!=', CareFacilityService::SOURCE_CATALOGUE)
            : collect();

        $counts = ['upserted' => 0, 'removed' => $stale->count(), 'manual_removed' => $manual->count()];

        $plans = [];
        foreach ($items as $item) {
            $entry = $dictionary[$item->dictionaryKey()] ?? null;
            $attrs = [
                'service_name'     => Str::limit($this->name($item, $entry), 250, ''),
                'service_category' => self::categoryFor($item),
                'service_code'     => $item->system . ':' . $item->code,
                'specialty'        => null,
                'price_range'      => null,
            ];
            $row = $byItem->get($item->id);
            if ($row === null || $this->differs($row, $attrs)) {
                $plans[] = [$row, $item, $attrs];
            }
        }
        $counts['upserted'] = count($plans);

        if ($dryRun || ($plans === [] && $stale->isEmpty() && $manual->isEmpty())) {
            return $counts;
        }

        DB::transaction(function () use ($listing, $plans, $stale, $manual) {
            foreach ($plans as [$row, $item, $attrs]) {
                $row ??= new CareFacilityService([
                    'facility_id'            => $listing->id,
                    'source'                 => CareFacilityService::SOURCE_CATALOGUE,
                    'catalogue_item_id'      => $item->id,
                    'availability_status'    => 'available',
                    'appointment_required'   => false,
                    'walk_in_allowed'        => true,
                    'telemedicine_available' => false,
                ]);
                $row->fill($attrs + ['last_updated_at' => now()])->save();
            }

            if ($stale->isNotEmpty()) {
                CareFacilityService::whereIn('id', $stale->pluck('id'))->delete();
            }

            foreach ($manual as $row) {
                DB::table('facility_update_audits')->insert([
                    'id'              => (string) Str::uuid(),
                    'facility_id'     => $listing->id,
                    'actor_id'        => null,
                    'actor_type'      => 'system',
                    'field_changed'   => 'service:' . $row->id,
                    'old_value'       => $row->service_name,
                    'new_value'       => null,
                    'source'          => self::AUDIT_SOURCE,
                    'requires_review' => false,
                    'created_at'      => now(),
                ]);
            }
            if ($manual->isNotEmpty()) {
                CareFacilityService::whereIn('id', $manual->pluck('id'))->delete();
            }

            $listing->forceFill(['last_profile_update_at' => now()])->save();
        });

        return $counts;
    }

    private function name(FacilityCatalogueItem $item, $entry): string
    {
        if (filled($item->local_name)) {
            return (string) $item->local_name;
        }

        // The stored name is the publisher's own (English) display: a stored
        // row cannot follow the reader's locale, and display_fr is not always
        // published. Bare code is the honest last resort.
        return filled($entry?->display) ? (string) $entry->display : (string) $item->code;
    }

    private function differs(CareFacilityService $row, array $attrs): bool
    {
        foreach ($attrs as $k => $v) {
            if ($row->{$k} !== $v) {
                return true;
            }
        }

        return false;
    }

    /** Map a catalogue system to one of the public service categories. */
    public static function categoryFor(FacilityCatalogueItem $item): string
    {
        return match (true) {
            $item->system === LabCatalogueProjector::LAB_TEST_SYSTEM => 'laboratory',
            $item->system === ImagingExamSearch::ICHI_SYSTEM && ImagingExamSearch::isImagingProcedureCode($item->code) => 'imaging',
            $item->system === ImagingExamSearch::ICHI_SYSTEM => 'procedure',
            $item->system === 'CVX' => 'preventive',
            default => 'consultation',
        };
    }
}
