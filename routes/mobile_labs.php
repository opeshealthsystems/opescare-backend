<?php

use App\Http\Controllers\Api\Mobile\MobileLabController;
use App\Http\Controllers\Api\Mobile\MobileLabRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Laboratory
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php). Register it
| there with:
|
|     // Laboratory results, trends, the orderable catalogue and the patient's
|     // request for a test. Same rationale as mobile_vitals.php.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_labs.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/labs
|
| auth.mobile (AuthenticateMobilePatient) sets `patient_id` on the request
| attributes from the bearer token. Both controllers read it from there and
| nowhere else — there is no patient identifier in any path, query or body,
| so one patient can never address another's laboratory record.
|
| ROUTE ORDER IS LOAD-BEARING. Every literal segment (`summary`, `catalogue`,
| `collection`, `requests`, `trends/…`) is declared BEFORE `labs/{id}`,
| because Laravel matches in declaration order and `labs/{id}` would
| otherwise swallow `labs/summary` and answer it with a 404 for an order
| whose id is the word "summary".
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    // ── Literal segments first ───────────────────────────────────────────
    Route::get('labs/summary', [MobileLabController::class, 'summary']);
    Route::get('labs/catalogue', [MobileLabRequestController::class, 'catalogue']);
    Route::get('labs/collection', [MobileLabRequestController::class, 'collection']);
    Route::post('labs/requests', [MobileLabRequestController::class, 'store']);

    // The analyte may contain spaces, slashes and accents ("Haemoglobin",
    // "Glycémie à jeun"), so the parameter is unconstrained and the controller
    // urldecodes it.
    Route::get('labs/trends/{analyte}', [MobileLabController::class, 'trend'])
        ->where('analyte', '.*');

    // ── Then the order list and the order-scoped reads ───────────────────
    Route::get('labs', [MobileLabController::class, 'index']);
    Route::get('labs/{id}/report', [MobileLabController::class, 'report']);
    Route::get('labs/{id}', [MobileLabController::class, 'show']);
});

/*
| The report PDF download sits OUTSIDE auth.mobile on purpose: the app hands
| the URL to the system browser, which carries no bearer token. The link is a
| short-lived signed URL naming one order and one patient (see
| MobileLabController::presentDocument), and the handler re-verifies the
| signature, the patient, the order and the file before streaming anything.
*/
Route::prefix('api/mobile')->group(function () {
    Route::get('labs/{id}/report/download', [MobileLabController::class, 'download'])
        ->name('mobile.labs.report.download');
});
