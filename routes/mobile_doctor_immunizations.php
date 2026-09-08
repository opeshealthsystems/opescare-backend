<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorImmunizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — the clinician IMMUNISATION write path
|
| Loaded from AppServiceProvider::boot() to avoid touching routes/api.php
| (same pattern as routes/mobile_doctor_referrals.php and mobile_doctor_chart.php):
|
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_doctor_immunizations.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=immuniz
|
| Auth is auth.provider (AuthenticateMobileProvider), which stamps
| `provider_user_id` and `provider_facility_id` onto the request attributes
| from the bearer token. The controller reads the administering facility and
| the recording clinician from there and from nowhere else — never from the
| body, query, header or session. `facility_id` and `administered_by_id` in a
| request body are not read, which is precisely where the platform's own
| partner-API immunisation endpoint (Api/V1/Immunization/ImmunizationController)
| got it wrong: it takes `administered_by_id` straight off the body.
|
| The whole file sits behind `capability:clinical_chart`. Giving a vaccine is
| a clinical act written onto the chart — the same authority that opens the
| record is the one that may add to it — so no new capability is invented for
| it. Pharmacy, lab, billing and front-desk roles get 403 {error_code:
| role_forbidden} from RequireStaffCapability, never a 404.
|
| This surface COMPLEMENTS the patient's own read and replaces neither:
|   • MobilePatientController@getImmunizations — the patient reads their own
|     card (routes/api.php:165, auth.mobile). It already filters
|     `status != entered_in_error`, which is exactly why void() below retracts
|     a dose by moving its status rather than deleting the row: the retraction
|     propagates to the patient's card with no change to that controller.
|   • The patient portal immunisations page — untouched.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware(['auth.provider', 'capability:clinical_chart'])->group(function () {

    // ── The chart ────────────────────────────────────────────────────────
    // {id} is the patient's id or their EXACT Health ID. There is no patient
    // search anywhere on this surface. The `(?!lookup$|scan$)` guard keeps the
    // literal /doctor/patients/lookup and /scan paths in routes/api.php from
    // being swallowed, exactly as routes/mobile_doctor_chart.php does.
    Route::get('doctor/patients/{id}/immunizations', [MobileDoctorImmunizationController::class, 'index'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');

    // Record a dose. THE gap this file exists to close.
    Route::post('doctor/patients/{id}/immunizations', [MobileDoctorImmunizationController::class, 'store'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');

    // The forward course — vaccination_schedules.
    Route::get('doctor/patients/{id}/vaccination-schedule', [MobileDoctorImmunizationController::class, 'patientSchedule'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');
    Route::post('doctor/patients/{id}/vaccination-schedule', [MobileDoctorImmunizationController::class, 'scheduleVaccines'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');

    // ── One dose ─────────────────────────────────────────────────────────
    // immunization_records.id is a uuid column. Without whereUuid a malformed
    // id reaches Postgres and raises 22P02, which surfaces as a 500; the router
    // answering 404 is the honest result.
    Route::get('doctor/immunizations/{id}', [MobileDoctorImmunizationController::class, 'show'])
        ->whereUuid('id');

    // A dose recorded in error is VOIDED (entered-in-error) with a reason, not
    // deleted and not silently edited — the same amend/void discipline
    // PatientRecordedVitalsController@void follows. There is deliberately no
    // update and no destroy route in this file.
    Route::post('doctor/immunizations/{id}/void', [MobileDoctorImmunizationController::class, 'void'])
        ->whereUuid('id');

    // AEFI — an adverse event following immunisation. A safety signal, so it
    // is appendable from wherever the patient presents, not only from the
    // facility that gave the dose. See the controller docblock.
    Route::post('doctor/immunizations/{id}/adverse-reactions', [MobileDoctorImmunizationController::class, 'reportAdverseReaction'])
        ->whereUuid('id');
});
