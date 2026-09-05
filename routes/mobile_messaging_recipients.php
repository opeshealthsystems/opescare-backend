<?php

use App\Http\Controllers\Api\Mobile\MobileMessagingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Messaging recipients
|
| Loaded from AppServiceProvider::boot() rather than routes/api.php, which is
| sealed (same pattern as routes/mobile_providers.php).
|
| ONE route: "who am I allowed to write to?". The three send paths themselves
| (appointment / facility / clinician) all POST to the existing
| /api/mobile/messages/threads in routes/api.php — no new send surface.
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix routes/api.php gets from bootstrap/app.php.
| Verify with: php artisan route:list --path=mobile/messages
|
| Auth is auth.mobile (AuthenticateMobilePatient) — patient_id always comes
| from the bearer token via $request->attributes, never from request input.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('messages/recipients', [MobileMessagingController::class, 'recipients']);
});
