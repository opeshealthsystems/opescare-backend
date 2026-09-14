<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Enforce role-to-portal routing.
 *
 * If an authenticated user attempts to access a portal they are not authorised
 * for (e.g. a patient navigating to /facility), they are silently
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
    //
    // Keys are URI prefixes. The operator dashboards moved to clean, short
    // prefixes (/patient, /facility, /admin, /developer, /pharmacy); the keys
    // below mirror those. detectPortalPrefix() returns the FIRST str_starts_with
    // match, so any prefix that is itself a prefix of another MUST be listed
    // first — hence 'facility/team' precedes 'facility'.
    private const PORTAL_ROLES = [
        'patient' => [
            'patient', 'guardian', 'caregiver', 'dependent_manager', 'emergency_contact',
        ],
        // Facility-tier administration of one's OWN facility (team management).
        // Was /portals/facility; now /facility/team, sharing the /facility root
        // with the operations desk below. It is listed FIRST so '/facility/team'
        // (which also starts with 'facility') resolves to the facility-admin
        // roles rather than the clinical roster.
        //
        // It stays its own prefix because the two neighbouring options are both
        // wrong: everything under /admin/* is platform god-mode
        // (RequirePlatformAdmin), so a facility admin gets 403 there, and adding
        // the facility-admin roles to the operations desk would hand them the
        // entire clinical portal. Only the five real facility-administration
        // roles are listed; platform roles are deliberately absent, since they
        // have no facility context to scope a team page to.
        //
        // The pharmacy and lab roles are here too, for the reason given under
        // 'facility/catalogue': a pharmacy or lab manager runs their own
        // tenant's team, and this layer only picks the portal bucket —
        // `capability:facility_team` decides who may act, so a pharmacist who
        // follows the link gets a 403 that names the reason, not a redirect.
        'facility/team' => [
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin',
            // Seeded 2026-09-14 (RolesSeeder); they render the admin sidebars,
            // whose Team / overview links land here.
            'department_manager', 'multi_facility_admin',
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
        ],
        // The facility's own service catalogue ("what we offer"). Listed
        // BEFORE 'facility' for the same reason 'facility/team' is: it also
        // starts with 'facility' and the first matching prefix wins.
        //
        // It needs its own key because its audience straddles the two portals
        // below. A facility administrator publishes the institution's service
        // list but is closed out of the clinical operations desk; a lab or
        // pharmacy manager owns their own menu and lives inside that desk. The
        // one screen has to admit both, and neither existing key does.
        //
        // The list is DELIBERATELY WIDER than the set allowed to use the page.
        // This layer decides which portal bucket a path belongs to, not who may
        // act; `capability:service_catalogue` decides that, from the one map in
        // config/staff_capabilities.php. Naming only the permitted roles here
        // would turn a role refusal into a redirect to another portal, and a
        // labtech who clicked the wrong link would be bounced somewhere else
        // instead of being told plainly that this is not theirs. A refusal is a
        // 403 that names the reason.
        'facility/catalogue' => [
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin',
            'department_manager', 'multi_facility_admin',
            'doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
            'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
            'student_doctor', 'student_nurse', 'intern', 'medical_supervisor',
            'receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer',
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
            'cashier', 'billing_officer', 'finance_manager', 'refund_approver', 'wallet_ops',
            'data_steward', 'reconciliation_officer', 'data_import_officer', 'data_quality_reviewer',
        ],
        // The patient queue (/facility/queue). Same shape and same reasoning as
        // 'facility/catalogue' above: the waiting room is run from inside the
        // operations desk, but the day's measured wait times are the number a
        // facility administrator answers for, and administrators are closed out
        // of the desk. This key admits both; `capability:queue` (the one map in
        // config/staff_capabilities.php) decides who may act, and a refusal is
        // a 403 that names the reason — never a redirect elsewhere.
        'facility/queue' => [
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin',
            'department_manager', 'multi_facility_admin',
            'doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
            'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
            'student_doctor', 'student_nurse', 'intern', 'medical_supervisor',
            'receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer',
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
            'cashier', 'billing_officer', 'finance_manager', 'refund_approver', 'wallet_ops',
            'data_steward', 'reconciliation_officer', 'data_import_officer', 'data_quality_reviewer',
        ],
        // "My facility" settings (/facility/settings): the public listing's
        // name, licence, map position, hours and contacts. Same shape and same
        // reasoning as 'facility/catalogue' above — the page is answered for
        // by a facility administrator (who lives under /admin and is closed
        // out of the desk) AND by a lab manager (who lives inside it), so
        // neither existing key admits both. Listed BEFORE 'facility' because
        // it also starts with 'facility' and the first matching prefix wins.
        //
        // The list is DELIBERATELY WIDER than the set allowed to use the page:
        // this layer only picks the portal bucket; `capability:service_catalogue`
        // (the one map in config/staff_capabilities.php) decides who may act,
        // and a refusal is a 403 that names the reason — never a redirect.
        'facility/settings' => [
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin',
            'department_manager', 'multi_facility_admin',
            'doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
            'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
            'student_doctor', 'student_nurse', 'intern', 'medical_supervisor',
            'receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer',
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
            'cashier', 'billing_officer', 'finance_manager', 'refund_approver', 'wallet_ops',
            'data_steward', 'reconciliation_officer', 'data_import_officer', 'data_quality_reviewer',
        ],
        // Operations desk (was /portals/staff, now /facility).
        'facility' => [
            // Clinical providers
            'doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
            // Nursing
            'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
            // Training
            'student_doctor', 'student_nurse', 'intern', 'medical_supervisor',
            // Front desk & operations
            'receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer',
            // Lab
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
            // Pharmacy
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
            // Billing. 'finance' sat under 'admin' only, yet it renders the
            // receptionist sidebar, whose every link points here — so a finance
            // officer was bounced to /admin by their own navigation. Its
            // capability is billing; it belongs on the operations desk.
            'cashier', 'billing_officer', 'finance_manager', 'refund_approver', 'wallet_ops', 'finance',
            // Data quality
            'data_steward', 'reconciliation_officer', 'data_import_officer', 'data_quality_reviewer',
        ],
        // Phantom portal (module removed from this product) — kept so the role
        // lists stay complete; no such route reaches this middleware.
        'insurance' => [
            'insurance_reviewer', 'insurance_claims', 'insurance_preauth',
            'insurance_admin', 'insurance_finance',
        ],
        'admin' => [
            // Facility administration
            'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo',
            'department_manager', 'branch_admin', 'multi_facility_admin', 'finance',
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
            // bridge_admin was in no list at all, so signing in ended on /login.
            // Admitted to the /admin landing only: everything beneath /admin/* is
            // platform tier, and RequirePlatformAdmin still refuses it there.
            'bridge_admin',
        ],
        'developer' => [
            'developer', 'developer_org_admin', 'api_partner', 'api_technical',
            'webhook_manager', 'sandbox_developer',
            // Both render the developer sidebar (dashboard_profile_key =
            // developer). partner_technical was under 'admin' only, so every
            // link on its own sidebar redirected it away; public_health_api was
            // in no list and landed on /login after signing in.
            'partner_technical', 'public_health_api',
        ],
        // Phantom portal (module removed) — see 'insurance' note above.
        'lite' => [
            'lite_facility', 'lite_staff', 'lite_device', 'lite_offline_sync',
        ],
        'pharmacy' => [
            'pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'medicine_stock', 'dispensing_officer',
        ],
        // Phantom portals (modules removed) — see 'insurance' note above.
        'lab' => [
            'labtech', 'lab_scientist', 'lab_manager', 'lab_validator', 'sample_collection',
        ],
        'healthorg' => [
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
        if ($requestedPrefix === 'patient') {
            $patientRole = $user->role?->name ?? null;
            $allowedPatientRoles = self::PORTAL_ROLES['patient'];
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
            in_array($role, self::PORTAL_ROLES['patient'])    => '/patient',
            in_array($role, self::PORTAL_ROLES['pharmacy'])   => '/pharmacy',
            in_array($role, self::PORTAL_ROLES['facility'])   => '/facility',
            in_array($role, self::PORTAL_ROLES['developer'])  => '/developer',
            in_array($role, self::PORTAL_ROLES['admin'])      => '/admin',
            // Roles whose portal was REMOVED from this product (insurance, lite,
            // healthorg — 'lab' roles all live on /facility and never reach
            // here). They used to fall through to /login: an insurance reviewer
            // signed in successfully and was shown the login page again, with
            // no explanation. That is a dead end dressed as a bug.
            //
            // Sent to the "portal unavailable" page directly rather than via
            // correctPortalFor()'s pathIsFrozen() check, because in Finder the
            // frozen-path map is EMPTY (see bootstrap/app.php) — those modules
            // were deleted, not frozen — so pathIsFrozen() can never return
            // true and the generic check would have redirected to a 404.
            in_array($role, self::PORTAL_ROLES['insurance'])
                || in_array($role, self::PORTAL_ROLES['lite'])
                || in_array($role, self::PORTAL_ROLES['healthorg'])
                                                                       => route('portal.unavailable', absolute: false),
            // Unknown/unassigned role must NOT default into the admin portal.
            // Device roles (kiosk, bridge_agent, facility_device,
            // integration_device) are deliberately here too: they are
            // credentials for machines, not people, and admitting them to a
            // portal would hand a device key the clinical desk. Denied.
            default                                                    => '/login',
        };
    }
}
