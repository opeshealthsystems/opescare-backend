<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountCategory;

class AccountCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['key' => 'patient_family',          'name' => 'Patient & Family Accounts',                'sort_order' => 1],
            ['key' => 'clinical_provider',        'name' => 'Clinical Provider Accounts',              'sort_order' => 2],
            ['key' => 'nursing_midwifery',        'name' => 'Nursing & Midwifery Accounts',            'sort_order' => 3],
            ['key' => 'clinical_training',        'name' => 'Clinical Training Accounts',              'sort_order' => 4],
            ['key' => 'front_desk_operations',    'name' => 'Front Desk & Patient Operations Accounts','sort_order' => 5],
            ['key' => 'laboratory',               'name' => 'Laboratory Accounts',                     'sort_order' => 6],
            ['key' => 'pharmacy',                 'name' => 'Pharmacy Accounts',                       'sort_order' => 7],
            ['key' => 'billing_finance',          'name' => 'Billing & Finance Accounts',              'sort_order' => 8],
            ['key' => 'insurance',                'name' => 'Insurance Accounts',                      'sort_order' => 9],
            ['key' => 'facility_administration',  'name' => 'Facility Administration Accounts',        'sort_order' => 10],
            ['key' => 'health_org_ngo',           'name' => 'Health Organization / NGO Accounts',      'sort_order' => 11],
            ['key' => 'public_health_government', 'name' => 'Public Health / Government Accounts',     'sort_order' => 12],
            ['key' => 'developer_api_partner',    'name' => 'Developer / API Partner Accounts',        'sort_order' => 13],
            ['key' => 'integration_device',       'name' => 'Integration Device / Bridge Agent Accounts','sort_order' => 14],
            ['key' => 'opescare_lite',            'name' => 'OpesCare Lite Accounts',                  'sort_order' => 15],
            ['key' => 'support_customer_success', 'name' => 'Support & Customer Success Accounts',     'sort_order' => 16],
            ['key' => 'privacy_security',         'name' => 'Privacy, Security & Compliance Accounts', 'sort_order' => 17],
            ['key' => 'data_quality',             'name' => 'Data Quality / Reconciliation Accounts',  'sort_order' => 18],
            ['key' => 'partner_governance',       'name' => 'Partner Governance Accounts',             'sort_order' => 20],
            ['key' => 'platform_super_admin',     'name' => 'Platform / Super Admin / Demo Accounts',  'sort_order' => 21],
        ];

        foreach ($categories as $data) {
            AccountCategory::firstOrCreate(
                ['key' => $data['key']],
                ['name' => $data['name'], 'sort_order' => $data['sort_order']]
            );
        }
    }
}
