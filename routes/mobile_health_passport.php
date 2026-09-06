<?php

use App\Http\Controllers\Api\Mobile\MobileHealthPassportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Health Passport
|
| Loaded from AppServiceProvider::boot() rather than routes/api.php, which is
| sealed. NOTE the explicit 'api/mobile' prefix: files loaded through
| Route::middleware('api')->group(base_path(...)) do NOT inherit the '/api'
| prefix bootstrap/app.php gives routes/api.php, so it is written out here or
| every client call 404s. Verify with:
|   php artisan route:list --path=health-passport
|
| Auth is `auth.mobile`, the group that also carries ActAsDependent — so a
| guardian holding an active link reads the DEPENDENT'S passport by sending the
| X-OpesCare-Dependent header, with the same rules as every other record route.
| The patient is taken from the request attribute the middleware stamped and
| from nowhere else.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('health-passport', [MobileHealthPassportController::class, 'show']);

    // The page id is one of the thirty in HealthPassportBuilder::PAGES; the
    // constraint keeps anything else from reaching the builder as a lookup.
    Route::get('health-passport/pages/{pageId}', [MobileHealthPassportController::class, 'page'])
        ->where('pageId', '[a-z-]{3,32}');
});
