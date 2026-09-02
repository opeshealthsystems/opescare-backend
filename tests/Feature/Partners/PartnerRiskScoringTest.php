<?php

namespace Tests\Feature\Partners;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Services\PartnerRiskScoreService;
use App\Modules\Partners\Enums\TrustLevel;
use Illuminate\Support\Str;

class PartnerRiskScoringTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->partner = Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'healthcare_facility',
            'legal_name' => 'Risky Hospital',
            'status' => 'active',
            'trust_level' => TrustLevel::LEVEL_4_CLINICAL_TRUSTED->value,
        ]);
    }

    public function test_critical_risk_automatically_suspends_partner()
    {
        $service = app(PartnerRiskScoreService::class);

        // Add 85 points of risk (critical threshold is 80)
        $service->recordRiskEvent($this->partner, 'Multiple unauthorized access attempts', 'high', 85, (string) Str::uuid());

        $this->partner->refresh();

        // Check the score level
        $this->assertEquals('critical', $this->partner->risk_level);
        
        // Check automated suspension
        $this->assertEquals('suspended', $this->partner->status);

        // Check governance case creation
        $this->assertDatabaseHas('partner_governance_cases', [
            'partner_id' => $this->partner->id,
            'severity' => 'critical',
            'case_type' => 'automated_risk_suspension'
        ]);

        // Check audits
        $this->assertDatabaseHas('partner_audit_logs', [
            'partner_id' => $this->partner->id,
            'action' => 'partner_governance_case_created'
        ]);
    }
}
