<?php

use App\Http\Controllers\MedicalId\FacilityAdminDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Facility administration desk — web portal routes
|--------------------------------------------------------------------------
|
| Registered from AppServiceProvider::boot(), like routes/lab.php. Built from
| docs/HOSPITAL_ADMIN_DASHBOARD_AUDIT.md (H-1, H-2).
|
| WHY THESE PATHS. EnsurePortalAccess admits the facility-administration roles
| (facility_admin, clinic_admin, hospital_admin, facility_ceo, branch_admin)
| to exactly four places: the bare /admin landing, /facility/team,
| /facility/catalogue and /facility/queue. Everything under /admin/* is
| platform god-mode (RequirePlatformAdmin). So the administrator's overview
| and the member pages live under /facility/team/…, the bucket that already
| means "run my own facility's people".
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open this portal bucket at all
|   facility.context          ONE rule for the whole facility-admin tier: a
|                             single-facility user is resolved silently, a
|                             user posted at more than one DISTINCT facility
|                             is sent to /select-facility. /facility/team and
|                             /facility/catalogue already ran it; without it
|                             here the same person got the overview at their
|                             primary facility and the selector on the team
|                             page (tests/Feature/Facility/FacilityContextAgreementTest).
|   capability:facility_team  the role, AT THE ACTIVE FACILITY, runs the team
|                             (config/staff_capabilities.php). A refusal is a
|                             403 `role_forbidden` — never a 404. No new
|                             capability was invented: this desk is the team
|                             page's own scope.
|
| Facility scope is NOT in this file and must not be: the controller reads it
| from PortalContextService, never from the request, and a member of another
| facility 404s.
|
| Every write is a POST that appends an AuditEvent (before/after state); there
| is no PUT/PATCH and no delete. A posting is ended, never erased.
|
| ROUTE ORDER: `/overview` and `/members/{userId}` share no literal segment,
| and /facility/team itself is declared in routes/web.php, so nothing here is
| swallowed by a wildcard.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'facility.context', 'capability:facility_team'])->group(function () {

    Route::get('/facility/team/overview', [FacilityAdminDeskController::class, 'overview'])
        ->name('portals.facility.admin.overview');

    // Read-only invoice list for the people who answer for the till but do
    // not run it. `capability:billing` gates the WRITES of /facility/billing
    // (issue, pay, void, refund) in the same group as its reads, so it is not
    // widened to administrators; this page reads `invoices` for the session
    // facility and offers no action. Nothing here gates care on payment.
    Route::get('/facility/team/overview/invoices', [FacilityAdminDeskController::class, 'invoices'])
        ->name('portals.facility.admin.invoices');

    // Departments as they exist today: `staff_profiles.department` is free
    // text, so this is a grouping of this facility's active staff by that
    // string — no Department model, no schema decision.
    Route::get('/facility/team/overview/departments', [FacilityAdminDeskController::class, 'departments'])
        ->name('portals.facility.admin.departments');

    Route::get('/facility/team/members/{userId}', [FacilityAdminDeskController::class, 'member'])
        ->name('portals.facility.admin.member');

    Route::post('/facility/team/members/{userId}/role', [FacilityAdminDeskController::class, 'changeRole'])
        ->name('portals.facility.admin.member.role');

    Route::post('/facility/team/members/{userId}/deactivate', [FacilityAdminDeskController::class, 'deactivate'])
        ->name('portals.facility.admin.member.deactivate');

    Route::post('/facility/team/members/{userId}/reactivate', [FacilityAdminDeskController::class, 'reactivate'])
        ->name('portals.facility.admin.member.reactivate');
});
