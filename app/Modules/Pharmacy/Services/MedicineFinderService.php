<?php

namespace App\Modules\Pharmacy\Services;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Catalog search + geolocated pharmacy discovery for the patient Medicine Finder.
 *
 * Distance is computed as a SQL bounding-box pre-filter (index-friendly, plain
 * arithmetic, portable) followed by an exact haversine in PHP. Doing the
 * trigonometry in SQL would tie the query to a specific database's math
 * function set for no accuracy gain at this row count.
 *
 * Provenance
 * ----------
 * Every stock read here goes through `MedicinePharmacyStock::reportedByRealSource()`,
 * which withholds `demo_seed` / `seed` AND rows with a NULL `source_system`.
 * This is the same allow-list the public CareMap finder uses, and it is not
 * optional: the two services answer the same question for the same person, and
 * this one feeds the mobile app a patient uses to decide whether to travel
 * across a city for a drug. Until 2026-09-02 these two queries had no scope at
 * all, so `GET /api/mobile/pharmacy/nearby` published seeded stock as fact
 * while the web finder correctly showed nothing.
 *
 * NULL is withheld deliberately, not incidentally: the only two writers in the
 * application (`PharmacyStockReportService`, `PartnerStockIngestService`) both
 * stamp a source, so a NULL row is one nobody claimed. If a test fails here for
 * want of provenance, stamp the fixture `'portal'` -- never widen the scope.
 */
class MedicineFinderService
{
    /** Mean Earth radius, kilometres. */
    private const EARTH_RADIUS_KM = 6371.0088;

    /** Hard ceiling on the search radius a client may ask for. */
    public const MAX_RADIUS_KM = 50.0;

    /**
     * Pharmacy listings within `$radiusKm` of a point, nearest first.
     *
     * When `$medicine` is given, each pharmacy is annotated with that
     * medicine's stock row and pharmacies are ranked available-first; when
     * `$onlyStocking` is also true, pharmacies without an available stock row
     * are dropped entirely.
     *
     * @return Collection<int, array<string,mixed>>
     */
    public function nearbyPharmacies(
        float $latitude,
        float $longitude,
        float $radiusKm = 5.0,
        ?Medicine $medicine = null,
        bool $onlyStocking = false,
        int $limit = 25,
    ): Collection {
        $radiusKm = min(max($radiusKm, 0.5), self::MAX_RADIUS_KM);

        // Bounding box: 1 degree of latitude is ~111.32 km everywhere; a degree
        // of longitude shrinks by cos(latitude). Guard the cosine against the
        // poles so the box never collapses to zero width.
        $latDelta = $radiusKm / 111.32;
        $cosLat   = max(cos(deg2rad($latitude)), 0.0001);
        $lngDelta = $radiusKm / (111.32 * $cosLat);

        $candidates = CareFacility::query()
            ->where('listing_status', 'active')
            ->where('facility_type', 'pharmacy')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lngDelta, $longitude + $lngDelta])
            ->with('hours')
            ->get();

        $stocksByFacility = collect();
        if ($medicine) {
            $stocksByFacility = MedicinePharmacyStock::query()
                ->reportedByRealSource()
                ->where('medicine_id', $medicine->id)
                ->whereIn('care_facility_id', $candidates->pluck('id'))
                ->get()
                ->keyBy('care_facility_id');
        }

        return $candidates
            ->map(function (CareFacility $facility) use ($latitude, $longitude, $stocksByFacility) {
                $stock = $stocksByFacility->get($facility->id);

                return [
                    'facility'    => $facility,
                    'stock'       => $stock,
                    'distance_km' => $this->distanceKm(
                        $latitude,
                        $longitude,
                        (float) $facility->latitude,
                        (float) $facility->longitude,
                    ),
                ];
            })
            ->filter(fn (array $row) => $row['distance_km'] <= $radiusKm)
            ->when($onlyStocking, fn (Collection $rows) => $rows->filter(
                fn (array $row) => $row['stock'] instanceof MedicinePharmacyStock
                    && $row['stock']->stock_status->isAvailable(),
            ))
            // Available pharmacies first, then nearest.
            ->sort(function (array $a, array $b) {
                $byStock = $this->availabilityRank($b['stock'] ?? null)
                    <=> $this->availabilityRank($a['stock'] ?? null);

                return $byStock !== 0 ? $byStock : ($a['distance_km'] <=> $b['distance_km']);
            })
            ->take($limit)
            ->values();
    }

    /**
     * Availability summary for a medicine across every active pharmacy listing:
     * how many stock it, and the observed price range.
     *
     * @param  list<string>  $medicineIds
     * @return array<string, array{pharmacy_count:int, price_min:?float, price_max:?float, currency:string}>
     */
    public function availabilitySummary(array $medicineIds): array
    {
        if ($medicineIds === []) {
            return [];
        }

        $rows = MedicinePharmacyStock::query()
            ->reportedByRealSource()
            ->whereIn('medicine_id', $medicineIds)
            ->available()
            ->whereHas('careFacility', fn ($q) => $q->where('listing_status', 'active'))
            ->get(['medicine_id', 'unit_price', 'currency']);

        $summary = [];
        foreach ($medicineIds as $id) {
            $summary[$id] = [
                'pharmacy_count' => 0,
                'price_min'      => null,
                'price_max'      => null,
                'currency'       => 'XAF',
            ];
        }

        foreach ($rows as $row) {
            $id = $row->medicine_id;
            if (! isset($summary[$id])) {
                continue;
            }

            $summary[$id]['pharmacy_count']++;
            $summary[$id]['currency'] = $row->currency ?: 'XAF';

            if ($row->unit_price === null) {
                continue;
            }

            $summary[$id]['price_min'] = $summary[$id]['price_min'] === null
                ? $row->unit_price
                : min($summary[$id]['price_min'], $row->unit_price);
            $summary[$id]['price_max'] = $summary[$id]['price_max'] === null
                ? $row->unit_price
                : max($summary[$id]['price_max'], $row->unit_price);
        }

        return $summary;
    }

    /**
     * Is the pharmacy open at `$at`, and when does it next open or close?
     *
     * Reads the care_facility_hours rows already eager-loaded on the facility;
     * returns nulls (rather than guessing "open") when no hours are published.
     *
     * @return array{is_open:?bool, closes_at:?string, opens_at:?string, is_24_hours:bool}
     */
    public function openingState(CareFacility $facility, ?Carbon $at = null): array
    {
        $at        = $at ?? Carbon::now();
        $dayOfWeek = (int) $at->dayOfWeek;

        // Prefer a pharmacy-specific hours row for today, else the general one.
        $todaysRows = $facility->hours->where('day_of_week', $dayOfWeek);
        $today      = $todaysRows->firstWhere('service_context', 'Pharmacy')
            ?? $todaysRows->first();

        if (! $today) {
            return ['is_open' => null, 'closes_at' => null, 'opens_at' => null, 'is_24_hours' => false];
        }

        if ($today->is_24_hours) {
            return ['is_open' => true, 'closes_at' => null, 'opens_at' => null, 'is_24_hours' => true];
        }

        if ($today->is_closed || ! $today->opens_at || ! $today->closes_at) {
            return ['is_open' => false, 'closes_at' => null, 'opens_at' => null, 'is_24_hours' => false];
        }

        $opens  = $this->timeString($today->opens_at);
        $closes = $this->timeString($today->closes_at);
        $now    = $at->format('H:i:s');
        $isOpen = $now >= $opens && $now < $closes;

        return [
            'is_open'     => $isOpen,
            'closes_at'   => substr($closes, 0, 5),
            'opens_at'    => substr($opens, 0, 5),
            'is_24_hours' => false,
        ];
    }

    /** Great-circle distance in kilometres, rounded to 100 m. */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round(2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a))), 1);
    }

    /** In stock > low stock > everything else, for result ordering. */
    private function availabilityRank(?MedicinePharmacyStock $stock): int
    {
        if (! $stock) {
            return 0;
        }

        return match ($stock->stock_status) {
            PharmacyStockStatus::InStock    => 3,
            PharmacyStockStatus::LowStock   => 2,
            PharmacyStockStatus::OutOfStock => 1,
            PharmacyStockStatus::Unknown    => 0,
        };
    }

    /** care_facility_hours times arrive as a string or a Carbon depending on driver. */
    private function timeString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $value = (string) $value;

        return strlen($value) === 5 ? $value . ':00' : $value;
    }
}
