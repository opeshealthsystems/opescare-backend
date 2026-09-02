<?php

namespace App\Services\Portal;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PortalContextService
 *
 * Single source of truth for portal request context:
 *   - Who is acting (actorId)
 *   - Which facility is active (facilityId)
 *   - Whether the session is a demo session (isDemo)
 *   - Helper for facility-scoped queries (scopeToFacility)
 *   - Helper for demo-scoped queries (scopeToDemo)
 *   - AuditEvent emission for patient data access
 */
class PortalContextService
{
    /**
     * The authenticated user's UUID (actor for audit events and write operations).
     */
    public function actorId(): ?string
    {
        return Auth::id(); // Returns null if unauthenticated — callers handle null via audit schema
    }

    /**
     * The active facility ID for this request.
     *
     * Resolution order:
     *   1. session('active_facility_id')   — explicitly selected by multi-facility user
     *   2. auth()->user()->primary_facility_id — single-facility default
     *   3. null                            — no facility context (admin/platform roles)
     */
    public function facilityId(): ?string
    {
        return session('active_facility_id')
            ?? Auth::user()?->primary_facility_id;
    }

    /**
     * Whether the current authenticated user is running in a demo session.
     */
    public function isDemo(): bool
    {
        return (bool) (Auth::user()?->is_demo ?? false);
    }

    /**
     * Scope an Eloquent query to the active facility.
     *
     * If no facility context is resolved (platform-level role), the query
     * is returned unscoped so platform admins see all facilities.
     *
     * @param  Builder  $query
     * @param  string   $column  FK column name on the model's table
     * @return Builder
     */
    public function scopeToFacility(Builder $query, string $column = 'facility_id'): Builder
    {
        $facilityId = $this->facilityId();

        if ($facilityId) {
            $table = $query->getModel()->getTable();
            return $query->where("{$table}.{$column}", $facilityId);
        }

        return $query;
    }

    /**
     * Scope an Eloquent query to demo or real records based on the user's demo status.
     *
     * Models using the IsDemoRecord global scope already handle this automatically when
     * config('demo.enabled') is set. Call this method for models that don't use the trait,
     * or to apply an explicit per-query scope regardless of the global config.
     *
     * @param  Builder  $query
     * @param  string   $column  is_demo column name (default: 'is_demo')
     * @return Builder
     */
    public function scopeToDemo(Builder $query, string $column = 'is_demo'): Builder
    {
        $table = $query->getModel()->getTable();
        return $query->where("{$table}.{$column}", $this->isDemo());
    }

    /**
     * Emit an AuditEvent for patient data access.
     *
     * Failures are caught and logged silently — audit errors must NEVER break portals.
     *
     * @param  string       $actionType     e.g. 'patient_record_view', 'access_log_view'
     * @param  string       $resourceType   e.g. 'Patient', 'Appointment'
     * @param  string|null  $resourceId     UUID of the specific record accessed
     * @param  string|null  $patientId      Patient UUID (if applicable)
     * @param  array        $extra          Additional fields to merge into the event
     */
    public function auditPatientAccess(
        string $actionType,
        string $resourceType,
        ?string $resourceId = null,
        ?string $patientId = null,
        array $extra = []
    ): void {
        try {
            AuditEvent::create(array_merge([
                'actor_id'          => $this->actorId(),
                'actor_role'        => Auth::user()?->role?->name,
                'facility_id'       => $this->facilityId(),
                'patient_id'        => $patientId,
                'action_type'       => $actionType,
                'resource_type'     => $resourceType,
                'resource_id'       => $resourceId,
                'source_system'     => 'portal',
                'ip_address'        => request()->ip(),
                'emergency_override'=> false,
                'created_at'        => now(),
            ], $extra));
        } catch (\Throwable $e) {
            Log::error('audit_event_failed', [
                'action'   => $actionType,
                'resource' => $resourceType,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
