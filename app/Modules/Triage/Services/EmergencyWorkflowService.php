<?php

namespace App\Modules\Triage\Services;

use App\Models\EmergencyCase;
use App\Models\EmergencyEscalation;
use App\Models\TriageRecord;
use App\Models\VisitTimeline;

/**
 * EmergencyWorkflowService — Module 16 (Triage & Emergency Workflow)
 *
 * Manages emergency case escalation and resolution workflows.
 *
 * CDSS Safety: Emergency prioritization assists triage staff.
 * It does NOT replace clinical judgment or autonomous decision-making.
 */
class EmergencyWorkflowService
{
    /**
     * Open a new emergency case linked to a triage assessment.
     */
    public function openCase(array $data): EmergencyCase
    {
        $case = EmergencyCase::create($data);

        if (isset($data['visit_id'])) {
            VisitTimeline::record($data['visit_id'], 'emergency_case_opened', [
                'emergency_case_id' => $case->id,
                'severity'          => $case->severity,
            ]);
        }

        return $case;
    }

    /**
     * Escalate an emergency case with an escalation record.
     */
    public function escalate(EmergencyCase $case, array $escalationData): EmergencyEscalation
    {
        $escalation = EmergencyEscalation::create(array_merge($escalationData, [
            'emergency_case_id' => $case->id,
        ]));

        if ($case->visit_id) {
            VisitTimeline::record($case->visit_id, 'emergency_escalated', [
                'emergency_case_id' => $case->id,
                'escalation_type'   => $escalation->escalation_type,
            ]);
        }

        return $escalation;
    }

    /**
     * Stabilize an active emergency case.
     */
    public function stabilize(EmergencyCase $case, string $actorId): void
    {
        $case->stabilize();

        if ($case->visit_id) {
            VisitTimeline::record($case->visit_id, 'emergency_stabilized', [
                'emergency_case_id' => $case->id,
                'actor_id'          => $actorId,
            ]);
        }
    }

    /**
     * Resolve a stabilized emergency case.
     */
    public function resolve(EmergencyCase $case, string $actorId, ?string $notes = null): void
    {
        $case->resolve();

        if ($case->visit_id) {
            VisitTimeline::record($case->visit_id, 'emergency_resolved', [
                'emergency_case_id' => $case->id,
                'actor_id'          => $actorId,
                'notes'             => $notes,
            ]);
        }
    }
}
