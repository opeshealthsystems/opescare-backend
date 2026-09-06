<?php

use App\Http\Controllers\Api\Mobile\MobileEmergencyAccessController;
use App\Http\Controllers\Api\Mobile\MobilePatientEmergencyAccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Break-Glass Routes — emergency access to a record, and the
| patient's view of it
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php (the same pattern as routes/mobile_doctor_chart.php):
|
|     // Mobile break-glass routes — loaded here rather than in routes/api.php
|     // so parallel work on separate features cannot collide in one file.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_emergency_access.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=emergency-access
|
| ── TWO SIDES, TWO GUARDS ────────────────────────────────────────────────
|
| The clinician half runs behind `auth.provider` (AuthenticateMobileProvider):
| these calls read someone else's record with no relationship to justify it,
| so they need a provider token, and the clinician + facility come from that
| token via $request->attributes — never from the body, a header or the query.
|
| The patient half runs behind `auth.mobile` (AuthenticateMobilePatient) and
| is scoped to the token's own patient id. A patient can see and close an
| override on their own record; they cannot see anyone else's.
|
| ── WHY POST IS THROTTLED AND GET IS NOT ─────────────────────────────────
|
| Opening the glass is throttled at 6/min on top of the service's own 24h
| caps (3 per patient, 10 per clinician). The service caps are the governance
| rule; this is the crude stop that keeps a scripted client from generating a
| hundred review cases and a hundred patient alerts in the time it takes a
| human to notice. Reading a grant you already hold is not throttled — a
| clinician refreshing an allergy list mid-resuscitation must never be told to
| slow down.
|
| ── WHAT IS DELIBERATELY NOT HERE ────────────────────────────────────────
|
| No listing of patients, no search, no lookup. Break-glass takes an EXACT
| patients.id or an EXACT Health ID in the body and resolves one row or 404s.
| It is an override, not a directory, and it must never become the enumeration
| surface the rest of the platform refuses to be.
|--------------------------------------------------------------------------
*/

/** patients.id / grant ids are uuids; anything else is a 404 from the router. */
$uuid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

Route::prefix('api/mobile')->group(function () use ($uuid) {

    // ── Clinician: break the glass, hold the grant, read under it ─────────
    // Role gate: `capability:emergency_access` (config/staff_capabilities.php) —
    // doctors, nurses and medical supervisors only. Trainees, front desk, lab,
    // pharmacy and billing get 403 {error_code: role_forbidden} before the
    // service's own caps are consulted. Listed AFTER auth.provider because it
    // reads the provider_user_id attribute that middleware sets.
    Route::middleware(['auth.provider', 'capability:emergency_access'])->prefix('doctor/emergency-access')->group(function () use ($uuid) {
        // Break the glass. Body: { patient, reason, acknowledged }.
        Route::post('/', [MobileEmergencyAccessController::class, 'open'])
            ->middleware('throttle:6,1');

        // The grants this clinician is holding right now, at this facility.
        Route::get('/', [MobileEmergencyAccessController::class, 'active']);

        // One grant's standing — metadata only, safe after expiry.
        Route::get('{grant}', [MobileEmergencyAccessController::class, 'show'])
            ->where('grant', $uuid);

        // The emergency profile, while the grant is live. 403 once it is not.
        Route::get('{grant}/profile', [MobileEmergencyAccessController::class, 'profile'])
            ->where('grant', $uuid);
    });

    // ── Patient: see every override on your record, and close a live one ──
    Route::middleware('auth.mobile')->prefix('emergency-access')->group(function () use ($uuid) {
        Route::get('/', [MobilePatientEmergencyAccessController::class, 'index']);

        Route::post('{grant}/revoke', [MobilePatientEmergencyAccessController::class, 'revoke'])
            ->where('grant', $uuid)
            ->middleware('throttle:20,1');
    });
});
