<?php

namespace Tests\Feature\Partners;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerAgreement;
use App\Modules\Partners\Models\PartnerContributionPermission;
use App\Modules\Partners\Services\PartnerPermissionService;
use Illuminate\Support\Str;

class PartnerAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Partner $activePartner;
    private Partner $suspendedPartner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->activePartner = Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'healthcare_facility',
            'legal_name' => 'Active Hospital',
            'status' => 'active',
            'trust_level' => 'level_4_clinical_trusted',
        ]);

        PartnerAgreement::create([
            'partner_id' => $this->activePartner->id,
            'agreement_type' => 'clinical_contribution_agreement',
            'status' => 'active',
            'effective_from' => now()->subDay(),
        ]);

        PartnerContributionPermission::create([
            'partner_id' => $this->activePartner->id,
            'contribution_type' => 'encounters.create',
            'effective_from' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->suspendedPartner = Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'healthcare_facility',
            'legal_name' => 'Suspended Hospital',
            'status' => 'suspended',
            'trust_level' => 'level_0_unverified',
        ]);
    }

    public function test_unverified_partner_is_blocked_from_api()
    {
        // No header provided
        $response = $this->getJson('/api/partner-governance/test-access');
        $response->assertStatus(403)
                 ->assertJsonPath('error_code', 'PARTNER_NOT_VERIFIED');
    }

    public function test_suspended_partner_is_blocked_from_api()
    {
        $response = $this->getJson('/api/partner-governance/test-access', [
            'X-Partner-ID' => $this->suspendedPartner->uuid
        ]);
        $response->assertStatus(403)
                 ->assertJsonPath('error_code', 'PARTNER_SUSPENDED');
    }

    public function test_active_partner_can_access_api()
    {
        $response = $this->getJson('/api/partner-governance/test-access', [
            'X-Partner-ID' => $this->activePartner->uuid
        ]);
        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    public function test_partner_service_blocks_contribution_without_agreement()
    {
        $service = new PartnerPermissionService();
        
        // Active partner has agreement and permission for encounters.create
        $this->assertTrue($service->canContribute($this->activePartner, 'encounters.create'));

        // Does not have permission for lab_results.create
        $this->assertFalse($service->canContribute($this->activePartner, 'lab_results.create'));

        // Suspended partner cannot contribute
        $this->assertFalse($service->canContribute($this->suspendedPartner, 'encounters.create'));
    }
}
