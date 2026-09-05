<?php

use App\Http\Controllers\Api\Mobile\MobileAppointmentRescheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Appointment reschedule
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php).
|
| Registration line to add to AppServiceProvider::boot(), next to the other
| mobile route groups:
|
|     // Mobile patient appointment reschedule routes — same rationale.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_appointments_reschedule.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/appointments
|
| auth.mobile (AuthenticateMobilePatient) sets `patient_id` on the request
| attributes from the bearer token. The controller reads it from there and
| nowhere else — there is no patient identifier in the path, query or body,
| and the appointment is loaded with `where('patient_id', …)` in the query
| itself, so one patient can never address another's appointment. It cannot
| even load the row to compare it.
|
| Both routes sit UNDER an existing path (`appointments/{id}`) that
| routes/api.php already serves with MobileAppointmentController. Laravel
| matches on the full path including the trailing segment, so
| `appointments/{id}/reschedule` and `appointments/{id}` are distinct routes
| and neither shadows the other. Because this file is registered AFTER
| routes/api.php, an accidental collision would resolve to the api.php route,
| never to this one — the safe direction.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    // The slot picker's data: whether this appointment may be moved (and if
    // not, exactly why) plus the open times at its OWN facility. The facility
    // is resolved server-side from the appointment — the mobile appointment
    // payload carries only `facility_name`, and letting the client nominate a
    // facility is how a reschedule would walk a booking to another hospital.
    Route::get('appointments/{id}/reschedule-options', [MobileAppointmentRescheduleController::class, 'options'])
        ->name('mobile.appointments.reschedule-options');

    // The atomic swap. Body: appointment_slot_id (required), reason (optional).
    // Nothing else is read — patient from the token, facility from the
    // appointment, time from the locked slot row.
    Route::post('appointments/{id}/reschedule', [MobileAppointmentRescheduleController::class, 'reschedule'])
        ->name('mobile.appointments.reschedule');
});
