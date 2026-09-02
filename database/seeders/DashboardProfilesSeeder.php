<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DashboardProfile;

/**
 * Seeds all 60 dashboard profiles from the access matrix document.
 * landing_route = named Laravel route used for post-login redirect.
 */
class DashboardProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            // ── Patient & Family ──────────────────────────────────────────────
            [
                'key'           => 'patient',
                'name'          => 'Patient Dashboard',
                'portal_prefix' => 'patient',
                'landing_route' => 'portals.patient',
                'description'   => 'Standard patient self-service dashboard',
            ],
            [
                'key'           => 'guardian',
                'name'          => 'Guardian Dashboard',
                'portal_prefix' => 'patient',
                'landing_route' => 'portals.patient',
                'description'   => 'Guardian / parent managing dependents',
            ],
            [
                'key'           => 'caregiver',
                'name'          => 'Caregiver Dashboard',
                'portal_prefix' => 'patient',
                'landing_route' => 'portals.patient',
                'description'   => 'Caregiver supporting a patient',
            ],
            [
                'key'           => 'dependent_manager',
                'name'          => 'Dependent Manager Dashboard',
                'portal_prefix' => 'patient',
                'landing_route' => 'portals.patient',
                'description'   => 'Manages multiple dependent accounts',
            ],

            // ── Clinical Providers ────────────────────────────────────────────
            [
                'key'           => 'doctor',
                'name'          => 'Doctor Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'General practitioner clinical workflow',
            ],
            [
                'key'           => 'specialist_doctor',
                'name'          => 'Specialist / Consultant Doctor Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Specialist or consultant physician workflow',
            ],
            [
                'key'           => 'resident_doctor',
                'name'          => 'Resident / Visiting / Multi-Facility Doctor Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Resident, visiting, or multi-facility doctor',
            ],

            // ── Nursing & Midwifery ───────────────────────────────────────────
            [
                'key'           => 'nurse',
                'name'          => 'Nurse Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'General nursing workflow',
            ],
            [
                'key'           => 'triage_nurse',
                'name'          => 'Triage Nurse Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Emergency triage and vital sign intake',
            ],
            [
                'key'           => 'ward_nurse',
                'name'          => 'Ward Nurse Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Inpatient ward nursing workflow',
            ],
            [
                'key'           => 'midwife',
                'name'          => 'Midwife Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Maternity and midwifery workflow',
            ],
            [
                'key'           => 'nurse_supervisor',
                'name'          => 'Nurse Supervisor Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Nursing team oversight and management',
            ],

            // ── Clinical Training ─────────────────────────────────────────────
            [
                'key'           => 'student_clinical',
                'name'          => 'Student Clinical Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Student doctor/nurse — restricted finalize actions',
            ],
            [
                'key'           => 'medical_supervisor',
                'name'          => 'Medical School Supervisor Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Medical school supervisor overseeing students',
            ],

            // ── Front Desk & Operations ───────────────────────────────────────
            [
                'key'           => 'receptionist',
                'name'          => 'Reception / Front Desk Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Patient check-in and registration workflow',
            ],
            [
                'key'           => 'appointment_coordinator',
                'name'          => 'Appointments Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.appointments',
                'description'   => 'Appointment scheduling and coordination',
            ],
            [
                'key'           => 'queue_manager',
                'name'          => 'Queue Operations Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.queue',
                'description'   => 'Patient queue management and flow',
            ],
            [
                'key'           => 'records_officer',
                'name'          => 'Records Office Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Medical records management and filing',
            ],

            // ── Laboratory ────────────────────────────────────────────────────
            [
                'key'           => 'labtech',
                'name'          => 'Lab Technician Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Lab test processing and result entry',
            ],
            [
                'key'           => 'lab_manager',
                'name'          => 'Lab Manager / Validator Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Lab quality assurance and result validation',
            ],
            [
                'key'           => 'sample_collection',
                'name'          => 'Sample Collection Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Sample collection and chain-of-custody workflow',
            ],

            // ── Pharmacy ──────────────────────────────────────────────────────
            [
                'key'           => 'pharmacist',
                'name'          => 'Pharmacist Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.inventory.pharmacy',
                'description'   => 'Prescription dispensing and medication management',
            ],
            [
                'key'           => 'pharmacy_manager',
                'name'          => 'Pharmacy Manager Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.inventory.pharmacy',
                'description'   => 'Pharmacy operations management and stock oversight',
            ],
            [
                'key'           => 'medicine_stock',
                'name'          => 'Medicine Stock Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.inventory.pharmacy',
                'description'   => 'Medicine inventory and stock management',
            ],

            // ── Billing & Finance ─────────────────────────────────────────────
            [
                'key'           => 'cashier',
                'name'          => 'Cashier Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.billing',
                'description'   => 'Point-of-sale billing and payment collection',
            ],
            [
                'key'           => 'billing_officer',
                'name'          => 'Billing Officer Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.billing',
                'description'   => 'Claims creation, invoice management',
            ],
            [
                'key'           => 'finance_manager',
                'name'          => 'Finance Manager Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.billing',
                'description'   => 'Financial oversight, reconciliation, refund approval',
            ],

            // ── Insurance ─────────────────────────────────────────────────────
            [
                'key'           => 'insurance_reviewer',
                'name'          => 'Insurance Reviewer Dashboard',
                'portal_prefix' => 'insurance',
                'landing_route' => 'portals.insurance.claims',
                'description'   => 'Claims review and pre-authorization workflow',
            ],
            [
                'key'           => 'insurance_admin',
                'name'          => 'Insurance Admin Dashboard',
                'portal_prefix' => 'insurance',
                'landing_route' => 'portals.insurance.claims',
                'description'   => 'Insurance policy management and administration',
            ],

            // ── Facility Administration ───────────────────────────────────────
            [
                'key'           => 'facility_admin',
                'name'          => 'Facility Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Facility-level operations and onboarding management',
            ],
            [
                'key'           => 'facility_executive',
                'name'          => 'Hospital / Clinic Executive Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.kpi.index',
                'description'   => 'Executive overview: KPIs, performance, compliance',
            ],
            [
                'key'           => 'department_manager',
                'name'          => 'Department Manager Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Department-scoped staff and service management',
            ],
            [
                'key'           => 'multi_facility_admin',
                'name'          => 'Multi-Facility Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Cross-facility administration and reporting',
            ],

            // ── Health Organization / NGO ─────────────────────────────────────
            [
                'key'           => 'ngo_health_org',
                'name'          => 'NGO / Health Organization Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Health program management and reporting',
            ],
            [
                'key'           => 'outreach_mobile',
                'name'          => 'Outreach / Mobile Clinic Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Field outreach team and mobile clinic management',
            ],

            // ── Public Health ─────────────────────────────────────────────────
            [
                'key'           => 'public_health_officer',
                'name'          => 'Public Health Officer Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Aggregate and de-identified public health reporting',
            ],
            [
                'key'           => 'public_health_admin',
                'name'          => 'Public Health Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Public health programme administration',
            ],
            [
                'key'           => 'disease_surveillance',
                'name'          => 'Disease Surveillance Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Communicable disease tracking and outbreak response',
            ],

            // ── Developer / API Partner ───────────────────────────────────────
            [
                'key'           => 'developer',
                'name'          => 'Developer Dashboard',
                'portal_prefix' => 'developer',
                'landing_route' => 'portals.developer.dashboard',
                'description'   => 'API developer self-service: apps, keys, webhooks',
            ],
            [
                'key'           => 'api_partner_admin',
                'name'          => 'API Partner Admin Dashboard',
                'portal_prefix' => 'developer',
                'landing_route' => 'portals.developer.dashboard',
                'description'   => 'API partner organization management',
            ],
            [
                'key'           => 'webhook_manager',
                'name'          => 'Webhook Manager Dashboard',
                'portal_prefix' => 'developer',
                'landing_route' => 'portals.developer.dashboard',
                'description'   => 'Webhook configuration and delivery monitoring',
            ],

            // ── Integration Device / Bridge Agent ─────────────────────────────
            [
                'key'           => 'bridge_agent_admin',
                'name'          => 'Bridge Agent Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.bridge',
                'description'   => 'Bridge agent device pairing and sync management',
            ],
            [
                'key'           => 'device_kiosk',
                'name'          => 'Device / Kiosk Dashboard',
                'portal_prefix' => 'lite',
                'landing_route' => 'portals.lite.dashboard',
                'description'   => 'Kiosk / facility device limited interface',
            ],

            // ── OpesCare Lite ─────────────────────────────────────────────────
            [
                'key'           => 'lite',
                'name'          => 'OpesCare Lite Dashboard',
                'portal_prefix' => 'lite',
                'landing_route' => 'portals.lite.dashboard',
                'description'   => 'Simplified portal for low-connectivity facilities',
            ],

            // ── Support & Customer Success ────────────────────────────────────
            [
                'key'           => 'support_agent',
                'name'          => 'Support Agent Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Ticket management and patient record access (high-risk gated)',
            ],
            [
                'key'           => 'support_manager',
                'name'          => 'Support Manager Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Support team oversight and escalation management',
            ],
            [
                'key'           => 'implementation_lead',
                'name'          => 'Implementation Lead Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.onboarding',
                'description'   => 'Facility onboarding and go-live project management',
            ],

            // ── Privacy, Security & Compliance ────────────────────────────────
            [
                'key'           => 'privacy_officer',
                'name'          => 'Privacy Officer Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.security',
                'description'   => 'Data protection and patient privacy management',
            ],
            [
                'key'           => 'security_officer',
                'name'          => 'Security Officer Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.security',
                'description'   => 'Platform security monitoring and incident response',
            ],
            [
                'key'           => 'compliance_audit',
                'name'          => 'Compliance / Audit Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.security.audit_explorer',
                'description'   => 'Compliance auditing and regulatory reporting',
            ],
            [
                'key'           => 'emergency_access_reviewer',
                'name'          => 'Emergency Access Review Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.security.emergency_access',
                'description'   => 'Review and approve emergency record access requests',
            ],

            // ── Data Quality / Reconciliation ─────────────────────────────────
            [
                'key'           => 'data_steward',
                'name'          => 'Data Steward / Reconciliation Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Data quality, reconciliation, and integrity workflows',
            ],
            [
                'key'           => 'data_import',
                'name'          => 'Data Import Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff.data_import.index',
                'description'   => 'Bulk data import, mapping, and validation',
            ],

            // ── Partner Governance ────────────────────────────────────────────
            [
                'key'           => 'partner_admin',
                'name'          => 'Partner Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Partner organization account and contract management',
            ],
            [
                'key'           => 'partner_review',
                'name'          => 'Partner Review Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Partner performance and compliance review',
            ],

            // ── Platform / Super Admin ────────────────────────────────────────
            [
                'key'           => 'super_admin',
                'name'          => 'Super Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.cc',
                'description'   => 'Full platform control centre access',
            ],
            [
                'key'           => 'platform_owner',
                'name'          => 'Platform Owner Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin.cc',
                'description'   => 'Opes Health Systems product owner and platform management',
            ],
            [
                'key'           => 'country_regional_admin',
                'name'          => 'Country / Regional Admin Dashboard',
                'portal_prefix' => 'admin',
                'landing_route' => 'portals.admin',
                'description'   => 'Country or regional OpesCare deployment management',
            ],

            // ── Demo ──────────────────────────────────────────────────────────
            [
                'key'           => 'demo_access',
                'name'          => 'Demo Access Dashboard',
                'portal_prefix' => 'staff',
                'landing_route' => 'portals.staff',
                'description'   => 'Demo mode — isolated from production data',
            ],
        ];

        foreach ($profiles as $data) {
            DashboardProfile::firstOrCreate(
                ['key' => $data['key']],
                [
                    'name'          => $data['name'],
                    'portal_prefix' => $data['portal_prefix'],
                    'landing_route' => $data['landing_route'],
                    'description'   => $data['description'],
                ]
            );
        }
    }
}
