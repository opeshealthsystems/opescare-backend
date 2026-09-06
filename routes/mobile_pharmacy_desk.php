<?php

use App\Http\Controllers\Api\Mobile\Pharmacy\PharmacyDeskController;
use App\Http\Controllers\Api\Mobile\Pharmacy\PharmacyReservationController;
use App\Http\Controllers\Api\Mobile\Pharmacy\PharmacyStockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — Pharmacy Desk
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_doctor_results.php).
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the
| automatic '/api' prefix that routes/api.php gets from bootstrap/app.php,
| so it has to be written out here or every client call 404s.
| Verify with: php artisan route:list --path=api/mobile/pharmacy-desk
|
| WHY `pharmacy-desk` AND NOT `pharmacy`: the patient Medicine Finder already
| registers GET /api/mobile/pharmacy/reservations and
| POST /api/mobile/pharmacy/reservations/{id}/cancel in routes/api.php behind
| the PATIENT middleware. Laravel keeps ONE handler per method+URI — whichever
| registers last — so mounting the desk on the same prefix would silently hand
| one side's reservations to the other. A sibling prefix costs the app one
| base-path constant and keeps both surfaces whole.
|
| Auth is auth.provider (AuthenticateMobileProvider): the pharmacist's user id
| and facility_id always come from the bearer token via $request->attributes,
| never from the request body, query string or a header.
|
| Role gates (config/staff_capabilities.php), listed AFTER auth.provider
| because they read the provider_user_id attribute that middleware sets:
|   - counter work    → capability:dispense         (the pharmacy roles)
|   - stock           → capability:pharmacy_stock   (pharmacy roles + medicine_stock)
| Everyone else gets 403 {error_code: role_forbidden}.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/pharmacy-desk')->middleware(['auth.provider'])->group(function () {

    Route::middleware('capability:dispense')->group(function () {
        Route::get('home', [PharmacyDeskController::class, 'home']);

        // Static segment FIRST: `lookup` would otherwise never be reached if a
        // `prescriptions/{id}` GET were ever added below it.
        Route::get('prescriptions/lookup', [PharmacyDeskController::class, 'lookup']);
        Route::get('prescriptions', [PharmacyDeskController::class, 'prescriptions']);

        // whereUuid keeps a malformed id from reaching Postgres as a bad uuid
        // cast (22P02), which would surface as a 500 instead of a 404.
        Route::post('prescriptions/{id}/accept', [PharmacyDeskController::class, 'accept'])->whereUuid('id');
        Route::post('prescriptions/{id}/ready', [PharmacyDeskController::class, 'ready'])->whereUuid('id');
        Route::post('prescriptions/{id}/dispense', [PharmacyDeskController::class, 'dispense'])->whereUuid('id');

        Route::get('reservations', [PharmacyReservationController::class, 'index']);
        Route::post('reservations/{id}/accept', [PharmacyReservationController::class, 'accept'])->whereUuid('id');
        Route::post('reservations/{id}/ready', [PharmacyReservationController::class, 'ready'])->whereUuid('id');
        Route::post('reservations/{id}/collected', [PharmacyReservationController::class, 'collected'])->whereUuid('id');
        Route::post('reservations/{id}/dispatch', [PharmacyReservationController::class, 'dispatch'])->whereUuid('id');
        Route::post('reservations/{id}/delivered', [PharmacyReservationController::class, 'delivered'])->whereUuid('id');
        Route::post('reservations/{id}/cancel', [PharmacyReservationController::class, 'cancel'])->whereUuid('id');
    });

    Route::middleware('capability:pharmacy_stock')->group(function () {
        Route::get('stock', [PharmacyStockController::class, 'index']);
        Route::post('stock/{id}/adjust', [PharmacyStockController::class, 'adjust'])->whereUuid('id');
    });
});
