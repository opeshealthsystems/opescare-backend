<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Opt-in trait for facility-level tenant isolation.
 *
 * Usage: add `use HasFacilityScope;` to any model that has a facility_id column.
 * Then call: Model::forFacility($facilityId)->get()
 *            Model::forCurrentFacility()->get()
 *
 * Deliberately NOT a global scope — existing cross-facility queries
 * (super-admin dashboards, analytics) continue to work unmodified.
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

        return $query;
    }
}
