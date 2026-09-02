<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * The CareMap directory's free-text pharmacy formulary.
 *
 * NOT the Medicine Finder's stock table. This one keys a medicine by
 * `medicine_name` text only, so the same drug at two pharmacies is not
 * joinable; `medicine_pharmacy_stocks` (MedicinePharmacyStock) is the FK-keyed
 * table that the pharmacy portal writes to and that patients are served from.
 * See the header of 2026_08_31_000001_create_pharmacy_medicine_finder_tables.
 *
 * As of 2026-09-01 nothing INSERTS into this table in production (0 rows): the
 * OpesCare Lite offline path and FacilityFreshnessService only ever update rows
 * that already exist. It is kept in the public search so the CareMap directory
 * contract still holds if that write path is ever completed.
 */
class PharmacyStockAvailability extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $table = 'pharmacy_stock_availability';

    /**
     * `source_system` values that mark a row as SYNTHETIC.
     *
     * Kept in sync with MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS: the
     * two stock tables must not disagree about what counts as demo data.
     */
    public const SYNTHETIC_SOURCE_SYSTEMS = ['demo_seed', 'seed'];

    protected $fillable = [
        'facility_id',
        'medicine_name',
        'generic_name',
        'brand_name',
        'strength',
        'form',
        'local_medicine_code',
        'gtin',
        'availability_status',
        'quantity_available_range',
        'price',
        'currency',
        'reservation_enabled',
        'source_system',
        'freshness_status',
        'last_updated_at',
    ];

    protected $casts = [
        'price' => 'float',
        'reservation_enabled' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Rows a PATIENT may be shown — seeded fiction excluded at the source.
     *
     * Mirrors MedicinePharmacyStock::scopeReportedByRealSource(), including the
     * deliberate decision to KEEP rows whose `source_system` is NULL:
     * "unstamped" is not "seeded", and `whereNotIn` alone would drop them
     * silently under SQL three-valued logic.
     */
    public function scopeReportedByRealSource(Builder $query): Builder
    {
        $column = $this->getTable() . '.source_system';

        /*
         * NULL provenance is withheld too, deliberately.
         *
         * Every legitimate write stamps a source: the pharmacy portal writes
         * 'portal', the seeders write 'seed'/'demo_seed'. A row with no source
         * is one nobody has claimed, so it is not evidence that a medicine is
         * on a shelf — and this is the query a patient's search runs before
         * deciding whether to travel. Allow-list, not blacklist.
         */
        return $query->whereNotNull($column)
            ->whereNotIn($column, self::SYNTHETIC_SOURCE_SYSTEMS);
    }

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
