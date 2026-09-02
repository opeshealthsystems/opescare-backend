<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Completes the demo developer account record in developer_accounts.
 *
 * The user (demo.developer@opescare.test) is created by DemoDeveloperSeeder.
 * This seeder adds the developer_accounts row with all fields filled.
 *
 * Idempotent – inserts only if the row does not already exist.
 */
class DemoDeveloperAccountSeeder extends Seeder
{
    private const DEV_ACCOUNT_ID = '00000000-0000-0000-0000-400000000001';
    private const DEV_USER_ID    = '00000000-0000-0000-0000-200000000050';

    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('developer_accounts')) {
            $this->command->warn('developer_accounts table not found — skipping.');
            return;
        }

        if (DB::table('developer_accounts')->where('id', self::DEV_ACCOUNT_ID)->exists()) {
            return;
        }

        DB::table('developer_accounts')->insert([
            'id'                       => self::DEV_ACCOUNT_ID,
            'user_id'                  => self::DEV_USER_ID,
            'display_name'             => 'OpesCare Demo Developer',
            'email'                    => 'demo.developer@opescare.test',
            'company_name'             => 'Acme Health Systems',
            'website_url'              => 'https://acmehealthsystems.example.com',
            'status'                   => 'active',
            'email_verification_token' => null,
            'email_verified_at'        => now(),
            'api_terms_accepted'       => true,
            'api_terms_accepted_at'    => now(),
            'api_terms_version'        => 'v1.0',
            'sandbox_only'             => false,
            'admin_notes'              => 'Demo account — pre-approved for all integration types. Production access granted.',
            'suspended_by'             => null,
            'suspend_reason'           => null,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        // Production access request — approved
        if (DB::getSchemaBuilder()->hasTable('production_access_requests')) {
            $prodRequestId = '00000000-0000-0000-0000-500000000001';
            if (!DB::table('production_access_requests')->where('id', $prodRequestId)->exists()) {
                DB::table('production_access_requests')->insert([
                    'id'                        => $prodRequestId,
                    'developer_account_id'      => self::DEV_ACCOUNT_ID,
                    'integration_client_id'     => '00000000-0000-0000-0000-300000000002',
                    'use_case'                  => 'OpesCare HIS interoperability — Health ID lookup, patient data sync, clinical record push.',
                    'technical_description'     => 'Integration with external Hospital Information System (OpesCare HIS / opeshisos). Pulls patient Health IDs, pushes encounter data and lab results via Bridge Agent and Connect API.',
                    'requested_scopes'          => json_encode(['health_id:verify', 'patient:read', 'encounter:push', 'lab:push', 'prescription:push', 'facility:sync']),
                    'estimated_daily_requests'  => '< 10 000',
                    'handles_patient_data'      => true,
                    'data_residency_region'     => 'AF',
                    'security_review_done'      => true,
                    'terms_accepted'            => true,
                    'terms_version'             => 'v1.0',
                    'status'                    => 'approved',
                    'reviewed_by'               => self::DEV_USER_ID,
                    'reviewed_at'               => now(),
                    'review_notes'              => 'Auto-approved for demo environment. Production credentials issued.',
                    'approved_scopes'           => json_encode(['health_id:verify', 'patient:read', 'encounter:push', 'lab:push', 'prescription:push', 'facility:sync']),
                    'approved_at'               => now(),
                    'rejected_reason'           => null,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }
        }

        // Production integration client for OpesCare HIS
        $prodClientId = '00000000-0000-0000-0000-300000000002';
        if (!DB::table('integration_clients')->where('id', $prodClientId)->exists()) {
            $facilityId = DB::table('facilities')->where('is_demo', true)->value('id');
            DB::table('integration_clients')->insert([
                'id'            => $prodClientId,
                'name'          => 'OpesCare HIS — Production Client',
                'client_id'     => 'opeshisos_production',
                'client_secret' => hash('sha256', 'prod_secret_opeshisos_2026'),
                'facility_id'   => $facilityId,
                'scopes'        => json_encode(['health_id:verify', 'patient:read', 'encounter:push', 'lab:push', 'prescription:push', 'facility:sync']),
                'status'        => 'active',
                'environment'   => 'production',
                'description'   => 'Production client for the OpesCare Hospital Information System integration. Authorised for full Health ID resolution and clinical record push.',
                'contact_email' => 'integration@opeshisos.test',
                'created_by'    => self::DEV_USER_ID,
                'approved_at'   => now(),
                'approved_by'   => self::DEV_USER_ID,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } else {
            DB::table('integration_clients')->where('id', $prodClientId)->update([
                'status'      => 'active',
                'environment' => 'production',
                'updated_at'  => now(),
            ]);
        }
    }
}
