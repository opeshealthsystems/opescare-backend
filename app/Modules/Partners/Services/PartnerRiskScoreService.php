<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerRiskScore;
use App\Modules\Partners\Models\PartnerGovernanceCase;
use Illuminate\Support\Str;

class PartnerRiskScoreService
{
    private PartnerAuditService $auditService;
    private PartnerApplicationService $applicationService;

    public function __construct(PartnerAuditService $auditService, PartnerApplicationService $applicationService)
    {
        $this->auditService = $auditService;
        $this->applicationService = $applicationService;
    }

    public function recordRiskEvent(Partner $partner, string $riskFactor, string $severity, int $scoreDelta, ?string $actorId = null): PartnerRiskScore
    {
        // partner_audit_logs.actor_id is a uuid column — the old 'system' string
        // default crashed Postgres. Null lets PartnerAuditService resolve the
        // authenticated user, and actor_role still records 'system' when absent.
        if ($actorId !== null && ! Str::isUuid($actorId)) {
            $actorId = null;
        }

        // 1. Fetch current or create new score record
        $riskRecord = PartnerRiskScore::firstOrCreate(
            ['partner_id' => $partner->id, 'status' => 'active'],
            ['risk_level' => 'low', 'risk_score' => 0, 'calculated_at' => now(), 'risk_factors_json' => '[]']
        );

        $oldLevel = $riskRecord->risk_level;
        $riskRecord->risk_score += $scoreDelta;
        
        $factors = json_decode($riskRecord->risk_factors_json, true) ?? [];
        $factors[] = ['factor' => $riskFactor, 'severity' => $severity, 'timestamp' => now()->toIso8601String()];
        $riskRecord->risk_factors_json = json_encode($factors);

        // 2. Evaluate new level
        if ($riskRecord->risk_score >= 80) {
            $riskRecord->risk_level = 'critical';
        } elseif ($riskRecord->risk_score >= 50) {
            $riskRecord->risk_level = 'high';
        } elseif ($riskRecord->risk_score >= 20) {
            $riskRecord->risk_level = 'moderate';
        } else {
            $riskRecord->risk_level = 'low';
        }

        $riskRecord->calculated_at = now();
        $riskRecord->save();

        if ($oldLevel !== $riskRecord->risk_level) {
            $partner->risk_level = $riskRecord->risk_level;
            $partner->save();

            $this->auditService->log(
                $partner->id,
                'partner_risk_score_changed',
                $oldLevel,
                $riskRecord->risk_level,
                "Risk event: {$riskFactor}",
                $actorId
            );

            // 3. Automated Governance Logic
            if ($riskRecord->risk_level === 'critical') {
                $this->triggerCriticalGovernance($partner, $riskFactor, $actorId);
            }
        }

        return $riskRecord;
    }

    private function triggerCriticalGovernance(Partner $partner, string $riskFactor, ?string $actorId)
    {
        // Open a governance case
        $case = PartnerGovernanceCase::create([
            'uuid' => Str::uuid(),
            'partner_id' => $partner->id,
            'case_type' => 'automated_risk_suspension',
            'severity' => 'critical',
            'description' => "Automated case opened due to critical risk score. Trigger event: {$riskFactor}",
        ]);

        $this->auditService->log($partner->id, 'partner_governance_case_created', null, $case->uuid, 'Automated risk escalation', $actorId);

        // Suspend the partner
        $this->applicationService->suspendPartner($partner, "Automated suspension due to critical risk: {$riskFactor}");
    }
}
