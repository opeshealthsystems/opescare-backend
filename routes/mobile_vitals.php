<?php

use App\Http\Controllers\Api\Mobile\MobileVitalsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Health Vitals
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_blood.php).
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/vitals
|
| auth.mobile (AuthenticateMobilePatient) sets `patient_id` on the request
| attributes from the bearer token. The controller reads it from there and
| nowhere else — there is no patient identifier in the path, query or body,
| so one patient cannot address another's vitals.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('vitals/latest', [MobileVitalsController::class, 'latest']);
});
