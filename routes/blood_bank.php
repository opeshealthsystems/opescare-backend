<?php

use App\Http\Controllers\Api\V1\BloodRequestQueueController;
use App\Http\Middleware\VerifyIntegrationClient;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Blood Bank Routes — the receiving end of a patient's blood request
|
| Loaded from AppServiceProvider::boot() because routes/api.php is SEALED
| (same pattern as routes/mobile_blood.php and routes/clinical.php).
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/v1` in full. Verify with:
|     php artisan route:list --path=blood-bank
|
| VerifyIntegrationClient (sealed) puts `facility_id` on the request
| attributes. The controller resolves the facility's public listings from
| it — facility_id is never read from a header, body or query.
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/blood-bank')->middleware(VerifyIntegrationClient::class)->group(function () {
    Route::get('requests',                    [BloodRequestQueueController::class, 'index']);
    Route::get('requests/{id}',               [BloodRequestQueueController::class, 'show']);
    Route::post('requests/{id}/decision',     [BloodRequestQueueController::class, 'decide']);
});
