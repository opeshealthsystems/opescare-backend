<?php

use App\Http\Controllers\Api\Mobile\Desk\DeskAppointmentController;
use App\Http\Controllers\Api\Mobile\Desk\DeskController;
use App\Http\Controllers\Api\Mobile\Desk\DeskPatientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — the Front Desk
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_doctor_schedule.php):
|
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_front_desk.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=api/mobile/desk
|
| Auth is auth.provider (AuthenticateMobileProvider), which stamps
| `provider_user_id` and `provider_facility_id` onto the request attributes
| from the bearer token. The desk controllers read the facility and the actor
| from there and from nowhere else — never from the body, query, header or
| session. No route here names a facility.
|
| Role gate: `capability:front_desk` (config/staff_capabilities.php) — the
| receptionist, front-desk officer, appointment coordinator, queue manager
| and records officer. Clinicians, pharmacy, lab and billing get 403
| {error_code: role_forbidden}; a clinician's own diary is
| routes/mobile_doctor_schedule.php. Listed AFTER auth.provider because it
| reads the provider_user_id attribute that middleware sets.
|
| Wire vocabulary for `status` (list filter and every appointment answered):
| scheduled | confirmed | checked_in | in_progress | completed | cancelled |
| no_show — see FrontDeskService::WIRE_STATUS.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/desk')->middleware(['auth.provider', 'capability:front_desk'])->group(function () {

    // ── Reading the book ─────────────────────────────────────────────────
    Route::get('home', [DeskController::class, 'home']);
    Route::get('appointments', [DeskController::class, 'appointments']);
    Route::get('providers', [DeskController::class, 'providers']);

    // ── Writing the book ─────────────────────────────────────────────────
    Route::post('appointments', [DeskAppointmentController::class, 'store']);

    // appointments.id is a uuid column. Without whereUuid a malformed id
    // reaches Postgres and raises 22P02, which surfaces as a 500; the router
    // answering 404 is the honest result.
    Route::put('appointments/{id}', [DeskAppointmentController::class, 'reschedule'])
        ->whereUuid('id');
    Route::patch('appointments/{id}/route', [DeskAppointmentController::class, 'route'])
        ->whereUuid('id');
    Route::post('appointments/{id}/confirm', [DeskAppointmentController::class, 'confirm'])
        ->whereUuid('id');
    Route::post('appointments/{id}/check-in', [DeskAppointmentController::class, 'checkIn'])
        ->whereUuid('id');
    Route::post('appointments/{id}/no-show', [DeskAppointmentController::class, 'noShow'])
        ->whereUuid('id');
    Route::post('appointments/{id}/cancel', [DeskAppointmentController::class, 'cancel'])
        ->whereUuid('id');

    // ── Walk-ins ─────────────────────────────────────────────────────────
    Route::post('patients', [DeskPatientController::class, 'store']);
});
