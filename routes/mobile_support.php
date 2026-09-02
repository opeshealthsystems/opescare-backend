<?php

use App\Http\Controllers\Api\Mobile\MobileSupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Help & Support
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_telehealth.php). All
| routes use the auth.mobile middleware (AuthenticateMobilePatient) —
| patient_id always comes from the bearer token via $request->attributes,
| never from request input.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('support/contact', [MobileSupportController::class, 'contact']);
    Route::get('support/tickets', [MobileSupportController::class, 'index']);
    Route::post('support/tickets', [MobileSupportController::class, 'store']);
    Route::get('support/tickets/{id}', [MobileSupportController::class, 'show']);
    Route::post('support/tickets/{id}/messages', [MobileSupportController::class, 'addMessage']);
});
