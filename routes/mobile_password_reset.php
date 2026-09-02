<?php

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Forgot / Reset Password
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_telehealth.php).
| Public, pre-auth endpoints — throttled the same as the other /mobile/auth/*
| endpoints defined in routes/api.php (5 requests/minute).
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [MobileAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [MobileAuthController::class, 'resetPassword']);
});
