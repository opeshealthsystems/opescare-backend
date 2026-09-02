<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds 7 production (non-demo) accounts for the core OpesCare account types.
 *
 * Idempotent — safe to run multiple times via firstOrCreate / insertOrIgnore.
 *
 * Accounts created:
 *   1. Hospital Account          — hospital.admin@opescare.com
 *   2. Diagnostic Center Account — diagnostics.admin@opescare.com
 *   3. Insurance Company Account — insurance.admin@opescare.com
 *   4. Pharmacy Account          — pharmacy.admin@opescare.com
 *   5. Health Organization Acct  — healthorg.admin@opescare.com
 *   6. Developer Account         — developer.admin@opescare.com
 *   7. Clinic Account            — clinic.admin@opescare.com
 */
class ProductionAccountsSeeder extends Seeder
{
    // ── Stable UUIDs ──────────────────────────────────────────────────────────
    // Users:     00000000-0000-0000-0000-9000000000XX
    // Facilities:00000000-0000-0000-0000-8000000000XX
    // Dev acct:  00000000-0000-0000-0000-7000000000XX

    private const ACCOUNTS = [
        [
            'label'       => 'Hospital Account',
            'user_id'     => '00000000-0000-0000-0000-900000000001',
            'facility_id' => '00000000-0000-0000-0000-800000000001',
            'name'        => 'OpesCare Hospital Admin',
            'email'       => 'hospital.admin@opescare.com',
            'password'    => 'H0sp1t@lOC#2026',
            'role'        => 'hospital_admin',
            'facility'    => ['name' => 'OpesCare General Hospital', 'type' => 'hospital'],
        ],
        [
            'label'       => 'Diagnostic Center Account',
            'user_id'     => '00000000-0000-0000-0000-900000000002',
            'facility_id' => '00000000-0000-0000-0000-800000000002',
            'name'        => 'OpesCare Diagnostics Admin',
            'email'       => 'diagnostics.admin@opescare.com',
            'password'    => 'D1@gn0st!csOC#2026',
            'role'        => 'lab_manager',
            'facility'    => ['name' => 'OpesCare Diagnostic Centre', 'type' => 'laboratory'],
        ],
        [
            'label'       => 'Insurance Company Account',
            'user_id'     => '00000000-0000-0000-0000-900000000003',
            'facility_id' => '00000000-0000-0000-0000-800000000003',
            'name'        => 'OpesCare Insurance Admin',
            'email'       => 'insurance.admin@opescare.com',
            'password'    => 'Insur@nc3OC#2026',
            'role'        => 'insurance_admin',
            'facility'    => ['name' => 'OpesCare Insurance Company', 'type' => 'insurance'],
        ],
        [
            'label'       => 'Pharmacy Account',
            'user_id'     => '00000000-0000-0000-0000-900000000004',
            'facility_id' => '00000000-0000-0000-0000-800000000004',
            'name'        => 'OpesCare Pharmacy Admin',
            'email'       => 'pharmacy.admin@opescare.com',
            'password'    => 'Ph@rm@cyOC#2026',
            'role'        => 'pharmacy_manager',
            'facility'    => ['name' => 'OpesCare Pharmacy', 'type' => 'pharmacy'],
        ],
        [
            'label'       => 'Health Organization Account',
            'user_id'     => '00000000-0000-0000-0000-900000000005',
            'facility_id' => '00000000-0000-0000-0000-800000000005',
            'name'        => 'OpesCare Health Org Admin',
            'email'       => 'healthorg.admin@opescare.com',
            'password'    => 'H3@lthOrgOC#2026',
            'role'        => 'ngo_admin',
            'facility'    => ['name' => 'OpesCare Health Organization', 'type' => 'health_organization'],
        ],
        [
            'label'       => 'Developer Account',
            'user_id'     => '00000000-0000-0000-0000-900000000006',
            'facility_id' => null,
            'name'        => 'OpesCare Developer Admin',
            'email'       => 'developer.admin@opescare.com',
            'password'    => 'D3v3l0p3rOC#2026',
            'role'        => 'developer_org_admin',
            'facility'    => null,
        ],
        [
            'label'       => 'Clinic Account',
            'user_id'     => '00000000-0000-0000-0000-900000000007',
            'facility_id' => '00000000-0000-0000-0000-800000000007',
            'name'        => 'OpesCare Clinic Admin',
            'email'       => 'clinic.admin@opescare.com',
            'password'    => 'Cl!n!cAdmOC#2026',
            'role'        => 'clinic_admin',
            'facility'    => ['name' => 'OpesCare Medical Clinic', 'type' => 'clinic'],
        ],
    ];

    public function run(): void
    {
        $roleMap = DB::table('roles')->pluck('id', 'name');

        foreach (self::ACCOUNTS as $account) {
            // 1. Create facility (if applicable)
            if ($account['facility'] && $account['facility_id']) {
                $facilityExists = DB::table('facilities')
                    ->where('id', $account['facility_id'])
                    ->exists();

                if (!$facilityExists) {
                    DB::table('facilities')->insert([
                        'id'         => $account['facility_id'],
                        'name'       => $account['facility']['name'],
                        'type'       => $account['facility']['type'],
                        'status'     => 'active',
                        'is_demo'    => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 2. Create user
            $userExists = DB::table('users')
                ->where('id', $account['user_id'])
                ->orWhere('email', $account['email'])
                ->exists();

            if (!$userExists) {
                $roleId = $roleMap[$account['role']] ?? null;

                DB::table('users')->insert([
                    'id'                  => $account['user_id'],
                    'name'                => $account['name'],
                    'email'               => $account['email'],
                    'password'            => Hash::make($account['password']),
                    'role_id'             => $roleId,
                    'primary_facility_id' => $account['facility_id'],
                    'status'              => 'active',
                    'is_demo'             => false,
                    'email_verified_at'   => now(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            // 3. Create facility role assignment (if facility exists)
            if ($account['facility_id']) {
                $roleId = $roleMap[$account['role']] ?? null;

                if ($roleId) {
                    $assignmentExists = DB::table('facility_role_assignments')
                        ->where('user_id', $account['user_id'])
                        ->where('facility_id', $account['facility_id'])
                        ->exists();

                    if (!$assignmentExists) {
                        DB::table('facility_role_assignments')->insert([
                            'id'          => Str::uuid()->toString(),
                            'user_id'     => $account['user_id'],
                            'facility_id' => $account['facility_id'],
                            'role_id'     => $roleId,
                            'is_active'   => true,
                            'assigned_by' => null,
                            'assigned_at' => now(),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }

            // 4. Developer account — extra developer_accounts row
            if ($account['role'] === 'developer_org_admin') {
                $this->seedDeveloperAccount($account['user_id'], $account['email']);
            }

            $this->command->info("✔ {$account['label']} — {$account['email']}");
        }
    }

    private function seedDeveloperAccount(string $userId, string $email): void
    {
        if (!DB::getSchemaBuilder()->hasTable('developer_accounts')) {
            return;
        }

        $devAccountId = '00000000-0000-0000-0000-700000000001';

        if (DB::table('developer_accounts')->where('id', $devAccountId)->exists()) {
            return;
        }

        DB::table('developer_accounts')->insert([
            'id'                       => $devAccountId,
            'user_id'                  => $userId,
            'display_name'             => 'OpesCare Developer Admin',
            'email'                    => $email,
            'company_name'             => 'OpesCare Platform',
            'website_url'              => 'https://opescare.com',
            'status'                   => 'active',
            'email_verification_token' => null,
            'email_verified_at'        => now(),
            'api_terms_accepted'       => true,
            'api_terms_accepted_at'    => now(),
            'api_terms_version'        => 'v1.0',
            'sandbox_only'             => false,
            'admin_notes'              => 'Production developer admin account.',
            'suspended_by'             => null,
            'suspend_reason'           => null,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }
}
