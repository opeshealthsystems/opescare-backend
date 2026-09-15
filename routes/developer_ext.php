<?php

// Registered from AppServiceProvider. Routes are added by the developer dashboard build
// (see docs/DEVELOPER_DASHBOARD_AUDIT.md). The middleware stack is declared here next to the routes it gates.

use App\Http\Controllers\MedicalId\DeveloperExtController;
use Illuminate\Support\Facades\Route;

// Same stack as the developer portal block in routes/web.php: the session, a
// signed-in user, and EnsurePortalAccess (PORTAL_ROLES['developer']).
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    // Webhooks — register an endpoint, pause/resume it, recent deliveries (D-1, D-2)
    Route::get('/developer/webhooks',                        [DeveloperExtController::class, 'webhooks'])->name('portals.developer.webhooks');
    Route::post('/developer/webhooks',                       [DeveloperExtController::class, 'storeWebhook'])->name('portals.developer.webhooks.store');
    Route::post('/developer/webhooks/{subscription}/toggle', [DeveloperExtController::class, 'toggleWebhook'])->name('portals.developer.webhooks.toggle');
    Route::post('/developer/webhooks/deliveries/{delivery}/replay', [DeveloperExtController::class, 'replayDelivery'])->name('portals.developer.webhooks.replay');

    // Consents — active grants to the facility each facility-bound app acts for (D-9; D-5 resolved)
    Route::get('/developer/consents',                        [DeveloperExtController::class, 'consents'])->name('portals.developer.consents');

    // Activity — the partner's own fhir_ingest_events + api_usage_logs (D-3)
    Route::get('/developer/activity',                        [DeveloperExtController::class, 'activity'])->name('portals.developer.activity');

    // Quickstart — a first call with the partner's own sandbox client (D-4, D-5, D-6)
    Route::get('/developer/quickstart',                      [DeveloperExtController::class, 'quickstart'])->name('portals.developer.quickstart');
});
