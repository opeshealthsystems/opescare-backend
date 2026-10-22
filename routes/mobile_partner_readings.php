<?php

use App\Http\Controllers\Api\Mobile\MobilePartnerReadingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Partner-reported readings
|
| Loaded from AppServiceProvider::boot() (same pattern as mobile_vitals.php),
| so the prefix spells out `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/records/partner-readings
|
| Read-only over fhir_partner_* through PartnerReadingsService. The patient
| comes from the bearer token only.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('records/partner-readings', [MobilePartnerReadingsController::class, 'index'])
        ->name('mobile.records.partner_readings');
});
