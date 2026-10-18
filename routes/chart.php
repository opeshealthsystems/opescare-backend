<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient chart — web portal routes
|--------------------------------------------------------------------------
|
| `clinical_chart` (config/staff_capabilities.php) is held by every clinical
| role and, until this file, gated nothing on the web: no screen let a doctor
| or nurse open a patient's record from the portal. This is that door.
|
| Loaded from AppServiceProvider::boot(), like routes/triage.php and
| routes/queue.php, rather than added to routes/web.php.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open a facility portal at all
|   capability:clinical_chart the role, AT THE ACTIVE FACILITY, may read charts
|                             — a refusal is 403 `role_forbidden`, never 404
|
| RECORD-LEVEL ACCESS IS NOT IN THIS FILE and must not be. Whether THIS
| clinician may open THIS patient is App\Support\ClinicalRelationshipGate's
| decision, applied per request by PatientChartController through
| App\Services\Chart\PatientChartAccess. A patient the clinician has no basis
| to see does not exist for this desk (404). The chart is patient-centred:
| the Health ID is portable, so ownership by facility is never the test.
|
| READ-ONLY. There is no POST, PUT, PATCH or DELETE here: this pass writes to
| no clinical table and removes nothing.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:clinical_chart'])->group(function () {

    // The lookup form. EXACT Health ID only — no name search, no partial
    // match, no listing: this platform does not let a professional enumerate
    // patients. A hit redirects to the chart; a miss and a refusal look alike.
    Route::get('/facility/patients', [\App\Http\Controllers\MedicalId\PatientChartController::class, 'index'])
        ->name('portals.chart.index');

    // One patient, one tab per request (?tab=summary|encounters|vitals|
    // diagnoses|labs|immunizations|preconsult|triage|documents). Each tab is
    // its own access-log row carrying the data category actually shown.
    // Ask the patient for access when no basis holds. The one write here: it
    // touches consent_requests, never a clinical table. Literal path, declared
    // before {id}. Same role gate as the app's ask (capability:request_access).
    Route::post('/facility/patients/consent-request', [\App\Http\Controllers\MedicalId\PatientChartController::class, 'requestConsent'])
        ->middleware(['capability:request_access', 'throttle:20,1'])
        ->name('portals.chart.consent_request');

    Route::get('/facility/patients/{id}', [\App\Http\Controllers\MedicalId\PatientChartController::class, 'show'])
        ->name('portals.chart.show');
});
