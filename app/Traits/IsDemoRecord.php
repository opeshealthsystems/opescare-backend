<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait IsDemoRecord
{
    /**
     * Boot the trait and add the global scope.
     */
    protected static function bootIsDemoRecord()
    {
        static::creating(function ($model) {
            if (!isset($model->is_demo)) {
                $model->is_demo = (bool) config('demo.enabled', false);
            }
        });

        static::addGlobalScope('isolate_demo', function (Builder $builder) {
            // If the application is NOT running in demo mode, explicitly hide demo data.
            // If the application IS running in demo mode, optionally we can hide real data
            // but the PRD says "is_demo=true enforced by global scopes" and "Production and demo data must never appear in the same query result."
            
            $isDemoMode = config('demo.enabled');

            if ($isDemoMode) {
                // Only show demo data
                $builder->where($builder->getModel()->getTable() . '.is_demo', true);
            } else {
                // Only show real data
                $builder->where($builder->getModel()->getTable() . '.is_demo', false);
            }
        });
    }

    /**
     * Scope to bypass demo isolation if absolutely needed by internal admin.
     */
    public function scopeWithoutDemoIsolation(Builder $query)
    {
        return $query->withoutGlobalScope('isolate_demo');
    }
}
