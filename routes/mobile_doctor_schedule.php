<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — the clinician's day, diary and appointments
|
| Loaded from AppServiceProvider::boot() to avoid touching routes/api.php
| (same pattern as routes/mobile_providers.php and routes/mobile_vitals.php):
|
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_doctor_schedule.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=mobile/doctor
|
| Auth is auth.provider (AuthenticateMobileProvider), which stamps
| `provider_user_id` and `provider_facility_id` onto the request attributes
| from the bearer token. The controller reads the facility and the clinician
| from there and from nowhere else — never from the body, query, header or
| session. There is no route parameter here that names either one.
|
| These are the routes MobilePrescribingController does NOT already serve;
| /doctor/patients/lookup and /doctor/prescriptions stay in routes/api.php.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.provider')->group(function () {

    // ── The clinician ────────────────────────────────────────────────────
    Route::get('doctor/me', [MobileDoctorScheduleController::class, 'me']);
    Route::get('doctor/home', [MobileDoctorScheduleController::class, 'home']);

    // ── Role gate ────────────────────────────────────────────────────────
    // Everything below is for roles that hold `schedule` — clinicians and
    // the front desk (config/staff_capabilities.php). Pharmacy, lab and
    // billing staff get 403 {error_code: role_forbidden}. doctor/me and
    // doctor/home above stay open to every professional: they are where
    // the app lands, and home is self-scoped anyway. The gate is nested
    // inside the auth.provider group so it runs after the attribute it
    // reads (provider_user_id) has been set.
    Route::middleware('capability:schedule')->group(function () {
        // ── Appointment requests ─────────────────────────────────────────────
        // Registered BEFORE the /doctor/appointments/{id} routes: 'appointment-requests'
        // is a distinct segment, but keeping the literal paths first makes the
        // ordering safe against any future wildcard on the appointments prefix.
        Route::get('doctor/appointment-requests', [MobileDoctorScheduleController::class, 'requests']);
        Route::post('doctor/appointment-requests/{id}/accept', [MobileDoctorScheduleController::class, 'accept'])
            ->whereUuid('id');
        Route::post('doctor/appointment-requests/{id}/decline', [MobileDoctorScheduleController::class, 'decline'])
            ->whereUuid('id');

        // ── The diary ────────────────────────────────────────────────────────
        // 'schedule' is the name the client contract's endpoint table uses for the
        // same list; both are served so neither spelling 404s.
        Route::get('doctor/schedule', [MobileDoctorScheduleController::class, 'index']);
        Route::get('doctor/appointments', [MobileDoctorScheduleController::class, 'index']);

        // appointments.id is a uuid column. Without whereUuid a malformed id reaches
        // Postgres and raises 22P02, which surfaces as a 500; the router answering
        // 404 is the honest result.
        Route::get('doctor/appointments/{id}', [MobileDoctorScheduleController::class, 'show'])
            ->whereUuid('id');
        Route::put('doctor/appointments/{id}', [MobileDoctorScheduleController::class, 'reschedule'])
            ->whereUuid('id');
        Route::patch('doctor/appointments/{id}/status', [MobileDoctorScheduleController::class, 'setStatus'])
            ->whereUuid('id');
        Route::post('doctor/appointments/{id}/check-in', [MobileDoctorScheduleController::class, 'checkIn'])
            ->whereUuid('id');
        Route::post('doctor/appointments/{id}/no-show', [MobileDoctorScheduleController::class, 'noShow'])
            ->whereUuid('id');
        Route::post('doctor/appointments/{id}/cancel', [MobileDoctorScheduleController::class, 'cancel'])
            ->whereUuid('id');
    });
});
