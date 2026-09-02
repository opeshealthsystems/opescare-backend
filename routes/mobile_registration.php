<?php

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Self-Registration
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/clinical.php and
| routes/mobile_telehealth.php). Public + throttled, mirroring the
| throttle:5,1 already applied to /mobile/auth/* in routes/api.php.
|
| IMPORTANT: unlike routes/api.php (auto-prefixed with "api/" by the
| `api:` key passed to withRouting() in bootstrap/app.php), a route file
| pulled in here via Route::middleware('api')->group() gets the `api`
| middleware group but NOT the "/api" URI prefix — that has to be added
| explicitly, or the route lands at the domain root and every client
| (which always calls "{API_BASE_URL}/mobile/...", already including
| /api) gets a 404. Confirmed locally: without this prefix the route
| registers as "mobile/auth/register", not "api/mobile/auth/register".
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/register', [MobileAuthController::class, 'register']);
});
