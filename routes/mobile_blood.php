<?php

use App\Http\Controllers\Api\Mobile\MobileBloodController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Blood Finder
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_support.php).
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/blood
|
| All routes use the auth.mobile middleware (AuthenticateMobilePatient) —
| patient_id always comes from the bearer token via $request->attributes,
| never from request input.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('blood/options',  [MobileBloodController::class, 'options']);
    Route::get('blood/search',   [MobileBloodController::class, 'search']);
    Route::get('blood/requests', [MobileBloodController::class, 'index']);
    Route::post('blood/requests', [MobileBloodController::class, 'store']);
    Route::post('blood/requests/{id}/cancel', [MobileBloodController::class, 'cancel']);
});
