<?php

namespace App\Modules\Triage\Services;

use App\Models\TriageScore;
use App\Models\TriageRecord;

/**
 * TriageScoringService — Module 16 (Triage & Emergency Workflow)
 *
 * Computes triage priority scores using configured scoring systems.
 *
 * CDSS Safety Rule (NON-NEGOTIABLE):
 * Computed scores are SUGGESTIONS for clinical review. They do NOT
 * replace nurse/clinician judgment. The clinician confirms final priority.
 * "Do not use AI for diagnosis or autonomous clinical decisions."
 */
class TriageScoringService
{
    /**
     * Supported scoring systems and their priority mappings.
     */
    private const PRIORITY_LEVELS = [
        'P1_immediate',
        'P2_urgent',
        'P3_less_urgent',
        'P4_standard',
        'P5_non_urgent',
    ];

    /**
     * Score a triage record using the Manchester Triage System (MTS).
     * Returns a TriageScore. The nurse must confirm the result.
     */
    public function scoreManchesterTriage(
        TriageRecord $triageRecord,
        array $componentData,
        string $computedBy = 'system'
    ): TriageScore {
        // Simplified MTS: uses chief complaint discriminator groups
        // Full MTS flowcharts would be referenced in production
        $numericScore = $this->calculateMtsScore($componentData);
        $priorityLevel = $this->mapMtsScoreToPriority($numericScore);

        return TriageScore::create([
            'triage_assessment_id' => $triageRecord->id,
            'visit_id'             => $triageRecord->visit_id,
            'scoring_system'       => 'manchester',
            'priority_level'       => $priorityLevel,
            'numeric_score'        => $numericScore,
            'component_scores'     => $componentData,
            'computed_by'          => $computedBy,
            'scored_at'            => now(),
        ]);
    }

    /**
     * Score using Emergency Severity Index (ESI) system.
     */
    public function scoreEsi(
        TriageRecord $triageRecord,
        array $componentData,
        string $computedBy = 'system'
    ): TriageScore {
        $level = $componentData['esi_level'] ?? 3; // 1=most urgent, 5=least urgent
        $priorityLevel = $this->mapEsiLevelToPriority($level);

        return TriageScore::create([
            'triage_assessment_id' => $triageRecord->id,
            'visit_id'             => $triageRecord->visit_id,
            'scoring_system'       => 'esi',
            'priority_level'       => $priorityLevel,
            'numeric_score'        => (float) $level,
            'component_scores'     => $componentData,
            'computed_by'          => $computedBy,
            'scored_at'            => now(),
        ]);
    }

    /**
     * Create a manual triage score assigned by the clinician.
     */
    public function scoreManual(
        TriageRecord $triageRecord,
        string $priorityLevel,
        string $clinicianId,
        ?array $componentData = null
    ): TriageScore {
        return TriageScore::create([
            'triage_assessment_id' => $triageRecord->id,
            'visit_id'             => $triageRecord->visit_id,
            'scoring_system'       => 'manual',
            'priority_level'       => $priorityLevel,
            'numeric_score'        => null,
            'component_scores'     => $componentData,
            'computed_by'          => $clinicianId,
            'scored_at'            => now(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function calculateMtsScore(array $componentData): float
    {
        // Simplified: in production would implement full MTS discriminators
        $vitalDerangement = $componentData['vital_signs_deranged'] ?? false;
        $consciousnessAltered = $componentData['consciousness_altered'] ?? false;
        $painScore = $componentData['pain_score'] ?? 0;

        if ($vitalDerangement || $consciousnessAltered) {
            return 1.0;
        }
        if ($painScore >= 8) {
            return 2.0;
        }
        if ($painScore >= 5) {
            return 3.0;
        }
        if ($painScore >= 2) {
            return 4.0;
        }
        return 5.0;
    }

    private function mapMtsScoreToPriority(float $score): string
    {
        return match(true) {
            $score <= 1.5 => 'P1_immediate',
            $score <= 2.5 => 'P2_urgent',
            $score <= 3.5 => 'P3_less_urgent',
            $score <= 4.5 => 'P4_standard',
            default       => 'P5_non_urgent',
        };
    }

    private function mapEsiLevelToPriority(int $level): string
    {
        return match($level) {
            1 => 'P1_immediate',
            2 => 'P2_urgent',
            3 => 'P3_less_urgent',
            4 => 'P4_standard',
            5 => 'P5_non_urgent',
            default => 'P4_standard',
        };
    }
}
