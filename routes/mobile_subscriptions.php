<?php

use App\Http\Controllers\Api\Mobile\MobileSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Subscriptions
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_payments.php).
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below spells out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/subscriptions
|
|--------------------------------------------------------------------------
| Authenticated as the patient, and only as the patient.
|--------------------------------------------------------------------------
|
| `auth.mobile` stamps `patient_id` on the request attributes from the bearer
| token, and the controller reads the subscriber from there and nowhere else.
| There is no subscriber id in any path, query or body on these routes.
|
| There is deliberately NO facility-facing route here. A facility subscription
| is a real thing the checkout service can create — a facility's plan, priced
| annually from its own catalogue row — but the mobile API authenticates
| patients, and inventing a way for a patient bearer token to buy a facility's
| subscription would be a hole, not a feature. The facility surface belongs on
| the authenticated web side, next to the rest of the facility administration,
| and it calls exactly the same service.
|
| Rate limits, on the two routes that cost something:
|   - starting a subscription: 10/min per token. Each one is a push prompt on
|     a handset; nobody legitimately subscribes ten times a minute.
|   - polling one: 120/min, matching the payments poll endpoint, because the
|     app polls this while a charge is in flight.
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {

    // The catalogue, priced for this subscriber. Cheap and read-only.
    Route::get('subscriptions/plans', [MobileSubscriptionController::class, 'plans']);

    // The patient's own subscriptions.
    Route::get('subscriptions', [MobileSubscriptionController::class, 'index']);

    // Take one out and start paying. Requires an Idempotency-Key header.
    Route::post('subscriptions', [MobileSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1');

    /*
     | The poll. Declared AFTER `subscriptions/plans` on purpose: a wildcard
     | segment registered first would swallow the literal `plans` and answer it
     | with a 404 from the subscription lookup. The uuid constraint makes that
     | ordering belt-and-braces rather than the only thing holding it up.
     */
    Route::get('subscriptions/{subscription}', [MobileSubscriptionController::class, 'show'])
        ->middleware('throttle:120,1')
        ->where('subscription', '[0-9a-fA-F-]{36}');
});
