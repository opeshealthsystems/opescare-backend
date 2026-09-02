<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Enforce role-to-portal routing.
 *
 * If an authenticated user attempts to access a portal they are not authorised
 * for (e.g. a patient navigating to /portals/staff), they are silently
 * redirected to the correct portal for their role instead of receiving a 403,
 * which produces a better UX while still preventing cross-portal access.
 *
 * Unauthenticated users are redirected to login (the auth middleware fires
 * before this one, but the guard is duplicated here for safety).
 */
class EnsurePortalAccess
{
    /**
     * Portal prefix → role names allowed in that portal.
     * Role names match the canonical names seeded in RolesSeeder.
     */
    private const PORTAL_ROLES = [
        'portals/patient' => [
            'patient', 'guardian', 'caregiver', 'dependent_manager', 'emergency_contact',
        ],
        'portals/staff' => [
            // Clinical providers
            'doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
            // Nursing
            'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
            // Training
            'student_doctor', 'student_nurse', 'intern',
            // Front desk & operations
            'receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer',
            // Lab
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            // Pharmacy
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
            // Billing
            'cashier', 'billing_officer', 'finance_manager', 'refund_approver', 'wallet_ops',
            // Data quality
            'data_steward', 'reconciliation_officer', 'data_import_officer', 'data_quality_reviewer',
        ],
        // Facility-tier administration of one's OWN facility (team management).
        //
        // This exists as its own prefix because the two neighbouring options are
        // both wrong: everything under portals/admin/* is platform god-mode
        // (RequirePlatformAdmin), so a facility admin gets 403 there, and adding
        // the facility-admin roles to portals/staff would hand them the entire
        // clinical portal. Only the five real facility-administration roles are
        // listed; platform roles are deliberately absent, since they have no
        // facility context to scope a team page to.
        'portals/facility' => [
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin',
        ],
        'portals/insurance' => [
            'insurance_reviewer', 'insurance_claims', 'insurance_preauth',
            'insurance_admin', 'insurance_finance',
        ],
        'portals/admin' => [
            // Facility administration
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo',
            'department_manager', 'branch_admin', 'finance',
            // Platform administration
            'platform_admin', 'super_admin', 'product_admin', 'system_admin',
            'legal_admin', 'country_admin', 'regional_admin',
            // Compliance & security (access security ops section)
            'privacy_officer', 'data_protection_officer', 'security_officer',
            'compliance_officer', 'audit_reviewer', 'emergency_access_reviewer',
            // Support & CS
            'support_agent', 'support_manager', 'customer_success',
            'implementation_lead', 'training_support',
            // Partner governance
            'partner_admin', 'partner_reviewer', 'partner_compliance', 'partner_technical',
        ],
        'portals/developer' => [
            'developer', 'developer_org_admin', 'api_partner', 'api_technical',
            'webhook_manager', 'sandbox_developer',
        ],
        'portals/lite' => [
            'lite_facility', 'lite_staff', 'lite_device', 'lite_offline_sync',
        ],
        'portals/pharmacy' => [
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
        ],
        'portals/lab' => [
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
        ],
        'portals/healthorg' => [
            'ngo_admin', 'health_program_manager', 'outreach_team', 'mobile_clinic_team',
        ],
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $requestedPrefix = $this->detectPortalPrefix($request->path());

        // Not a portal path we manage — skip
        if ($requestedPrefix === null) {
            return $next($request);
        }

        // Patient portal: only patient/guardian/caregiver roles allowed.
        // [RBAC FIX] Previously bypassed all role checks — a doctor or pharmacist
        // could access any patient's portal. Now enforced like every other portal.
        if ($requestedPrefix === 'portals/patient') {
            $patientRole = $user->role?->name ?? null;
            $allowedPatientRoles = self::PORTAL_ROLES['portals/patient'];
            if ($patientRole && !in_array($patientRole, $allowedPatientRoles, true)) {
                // Staff/admin user tried to open the patient portal — redirect to their own portal
                return redirect($this->correctPortalFor($patientRole), 302);
            }
            // Users without an explicit role record (e.g. unverified patient accounts)
            // are allowed through — the patient portal itself handles identity gating.
            return $next($request);
        }

        // Resolve the facility-scoped role (W6T2: per-facility RBAC).
        // Use the active_facility_id from the session (set by RequireFacilityContext),
        // with a backward-compatible fallback to the user's primary facility and then
        // the global role record.
        $facilityId = session('active_facility_id') ?? $user->primary_facility_id ?? null;

        $role = null;
        if ($facilityId && method_exists($user, 'roleAtFacility')) {
            $role = $user->roleAtFacility($facilityId);
        } else {
            // Fallback: global role (backward compatibility for users without facility context)
            $role = $user->role ?? null;
        }

        $roleName = $role?->name;

        // No role assigned at this facility (or globally): abort for all non-patient portals.
        if (!$roleName) {
            abort(403, 'Your account has no role assigned at this facility. Contact your administrator.');
        }

        $allowedRoles = self::PORTAL_ROLES[$requestedPrefix] ?? [];

        if (!in_array($roleName, $allowedRoles, true)) {
            return redirect($this->correctPortalFor($roleName), 302);
        }

        return $next($request);
    }

    private function detectPortalPrefix(string $path): ?string
    {
        foreach (array_keys(self::PORTAL_ROLES) as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $prefix;
            }
        }
        return null;
    }

    /**
     * Where does this role belong?
     *
     * Anything this returns is passed straight to redirect(), so it must be a
     * destination that actually answers. A portal frozen out of the launch
     * scope does not: EnforceFeatureFlag 404s it, correctly and deliberately,
     * which used to mean insurance staff signed in and landed on a dead page
     * with a sidebar of dead links. The freeze check below is generic rather
     * than an insurance special case, so the next module to be frozen cannot
     * reintroduce the same dead end.
     */
    private function correctPortalFor(string $role): string
    {
        $portal = $this->portalForRole($role);

        return \App\Support\Features::pathIsFrozen($portal)
            ? route('portal.unavailable', absolute: false)
            : $portal;
    }

    private function portalForRole(string $role): string
    {
        return match (true) {
            in_array($role, self::PORTAL_ROLES['portals/patient'])    => '/portals/patient',
            in_array($role, self::PORTAL_ROLES['portals/pharmacy'])   => '/portals/pharmacy',
            in_array($role, self::PORTAL_ROLES['portals/lab'])        => '/portals/lab',
            in_array($role, self::PORTAL_ROLES['portals/healthorg'])  => '/portals/healthorg',
            in_array($role, self::PORTAL_ROLES['portals/staff'])      => '/portals/staff',
            in_array($role, self::PORTAL_ROLES['portals/insurance'])  => '/portals/insurance',
            in_array($role, self::PORTAL_ROLES['portals/developer'])  => '/portals/developer',
            in_array($role, self::PORTAL_ROLES['portals/lite'])       => '/portals/lite',
            in_array($role, self::PORTAL_ROLES['portals/admin'])      => '/portals/admin',
            // Unknown/unassigned role must NOT default into the admin portal.
            default                                                    => '/login',
        };
    }
}
