<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorReferralController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — clinician-to-clinician REFERRALS
|
| Loaded from AppServiceProvider::boot() to avoid touching routes/api.php
| (same pattern as routes/mobile_doctor_chart.php and mobile_doctor_schedule.php):
|
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_doctor_referrals.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=referral
|
| Auth is auth.provider (AuthenticateMobileProvider), which stamps
| `provider_user_id` and `provider_facility_id` onto the request attributes
| from the bearer token. The controller reads the referring facility and the
| referring clinician from there and from nowhere else — never from the body,
| query, header or session. There is no route parameter here that names either
| one, and `referring_facility_id` in a request body is not read.
|
| The whole file sits behind `capability:clinical_chart`. Referring a patient
| is a clinical act on their chart — the same authority that opens the record
| is the one that may hand it on — so no new capability is invented for it.
| Pharmacy, lab, billing and front-desk roles get 403 {error_code:
| role_forbidden} from RequireStaffCapability, never a 404.
|
| These routes COMPLEMENT the two patient-side surfaces and replace neither:
|   • MobileReferralController          — the patient reads their own referrals
|                                         (routes/api.php, auth.mobile)
|   • PatientReferralRequestController  — the patient ASKS to be referred
|                                         (routes/mobile_patient_writes.php)
| A patient's request only ever becomes a referral through the clinician-side
| store() below, which links it back through referral_case_id — the one path
| the CHECK constraint on patient_referral_requests permits.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware(['auth.provider', 'capability:clinical_chart'])->group(function () {

    // ── The facility's referral book ─────────────────────────────────────
    // Scoped to the token's facility in the controller; there is no parameter
    // that widens it to another facility, so this cannot be turned into a
    // cross-facility listing. Registered BEFORE the /{id} route so the literal
    // path can never be swallowed by the wildcard.
    Route::get('doctor/referrals', [MobileDoctorReferralController::class, 'index']);

    // referral_cases.id is a uuid column. Without whereUuid a malformed id
    // reaches Postgres and raises 22P02, which surfaces as a 500; the router
    // answering 404 is the honest result.
    Route::get('doctor/referrals/{id}', [MobileDoctorReferralController::class, 'show'])
        ->whereUuid('id');

    // ── The lifecycle ────────────────────────────────────────────────────
    // draft → sent → accepted → completed, or cancelled / rejected / expired.
    // Which PARTY may make each move (referring vs receiving facility) and
    // which moves are legal at all are enforced in the controller, not here —
    // an illegal move is 409, and nothing on this surface deletes a referral.
    Route::post('doctor/referrals/{id}/send', [MobileDoctorReferralController::class, 'send'])
        ->whereUuid('id');
    Route::post('doctor/referrals/{id}/accept', [MobileDoctorReferralController::class, 'accept'])
        ->whereUuid('id');
    Route::post('doctor/referrals/{id}/reject', [MobileDoctorReferralController::class, 'reject'])
        ->whereUuid('id');
    Route::post('doctor/referrals/{id}/complete', [MobileDoctorReferralController::class, 'complete'])
        ->whereUuid('id');
    Route::post('doctor/referrals/{id}/cancel', [MobileDoctorReferralController::class, 'cancel'])
        ->whereUuid('id');

    // ── Raised from the chart ────────────────────────────────────────────
    // {id} is the patient's id or their EXACT Health ID — there is no patient
    // search anywhere on this surface. The `(?!lookup$|scan$)` guard keeps the
    // literal /doctor/patients/lookup and /scan paths in routes/api.php from
    // being swallowed, exactly as routes/mobile_doctor_chart.php does.
    Route::post('doctor/patients/{id}/referrals', [MobileDoctorReferralController::class, 'store'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');

    // The patient's own asks, read in the context of their chart rather than
    // as a national queue — see the controller docblock for why there is no
    // facility-wide inbox here.
    Route::get('doctor/patients/{id}/referral-requests', [MobileDoctorReferralController::class, 'patientRequests'])
        ->where('id', '(?!lookup$|scan$)[A-Za-z0-9\-]{1,64}');
});
