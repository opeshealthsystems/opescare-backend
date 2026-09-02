<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds production staff accounts for every RBAC role defined in RolesSeeder.
 *
 * All accounts are non-demo (is_demo = false), active, and email-verified.
 * Each user is assigned to the appropriate production facility and receives
 * a FacilityRoleAssignment so the portal.access middleware routes them correctly.
 *
 * Idempotent — safe to run multiple times.
 *
 * Facility IDs (from ProductionAccountsSeeder):
 *   Hospital    00000000-0000-0000-0000-800000000001
 *   Diagnostic  00000000-0000-0000-0000-800000000002
 *   Insurance   00000000-0000-0000-0000-800000000003
 *   Pharmacy    00000000-0000-0000-0000-800000000004
 *   Health Org  00000000-0000-0000-0000-800000000005
 *   Clinic      00000000-0000-0000-0000-800000000007
 *
 * User UUIDs range: 00000000-0000-0000-0000-9200000000XX
 */
class StaffAccountsSeeder extends Seeder
{
    // ── Facility shortcuts ────────────────────────────────────────────────────
    private const HOSPITAL   = '00000000-0000-0000-0000-800000000001';
    private const DIAGNOSTIC = '00000000-0000-0000-0000-800000000002';
    private const INSURANCE  = '00000000-0000-0000-0000-800000000003';
    private const PHARMACY   = '00000000-0000-0000-0000-800000000004';
    private const HEALTH_ORG = '00000000-0000-0000-0000-800000000005';
    private const CLINIC     = '00000000-0000-0000-0000-800000000007';

    // ── Password tiers (per role category) ───────────────────────────────────
    private const PW_DOCTOR   = 'D0ct0r@OC#2026';
    private const PW_NURSE    = 'Nurs3@OC#2026';
    private const PW_TRAINEE  = 'Tr@in3e@OC#2026';
    private const PW_DESK     = 'Fr0ntDesk@OC26';
    private const PW_LAB      = 'L@bT3ch#OC2026';
    private const PW_PHARMACY = 'Ph@rm@cy#OC26';
    private const PW_BILLING  = 'B1ll1ng@OC#2026';
    private const PW_INSURE   = 'Insur@nce@OC26';
    private const PW_FACADMIN = 'F@cAdm1n#OC26';
    private const PW_HEALTHORG= 'H3@lthOrg#OC26';
    private const PW_DEVELOPER= 'D3v3l0p@r#OC26';
    private const PW_SUPPORT  = 'Supp0rt@OC#2026';
    private const PW_SECURITY = 'S3cur1ty@OC#26';
    private const PW_PARTNER  = 'P@rtn3r@OC#2026';
    private const PW_PLATFORM = 'Pl@tAdm1n#OC26';

    /**
     * [ user_id, email, name, password, role_name, facility_id|null ]
     */
    private const STAFF = [

        // ── Clinical Providers — OpesCare General Hospital ───────────────────
        ['00000000-0000-0000-0000-920000000001', 'kofi.mensah@opescare.com',         'Dr. Kofi Mensah',                    self::PW_DOCTOR,    'doctor',                self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000002', 'amara.diallo@opescare.com',         'Dr. Amara Diallo',                   self::PW_DOCTOR,    'multi_doctor',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000003', 'fatima.bello@opescare.com',         'Dr. Fatima Bello (Radiologist)',      self::PW_DOCTOR,    'specialist',            self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000004', 'omar.sy@opescare.com',              'Dr. Omar Sy (Cardiologist)',          self::PW_DOCTOR,    'specialist',            self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000005', 'ibrahim.toure@opescare.com',        'Dr. Ibrahim Touré',                  self::PW_DOCTOR,    'consultant',            self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000006', 'aissatou.camara@opescare.com',      'Dr. Aissatou Camara',                self::PW_DOCTOR,    'resident',              self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000007', 'emmanuel.nkomo@opescare.com',       'Dr. Emmanuel Nkomo',                 self::PW_DOCTOR,    'visiting_doctor',       self::HOSPITAL],

        // ── Nursing & Midwifery — OpesCare General Hospital ──────────────────
        ['00000000-0000-0000-0000-920000000008', 'grace.osei@opescare.com',           'Nurse Grace Osei',                   self::PW_NURSE,     'nurse',                 self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000009', 'fatou.kone@opescare.com',           'Nurse Fatou Koné',                   self::PW_NURSE,     'triage_nurse',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000010', 'aminata.sow@opescare.com',          'Nurse Aminata Sow',                  self::PW_NURSE,     'ward_nurse',            self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000011', 'mariam.ba@opescare.com',            'Midwife Mariam Ba',                  self::PW_NURSE,     'midwife',               self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000012', 'kadiatou.bah@opescare.com',         'Nurse Supervisor Kadiatou Bah',      self::PW_NURSE,     'nurse_supervisor',      self::HOSPITAL],

        // ── Clinical Training — OpesCare General Hospital ────────────────────
        ['00000000-0000-0000-0000-920000000013', 'moussa.coulibaly@opescare.com',     'Student Doctor Moussa Coulibaly',    self::PW_TRAINEE,   'student_doctor',        self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000014', 'aicha.traore@opescare.com',         'Student Nurse Aicha Traoré',         self::PW_TRAINEE,   'student_nurse',         self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000015', 'boubacar.kouyate@opescare.com',     'Intern Dr. Boubacar Kouyaté',        self::PW_TRAINEE,   'intern',                self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000016', 'seydou.ouedraogo@opescare.com',     'Dr. Seydou Ouédraogo (Supervisor)',  self::PW_DOCTOR,    'medical_supervisor',    self::HOSPITAL],

        // ── Front Desk & Operations — OpesCare General Hospital ──────────────
        ['00000000-0000-0000-0000-920000000017', 'marietou.diallo@opescare.com',      'Receptionist Marietou Diallo',       self::PW_DESK,      'receptionist',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000018', 'jeanbaptiste.nguema@opescare.com',  'Front Desk Jean-Baptiste Nguema',    self::PW_DESK,      'front_desk',            self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000019', 'rabi.abdullahi@opescare.com',       'Appointment Coordinator Rabi',       self::PW_DESK,      'appointment_coordinator',self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000020', 'ousmane.fofana@opescare.com',       'Queue Manager Ousmane Fofana',       self::PW_DESK,      'queue_manager',         self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000021', 'binta.keita@opescare.com',          'Records Officer Binta Keïta',        self::PW_DESK,      'records_officer',       self::HOSPITAL],

        // ── Laboratory — OpesCare Diagnostic Centre ───────────────────────────
        ['00000000-0000-0000-0000-920000000022', 'aboubakar.sangare@opescare.com',    'Lab Tech Aboubakar Sangaré',         self::PW_LAB,       'labtech',               self::DIAGNOSTIC],
        ['00000000-0000-0000-0000-920000000023', 'rokiatou.barry@opescare.com',       'Lab Scientist Dr. Rokiatou Barry',   self::PW_LAB,       'lab_scientist',         self::DIAGNOSTIC],
        ['00000000-0000-0000-0000-920000000024', 'seku.camara@opescare.com',          'Lab Manager Dr. Sékou Camara',       self::PW_LAB,       'lab_manager',           self::DIAGNOSTIC],
        ['00000000-0000-0000-0000-920000000025', 'coumba.diallo@opescare.com',        'Lab Validator Dr. Coumba Diallo',    self::PW_LAB,       'lab_validator',         self::DIAGNOSTIC],
        ['00000000-0000-0000-0000-920000000026', 'adama.kone@opescare.com',           'Sample Collection Adama Koné',       self::PW_LAB,       'sample_collection',     self::DIAGNOSTIC],

        // ── Pharmacy — OpesCare Pharmacy ──────────────────────────────────────
        ['00000000-0000-0000-0000-920000000027', 'aicha.coulibaly@opescare.com',      'Pharmacist Aïcha Coulibaly',         self::PW_PHARMACY,  'pharmacist',            self::PHARMACY],
        ['00000000-0000-0000-0000-920000000028', 'fatou.cisse@opescare.com',          'Pharmacy Technician Fatou Cissé',    self::PW_PHARMACY,  'pharmacy_technician',   self::PHARMACY],
        ['00000000-0000-0000-0000-920000000029', 'oumar.balde@opescare.com',          'Medicine Stock Oumar Baldé',         self::PW_PHARMACY,  'medicine_stock',        self::PHARMACY],
        ['00000000-0000-0000-0000-920000000030', 'mariama.balde@opescare.com',        'Dispensing Officer Mariama Baldé',   self::PW_PHARMACY,  'dispensing_officer',    self::PHARMACY],

        // ── Billing & Finance — OpesCare General Hospital ────────────────────
        ['00000000-0000-0000-0000-920000000031', 'mamadou.diarra@opescare.com',       'Cashier Mamadou Diarra',             self::PW_BILLING,   'cashier',               self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000032', 'fatoumata.toure@opescare.com',      'Billing Officer Fatoumata Touré',    self::PW_BILLING,   'billing_officer',       self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000033', 'cheikh.ndiaye@opescare.com',        'Finance Manager Cheikh Ndiaye',      self::PW_BILLING,   'finance_manager',       self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000034', 'rokhaya.diop@opescare.com',         'Refund Approver Rokhaya Diop',       self::PW_BILLING,   'refund_approver',       self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000035', 'amadou.ly@opescare.com',            'Wallet Ops Amadou Ly',               self::PW_BILLING,   'wallet_ops',            self::HOSPITAL],

        // ── Insurance — OpesCare Insurance Company ────────────────────────────
        ['00000000-0000-0000-0000-920000000036', 'claims.mbacke@opescare.com',        'Insurance Reviewer Mbacké',          self::PW_INSURE,    'insurance_reviewer',    self::INSURANCE],
        ['00000000-0000-0000-0000-920000000037', 'sokhna.diop@opescare.com',          'Claims Officer Sokhna Diop',         self::PW_INSURE,    'insurance_claims',      self::INSURANCE],
        ['00000000-0000-0000-0000-920000000038', 'ndeye.fall@opescare.com',           'Preauth Officer Ndéye Fall',         self::PW_INSURE,    'insurance_preauth',     self::INSURANCE],
        ['00000000-0000-0000-0000-920000000039', 'pape.sow@opescare.com',             'Insurance Finance Pape Sow',         self::PW_INSURE,    'insurance_finance',     self::INSURANCE],

        // ── Facility Administration — OpesCare General Hospital ───────────────
        ['00000000-0000-0000-0000-920000000040', 'ndeye.mbaye@opescare.com',          'Facility Admin Ndeye Mbaye',         self::PW_FACADMIN,  'facility_admin',        self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000041', 'mamoudou.diallo@opescare.com',      'CEO Dr. Mamoudou Diallo',            self::PW_FACADMIN,  'facility_ceo',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000042', 'hawa.conde@opescare.com',           'Department Manager Hawa Condé',      self::PW_FACADMIN,  'department_manager',    self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000043', 'modibo.traore@opescare.com',        'Branch Admin Modibo Traoré',         self::PW_FACADMIN,  'branch_admin',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000044', 'oumou.sangare@opescare.com',        'Finance Role Oumou Sangaré',         self::PW_BILLING,   'finance',               self::CLINIC],

        // ── Health Organization & Public Health ───────────────────────────────
        ['00000000-0000-0000-0000-920000000045', 'bineta.ndiaye@opescare.com',        'Health Programme Manager Dr. Bineta',self::PW_HEALTHORG, 'health_program_manager',self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000046', 'yaye.toure@opescare.com',           'Outreach Team Officer Yaye Touré',   self::PW_HEALTHORG, 'outreach_team',         self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000047', 'mobile.kouyate@opescare.com',       'Mobile Clinic Team Mamadou K.',      self::PW_HEALTHORG, 'mobile_clinic_team',    self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000048', 'rokia.keita@opescare.com',          'Public Health Officer Dr. Rokia',    self::PW_HEALTHORG, 'public_health_officer', self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000049', 'ibou.diatta@opescare.com',          'Public Health Reviewer Ibou Diatta', self::PW_HEALTHORG, 'public_health_reviewer',self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000050', 'awa.ndiaye@opescare.com',           'Public Health Admin Dr. Awa Ndiaye', self::PW_HEALTHORG, 'public_health_admin',   self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000051', 'alassane.diop@opescare.com',        'Government Supervisor Dir. Diop',    self::PW_HEALTHORG, 'government_supervisor', self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000052', 'halimatou.barry@opescare.com',      'Disease Surveillance Dr. Halimatou', self::PW_HEALTHORG, 'disease_surveillance',  self::HEALTH_ORG],
        ['00000000-0000-0000-0000-920000000053', 'publichealth.api@opescare.com',     'Public Health API Client',           self::PW_DEVELOPER, 'public_health_api',     null],

        // ── Developer / API Partner ───────────────────────────────────────────
        ['00000000-0000-0000-0000-920000000054', 'api.developer@opescare.com',        'API Developer',                      self::PW_DEVELOPER, 'developer',             null],
        ['00000000-0000-0000-0000-920000000055', 'api.partner@opescare.com',          'API Partner Admin',                  self::PW_DEVELOPER, 'api_partner',           null],
        ['00000000-0000-0000-0000-920000000056', 'api.technical@opescare.com',        'API Technical User',                 self::PW_DEVELOPER, 'api_technical',         null],
        ['00000000-0000-0000-0000-920000000057', 'webhook.manager@opescare.com',      'Webhook Manager',                    self::PW_DEVELOPER, 'webhook_manager',       null],
        ['00000000-0000-0000-0000-920000000058', 'sandbox.dev@opescare.com',          'Sandbox Developer',                  self::PW_DEVELOPER, 'sandbox_developer',     null],

        // ── Bridge & Lite ─────────────────────────────────────────────────────
        ['00000000-0000-0000-0000-920000000059', 'bridge.agent@opescare.com',         'Bridge Agent Device',                self::PW_DEVELOPER, 'bridge_agent',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000060', 'bridge.admin@opescare.com',         'Bridge Admin',                       self::PW_DEVELOPER, 'bridge_admin',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000061', 'lite.facility@opescare.com',        'Lite Facility Admin',                self::PW_DESK,      'lite_facility',         self::CLINIC],
        ['00000000-0000-0000-0000-920000000062', 'lite.staff@opescare.com',           'Lite Staff User',                    self::PW_DESK,      'lite_staff',            self::CLINIC],

        // ── Support & Customer Success ────────────────────────────────────────
        ['00000000-0000-0000-0000-920000000063', 'ibou.mbaye@opescare.com',           'Support Agent Ibou Mbaye',           self::PW_SUPPORT,   'support_agent',         self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000064', 'aissata.bah@opescare.com',          'Support Manager Aissata Bah',        self::PW_SUPPORT,   'support_manager',       self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000065', 'kadia.traore@opescare.com',         'Customer Success Kadia Traoré',      self::PW_SUPPORT,   'customer_success',      self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000066', 'mamoud.keita@opescare.com',         'Implementation Lead Mamoud Keïta',   self::PW_SUPPORT,   'implementation_lead',   self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000067', 'awa.diallo@opescare.com',           'Training Support Awa Diallo',        self::PW_SUPPORT,   'training_support',      self::HOSPITAL],

        // ── Privacy, Security & Compliance ───────────────────────────────────
        ['00000000-0000-0000-0000-920000000068', 'privacy.officer@opescare.com',      'Privacy Officer Dr. Oumou Kouyate',  self::PW_SECURITY,  'privacy_officer',       self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000069', 'dpo@opescare.com',                  'Data Protection Officer Moussa Ba',  self::PW_SECURITY,  'data_protection_officer',self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000070', 'security.officer@opescare.com',     'Security Officer Sidy Diallo',       self::PW_SECURITY,  'security_officer',      self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000071', 'compliance@opescare.com',           'Compliance Officer Aminata Fall',    self::PW_SECURITY,  'compliance_officer',    self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000072', 'audit@opescare.com',                'Audit Reviewer Assane Diop',         self::PW_SECURITY,  'audit_reviewer',        self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000073', 'emergency.reviewer@opescare.com',   'Emergency Access Reviewer Astou',    self::PW_SECURITY,  'emergency_access_reviewer',self::HOSPITAL],

        // ── Data Quality / Reconciliation ─────────────────────────────────────
        ['00000000-0000-0000-0000-920000000074', 'data.steward@opescare.com',         'Data Steward Fatoumata Koné',        self::PW_SECURITY,  'data_steward',          self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000075', 'reconciliation@opescare.com',       'Reconciliation Officer Ibrahima D.', self::PW_SECURITY,  'reconciliation_officer',self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000076', 'data.import@opescare.com',          'Data Import Officer Coumba Fall',    self::PW_SECURITY,  'data_import_officer',   self::HOSPITAL],
        ['00000000-0000-0000-0000-920000000077', 'data.quality@opescare.com',         'Data Quality Reviewer Ramata T.',    self::PW_SECURITY,  'data_quality_reviewer', self::HOSPITAL],

        // ── Partner Governance ────────────────────────────────────────────────
        ['00000000-0000-0000-0000-920000000082', 'partner.admin@opescare.com',        'Partner Admin Mody Diallo',          self::PW_PARTNER,   'partner_admin',         null],
        ['00000000-0000-0000-0000-920000000083', 'partner.reviewer@opescare.com',     'Partner Reviewer Yacine Ba',         self::PW_PARTNER,   'partner_reviewer',      null],
        ['00000000-0000-0000-0000-920000000084', 'partner.compliance@opescare.com',   'Partner Compliance Nafissatou Sow',  self::PW_PARTNER,   'partner_compliance',    null],
        ['00000000-0000-0000-0000-920000000085', 'partner.technical@opescare.com',    'Partner Technical Malick Niang',     self::PW_PARTNER,   'partner_technical',     null],

        // ── Platform & Super Admin ────────────────────────────────────────────
        ['00000000-0000-0000-0000-920000000086', 'super.admin@opescare.com',          'Super Admin',                        self::PW_PLATFORM,  'super_admin',           null],
        ['00000000-0000-0000-0000-920000000087', 'platform.admin@opescare.com',       'Platform Owner',                     self::PW_PLATFORM,  'platform_admin',        null],
        ['00000000-0000-0000-0000-920000000088', 'system.admin@opescare.com',         'System Administrator',               self::PW_PLATFORM,  'system_admin',          null],
        ['00000000-0000-0000-0000-920000000089', 'product.admin@opescare.com',        'Product Admin',                      self::PW_PLATFORM,  'product_admin',         null],
        ['00000000-0000-0000-0000-920000000090', 'legal.admin@opescare.com',          'Legal Document Admin',               self::PW_PLATFORM,  'legal_admin',           null],
        ['00000000-0000-0000-0000-920000000091', 'country.admin@opescare.com',        'Country Admin Cameroon',             self::PW_PLATFORM,  'country_admin',         null],
        ['00000000-0000-0000-0000-920000000092', 'regional.admin@opescare.com',       'Regional Admin West Africa',         self::PW_PLATFORM,  'regional_admin',        null],
    ];

    public function run(): void
    {
        $roleMap = DB::table('roles')->pluck('id', 'name');

        foreach (self::STAFF as [$userId, $email, $name, $password, $roleName, $facilityId]) {
            // ── 1. User ──────────────────────────────────────────────────────
            $exists = DB::table('users')
                ->where('id', $userId)
                ->orWhere('email', $email)
                ->exists();

            if (!$exists) {
                $roleId = $roleMap[$roleName] ?? null;

                DB::table('users')->insert([
                    'id'                  => $userId,
                    'name'                => $name,
                    'email'               => $email,
                    'password'            => Hash::make($password),
                    'role_id'             => $roleId,
                    'primary_facility_id' => $facilityId,
                    'status'              => 'active',
                    'is_demo'             => false,
                    'email_verified_at'   => now(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            // ── 2. FacilityRoleAssignment ────────────────────────────────────
            if ($facilityId) {
                $roleId = $roleMap[$roleName] ?? null;

                if ($roleId) {
                    $assignmentExists = DB::table('facility_role_assignments')
                        ->where('user_id', $userId)
                        ->where('facility_id', $facilityId)
                        ->exists();

                    if (!$assignmentExists) {
                        DB::table('facility_role_assignments')->insert([
                            'id'          => Str::uuid()->toString(),
                            'user_id'     => $userId,
                            'facility_id' => $facilityId,
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

            $this->command->info("✔ [{$roleName}] {$name} — {$email}");
        }

        $this->command->info('');
        $this->command->info('All ' . count(self::STAFF) . ' staff accounts seeded successfully.');
    }
}
