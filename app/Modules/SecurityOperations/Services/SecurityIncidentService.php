<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\SecurityIncident;
use App\Models\AuditEvent;

/**
 * SecurityIncidentService — Manages security incident lifecycle.
 *
 * Covers: unauthorized access, credential compromise, malware, data exfiltration,
 * API abuse, bridge agent compromise, and device theft.
 *
 * Incidents are separate from clinical IncidentReports (patient safety).
 * This service handles IT/security incidents only.
 */
class SecurityIncidentService
{
    public function openIncident(array $data, string $reportedBy): SecurityIncident
    {
        // `summary` is required by the schema; derive from description if not provided
        if (empty($data['summary']) && ! empty($data['description'])) {
            $data['summary'] = $data['description'];
        }

        $incident = SecurityIncident::create(array_merge($data, [
            'created_by' => $reportedBy,
            'status'     => 'new',
        ]));

        AuditEvent::create([
            'actor_id'      => $reportedBy,
            'action_type'   => 'create',
            'resource_type' => 'security_incident',
            'resource_id'   => $incident->id,
            'reason'        => sprintf(
                'Security incident opened. Type: %s, Severity: %s',
                $incident->incident_type ?? 'unknown',
                $incident->severity ?? 'unknown'
            ),
        ]);

        return $incident;
    }

    public function escalate(string $incidentId, string $actorId, string $notes): SecurityIncident
    {
        $incident = SecurityIncident::findOrFail($incidentId);
        $incident->update(['status' => 'triaging']);

        AuditEvent::create([
            'actor_id'      => $actorId,
            'action_type'   => 'update',
            'resource_type' => 'security_incident',
            'resource_id'   => $incidentId,
            'reason'        => 'Incident escalated. Notes: ' . $notes,
        ]);

        return $incident->fresh();
    }

    public function resolve(string $incidentId, string $actorId, string $resolution): SecurityIncident
    {
        $incident = SecurityIncident::findOrFail($incidentId);
        $incident->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        AuditEvent::create([
            'actor_id'      => $actorId,
            'action_type'   => 'update',
            'resource_type' => 'security_incident',
            'resource_id'   => $incidentId,
            'reason'        => 'Incident resolved. Resolution: ' . $resolution,
        ]);

        return $incident->fresh();
    }

    public function getOpenIncidents(): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityIncident::whereIn('status', ['new', 'triaging'])
            ->orderByDesc('created_at')
            ->get();
    }
}
