<?php

namespace App\Modules\CareMap\Services;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\MedicinePharmacyStock;
use App\Models\PharmacyStockAvailability;
use Illuminate\Database\Eloquent\Builder;

/**
 * Public "which pharmacy near me has this medicine" search
 * (GET /api/v1/care-map/pharmacies/medicine-search).
 *
 * Why this reads two tables
 * -------------------------
 * Reported medicine availability is stored twice on this platform:
 *
 *  1. `medicine_pharmacy_stocks` (MedicinePharmacyStock) — FK-keyed to the
 *     `medicines` catalog and to `care_facilities`. This is the LIVE table:
 *     PharmacyStockReportService writes it from the pharmacy portal, the mobile
 *     finder and MedicineFinderService read it. ~22.8k rows in production.
 *  2. `pharmacy_stock_availability` (PharmacyStockAvailability) — the older
 *     CareMap directory formulary, keyed on free-text `medicine_name`.
 *     0 rows in production, and no code path inserts into it.
 *
 * Until 2026-09-01 this service queried only (2), so the public Medicine Finder
 * was permanently empty and would have STAYED empty even after pharmacies began
 * reporting stock through the normal path. It now reads (1) as the primary
 * source and keeps (2) as a secondary, so the CareMap directory contract still
 * holds if that write path is ever completed.
 *
 * Both sources are normalised into the SAME `matched_stock` payload shape — the
 * one `pharmacy_stock_availability` already produced — so the public JSON
 * contract of the endpoint is unchanged for existing clients.
 *
 * Safety rules enforced here
 * --------------------------
 *  - Synthetic rows are excluded via `reportedByRealSource()` on both models.
 *    A patient must never be told a medicine is waiting for them on the
 *    strength of seeded data.
 *  - Only ACTIVE listings surface. This is the invariant pharmacists are
 *    already told about in PharmacyStockReportService::listingIssues() and that
 *    MedicineFinderService::nearbyPharmacies() enforces; this endpoint was the
 *    odd one out.
 *  - Freshest first. Stock reports decay; the previous sort put the STALEST
 *    result at the top, which never showed because the table it read was empty.
 */
class PharmacyStockSearchService
{
    /**
     * Ceiling on stock rows pulled from each source before distance filtering.
     *
     * This endpoint is public and unauthenticated, and the live table now holds
     * tens of thousands of rows: a one-letter query used to be free (empty
     * table) and would otherwise now hydrate every match plus its facility.
     * Rows are ordered freshest-first in SQL, so the cap keeps the most
     * trustworthy reports rather than an arbitrary slice.
     */
    private const MAX_CANDIDATE_ROWS = 500;

    /** Freshness ordering for the no-coordinates fallback sort. Higher is better. */
    private const FRESHNESS_RANK = ['fresh' => 3, 'recent' => 2, 'stale' => 1];

    /**
     * Search for pharmacies having reported stock for a specific medicine.
     *
     * @return list<CareFacility> facilities annotated with `distance` (km, or
     *                            null) and `matched_stock`
     */
    public function searchMedicine($medicineQuery, $lat = null, $lon = null, $radius = 50)
    {
        $term = trim((string) $medicineQuery);

        if ($term === '') {
            return [];
        }

        $matches = array_merge(
            $this->reportedStockMatches($term),
            $this->directoryStockMatches($term),
        );

        $facilities = [];

        foreach ($matches as [$facility, $stock]) {
            // Eager loading hands the SAME CareFacility instance to every stock
            // row of that pharmacy, so annotating it in place would let the last
            // match overwrite the others. One clone per result row keeps
            // `distance` and `matched_stock` honest when a pharmacy matches
            // several medicines (e.g. Ibuprofen 200mg and 400mg).
            $facility = clone $facility;

            // Apply coordinates filter
            if ($lat !== null && $lon !== null && $facility->latitude && $facility->longitude) {
                // Haversine formula calculation
                $distance = $this->calculateDistance($lat, $lon, $facility->latitude, $facility->longitude);
                if ($distance > $radius) {
                    continue;
                }
                $facility->distance = $distance;
            } else {
                $facility->distance = null;
            }

            $facility->matched_stock = $stock;
            $facilities[] = $facility;
        }

        // Sort by distance if available, else by freshness (freshest first).
        usort($facilities, function ($a, $b) {
            if ($a->distance !== null && $b->distance !== null) {
                return $a->distance <=> $b->distance;
            }

            return $this->freshnessRank($b->matched_stock['freshness_status'])
                <=> $this->freshnessRank($a->matched_stock['freshness_status']);
        });

        return $facilities;
    }

    /**
     * Matches from `medicine_pharmacy_stocks` — the table pharmacies and
     * partners actually write to.
     *
     * @return list<array{0: CareFacility, 1: array<string,mixed>}>
     */
    private function reportedStockMatches(string $term): array
    {
        $rows = MedicinePharmacyStock::query()
            // Seeded rows never reach a patient. See the scope's docblock.
            ->reportedByRealSource()
            ->whereHas('medicine', fn (Builder $q) => $q->active()->matchingTerm($term))
            ->whereHas('careFacility', fn (Builder $q) => $q->where('listing_status', 'active'))
            ->with(['medicine', 'careFacility'])
            ->orderByDesc('last_reported_at')
            ->limit(self::MAX_CANDIDATE_ROWS)
            ->get();

        $matches = [];

        foreach ($rows as $row) {
            $facility = $row->careFacility;
            $medicine = $row->medicine;

            if ($facility === null || $medicine === null) {
                continue;
            }

            $freshness = $this->freshnessFrom($row->last_reported_at);

            // `stock_status` is a backed enum (PharmacyStockStatus), never a
            // string — match() on the case, never `=== 'in_stock'`.
            $availability = match ($row->stock_status) {
                PharmacyStockStatus::InStock    => 'reported_available',
                PharmacyStockStatus::LowStock   => 'low_stock',
                PharmacyStockStatus::OutOfStock => 'out_of_stock',
                default                         => 'unknown',
            };

            $matches[] = [$facility, [
                'id'                       => $row->id,
                'facility_id'              => $row->care_facility_id,
                'medicine_id'              => $medicine->id,
                'medicine_name'            => $medicine->name,
                'generic_name'             => $medicine->generic_name,
                'brand_name'               => $medicine->brand_name,
                'strength'                 => $medicine->strength,
                'form'                     => $medicine->form,
                'local_medicine_code'      => null,
                'gtin'                     => null,
                'availability_status'      => $availability,
                'quantity_available_range' => $this->quantityRange($row->packs_available),
                'pack_size'                => $row->pack_size,
                'price'                    => $row->unit_price,
                'currency'                 => $row->currency,
                'reservation_enabled'      => (bool) $row->reservation_enabled,
                'source_system'            => $row->source_system,
                'freshness_status'         => $freshness,
                'last_updated_at'          => $row->last_reported_at?->toIso8601String(),
            ]];
        }

        return $matches;
    }

    /**
     * Matches from the legacy CareMap directory formulary.
     *
     * @return list<array{0: CareFacility, 1: array<string,mixed>}>
     */
    private function directoryStockMatches(string $term): array
    {
        $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';

        $rows = PharmacyStockAvailability::query()
            // Seeded rows never reach a patient. See the scope's docblock.
            ->reportedByRealSource()
            // Grouped, so the name alternatives cannot escape the source and
            // listing constraints above. The previous
            // where()->orWhere()->orWhere() chain was only safe because nothing
            // else constrained the query; it would have leaked the moment one
            // did — as it now does.
            ->where(function (Builder $q) use ($needle) {
                // LOWER() both sides: PostgreSQL LIKE is case-sensitive, so the
                // bare `like` this used to run never matched "ibuprofen"
                // against "Ibuprofen". Substring matching on a product
                // formulary carries none of the patient-enumeration risk the
                // LIKE prohibition guards against — no row here is personal data.
                $q->whereRaw('LOWER(medicine_name) LIKE LOWER(?)', [$needle])
                    ->orWhereRaw("LOWER(COALESCE(generic_name, '')) LIKE LOWER(?)", [$needle])
                    ->orWhereRaw("LOWER(COALESCE(brand_name, '')) LIKE LOWER(?)", [$needle]);
            })
            ->whereHas('facility', fn (Builder $q) => $q->where('listing_status', 'active'))
            ->with('facility')
            ->orderByDesc('last_updated_at')
            ->limit(self::MAX_CANDIDATE_ROWS)
            ->get();

        $matches = [];

        foreach ($rows as $row) {
            $facility = $row->facility;

            if ($facility === null) {
                continue;
            }

            $matches[] = [$facility, [
                'id'                       => $row->id,
                'facility_id'              => $row->facility_id,
                'medicine_id'              => null,
                'medicine_name'            => $row->medicine_name,
                'generic_name'             => $row->generic_name,
                'brand_name'               => $row->brand_name,
                'strength'                 => $row->strength,
                'form'                     => $row->form,
                'local_medicine_code'      => $row->local_medicine_code,
                'gtin'                     => $row->gtin,
                'availability_status'      => $row->availability_status,
                'quantity_available_range' => $row->quantity_available_range,
                'pack_size'                => null,
                'price'                    => $row->price,
                'currency'                 => $row->currency,
                'reservation_enabled'      => (bool) $row->reservation_enabled,
                'source_system'            => $row->source_system,
                'freshness_status'         => $row->freshness_status,
                'last_updated_at'          => $row->last_updated_at?->toIso8601String(),
            ]];
        }

        return $matches;
    }

    /**
     * Freshness of a report, on the same thresholds FacilityFreshnessService
     * applies to the directory table (<=24h fresh, <=72h recent, else stale).
     *
     * `medicine_pharmacy_stocks` has no stored freshness column, so it is
     * derived from `last_reported_at` at read time — which is strictly more
     * accurate than a column that only refreshes when a sweep job runs.
     * Computed from raw timestamps to stay independent of Carbon's
     * version-dependent signed/absolute `diffInHours()` behaviour.
     */
    private function freshnessFrom($reportedAt): string
    {
        if ($reportedAt === null) {
            return 'stale';
        }

        $ageHours = max(0, time() - $reportedAt->getTimestamp()) / 3600;

        return match (true) {
            $ageHours <= 24 => 'fresh',
            $ageHours <= 72 => 'recent',
            default         => 'stale',
        };
    }

    /** Sort weight for a freshness label; unknown labels sort last. */
    private function freshnessRank(?string $freshness): int
    {
        return self::FRESHNESS_RANK[$freshness] ?? 0;
    }

    /**
     * Bucket an exact pack count into the coarse range the directory payload
     * has always carried. Machine tokens, not display strings — anything a
     * patient reads has to come from a lang file.
     */
    private function quantityRange(?int $packsAvailable): ?string
    {
        if ($packsAvailable === null) {
            return null;
        }

        return match (true) {
            $packsAvailable <= 0   => '0',
            $packsAvailable <= 10  => '1-10',
            $packsAvailable <= 50  => '11-50',
            $packsAvailable <= 100 => '51-100',
            default                => '>100',
        };
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
