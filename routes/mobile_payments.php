<?php

use App\Http\Controllers\Api\Mobile\MobilePaymentCallbackController;
use App\Http\Controllers\Api\Mobile\MobilePaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Invoices & Mobile Money Payments
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php).
|
| REGISTER WITH (in AppServiceProvider::boot(), beside the other mobile_*
| groups):
|
|     // Mobile patient invoices + mobile money payments — same rationale.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_payments.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefixes below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/payments
|
|--------------------------------------------------------------------------
| Two groups, because they are authenticated by two different parties.
|--------------------------------------------------------------------------
|
| 1. The PATIENT group (auth.mobile). `patient_id` is set on the request
|    attributes from the bearer token; the controller reads it from there and
|    nowhere else. There is no patient identifier in any path, query or body,
|    and every lookup is scoped by that id in the WHERE clause — so another
|    patient's invoice is a 404, not a 403.
|
| 2. The CALLBACK route, which has NO patient auth and cannot have any: it is
|    called by MTN or Orange, who hold no patient token. It is authenticated by
|    the provider's own mechanism inside
|    MobileMoneyProvider::verifyCallback(), answers 202 to everything so the
|    response code is not an oracle, and — even when verification passes — never
|    writes a status from the request body. It re-polls the provider instead.
|    See MobilePaymentCallbackController for why.
|
| Rate limits are deliberately tight on the two routes that cost money or
| reach a third party:
|   - starting a charge: 10/min per patient token. A patient cannot legitimately
|     start ten payments a minute, and each one is a push prompt on a handset.
|   - the callback: 120/min per IP, which is generous for a telco and still
|     bounds a replay flood.
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {

    // What this host can take, and whether it is real money.
    Route::get('payments/methods', [MobilePaymentController::class, 'methods']);

    // The patient's own invoices.
    Route::get('payments/invoices', [MobilePaymentController::class, 'index']);
    Route::post('payments/invoices', [MobilePaymentController::class, 'store'])
        ->middleware('throttle:20,1');
    Route::get('payments/invoices/{invoice}', [MobilePaymentController::class, 'show']);

    // Start (or safely replay) a payment. Requires an Idempotency-Key header.
    Route::post('payments/invoices/{invoice}/charges', [MobilePaymentController::class, 'pay'])
        ->middleware('throttle:10,1');

    // The app's polling endpoint while a charge is pending.
    Route::get('payments/charges/{charge}', [MobilePaymentController::class, 'charge'])
        ->middleware('throttle:120,1');
});

/*
| The provider callback. Outside the auth.mobile group on purpose — see above.
| {provider} is the rail key ('mtn_momo' | 'orange_money' | 'demo'), never a
| charge identifier: the charge is named in the body and authenticated by the
| provider's own mechanism.
*/
Route::post('api/mobile/payments/callbacks/{provider}', MobilePaymentCallbackController::class)
    ->middleware('throttle:120,1')
    ->where('provider', '[a-z_]{3,32}');
