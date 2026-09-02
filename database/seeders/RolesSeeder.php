<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\AccountCategory;

/**
 * Seeds 107 canonical role names from the OpesCare account types document.
 * Each role is mapped to an account category key and a dashboard profile key.
 * All inserts are idempotent via firstOrCreate + updateOrCreate for existing rows.
 */
class RolesSeeder extends Seeder
{
    /** [ role_name, account_category_key, dashboard_profile_key, description ] */
    private const ROLES = [
        // Patient & Family
        ['patient',              'patient_family',          'patient',              'Patient Account'],
        ['guardian',             'patient_family',          'guardian',             'Guardian / Parent Account'],
        ['caregiver',            'patient_family',          'caregiver',            'Caregiver Account'],
        ['dependent_manager',    'patient_family',          'dependent_manager',    'Dependent Manager Account'],
        ['emergency_contact',    'patient_family',          'patient',              'Emergency Contact Account'],

        // Clinical Providers
        ['doctor',               'clinical_provider',       'doctor',               'General Doctor'],
        ['multi_doctor',         'clinical_provider',       'resident_doctor',      'Multi-Facility Doctor'],
        ['specialist',           'clinical_provider',       'specialist_doctor',    'Specialist Doctor'],
        ['consultant',           'clinical_provider',       'specialist_doctor',    'Consultant Doctor'],
        ['resident',             'clinical_provider',       'resident_doctor',      'Resident Doctor'],
        ['visiting_doctor',      'clinical_provider',       'resident_doctor',      'Visiting Doctor'],

        // Nursing & Midwifery
        ['nurse',                'nursing_midwifery',       'nurse',                'Nurse'],
        ['triage_nurse',         'nursing_midwifery',       'triage_nurse',         'Triage Nurse'],
        ['ward_nurse',           'nursing_midwifery',       'ward_nurse',           'Ward Nurse'],
        ['midwife',              'nursing_midwifery',       'midwife',              'Midwife'],
        ['nurse_supervisor',     'nursing_midwifery',       'nurse_supervisor',     'Nurse Supervisor'],

        // Clinical Training
        ['student_doctor',       'clinical_training',       'student_clinical',     'Student Doctor'],
        ['student_nurse',        'clinical_training',       'student_clinical',     'Student Nurse'],
        ['intern',               'clinical_training',       'student_clinical',     'Intern / Trainee'],
        ['medical_supervisor',   'clinical_training',       'medical_supervisor',   'Medical School Supervisor'],

        // Front Desk & Operations
        ['receptionist',         'front_desk_operations',   'receptionist',         'Receptionist / Front Desk'],
        ['front_desk',           'front_desk_operations',   'receptionist',         'Front Desk Officer'],
        ['records_officer',      'front_desk_operations',   'records_officer',      'Records Officer'],

        // Laboratory
        ['labtech',              'laboratory',              'labtech',              'Lab Technician'],
        ['lab_scientist',        'laboratory',              'labtech',              'Lab Scientist'],
        ['lab_manager',          'laboratory',              'lab_manager',          'Lab Manager'],
        ['lab_validator',        'laboratory',              'lab_manager',          'Lab Result Validator'],
        ['sample_collection',    'laboratory',              'sample_collection',    'Sample Collection Officer'],

        // Pharmacy
        ['pharmacist',           'pharmacy',                'pharmacist',           'Pharmacist'],
        ['pharmacy_technician',  'pharmacy',                'pharmacist',           'Pharmacy Technician'],
        ['pharmacy_manager',     'pharmacy',                'pharmacy_manager',     'Pharmacy Manager'],
        ['medicine_stock',       'pharmacy',                'medicine_stock',       'Medicine Stock Manager'],
        ['dispensing_officer',   'pharmacy',                'pharmacist',           'Dispensing Officer'],

        // Billing & Finance
        ['cashier',              'billing_finance',         'receptionist',         'Cashier'],
        ['billing_officer',      'billing_finance',         'receptionist',         'Billing Officer'],
        ['finance_manager',      'billing_finance',         'receptionist',         'Finance Manager'],

        // Insurance
        ['insurance_reviewer',   'insurance',               'insurance_reviewer',   'Insurance Reviewer'],
        ['insurance_claims',     'insurance',               'insurance_reviewer',   'Insurance Claims Officer'],
        ['insurance_preauth',    'insurance',               'insurance_reviewer',   'Insurance Pre-Authorization Officer'],
        ['insurance_admin',      'insurance',               'insurance_admin',      'Insurance Admin'],
        ['insurance_finance',    'insurance',               'insurance_admin',      'Insurance Finance / Payment Officer'],

        // Facility Administration
        ['facility_admin',       'facility_administration', 'facility_admin',       'Facility Admin'],
        ['clinic_admin',         'facility_administration', 'facility_admin',       'Clinic Admin'],
        ['hospital_admin',       'facility_administration', 'facility_admin',       'Hospital Admin'],
        ['facility_ceo',         'facility_administration', 'facility_executive',   'Hospital Director / Facility Executive'],
        ['branch_admin',         'facility_administration', 'multi_facility_admin', 'Branch / Multi-Facility Admin'],
        ['finance',              'billing_finance',         'receptionist',      'Facility Finance Role'],

        // Health Organization / NGO
        ['ngo_admin',            'health_org_ngo',          'ngo_health_org',       'Health Organization / NGO Admin'],
        ['health_program_manager','health_org_ngo',         'ngo_health_org',       'Health Program Manager'],
        ['outreach_team',        'health_org_ngo',          'outreach_mobile',      'Outreach Team'],
        ['mobile_clinic_team',   'health_org_ngo',          'outreach_mobile',      'Mobile Clinic Team'],

        // Public Health
        ['public_health_api',    'developer_api_partner',   'developer',            'Public Health Reporting API Client'],

        // Developer / API Partner
        ['developer',            'developer_api_partner',   'developer',            'Developer Account'],
        ['developer_org_admin',  'developer_api_partner',   'api_partner_admin',    'Developer Organization Admin'],
        ['api_partner',          'developer_api_partner',   'api_partner_admin',    'API Partner Admin'],
        ['api_technical',        'developer_api_partner',   'developer',            'API Technical User'],
        ['webhook_manager',      'developer_api_partner',   'webhook_manager',      'Webhook Manager'],
        ['sandbox_developer',    'developer_api_partner',   'developer',            'Sandbox Developer'],

        // Integration Device / Bridge Agent
        ['bridge_agent',         'integration_device',      'device_kiosk',         'Bridge Agent Device Account'],
        ['bridge_admin',         'integration_device',      'bridge_agent_admin',   'Bridge Agent Admin'],
        ['integration_device',   'integration_device',      'device_kiosk',         'Integration Device Account'],
        ['facility_device',      'integration_device',      'device_kiosk',         'Facility Device Account'],
        ['kiosk',                'integration_device',      'device_kiosk',         'Kiosk / Shared Workstation'],

        // OpesCare Lite
        ['lite_facility',        'opescare_lite',           'lite',                 'OpesCare Lite Facility Account'],
        ['lite_staff',           'opescare_lite',           'lite',                 'OpesCare Lite Staff Account'],
        ['lite_device',          'opescare_lite',           'lite',                 'OpesCare Lite Device Account'],
        ['lite_offline_sync',    'opescare_lite',           'lite',                 'OpesCare Lite Offline Sync Account'],

        // Support & Customer Success

        // Privacy, Security & Compliance
        ['privacy_officer',      'privacy_security',        'privacy_officer',      'Privacy Officer'],
        ['data_protection_officer','privacy_security',      'privacy_officer',      'Data Protection Officer'],
        ['security_officer',     'privacy_security',        'security_officer',     'Security Officer'],
        ['compliance_officer',   'privacy_security',        'compliance_audit',     'Compliance Officer'],
        ['audit_reviewer',       'privacy_security',        'compliance_audit',     'Audit Reviewer'],
        ['emergency_access_reviewer','privacy_security',    'emergency_access_reviewer','Emergency Access Reviewer'],

        // Data Quality / Reconciliation
        ['data_steward',         'data_quality',            'data_steward',         'Data Steward'],
        ['reconciliation_officer','data_quality',           'data_steward',         'Reconciliation Officer'],
        ['data_quality_reviewer','data_quality',            'data_steward',         'Data Quality Reviewer'],

        // Partner Governance
        ['partner_technical',    'partner_governance',      'developer',            'Partner Technical Contact'],

        // Platform / Super Admin
        ['super_admin',          'platform_super_admin',    'super_admin',          'Super Admin'],
        ['platform_admin',       'platform_super_admin',    'platform_owner',       'Platform Owner'],
        ['system_admin',         'platform_super_admin',    'super_admin',          'System Administrator'],
        ['product_admin',        'platform_super_admin',    'super_admin',          'Product Admin'],
        ['legal_admin',          'platform_super_admin',    'super_admin',          'Legal Document Admin'],
    ];

    public function run(): void
    {
        // Pre-load categories into a name→id map to avoid N+1 queries
        $categoryMap = AccountCategory::all()->keyBy('key')->map->id;

        foreach (self::ROLES as [$name, $categoryKey, $profileKey, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                [
                    'description'           => $description,
                    'account_category_id'   => $categoryMap[$categoryKey] ?? null,
                    'dashboard_profile_key' => $profileKey,
                ]
            );
        }
    }
}
