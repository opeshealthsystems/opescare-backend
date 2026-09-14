<?php

use App\Http\Controllers\MedicalId\LabDeskWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laboratory desk — web portal routes
|--------------------------------------------------------------------------
|
| `lab_orders` and `lab_results` had a clinician-app desk and no web page:
| the `lab_results` and `sample_collection` capabilities were granted to five
| roles and gated nothing. This file is the door.
|
| Loaded from AppServiceProvider::boot(), like routes/triage.php and
| routes/queue.php, rather than added to the shared routes/web.php — parallel
| work on separate features cannot then collide in one file.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open a facility portal at all
|   capability:lab_results    the role, AT THE ACTIVE FACILITY, works the
|                             bench (config/staff_capabilities.php). A refusal
|                             is a 403 `role_forbidden` — never a 404.
|   capability:lab_results,sample_collection
|                             the collection routes only, so a collection-only
|                             account can book a sample in without seeing a
|                             result.
|
| Validation (a second person signs the panel off) has no capability of its
| own in the config; LabDeskWebService checks the role itself (lab_validator,
| lab_manager) and answers 403.
|
| Facility scope is NOT in this file and must not be: LabDeskWebController reads
| it from PortalContextService, never from the request, and an order at
| another facility 404s.
|
| No route here consults billing. Care is never gated on payment.
|
| ROUTE ORDER: `/collection` precedes `orders/{id}` and every literal segment
| differs, so nothing is swallowed by a wildcard.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {

    Route::middleware('capability:lab_results,sample_collection')->group(function () {
        Route::get('/facility/lab/collection', [LabDeskWebController::class, 'collection'])
            ->name('portals.lab.collection');

        Route::post('/facility/lab/orders/{id}/collect', [LabDeskWebController::class, 'collect'])
            ->name('portals.lab.collect');
    });

    Route::middleware('capability:lab_results')->group(function () {
        Route::get('/facility/lab', [LabDeskWebController::class, 'index'])
            ->name('portals.lab.index');

        Route::get('/facility/lab/orders/{id}', [LabDeskWebController::class, 'show'])
            ->name('portals.lab.show');

        // Every write below is an APPEND: a new result row, a forward status
        // move. There is deliberately no PUT/PATCH and no delete anywhere in
        // this feature — a result is corrected by a new row, never rewritten.
        Route::post('/facility/lab/orders/{id}/results', [LabDeskWebController::class, 'storeResult'])
            ->name('portals.lab.results.store');

        Route::post('/facility/lab/orders/{id}/results/{resultId}/correct', [LabDeskWebController::class, 'correct'])
            ->name('portals.lab.results.correct');

        Route::post('/facility/lab/orders/{id}/release', [LabDeskWebController::class, 'release'])
            ->name('portals.lab.release');

        Route::post('/facility/lab/orders/{id}/validate', [LabDeskWebController::class, 'validateOrder'])
            ->name('portals.lab.validate');
    });
});
