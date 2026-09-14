<?php

/*
|--------------------------------------------------------------------------
| Pharmacy orders desk (web)
|--------------------------------------------------------------------------
|
| Patient medicine reservations, handled from the browser. Until this file
| existed the accept / ready / collected flow lived only in the mobile API
| (routes/mobile_pharmacy_desk.php) — a pharmacy without the staff app never
| saw an order. Registered from AppServiceProvider; the middleware stack is
| declared here next to the routes it gates.
*/

use App\Http\Controllers\MedicalId\PharmacyOrdersController;
use Illuminate\Support\Facades\Route;

/*
| THE GATE mirrors routes/pharmacy_ops.php, with the counter capability:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   capability:dispense       the role, AT THE ACTIVE FACILITY, may work the
|                             counter (config/staff_capabilities.php: the four
|                             pharmacy roles — medicine_stock keeps stock but
|                             never hands an order over). 403 `role_forbidden`.
|   portal.access             the user may open the pharmacy portal at all
|
| Facility scope is a property of the data: the controller reads the facility
| from PortalContextService only and a reservation placed at another
| pharmacy is not refused — it does not exist (404).
|
| Every transition is a POST and forward-only; there is no PUT, PATCH or
| DELETE. The state machine lives in PharmacyDeskService::transitionReservation
| — the same code path the mobile desk uses — so both surfaces agree.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'capability:dispense', 'portal.access'])
    ->prefix('/pharmacy/orders')
    ->name('portals.pharmacy.orders.')
    ->group(function () {
        Route::get('/', [PharmacyOrdersController::class, 'index'])->name('index');
        Route::get('/{id}', [PharmacyOrdersController::class, 'show'])->whereUuid('id')->name('show');

        Route::post('/{id}/accept', [PharmacyOrdersController::class, 'accept'])->whereUuid('id')->name('accept');
        Route::post('/{id}/ready', [PharmacyOrdersController::class, 'ready'])->whereUuid('id')->name('ready');
        Route::post('/{id}/collected', [PharmacyOrdersController::class, 'collected'])->whereUuid('id')->name('collected');
        Route::post('/{id}/dispatch', [PharmacyOrdersController::class, 'dispatch'])->whereUuid('id')->name('dispatch');
        Route::post('/{id}/delivered', [PharmacyOrdersController::class, 'delivered'])->whereUuid('id')->name('delivered');
        Route::post('/{id}/cancel', [PharmacyOrdersController::class, 'cancel'])->whereUuid('id')->name('cancel');
    });
