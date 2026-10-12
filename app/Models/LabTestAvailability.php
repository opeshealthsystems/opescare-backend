<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One test a public listing (`care_facilities`) says it performs.
 *
 * Since 2026-09-14 this table is a PROJECTION of the facility service
 * catalogue: `App\Services\Lab\LabCatalogueProjector` writes one row per
 * LOINC catalogue item, stamped `source_system = 'portal'`. The public finder
 * reads only rows a real source published — see scopeReportedByRealSource().
 */
class LabTestAvailability extends Model
{
    use HasUuids;

    protected $table = 'lab_test_availability';

    /** What the projector stamps on a row published from /facility/catalogue. */
    public const SOURCE_PORTAL = 'portal';

    /**
     * Provenance values that are NOT evidence a lab performs a test. Keep in
     * sync with MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS.
     */
    public const SYNTHETIC_SOURCE_SYSTEMS = ['demo_seed', 'seed'];

    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'facility_id',
        'test_name',
        'local_test_code',
        'loinc_code',
        'specimen_type',
        'turnaround_time',
        'price',
        'price_xaf',
        'currency',
        'requires_doctor_order',
        'sample_collection_available',
        'home_sample_collection_available',
        'availability_status',
        'freshness_status',
        'source_system',
        'last_updated_at',
        'last_published_at',
    ];

    protected $casts = [
        'price' => 'float',
        // XAF is a zero-decimal currency: whole francs, never a float.
        'price_xaf' => 'integer',
        'requires_doctor_order' => 'boolean',
        'sample_collection_available' => 'boolean',
        'home_sample_collection_available' => 'boolean',
        'last_updated_at' => 'datetime',
        'last_published_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    /**
     * Rows a real source published. Allow-list, not blacklist: a NULL source
     * is a row nobody has claimed, and this is the query a patient runs
     * before deciding whether to travel to a laboratory.
     */
    public function scopeReportedByRealSource(Builder $query): Builder
    {
        $column = $this->getTable() . '.source_system';

        return $query->whereNotNull($column)
            ->whereNotIn($column, self::SYNTHETIC_SOURCE_SYSTEMS);
    }

    /** Still offered — a retired catalogue item is kept but marked unavailable. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.availability_status', self::STATUS_AVAILABLE);
    }

    /**
     * Laboratory tests only. Imaging exams publish through this same table but
     * belong on the imaging finder (IMAGING_DASHBOARD_AUDIT_2 I-2, DIAGNOSTIC_CENTRE
     * D-1/D-2): the dictionary tags them by LOINC reason, never a guess from the
     * title. The public lab finder and the lab manager's "published" count must
     * apply the same rule, or the desk claims more than the finder shows.
     */
    public function scopeExcludingImaging(Builder $query): Builder
    {
        $column = $this->getTable() . '.loinc_code';

        return $query->where(function (Builder $q) use ($column) {
            $q->whereNull($column)
                ->orWhereNotIn($column, \Illuminate\Support\Facades\DB::table('clinical_code_dictionary')
                    ->select('code')
                    ->where('system', 'LOINC')
                    ->where('common_reason', 'like', \App\Services\Imaging\ImagingExamSearch::IMAGING_REASON_PREFIX . '%'));
        });
    }
}
