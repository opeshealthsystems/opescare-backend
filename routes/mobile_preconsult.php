<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorPreconsultController;
use App\Http\Controllers\Api\Mobile\MobilePreconsultController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Routes — the PRE-CONSULT QUESTIONNAIRE
|
| Loaded from AppServiceProvider::boot() rather than routes/api.php, the same
| way every other routes/mobile_*.php file is, so parallel work on separate
| features cannot collide in one file:
|
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_preconsult.php'));
|
| IMPORTANT: files loaded this way do NOT inherit the automatic '/api' prefix
| that routes/api.php gets from bootstrap/app.php, so 'api/mobile' is spelled
| out in full below or every client call 404s.
| Verify with: php artisan route:list --path=preconsult
|
| ── The patient's side ───────────────────────────────────────────────────
| auth.mobile (a GROUP: AuthenticateMobilePatient, then ActAsDependent) sets
| `patient_id` on the request attributes from the bearer token — and swaps it
| to a dependent when a guardian presents a valid FamilyLink. So a guardian
| filling in a child's questionnaire needs no code of its own here.
|
| There is NO patient identifier in any of these paths. {appointment} is an
| exact uuid, matched by equality, and the controller resolves it scoped to
| the authenticated patient: someone else's appointment is a 404, identical to
| one that does not exist.
|
| ── The clinician's side ─────────────────────────────────────────────────
| auth.provider + capability:clinical_chart, exactly as mobile_doctor_chart.php:
| this reads ANOTHER person's record, so it needs a provider token and a
| clinical role. Front desk, pharmacy, lab and billing staff get
| 403 role_forbidden before the controller runs. GET only — a clinician reads
| what the patient said; they never edit it.
|
| ── What is NOT here ─────────────────────────────────────────────────────
| No route on this surface writes to `appointments`, and no appointment route
| consults a questionnaire. A patient who never answers is seen on exactly the
| same terms. Held by
| tests/Feature/Architecture/CareIsNeverGatedOnPreconsultTest.php.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::prefix('preconsult/appointments/{appointment}')
        ->where(['appointment' => '[0-9a-fA-F\-]{36}'])
        ->group(function () {
            Route::get('/', [MobilePreconsultController::class, 'show']);
            Route::put('draft', [MobilePreconsultController::class, 'saveDraft']);
            Route::post('submit', [MobilePreconsultController::class, 'submit']);
        });
});

Route::prefix('api/mobile')->middleware(['auth.provider', 'capability:clinical_chart'])->group(function () {
    Route::get('doctor/patients/{patient}/preconsult', [MobileDoctorPreconsultController::class, 'index'])
        ->where(['patient' => '(?!lookup$)[A-Za-z0-9\-]{1,64}']);
});
