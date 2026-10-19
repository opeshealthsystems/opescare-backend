<?php

/**
 * Portal role-badge labels, rendered in every sidebar partial via
 * {{ __('portal.<profile>_role', [], $l) ?: 'Fallback' }}.
 *
 * This namespace was previously missing entirely, so every sidebar role badge
 * rendered the raw key (e.g. "portal.doctor_role") in both locales. EN and FR
 * MUST keep identical keys (enforced by scripts/i18n-audit.php).
 *
 * ── `role_name.*` — the OTHER role label on the same page ──────────────────
 *
 * The `*_role` keys above are keyed by DASHBOARD PROFILE, and there are far
 * fewer profiles than roles: many roles share one. The sidebar brand-sub, the
 * sidebar footer and the topbar do not show the profile badge — they show
 * `roles.description`, a column on the `roles` table, and a database column is
 * not translatable. So a French-speaking visiting doctor read "Visiting Doctor"
 * in three places on every portal page while the badge between them was
 * correctly French, and lang/fr/team.php had been carrying "Médecin vacataire"
 * the whole time for the invitation list.
 *
 * `role_name.*` is keyed by `roles.name` — the role slug — and covers every
 * row RolesSeeder plants. The English values are the descriptions verbatim, so
 * nothing changes for an English reader; layouts/portal.blade.php falls back to
 * the column for any role that has no entry here, which is what a role added to
 * the table but not to lang/ should look like: the stored English, not a blank
 * and not a guess.
 */
return [
    'api_partner_admin_role'        => 'API Partner Admin',
    'appointment_coordinator_role'  => 'Appointment Coordinator',
    'billing_officer_role'          => 'Billing Officer',
    'bridge_agent_admin_role'       => 'Bridge Agent Admin',
    'caregiver_role'                => 'Caregiver',
    'cashier_role'                  => 'Cashier',
    'compliance_audit_role'         => 'Compliance Auditor',
    'country_regional_admin_role'   => 'Country / Regional Admin',
    'data_import_role'              => 'Data Import Officer',
    'data_steward_role'             => 'Data Steward',
    'demo_role'                     => 'Demo Mode',
    'department_manager_role'       => 'Department Manager',
    'dependent_manager_role'        => 'Dependent Manager',
    'developer_role'                => 'Developer',
    'device_kiosk_role'             => 'Device / Kiosk',
    'disease_surveillance_role'     => 'Disease Surveillance',
    'doctor_role'                   => 'Doctor',
    'emergency_access_reviewer_role' => 'Emergency Access Reviewer',
    'facility_admin_role'           => 'Facility Admin',
    'facility_executive_role'       => 'Facility Executive',
    'finance_manager_role'          => 'Finance Manager',
    'guardian_role'                 => 'Guardian / Parent',
    'implementation_lead_role'      => 'Implementation Lead',
    'lab_manager_role'              => 'Lab Manager',
    'labtech_role'                  => 'Lab Technician',
    'imaging_tech_role'             => 'Imaging Technologist',
    'radiologist_role'              => 'Radiologist',
    'lite_role'                     => 'Lite Access',
    'medical_supervisor_role'       => 'Medical Supervisor',
    'medicine_stock_role'           => 'Medicine Stock',
    'midwife_role'                  => 'Midwife',
    'multi_facility_admin_role'     => 'Multi-Facility Admin',
    'ngo_health_org_role'           => 'NGO Health Org',
    'nurse_role'                    => 'Nurse',
    'nurse_supervisor_role'         => 'Nurse Supervisor',
    'outreach_mobile_role'          => 'Outreach / Mobile',
    'partner_admin_role'            => 'Partner Admin',
    'partner_review_role'           => 'Partner Reviewer',
    'pharmacist_role'               => 'Pharmacist',
    'pharmacy_manager_role'         => 'Pharmacy Manager',
    'privacy_officer_role'          => 'Privacy Officer',
    'public_health_admin_role'      => 'Public Health Admin',
    'public_health_officer_role'    => 'Public Health Officer',
    'queue_manager_role'            => 'Queue Manager',
    'refund_approver_role'          => 'Refund Approver',
    'wallet_ops_role'               => 'Wallet Operations',
    'receptionist_role'             => 'Receptionist',
    'records_officer_role'          => 'Records Officer',
    'resident_doctor_role'          => 'Resident Doctor',
    'sample_collection_role'        => 'Sample Collection',
    'security_officer_role'         => 'Security Officer',
    'specialist_role'               => 'Specialist Doctor',
    'student_clinical_role'         => 'Student / Intern',
    'support_agent_role'            => 'Support Agent',
    'support_manager_role'          => 'Support Manager',
    'triage_nurse_role'             => 'Triage Nurse',
    'ward_nurse_role'               => 'Ward Nurse',
    'webhook_manager_role'          => 'Webhook Manager',

    /*
     * Keyed by `roles.name`. Mirrors RolesSeeder::ROLES — a role added there
     * needs an entry here and in lang/fr/portal.php, or the portal falls back
     * to the English `roles.description` column for it.
     */
    'role_name' => [
        'api_partner'               => 'API Partner Admin',
        'api_technical'             => 'API Technical User',
        'audit_reviewer'            => 'Audit Reviewer',
        'billing_officer'           => 'Billing Officer',
        'branch_admin'              => 'Branch / Multi-Facility Admin',
        'bridge_admin'              => 'Bridge Agent Admin',
        'bridge_agent'              => 'Bridge Agent Device Account',
        'caregiver'                 => 'Caregiver Account',
        'cashier'                   => 'Cashier',
        'clinic_admin'              => 'Clinic Admin',
        'compliance_officer'        => 'Compliance Officer',
        'consultant'                => 'Consultant Doctor',
        'data_protection_officer'   => 'Data Protection Officer',
        'data_quality_reviewer'     => 'Data Quality Reviewer',
        'data_steward'              => 'Data Steward',
        'dependent_manager'         => 'Dependent Manager Account',
        'developer'                 => 'Developer Account',
        'developer_org_admin'       => 'Developer Organization Admin',
        'dispensing_officer'        => 'Dispensing Officer',
        'doctor'                    => 'General Doctor',
        'emergency_access_reviewer' => 'Emergency Access Reviewer',
        'emergency_contact'         => 'Emergency Contact Account',
        'facility_admin'            => 'Facility Admin',
        'facility_ceo'              => 'Hospital Director / Facility Executive',
        'facility_device'           => 'Facility Device Account',
        'finance'                   => 'Facility Finance Role',
        'finance_manager'           => 'Finance Manager',
        'front_desk'                => 'Front Desk Officer',
        'guardian'                  => 'Guardian / Parent Account',
        'health_program_manager'    => 'Health Program Manager',
        'hospital_admin'            => 'Hospital Admin',
        'insurance_admin'           => 'Insurance Admin',
        'insurance_claims'          => 'Insurance Claims Officer',
        'insurance_finance'         => 'Insurance Finance / Payment Officer',
        'insurance_preauth'         => 'Insurance Pre-Authorization Officer',
        'insurance_reviewer'        => 'Insurance Reviewer',
        'integration_device'        => 'Integration Device Account',
        'intern'                    => 'Intern / Trainee',
        'kiosk'                     => 'Kiosk / Shared Workstation',
        'lab_manager'               => 'Lab Manager',
        'lab_scientist'             => 'Lab Scientist',
        'lab_validator'             => 'Lab Result Validator',
        'labtech'                   => 'Lab Technician',
        'imaging_tech'              => 'Imaging Technologist',
        'radiologist'               => 'Radiologist',
        'legal_admin'               => 'Legal Document Admin',
        'lite_device'               => 'OpesCare Lite Device Account',
        'lite_facility'             => 'OpesCare Lite Facility Account',
        'lite_offline_sync'         => 'OpesCare Lite Offline Sync Account',
        'lite_staff'                => 'OpesCare Lite Staff Account',
        'medical_supervisor'        => 'Medical School Supervisor',
        'medicine_stock'            => 'Medicine Stock Manager',
        'midwife'                   => 'Midwife',
        'mobile_clinic_team'        => 'Mobile Clinic Team',
        'multi_doctor'              => 'Multi-Facility Doctor',
        'ngo_admin'                 => 'Health Organization / NGO Admin',
        'nurse'                     => 'Nurse',
        'nurse_supervisor'          => 'Nurse Supervisor',
        'outreach_team'             => 'Outreach Team',
        'partner_technical'         => 'Partner Technical Contact',
        'patient'                   => 'Patient Account',
        'pharmacist'                => 'Pharmacist',
        'pharmacy_manager'          => 'Pharmacy Manager',
        'pharmacy_technician'       => 'Pharmacy Technician',
        'platform_admin'            => 'Platform Owner',
        'privacy_officer'           => 'Privacy Officer',
        'product_admin'             => 'Product Admin',
        'public_health_api'         => 'Public Health Reporting API Client',
        'receptionist'              => 'Receptionist / Front Desk',
        'reconciliation_officer'    => 'Reconciliation Officer',
        'records_officer'           => 'Records Officer',
        'resident'                  => 'Resident Doctor',
        'sample_collection'         => 'Sample Collection Officer',
        'sandbox_developer'         => 'Sandbox Developer',
        'security_officer'          => 'Security Officer',
        'specialist'                => 'Specialist Doctor',
        'student_doctor'            => 'Student Doctor',
        'student_nurse'             => 'Student Nurse',
        'super_admin'               => 'Super Admin',
        'system_admin'              => 'System Administrator',
        'triage_nurse'              => 'Triage Nurse',
        'visiting_doctor'           => 'Visiting Doctor',
        'ward_nurse'                => 'Ward Nurse',
        'webhook_manager'           => 'Webhook Manager',
    ],
];
