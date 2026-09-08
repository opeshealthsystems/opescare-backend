<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Triage desk — web portal routes
|--------------------------------------------------------------------------
|
| The triage schema (`triage_records`, `triage_vital_signs`, `triage_scores`,
| `triage_reassessments`, `triage_audits`) shipped with the clinical MVP and
| had no route and no screen: nothing in the product could perform or read a
| triage. This file is that door.
|
| Loaded from AppServiceProvider::boot(), like routes/clinical.php and the
| ~35 other per-feature route files, rather than added to the large shared
| routes/web.php — parallel work on separate features cannot then collide in
| one file.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open a facility portal at all
|   capability:triage         the role, AT THE ACTIVE FACILITY, may triage
|                             (config/staff_capabilities.php). A refusal is a
|                             403 `role_forbidden` — never a 404, because the
|                             caller is authenticated and the route's existence
|                             is not a secret.
|
| Facility scope is NOT in this file and must not be: it is a property of the
| DATA, not of the URL. `triage_records` has no facility_id of its own, so
| TriageDeskController joins every read through `visits.facility_id` (read from
| PortalContextService, never from the request). A record belonging to another
| facility therefore 404s — it does not exist for this desk.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:triage'])->group(function () {

    // The queue — everyone triaged at this facility, newest first, with the
    // priority each patient carries RIGHT NOW (latest reassessment wins).
    Route::get('/facility/triage', [\App\Http\Controllers\MedicalId\TriageDeskController::class, 'index'])
        ->name('portals.triage.index');

    // The assessment form. `/new` precedes `/{id}` so it is never swallowed.
    // The patient is found by EXACT Health ID only — no name search, no
    // partial match: this platform does not let a professional enumerate
    // patients.
    Route::get('/facility/triage/new', [\App\Http\Controllers\MedicalId\TriageDeskController::class, 'create'])
        ->name('portals.triage.create');

    Route::post('/facility/triage', [\App\Http\Controllers\MedicalId\TriageDeskController::class, 'store'])
        ->name('portals.triage.store');

    // Read-back for the attending clinician: the assessment, the observations,
    // and the full reassessment history.
    Route::get('/facility/triage/{id}', [\App\Http\Controllers\MedicalId\TriageDeskController::class, 'show'])
        ->name('portals.triage.show');

    // Re-triage. There is deliberately no PUT/PATCH and no delete anywhere in
    // this feature: a triage record is superseded by a reassessment, never
    // overwritten and never removed.
    Route::post('/facility/triage/{id}/reassess', [\App\Http\Controllers\MedicalId\TriageDeskController::class, 'reassess'])
        ->name('portals.triage.reassess');
});
