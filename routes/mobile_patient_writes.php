<?php

use App\Http\Controllers\Api\Mobile\PatientRecordedVitalsController;
use App\Http\Controllers\Api\Mobile\PatientReferralRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — the writes that are genuinely the patient's
|
| Seven patient domains ship as one GET each. These are the two write paths a
| patient legitimately owns:
|
|   1. Their own vitals log — a reading THEY took, at home, on their own
|      device. Never a clinical measurement; see
|      App\Http\Controllers\Api\Mobile\PatientRecordedVitalsController and the
|      `patient_recorded_vitals` migration for why it is a separate table.
|
|   2. A request FOR a referral — a request, not a referral. It creates
|      nothing in `referral_cases`; a clinician must act on it first. See
|      App\Http\Controllers\Api\Mobile\PatientReferralRequestController.
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php).
|
| REGISTRATION — add to AppServiceProvider::boot(), after the
| routes/mobile_vitals.php group:
|
|     // Mobile patient WRITE routes — the patient's own vitals log and their
|     // request for a referral. Same rationale as mobile_vitals.php.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_patient_writes.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/patient-vitals
|     php artisan route:list --path=mobile/referral-requests
|
| auth.mobile (AuthenticateMobilePatient) sets `patient_id` on the request
| attributes from the bearer token. Both controllers read it from there and
| nowhere else — there is no patient identifier in the path, query or body, so
| one patient can never write to another's record, and a `patient_id` posted in
| the body is not overridden but simply never read.
|
| The throttle is on the writes because they are writes: a runaway client
| looping a POST would otherwise fill a patient's own record with noise. The
| reads are left on the default API budget.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {

    /*
     * The patient's own vitals log.
     *
     * There is no PUT/PATCH by design: a recorded reading is an event, so a
     * mistyped value is voided with a reason and re-entered rather than edited
     * in place — the same amend/void discipline the clinical modules follow.
     */
    Route::get('patient-vitals/options', [PatientRecordedVitalsController::class, 'options']);
    Route::get('patient-vitals', [PatientRecordedVitalsController::class, 'index']);

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('patient-vitals', [PatientRecordedVitalsController::class, 'store']);
        Route::post('patient-vitals/{id}/void', [PatientRecordedVitalsController::class, 'void']);
    });

    /*
     * Requests FOR a referral. Distinct from GET /api/mobile/referrals
     * (MobileReferralController), which lists the referrals a clinician
     * actually issued.
     */
    Route::get('referral-requests/options', [PatientReferralRequestController::class, 'options']);
    Route::get('referral-requests', [PatientReferralRequestController::class, 'index']);

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('referral-requests', [PatientReferralRequestController::class, 'store']);
        Route::post('referral-requests/{id}/withdraw', [PatientReferralRequestController::class, 'withdraw']);
    });
});
