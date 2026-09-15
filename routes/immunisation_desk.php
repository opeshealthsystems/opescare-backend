<?php

// Registered from AppServiceProvider. Web desk added from the facility audits
// (docs/audits/*_DASHBOARD_AUDIT_2.md). The middleware stack is declared here next to the routes it gates.

use App\Http\Controllers\MedicalId\ImmunisationDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Immunisation desk — web (HC-2)
|--------------------------------------------------------------------------
|
| Record a dose from a desk PC. Same door as the chart (routes/chart.php):
| capability:clinical_chart, then ClinicalRelationshipGate through
| App\Services\Chart\PatientChartAccess — no basis is a 404 plus a denied
| access-log row. {patientId} is the patient's uuid or EXACT Health ID; there
| is no search.
|
| Rows are immutable: there is no update and no delete route. A dose recorded
| in error is voided (entered_in_error) through the clinician app's void
| endpoint and re-recorded as a new dose.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:clinical_chart'])->group(function () {
    Route::get('/facility/patients/{patientId}/immunizations', [ImmunisationDeskController::class, 'index'])
        ->where('patientId', '[A-Za-z0-9\-]{1,64}')
        ->name('portals.immunisations.index');

    Route::post('/facility/patients/{patientId}/immunizations', [ImmunisationDeskController::class, 'store'])
        ->where('patientId', '[A-Za-z0-9\-]{1,64}')
        ->name('portals.immunisations.store');
});

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:clinical_chart'])->group(function () {
    // The only correction there is: mark a dose entered-in-error (recording
    // facility only), then record the truth as a NEW dose. Never an update.
    Route::post('/facility/patients/{patientId}/immunizations/{recordId}/void', [ImmunisationDeskController::class, 'void'])
        ->where('patientId', '[A-Za-z0-9\-]{1,64}')
        ->whereUuid('recordId')
        ->name('portals.immunisations.void');
});
