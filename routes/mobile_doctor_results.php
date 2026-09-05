<?php

use App\Http\Controllers\Api\Mobile\MobileResultsInboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — Results Inbox
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_providers.php and
| routes/mobile_vitals.php).
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the
| automatic '/api' prefix that routes/api.php gets from bootstrap/app.php,
| so it has to be written out here or every client call 404s.
| Verify with: php artisan route:list --path=doctor/results
|
| Auth is auth.provider (AuthenticateMobileProvider) — the clinician's user id
| and facility_id always come from the bearer token via $request->attributes,
| never from the request body, query string or a header.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/doctor')->middleware('auth.provider')->group(function () {

    // Static segments FIRST: `counts` would otherwise be swallowed by the
    // `{id}` route below and answered as a missing result.
    Route::get('results-inbox/counts', [MobileResultsInboxController::class, 'unreadCount']);

    Route::get('results-inbox', [MobileResultsInboxController::class, 'index']);

    // {id} is the inbox's own composite id: `lab-<uuid>` | `rad-<uuid>`. The
    // constraint keeps a malformed id from reaching Postgres as a bad uuid
    // cast (22P02), which would surface as a 500 instead of a 404.
    Route::get('results-inbox/{id}', [MobileResultsInboxController::class, 'show'])
        ->where('id', '(lab|rad)-[0-9a-fA-F-]{36}');

    Route::post('results-inbox/{id}/acknowledge', [MobileResultsInboxController::class, 'acknowledge'])
        ->where('id', '(lab|rad)-[0-9a-fA-F-]{36}');

    // Analyte rows for one lab order — accepts the bare lab_orders.id a chart
    // row carries, or the inbox's `lab-<uuid>` form.
    Route::get('orders/{id}/results', [MobileResultsInboxController::class, 'orderResults']);

    /*
     * The results half of the clinician's unread badge.
     *
     * CONTENTION NOTE: the messaging slice also claims this path
     * (doctorQueries.ts `unreadCounts`). Laravel keeps the FIRST matching
     * route, so whichever file registers first wins silently. If the messaging
     * agent lands a handler here, delete this one line — the same payload is
     * served at `results-inbox/counts` above, which nothing else claims — and
     * ask them to merge these keys into their response:
     *   unacknowledged_results, critical_unacknowledged, critical_total
     * Criticals must stay a SEPARATE figure; folding them into a single
     * "unread" total is the exact collapse this screen exists to prevent.
     */
    Route::get('messages/unread-count', [MobileResultsInboxController::class, 'unreadCount']);
});
