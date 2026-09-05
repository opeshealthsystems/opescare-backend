<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorChartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Clinician Routes — the read-only patient chart
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_providers.php and
| routes/mobile_vitals.php):
|
|     // Mobile clinician patient-chart routes — same rationale as above.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_doctor_chart.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the
| automatic '/api' prefix that routes/api.php gets from bootstrap/app.php,
| so it has to be written out here or every client call 404s.
| Verify with: php artisan route:list --path=mobile/doctor/patients
|
| Auth is auth.provider (AuthenticateMobileProvider), NOT auth.mobile: these
| routes read OTHER people's records, so they need a provider token. The
| clinician's user id and facility id come from that token via
| $request->attributes and are never read from the request.
|
| GET only. The chart is a read surface; prescribing lives on
| MobilePrescribingController and clinical documentation is not on the phone
| at all.
|
| Finding a patient is deliberately NOT here: the exact-Health-ID lookup
| already exists as GET /api/mobile/doctor/patients/lookup
| (MobilePrescribingController::lookupPatient, wired in routes/api.php) and
| is reused rather than duplicated. There is no listing route in this file
| and no search of any kind — patient enumeration is forbidden platform-wide.
|
| {patient} is an EXACT patients.id (uuid) or an EXACT Health ID. It is
| matched by equality on the whole identifier; no prefix, no LIKE, no name.
| The route pattern below keeps the segment to the characters both forms use,
| so a malformed id is a 404 from the router rather than a Postgres cast error.
| It also refuses the literal segment `lookup`, so this file can never shadow
| GET /api/mobile/doctor/patients/lookup whatever order the two route files
| happen to be registered in.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.provider')->group(function () {
    Route::prefix('doctor/patients/{patient}')
        ->where(['patient' => '(?!lookup$)[A-Za-z0-9\-]{1,64}'])
        ->group(function () {
            // The chart header — identity, consent standing, whether there
            // are allergies to open. No clinical content.
            Route::get('/', [MobileDoctorChartController::class, 'show']);

            // The sections. Each is its own request, its own data category and
            // its own access_logs row, so the audit says which part of the
            // record was actually disclosed.
            Route::get('allergies', [MobileDoctorChartController::class, 'allergies']);
            Route::get('problems', [MobileDoctorChartController::class, 'problems']);
            Route::get('encounters', [MobileDoctorChartController::class, 'encounters']);
            Route::get('vitals', [MobileDoctorChartController::class, 'vitals']);
            Route::get('prescriptions', [MobileDoctorChartController::class, 'prescriptions']);
            Route::get('diagnoses', [MobileDoctorChartController::class, 'diagnoses']);
            Route::get('orders', [MobileDoctorChartController::class, 'orders']);
        });
});
