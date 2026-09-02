<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerAgreement;
use App\Modules\Partners\Models\PartnerContributionPermission;
use App\Modules\Partners\Models\PartnerAccessPermission;
use Illuminate\Support\Str;

class PartnerDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Approved Hospital Partner
        $hospital = Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'healthcare_facility',
            'legal_name' => 'Demo General Hospital',
            'trade_name' => 'DGH',
            'country_code' => 'CM',
            'status' => 'active',
            'trust_level' => 'level_4_clinical_trusted',
            'risk_level' => 'low',
        ]);

        PartnerAgreement::create([
            'partner_id' => $hospital->id,
            'agreement_type' => 'clinical_contribution_agreement',
            'status' => 'active',
            'effective_from' => now()->subYear(),
            'expires_at' => now()->addYear(),
        ]);

        PartnerContributionPermission::create([
            'partner_id' => $hospital->id,
            'contribution_type' => 'encounters.create',
            'effective_from' => now()->subYear(),
            'status' => 'active',
        ]);

        PartnerAccessPermission::create([
            'partner_id' => $hospital->id,
            'access_type' => 'patient_summary.read',
            'effective_from' => now()->subYear(),
            'status' => 'active',
        ]);

        // 2. Pending Insurance Partner
        Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'insurance_and_payment',
            'legal_name' => 'Global Health Insurance',
            'trade_name' => 'GHI',
            'country_code' => 'CM',
            'status' => 'submitted',
            'trust_level' => 'level_0_unverified',
            'risk_level' => 'low',
        ]);

        // 3. Suspended Tech Vendor
        $tech = Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'technology_and_interoperability',
            'legal_name' => 'BadActor Tech Ltd',
            'trade_name' => 'BadTech',
            'country_code' => 'CM',
            'status' => 'suspended',
            'trust_level' => 'level_0_unverified',
            'risk_level' => 'critical',
        ]);

        PartnerAgreement::create([
            'partner_id' => $tech->id,
            'agreement_type' => 'api_integration_agreement',
            'status' => 'suspended',
            'effective_from' => now()->subYear(),
            'expires_at' => now()->addYear(),
        ]);
    }
}
