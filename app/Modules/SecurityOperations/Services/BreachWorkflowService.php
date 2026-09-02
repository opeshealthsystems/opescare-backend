<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\BreachReport;
use App\Models\AuditEvent;

/**
 * BreachWorkflowService — Manages data breach lifecycle.
 *
 * GDPR Article 33 requires notification to supervisory authority within 72 hours
 * of becoming aware of a breach. This service tracks that window automatically.
 *
 * Flow:
 *  1. Security officer opens breach report
 *  2. System starts 72-hour regulatory notification clock
 *  3. Containment steps are recorded
 *  4. Affected subjects are identified
 *  5. Regulatory notification is submitted and logged
 *  6. Breach is closed with resolution notes
 */
class BreachWorkflowService
{
    public function openBreach(array $data, string $reportedBy): BreachReport
    {
        // Derive a title from description if not provided
        if (empty($data['title']) && ! empty($data['description'])) {
            $data['title'] = substr($data['description'], 0, 100);
        }

        $breach = BreachReport::create(array_merge($data, [
            'reported_by'   => $reportedBy,
            'status'        => 'open',
            'discovered_at' => $data['discovered_at'] ?? now(),
        ]));

        AuditEvent::create([
            'actor_id'      => $reportedBy,
            'action_type'   => 'create',
            'resource_type' => 'breach_report',
            'resource_id'   => $breach->id,
            'reason'        => 'Breach report opened: ' . $breach->title,
        ]);

        return $breach;
    }

    public function contain(string $breachId, string $actorId, string $notes): BreachReport
    {
        $breach = BreachReport::findOrFail($breachId);
        $breach->contain($actorId);

        AuditEvent::create([
            'actor_id'      => $actorId,
            'action_type'   => 'update',
            'resource_type' => 'breach_report',
            'resource_id'   => $breachId,
            'reason'        => 'Breach contained. Notes: ' . $notes,
        ]);

        return $breach->fresh();
    }

    public function markNotified(string $breachId, string $actorId): BreachReport
    {
        $breach = BreachReport::findOrFail($breachId);
        $breach->markNotified();

        AuditEvent::create([
            'actor_id'      => $actorId,
            'action_type'   => 'update',
            'resource_type' => 'breach_report',
            'resource_id'   => $breachId,
            'reason'        => 'Regulatory notification submitted for breach ' . $breachId,
        ]);

        return $breach->fresh();
    }

    public function closeBreach(string $breachId, string $actorId, string $resolution): BreachReport
    {
        $breach = BreachReport::findOrFail($breachId);
        $breach->close();

        AuditEvent::create([
            'actor_id'      => $actorId,
            'action_type'   => 'update',
            'resource_type' => 'breach_report',
            'resource_id'   => $breachId,
            'reason'        => 'Breach closed. Resolution: ' . $resolution,
        ]);

        return $breach->fresh();
    }

    /**
     * Returns open breaches where the 48-hour warning threshold has passed
     * with no regulatory notification yet sent. At 72 hours, notification is overdue.
     */
    public function getBreachesRequiringRegulatoryAction(): \Illuminate\Database\Eloquent\Collection
    {
        return BreachReport::whereIn('status', ['open', 'investigating'])
            ->where('discovered_at', '<=', now()->subHours(48))
            ->whereNull('reported_to_authority_at')
            ->get();
    }
}
