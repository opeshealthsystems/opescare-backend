<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * The PUBLISHED blood signal: one coarse band per
 * (care_facilities.id, blood_group, component_type).
 *
 * This is what the Blood Finder answers with. It is a projection of the
 * operational `blood_inventories` record, written by
 * App\Modules\CareMap\Services\BloodAvailabilityProjector — never a
 * hand-maintained second copy of the shelf.
 */
class BloodAvailability extends Model
{
    use HasUuids;

    protected $table = 'blood_availability';

    /**
     * `source_system` values that mark a row as SYNTHETIC — written by a
     * seeder, never reported by a blood bank.
     *
     * `demo_seed` comes from DemoBloodInventorySeeder, which is where every row
     * in this table came from before the staff blood screen was reachable.
     * `seed` is carried for symmetry with the medicine tables.
     *
     * Kept in sync with MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS and
     * PharmacyStockAvailability::SYNTHETIC_SOURCE_SYSTEMS: the availability
     * tables must not disagree about what counts as demo data.
     */
    public const SYNTHETIC_SOURCE_SYSTEMS = ['demo_seed', 'seed'];

    protected $fillable = [
        'facility_id',
        'blood_group',
        'component_type',
        'units_available_range',
        'availability_status',
        'freshness_status',
        'emergency_contact',
        'source_system',
        'last_updated_at',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    /** Rows a facility is currently reporting as collectable. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.availability_status', 'available');
    }

    /**
     * Rows a PATIENT may be shown. Seeded fiction is excluded here, once, so a
     * future public call site cannot forget to do it.
     *
     * This is the same rule MedicinePharmacyStock::scopeReportedByRealSource()
     * applies to medicine, and it matters more here. A patient reading a
     * medicine listing may waste a trip; a patient — or a relative, or a
     * clinician — reading a BLOOD listing during a haemorrhage may drive past a
     * hospital that has units to reach one that never did. Seeded stock levels
     * are illustrative by construction: no public source publishes live
     * Cameroonian blood inventory, so DemoBloodInventorySeeder invented every
     * number it wrote. None of it is evidence about a real fridge.
     *
     * NULL `source_system` is withheld too, deliberately, and spelled out
     * rather than left to `whereNotIn` because SQL three-valued logic would
     * otherwise drop NULL rows silently — an accidental policy instead of a
     * decided one. Every legitimate write stamps a source: the projector
     * stamps every row it publishes from the operational blood-bank record,
     * and the seeder stamps 'demo_seed'. A row with no source is one nobody has
     * claimed. Allow-list, not blacklist.
     *
     * Any query that feeds a public surface MUST go through this scope.
     */
    public function scopeReportedByRealSource(Builder $query): Builder
    {
        $column = $this->getTable() . '.source_system';

        return $query->whereNotNull($column)
            ->whereNotIn($column, self::SYNTHETIC_SOURCE_SYSTEMS);
    }

    /** Whether this row may be shown to a patient. Mirrors the scope above. */
    public function isReportedByRealSource(): bool
    {
        return $this->source_system !== null
            && ! in_array($this->source_system, self::SYNTHETIC_SOURCE_SYSTEMS, true);
    }
}
