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
|
| Role gate: `capability:clinical_chart,lab_results` (any-of, from
| config/staff_capabilities.php) — clinicians who order, and the lab that
| results. Front desk, pharmacy and billing get 403 {error_code:
| role_forbidden}. Listed AFTER auth.provider because it reads the
| provider_user_id attribute that middleware sets.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/doctor')->middleware(['auth.provider', 'capability:clinical_chart,lab_results'])->group(function () {

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

    // `messages/unread-count` is deliberately NOT registered here. It used to
    // be, and because this file loads AFTER routes/mobile_doctor_messaging.php
    // (AppServiceProvider::boot) Laravel kept this handler for the URI, so the
    // app's messaging badge silently received result counts. The app parses
    // that path as the MESSAGING payload ({ unread_threads, unread_messages },
    // doctorQueries.ts `unreadCounts`), which the messaging file serves. The
    // result figures live at `results-inbox/counts` above (doctorQueries.ts
    // `resultCounts`) and nothing else claims that path. Criticals stay a
    // SEPARATE figure there; never fold them into a single "unread" total.
});
