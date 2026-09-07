<?php

use App\Http\Controllers\Api\Mobile\MobileBloodController;
use App\Http\Controllers\Api\Mobile\MobileBloodDonorController;
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
    Route::get('blood/requests/{id}', [MobileBloodController::class, 'show']);
    Route::patch('blood/requests/{id}', [MobileBloodController::class, 'update']);
    Route::post('blood/requests/{id}/cancel', [MobileBloodController::class, 'cancel']);

    /*
     | The volunteer donor register (SB-02 search, SB-03 join).
     |
     | `donors/me` and `donors/status` are declared BEFORE `donors` so neither
     | is ever captured as a search parameter, and the search itself requires a
     | blood group — there is no call that lists everybody.
     */
    Route::get('blood/donors/me', [MobileBloodDonorController::class, 'me']);
    Route::post('blood/donors/status', [MobileBloodDonorController::class, 'status']);
    Route::get('blood/donors', [MobileBloodDonorController::class, 'index']);
    Route::post('blood/donors', [MobileBloodDonorController::class, 'store']);
});
