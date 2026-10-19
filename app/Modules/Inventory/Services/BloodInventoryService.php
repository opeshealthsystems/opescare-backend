<?php

namespace App\Modules\Inventory\Services;

use App\Models\BloodInventory;
use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use Illuminate\Support\Collection;

/**
 * The operational blood-bank record: integer units and safety flags per
 * (blood_group, component), keyed on the tenant `facilities.id`.
 *
 * This table is the SOURCE OF TRUTH for "how much O+ does this facility have".
 * The patient-facing `blood_availability` row is a projection of it, refreshed
 * by BloodAvailabilityProjector after every write here — so the number a clerk
 * types into the staff portal is the number the Blood Finder shows, and the one
 * BloodRequestService gates a patient's request against. Nothing else may write
 * availability for a facility that has an operational record.
 */
class BloodInventoryService
{
    public function __construct(
        private readonly BloodAvailabilityProjector $projector = new BloodAvailabilityProjector(),
    ) {
    }

    public function list(string $facilityId, array $filters = []): Collection
    {
        $query = BloodInventory::where('facility_id', $facilityId)
            ->orderBy('blood_group')
            ->orderBy('component');

        if (!empty($filters['blood_group'])) {
            $query->where('blood_group', $filters['blood_group']);
        }
        if (!empty($filters['component'])) {
            $query->where('component', $filters['component']);
        }

        return $query->get();
    }

    /**
     * @param  string|null  $source  Provenance stamped on the published
     *                               `blood_availability` row this write
     *                               refreshes. Null keeps the projector's
     *                               default (BloodAvailabilityProjector::SOURCE_OPERATIONAL).
     *                               The Blood Finder withholds rows with no
     *                               source, so this is what makes a staff
     *                               entry visible to a patient at all.
     */
    public function upsertUnit(string $facilityId, array $data, ?string $source = null): BloodInventory
    {
        // `source_system` is provenance for the PUBLISHED row, not a column on
        // this table — strip it if a caller passed the whole request through.
        unset($data['source_system']);

        // Only the stock fields are writable from a caller. The portal passes
        // the request through, and `facility_id` is fillable on the model, so
        // without this allow-list a body field `facility_id` on the UPDATE path
        // re-homed the row into another facility's shelf (the create path was
        // safe only because array_merge put the trusted id last). The facility
        // is always the trusted $facilityId argument.
        $data = array_intersect_key($data, array_flip([
            'blood_group', 'component', 'available_units',
            'is_expired', 'is_quarantined', 'is_unsafe',
        ]));

        // Upsert by facility + blood_group + component
        $existing = BloodInventory::where('facility_id', $facilityId)
            ->where('blood_group', $data['blood_group'])
            ->where('component', $data['component'])
            ->first();

        if ($existing) {
            $existing->update(array_merge($data, [
                'last_stock_update' => now(),
            ]));
            $this->projector->projectFacility($facilityId, $source);

            return $existing;
        }

        $created = BloodInventory::create(array_merge($data, [
            'facility_id'       => $facilityId,
            'last_stock_update' => now(),
        ]));

        $this->projector->projectFacility($facilityId, $source);

        return $created;
    }

    /**
     * @param  string|null  $facilityId  When given, the item must belong to it.
     *                                   The API passes the middleware-resolved
     *                                   facility so one client can never adjust
     *                                   another blood bank's shelf.
     */
    public function adjustUnits(string $itemId, int $delta, string $direction = 'add', ?string $facilityId = null, ?string $source = null): BloodInventory
    {
        $item = $this->findScoped($itemId, $facilityId);

        if ($direction === 'add') {
            $item->available_units = max(0, $item->available_units + $delta);
        } else {
            $item->available_units = max(0, $item->available_units - $delta);
        }

        $item->last_stock_update = now();
        $item->save();

        $this->projector->projectFacility((string) $item->facility_id, $source);

        return $item;
    }

    /** @param  string|null  $facilityId  See adjustUnits(). */
    public function setFlags(string $itemId, array $flags, ?string $facilityId = null, ?string $source = null): BloodInventory
    {
        $allowed = ['is_expired', 'is_quarantined', 'is_unsafe'];
        $update  = array_intersect_key($flags, array_flip($allowed));
        $item    = $this->findScoped($itemId, $facilityId);
        $item->update($update);

        // Flagging a unit expired/quarantined/unsafe pulls it out of the
        // published band immediately — an unsafe unit must never be advertised.
        $this->projector->projectFacility((string) $item->facility_id, $source);

        return $item;
    }

    /**
     * Look an item up, optionally pinned to one facility.
     *
     * Without the pin this was a cross-facility IDOR on the API: the routes
     * carry only an item id, so any authenticated integration client could
     * adjust or flag another blood bank's units.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function findScoped(string $itemId, ?string $facilityId): BloodInventory
    {
        $query = BloodInventory::query()->whereKey($itemId);

        if ($facilityId !== null) {
            $query->where('facility_id', $facilityId);
        }

        return $query->firstOrFail();
    }

    public function summary(string $facilityId): array
    {
        $items = BloodInventory::where('facility_id', $facilityId)->get();
        return [
            'total_units'    => $items->sum('available_units'),
            'groups_covered' => $items->where('available_units', '>', 0)->pluck('blood_group')->unique()->count(),
            'expired'        => $items->where('is_expired', true)->count(),
            'unsafe'         => $items->where('is_unsafe', true)->count(),
            'quarantined'    => $items->where('is_quarantined', true)->count(),
        ];
    }
}
