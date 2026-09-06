<?php

namespace App\Models;

use App\Enums\PharmacyStockStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Availability + price of one catalog Medicine at one pharmacy listing
 * (care_facilities row of facility_type = 'pharmacy').
 *
 * Reported stock, not dispensed-from stock: `stock_status` may legitimately be
 * Unknown and must never be rendered as available in that case.
 */
class MedicinePharmacyStock extends Model
{
    use HasUuids;

    protected $table = 'medicine_pharmacy_stocks';

    /**
     * `source_system` values that mark a row as SYNTHETIC — written by a
     * seeder, never reported by a pharmacy.
     *
     * `demo_seed` comes from PharmacyFinderCoverageSeeder, `seed` from
     * PharmacyCatalogSeeder; between them they account for essentially every
     * row in this table today. They exist so the finder has something to
     * render in demos and so real coverage can be measured against them
     * (PharmacyStockReportService::coverage()) — they are NOT statements about
     * what any pharmacy actually has on its shelf.
     *
     * Keep in sync with PharmacyStockAvailability::SYNTHETIC_SOURCE_SYSTEMS
     * and with the takeover rule in PharmacyStockReportService::report().
     */
    public const SYNTHETIC_SOURCE_SYSTEMS = ['demo_seed', 'seed'];

    /**
     * The "low" threshold a line falls back to when the pharmacy has not set
     * its own `reorder_level`. Ten packs: enough for a day at a busy counter,
     * few enough that the warning is worth acting on.
     */
    public const DEFAULT_REORDER_LEVEL = 10;

    protected $fillable = [
        'medicine_id',
        'care_facility_id',
        'stock_status',
        'packs_available',
        'reorder_level',
        'pack_size',
        'unit_price',
        'currency',
        'reservation_enabled',
        'source_system',
        'last_stocked_at',
        'last_reported_at',
    ];

    protected $casts = [
        'stock_status'        => PharmacyStockStatus::class,
        'packs_available'     => 'integer',
        'reorder_level'       => 'integer',
        'unit_price'          => 'float',
        'reservation_enabled' => 'boolean',
        'last_stocked_at'     => 'datetime',
        'last_reported_at'    => 'datetime',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function careFacility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'care_facility_id');
    }

    /** Listings a patient could actually collect from today. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('stock_status', [
            PharmacyStockStatus::InStock->value,
            PharmacyStockStatus::LowStock->value,
        ]);
    }

    /**
     * Rows a PATIENT may be shown. Seeded fiction is excluded here, once, so a
     * future public call site cannot forget to do it.
     *
     * Telling somebody a medicine is waiting for them at a pharmacy on the
     * strength of a seeder is a safety failure, not a cosmetic one: they may
     * travel for it, or stop looking elsewhere. Any query that feeds a public
     * surface MUST go through this scope.
     *
     * NULL `source_system` is deliberately WITHHELD, not kept. Every legitimate
     * write stamps a source — the pharmacy portal writes 'portal' via
     * PharmacyStockReportService::SOURCE_PORTAL, the seeders write
     * 'seed'/'demo_seed' — so an unstamped row is one nobody has claimed. That
     * is not evidence a medicine is on a shelf, and this is the query a patient
     * runs before deciding whether to travel. Allow-list, not blacklist.
     *
     * It is `whereNotNull` plus `whereNotIn` rather than `whereNotIn` alone
     * because SQL three-valued logic would drop NULL rows anyway — spelling it
     * out makes that a decided policy instead of an accident of the dialect.
     *
     * If a future reader finds this comment disagreeing with the code, the CODE
     * is right: do not "correct" it to admit NULL rows. That would publish
     * unattributed stock to patients.
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

    /** Whether this listing may back a new reservation right now. */
    public function isReservable(): bool
    {
        return $this->reservation_enabled && $this->stock_status->isReservable();
    }

    /** The threshold this line is judged against — its own, else the platform default. */
    public function effectiveReorderLevel(): int
    {
        return $this->reorder_level ?? self::DEFAULT_REORDER_LEVEL;
    }

    /**
     * The status a pack count implies, against this line's threshold. This is
     * what the desk writes after an adjustment, so the count and the badge a
     * patient sees can never contradict each other.
     */
    public function statusForQuantity(int $quantity): PharmacyStockStatus
    {
        if ($quantity <= 0) {
            return PharmacyStockStatus::OutOfStock;
        }

        return $quantity <= $this->effectiveReorderLevel()
            ? PharmacyStockStatus::LowStock
            : PharmacyStockStatus::InStock;
    }

    /** Needs the pharmacist's attention: low or gone. */
    public function isLow(): bool
    {
        return in_array($this->stock_status, [PharmacyStockStatus::LowStock, PharmacyStockStatus::OutOfStock], true);
    }

    /** Query-side twin of isLow(), so the home counter and the `?low=1` list agree. */
    public function scopeLow(Builder $query): Builder
    {
        return $query->whereIn($this->getTable() . '.stock_status', [
            PharmacyStockStatus::LowStock->value,
            PharmacyStockStatus::OutOfStock->value,
        ]);
    }
}
