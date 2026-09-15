<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\AuditEvent;
use App\Models\AccessLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * AuditExplorerService — Filtered audit log search for privacy/security officers.
 *
 * Every search action via this service is itself logged to AccessLog.
 * Only users with audit.view_sensitive permission may access PHI-related audit events.
 */
class AuditExplorerService
{
    /**
     * Search audit events with optional filters.
     *
     * @param  array{
     *     actor_id?: string,
     *     patient_id?: string,
     *     facility_id?: string,
     *     action_type?: string,
     *     resource_type?: string,
     *     from?: string,
     *     to?: string,
     *     per_page?: int
     * } $filters
     */
    public function search(string $requesterId, array $filters = []): LengthAwarePaginator
    {
        $this->recordSearch($requesterId);

        return $this->query($filters)->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Log the access to the audit explorer itself (compliance requirement).
     * Finder: split out so the existing /admin/security/audit page (which has
     * richer filters than this service) records its searches too.
     */
    public function recordSearch(string $requesterId): void
    {
        AccessLog::create([
            'actor_id'      => $requesterId,
            'actor_type'    => 'user',
            'purpose'       => 'security_audit',
            'data_category' => 'audit_trail',
            'resource_type' => 'audit_explorer',
            'access_type'   => 'search',
        ]);
    }

    private function query(array $filters)
    {
        $query = AuditEvent::query()->latest('created_at');

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }
        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        if (! empty($filters['facility_id'])) {
            $query->where('facility_id', $filters['facility_id']);
        }
        if (! empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }
        if (! empty($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
