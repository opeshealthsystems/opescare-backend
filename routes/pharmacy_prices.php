<?php

/*
|--------------------------------------------------------------------------
| Pharmacy price list (web)
|--------------------------------------------------------------------------
|
| The selling price a patient sees, set per medicine in whole francs and
| carried to the public availability row. Registered from AppServiceProvider;
| the middleware stack is declared here next to the routes it gates.
*/

use App\Http\Controllers\MedicalId\PharmacyPriceListController;
use Illuminate\Support\Facades\Route;

/*
| Same gate as routes/pharmacy_ops.php, in the same order: the ordinary portal
| preamble, then `capability:pharmacy_stock` (403 `role_forbidden`) BEFORE
| portal.access so a wrong-bucket role's redirect cannot hide the refusal.
| Facility scope comes from PortalContextService only; another pharmacy's
| item is not refused, it does not exist (404).
|
| No PUT, PATCH or DELETE: a price is set by POSTing a new one, and every
| change appends to `inventory_price_changes`, which is never rewritten.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'capability:pharmacy_stock', 'portal.access'])->group(function () {
    Route::get('/pharmacy/prices', [PharmacyPriceListController::class, 'index'])
        ->name('portals.pharmacy.prices.index');
    Route::post('/pharmacy/prices/{item}/price', [PharmacyPriceListController::class, 'update'])
        ->name('portals.pharmacy.prices.update');
    Route::get('/pharmacy/prices/{item}/history', [PharmacyPriceListController::class, 'history'])
        ->name('portals.pharmacy.prices.history');
});
