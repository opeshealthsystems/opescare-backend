<?php

namespace App\Modules\CareMap\Services;

use App\Enums\BloodComponentType;
use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\CareFacility;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Projects the operational blood-bank record onto the patient-facing signal.
 *
 * ── Why this class exists ────────────────────────────────────────────────────
 * Blood was recorded in two places that never spoke to each other:
 *
 *   `blood_inventories`   — the OPERATIONAL record. Integer `available_units`
 *                           per (blood_group, component) with the safety flags
 *                           (`is_expired` / `is_quarantined` / `is_unsafe`).
 *                           Keyed on `facilities.id` — the tenant record the
 *                           staff portal, the /v1/inventory/blood API and the
 *                           bridge agent all write through.
 *
 *   `blood_availability`  — the PUBLISHED signal. A coarse band
 *                           (`units_available_range`) plus status/freshness,
 *                           keyed on `care_facilities.id` — the public
 *                           directory listing the Blood Finder searches and
 *                           BloodRequestService gates a request against.
 *
 * They are NOT duplicates: one is units-and-safety, the other is a public
 * band the patient sees. But nothing derived the second from the first, so for
 * the one facility that had both they simply disagreed — the blood bank held
 * 5 units of O+ whole blood while the patient app advertised "20+".
 *
 * This projector is the missing arrow. `blood_inventories` is the source of
 * truth; `blood_availability` is its projection for every facility whose public
 * listing is linked to a tenant facility (`care_facilities.facility_id`).
 * Facilities with no operational record keep their self-reported availability —
 * still exactly one answer per facility, just a different (and clearly weaker)
 * source.
 *
 * ── Rules ────────────────────────────────────────────────────────────────────
 *  - Flagged units (expired / quarantined / unsafe) count as ZERO. They must
 *    never be advertised to a patient — the same safety rule
 *    BloodInventoryController documents for allocation.
 *  - A group/component the operational record does not back is published as
 *    `unavailable`, never left showing a stale band.
 *  - Rows are only ever updated, never deleted: `blood_requests` links to them
 *    and a patient's history must survive a stock rotation.
 *  - Every published row is STAMPED with the source that caused it
 *    (`blood_availability.source_system`). The Blood Finder withholds rows
 *    that are seeded or unattributed — see
 *    App\Models\BloodAvailability::scopeReportedByRealSource() — so a row this
 *    projector writes and does not stamp would be invisible to patients. The
 *    stamp is what makes projected data publishable at all.
 *  - Idempotent — running it twice changes nothing the second time.
 */
class BloodAvailabilityProjector
{
    /**
     * Default provenance for a projected row: the facility's own operational
     * blood-bank record.
     *
     * Callers that know something more specific say so — the staff portal
     * passes 'portal'. The default is deliberately a REAL source rather than
     * null: a `blood_inventories` row can only be created through an
     * authenticated facility write path (the staff blood screen, the partner
     * ingest at api/v1/connect/inventory/blood-stock/sync, the bridge agent, or
     * the facility inventory API), and the one seeder that touches that table
     * — DemoPharmacyStockSeeder — never runs this projector, so nothing it
     * writes is ever published.
     */
    public const SOURCE_OPERATIONAL = 'blood_inventory';

    /**
     * Operational component vocabulary → the patient-facing one.
     *
     * Three spellings are in the wild for the same thing: the seeded rows use
     * `packed_red_cells` / `fresh_frozen_plasma`, BloodInventoryController's
     * validator used to accept `packed_cells` / `plasma`, and
     * `blood_availability` stores `red_cells` / `plasma`. Aliases collapse onto
     * one published component and their units are summed, so an inventory
     * carrying both spellings still yields a single answer.
     *
     * `cryoprecipitate` deliberately maps to nothing: it has no patient-facing
     * chip in App\Enums\BloodComponentType, so it is held but not advertised.
     */
    private const COMPONENT_MAP = [
        'whole_blood'         => BloodComponentType::WholeBlood,
        'red_cells'           => BloodComponentType::RedCells,
        'packed_cells'        => BloodComponentType::RedCells,
        'packed_red_cells'    => BloodComponentType::RedCells,
        'prbc'                => BloodComponentType::RedCells,
        'plasma'              => BloodComponentType::Plasma,
        'fresh_frozen_plasma' => BloodComponentType::Plasma,
        'ffp'                 => BloodComponentType::Plasma,
        'platelets'           => BloodComponentType::Platelets,
    ];

    /**
     * Operational spellings the staff API and bridge agent may write.
     *
     * Every alias above, plus `cryoprecipitate` — genuinely held by a blood
     * bank but with no patient-facing component, so it is storable and simply
     * never published.
     *
     * @return list<string>
     */
    public static function operationalComponents(): array
    {
        return array_values(array_merge(array_keys(self::COMPONENT_MAP), ['cryoprecipitate']));
    }

    /** Maps one operational component spelling onto a published component. */
    public static function publishedComponent(string $component): ?BloodComponentType
    {
        return self::COMPONENT_MAP[strtolower(trim($component))] ?? null;
    }

    /**
     * Re-publish availability for one tenant facility (`facilities.id`).
     *
     * @param  string|null  $source  Provenance to stamp on every row published
     *                               by this pass. Defaults to
     *                               self::SOURCE_OPERATIONAL. Never pass a
     *                               value from BloodAvailability::SYNTHETIC_SOURCE_SYSTEMS
     *                               unless the data really is seeded — that is
     *                               the switch that hides a row from patients.
     * @return int Number of `blood_availability` rows written or refreshed.
     */
    public function projectFacility(string $facilityId, ?string $source = null): int
    {
        $listings = CareFacility::query()
            ->where('facility_id', $facilityId)
            ->get(['id', 'emergency_contact', 'phone_primary']);

        if ($listings->isEmpty()) {
            // The blood bank has no public directory listing, so there is
            // nothing for a patient to search. Not an error.
            return 0;
        }

        $inventory = BloodInventory::query()
            ->where('facility_id', $facilityId)
            ->get();

        if ($inventory->isEmpty()) {
            // No operational record: leave the self-reported availability
            // alone rather than blanking a facility that never opted in.
            return 0;
        }

        $projected = $this->aggregate($inventory);
        $written   = 0;

        foreach ($listings as $listing) {
            $written += $this->publish($listing, $projected, $source ?? self::SOURCE_OPERATIONAL);
        }

        return $written;
    }

    /**
     * Re-publish every facility that has an operational blood-bank record.
     *
     * @return array{facilities:int, rows:int}
     */
    public function projectAll(): array
    {
        $facilityIds = BloodInventory::query()
            ->select('facility_id')
            ->distinct()
            ->pluck('facility_id');

        $rows = 0;
        $done = 0;

        foreach ($facilityIds as $facilityId) {
            $written = $this->projectFacility((string) $facilityId);
            $rows   += $written;
            $done   += $written > 0 ? 1 : 0;
        }

        return ['facilities' => $done, 'rows' => $rows];
    }

    /**
     * Collapse the operational rows onto one entry per published
     * (blood_group, component_type).
     *
     * @param  \Illuminate\Support\Collection<int,BloodInventory>  $inventory
     * @return array<string,array{group:string,component:string,units:int,updated:?CarbonInterface}>
     */
    private function aggregate($inventory): array
    {
        $projected = [];

        foreach ($inventory as $item) {
            $component = self::publishedComponent((string) $item->component);

            if ($component === null) {
                continue;   // held, but has no patient-facing representation
            }

            $key = $item->blood_group . '|' . $component->value;

            // A flagged unit is not stock. It still registers the key so the
            // published row is actively set to `unavailable` instead of being
            // left advertising yesterday's band.
            $safe = ! $item->is_expired && ! $item->is_quarantined && ! $item->is_unsafe;

            $projected[$key] ??= [
                'group'     => (string) $item->blood_group,
                'component' => $component->value,
                'units'     => 0,
                'updated'   => null,
            ];

            $projected[$key]['units'] += $safe ? max(0, (int) $item->available_units) : 0;

            $stamp = $item->last_stock_update ?? $item->updated_at;
            if ($stamp !== null
                && ($projected[$key]['updated'] === null || $stamp->greaterThan($projected[$key]['updated']))) {
                $projected[$key]['updated'] = $stamp;
            }
        }

        return $projected;
    }

    /**
     * Write the projection onto one public listing.
     *
     * @param  array<string,array{group:string,component:string,units:int,updated:?CarbonInterface}>  $projected
     * @param  string  $source  Provenance stamped on every row touched here.
     */
    private function publish(CareFacility $listing, array $projected, string $source): int
    {
        return DB::transaction(function () use ($listing, $projected, $source) {
            $existing = BloodAvailability::query()
                ->where('facility_id', $listing->id)
                ->get()
                ->keyBy(fn (BloodAvailability $row) => $row->blood_group . '|' . $row->component_type);

            $contact = $listing->emergency_contact
                ?: ($listing->phone_primary !== 'N/A' ? $listing->phone_primary : null);

            $written = 0;

            foreach ($projected as $key => $entry) {
                [$status, $range] = $this->band($entry['units']);
                $row = $existing->get($key);

                $attributes = [
                    'units_available_range' => $range,
                    'availability_status'   => $status,
                    'freshness_status'      => $this->freshness($entry['updated']),
                    // Provenance is re-stamped on UPDATE as well as INSERT: a
                    // seeded row that a real blood bank has now taken over must
                    // stop being withheld from patients, and a row projected
                    // from the operational record IS the bank's own statement.
                    'source_system'         => $source,
                    // The freshness contract in CareMapController::searchBlood()
                    // reads exactly this column (REPORTED_AT_KEYS), so it must
                    // never be left null on a published row — a null here reads
                    // as 'stale' at best and drops the row out of meta entirely
                    // at worst.
                    'last_updated_at'       => $entry['updated'] ?? now(),
                ];

                if ($row === null) {
                    BloodAvailability::create($attributes + [
                        'facility_id'       => $listing->id,
                        'blood_group'       => $entry['group'],
                        'component_type'    => $entry['component'],
                        'emergency_contact' => $contact,
                    ]);
                } else {
                    // An emergency contact typed by the facility is theirs to
                    // keep; only fill it when it is missing.
                    if ($row->emergency_contact === null && $contact !== null) {
                        $attributes['emergency_contact'] = $contact;
                    }
                    $row->update($attributes);
                }

                $written++;
            }

            // Anything published but no longer backed by the operational record
            // is retired to `unavailable` — never deleted, because a patient's
            // request points at it.
            $orphans = $existing->keys()->diff(array_keys($projected));

            foreach ($orphans as $key) {
                $row = $existing->get($key);

                if ($row->availability_status === 'unavailable' && $row->units_available_range === '0') {
                    continue;   // already retired, keep the projection idempotent
                }

                $row->update([
                    'units_available_range' => '0',
                    'availability_status'   => 'unavailable',
                    'freshness_status'      => 'stale',
                    // "We no longer hold this" is the bank's statement too, and
                    // it supersedes whatever seeded row used to sit here.
                    'source_system'         => $source,
                    'last_updated_at'       => now(),
                ]);

                $written++;
            }

            return $written;
        });
    }

    /**
     * Integer units → the published band.
     *
     * Anything with at least one safe unit is `available`; the band carries how
     * scarce it is. The Blood Finder and BloodRequestService both gate on
     * `availability_status === 'available'`, so a bank holding two units of O-
     * stays findable — and can now decline the request through the blood-bank
     * queue instead of being invisible.
     *
     * @return array{0:string,1:string}
     */
    private function band(int $units): array
    {
        return match (true) {
            $units <= 0  => ['unavailable', '0'],
            $units <= 5  => ['available', '1-5'],
            $units <= 20 => ['available', '6-20'],
            default      => ['available', '20+'],
        };
    }

    /**
     * Same thresholds App\Modules\CareMap\Services\FacilityFreshnessService
     * applies to blood: 2h fresh, 6h recent, older is stale.
     */
    private function freshness(?CarbonInterface $updatedAt): string
    {
        if ($updatedAt === null) {
            return 'stale';
        }

        $hours = $updatedAt->diffInHours(now(), true);

        return match (true) {
            $hours <= 2 => 'fresh',
            $hours <= 6 => 'recent',
            default     => 'stale',
        };
    }
}
