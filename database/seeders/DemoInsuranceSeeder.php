<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo insurance providers, plans, patient policies, and claims.
 * Idempotent – safe to run multiple times.
 */
class DemoInsuranceSeeder extends Seeder
{
    private const FAC  = '00000000-0000-0000-0000-100000000001';
    private const PAT1 = '00000000-0000-0000-0000-300000000001';
    private const PAT3 = '00000000-0000-0000-0000-300000000003';

    // Stable UUIDs
    private const PROV1  = '00000000-0000-0000-0013-100000000001';
    private const PROV2  = '00000000-0000-0000-0013-100000000002';
    private const PLAN1  = '00000000-0000-0000-0014-100000000001';
    private const PLAN2  = '00000000-0000-0000-0014-100000000002';
    private const PLAN3  = '00000000-0000-0000-0014-100000000003';
    private const POL1   = '00000000-0000-0000-0015-100000000001';
    private const POL2   = '00000000-0000-0000-0015-100000000002';
    private const CLAIM1 = '00000000-0000-0000-0016-100000000001';
    private const CLAIM2 = '00000000-0000-0000-0016-100000000002';
    private const CLAIM3 = '00000000-0000-0000-0016-100000000003';

    public function run(): void
    {
        // ── Insurance providers ───────────────────────────────────────
        if (DB::table('insurance_providers')->where('id', self::PROV1)->doesntExist()) {
            DB::table('insurance_providers')->insert([
                'id' => self::PROV1, 'name' => 'National Health Insurance Fund (NHIF)',
                'code' => 'NHIF-DEMO', 'country_code' => 'CM',
                'contact_email' => 'claims@nhif.demo', 'contact_phone' => '+237 222 000 001',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (DB::table('insurance_providers')->where('id', self::PROV2)->doesntExist()) {
            DB::table('insurance_providers')->insert([
                'id' => self::PROV2, 'name' => 'Saham Assurance West Africa',
                'code' => 'SAHAM-DEMO', 'country_code' => 'CM',
                'contact_email' => 'health@saham.demo', 'contact_phone' => '+237 233 000 002',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ── Insurance plans ───────────────────────────────────────────
        if (DB::table('insurance_plans')->where('id', self::PLAN1)->doesntExist()) {
            DB::table('insurance_plans')->insert([
                'id' => self::PLAN1, 'insurance_provider_id' => self::PROV1,
                'name' => 'NHIF Standard Cover', 'plan_code' => 'NHIF-STD',
                'plan_type' => 'nhia', 'requires_preauthorization' => false,
                'cashless_available' => true, 'copay_percentage' => 20.00,
                'covered_services' => json_encode(['outpatient', 'inpatient', 'pharmacy', 'lab']),
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (DB::table('insurance_plans')->where('id', self::PLAN2)->doesntExist()) {
            DB::table('insurance_plans')->insert([
                'id' => self::PLAN2, 'insurance_provider_id' => self::PROV2,
                'name' => 'Saham Gold Private Plan', 'plan_code' => 'SAHAM-GOLD',
                'plan_type' => 'private', 'requires_preauthorization' => true,
                'cashless_available' => true, 'copay_percentage' => 10.00,
                'covered_services' => json_encode(['outpatient', 'inpatient', 'specialist', 'pharmacy', 'lab', 'imaging']),
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (DB::table('insurance_plans')->where('id', self::PLAN3)->doesntExist()) {
            DB::table('insurance_plans')->insert([
                'id' => self::PLAN3, 'insurance_provider_id' => self::PROV1,
                'name' => 'NHIF Employee Group Plan', 'plan_code' => 'NHIF-EMP',
                'plan_type' => 'employer', 'requires_preauthorization' => false,
                'cashless_available' => true, 'copay_percentage' => 15.00,
                'covered_services' => json_encode(['outpatient', 'inpatient', 'pharmacy']),
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ── Patient insurance policies ─────────────────────────────────
        if (DB::table('patient_insurance_policies')->where('id', self::POL1)->doesntExist()) {
            DB::table('patient_insurance_policies')->insert([
                'id' => self::POL1, 'patient_id' => self::PAT1,
                'insurance_plan_id' => self::PLAN1,
                'policy_number' => 'NHIF-0000001-PAT',
                'member_id' => 'MBR-00001', 'group_number' => 'GRP-001',
                'relationship_to_primary' => 'self',
                'effective_date' => now()->subYear()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
                'status' => 'active',
                'verified_at' => now()->subMonth(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (DB::table('patient_insurance_policies')->where('id', self::POL2)->doesntExist()) {
            DB::table('patient_insurance_policies')->insert([
                'id' => self::POL2, 'patient_id' => self::PAT3,
                'insurance_plan_id' => self::PLAN2,
                'policy_number' => 'SAHAM-GOLD-0000003',
                'member_id' => 'MBR-00003', 'group_number' => null,
                'relationship_to_primary' => 'self',
                'effective_date' => now()->subMonths(6)->toDateString(),
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'status' => 'active',
                'verified_at' => now()->subWeek(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ── Insurance claims ──────────────────────────────────────────
        $this->upsertClaim(self::CLAIM1, self::POL1, '00000000-0000-0000-0009-100000000001',
            'CLM-DEMO-0001', 'approved', 45000, 36000, 36000,
            now()->subDays(2), now()->subDay());
        $this->upsertClaim(self::CLAIM2, self::POL2, '00000000-0000-0000-0009-100000000002',
            'CLM-DEMO-0002', 'submitted', 350000, null, null,
            now()->subHours(6), null);
        $this->upsertClaim(self::CLAIM3, self::POL1, '00000000-0000-0000-0009-100000000003',
            'CLM-DEMO-0003', 'under_review', 15000, null, null,
            now()->subHours(2), null);
    }

    private function upsertClaim(string $id, string $policyId, string $invoiceId, string $number,
        string $status, float $claimed, ?float $approved, ?float $paid,
        ?\Carbon\Carbon $submittedAt, ?\Carbon\Carbon $decidedAt): void
    {
        if (DB::table('insurance_claims')->where('id', $id)->doesntExist()) {
            DB::table('insurance_claims')->insert([
                'id' => $id, 'patient_insurance_policy_id' => $policyId,
                'invoice_id' => $invoiceId, 'facility_id' => self::FAC,
                'claim_number' => $number, 'status' => $status,
                'claimed_amount' => $claimed, 'approved_amount' => $approved,
                'paid_amount' => $paid, 'submitted_at' => $submittedAt,
                'decided_at' => $decidedAt,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
