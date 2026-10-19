<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Opt-in trait for facility-level tenant isolation.
 *
 * Usage: add `use HasFacilityScope;` to any model that has a facility_id column.
 *   Model::forFacility($facilityId)->get()   explicit facility
 *   Model::forCurrentFacility()->get()       facility bound as `current_facility_id`
 *   Model::acrossFacilities()->get()         deliberate cross-facility read
 *
 * forCurrentFacility() FAILS CLOSED: with no bound facility it returns an empty
 * result (never all facilities) and logs one warning per model per request, so
 * a missed binding shows up in the logs instead of leaking another tenant's rows.
 * Legitimate cross-facility callers (platform admin reports, console commands,
 * projectors, public finders over published projections) must call
 * acrossFacilities() on purpose, with a comment saying why.
 *
 * Deliberately NOT a global scope.
 */
trait HasFacilityScope
{
    public function scopeForFacility(Builder $query, string $facilityId): Builder
    {
        return $query->where($this->getTable() . '.facility_id', $facilityId);
    }

    public function scopeForCurrentFacility(Builder $query): Builder
    {
        $facilityId = app()->bound('current_facility_id')
            ? app('current_facility_id')
            : null;

        if ($facilityId) {
            return $query->where($this->getTable() . '.facility_id', $facilityId);
        }

        $this->warnUnboundFacilityScope();

        return $query->whereRaw('1 = 0');
    }

    /**
     * Explicit escape hatch: no facility constraint. Call only where reading
     * across facilities is the point, and say why at the call site.
     */
    public function scopeAcrossFacilities(Builder $query): Builder
    {
        return $query;
    }

    private function warnUnboundFacilityScope(): void
    {
        // Container-held so it resets every request (and every test).
        $key    = 'facility_scope.unbound_warned';
        $warned = app()->bound($key) ? app($key) : [];
        $model  = static::class;

        if (isset($warned[$model])) {
            return;
        }

        $warned[$model] = true;
        app()->instance($key, $warned);

        Log::channel('single')->warning('forCurrentFacility() called with no current_facility_id bound; returning empty result', [
            'model' => $model,
        ]);
    }
}
