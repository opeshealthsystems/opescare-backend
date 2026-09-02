<?php

use App\Http\Controllers\Api\Mobile\MobileProviderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Clinician / Specialist Directory
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_telehealth.php and
| routes/mobile_support.php).
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the
| automatic '/api' prefix that routes/api.php gets from bootstrap/app.php,
| so it has to be written out here or every client call 404s.
| Verify with: php artisan route:list --path=mobile/providers
|
| Auth is auth.mobile (AuthenticateMobilePatient) — patient_id always comes
| from the bearer token via $request->attributes, never from request input.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    // Clinicians at one directory facility (route param is a care_facilities id,
    // matching /facilities/{id} and /facilities/{id}/slots).
    Route::get('facilities/{careFacilityId}/providers', [MobileProviderController::class, 'index']);

    // One clinician's directory profile + their next open slots.
    // {providerId} is a users.id — the same value carried on a slot's provider_id.
    Route::get('providers/{providerId}', [MobileProviderController::class, 'show']);
});
