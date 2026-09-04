<?php

// OpesCare Finder extraction — surface reduced from the platform on 2026-09-02;
// the platform's sealed api.php remains authoritative for the platform.
//
// Kept: health, Connect auth + partner inventory stock ingest (+ widget session),
// the mobile patient API for the Finder feature set (auth, profile, timeline, QR,
// privacy/governance, appointments, facilities, family, notifications, settings,
// pharmacy/medicine finder), the public care-map finder API, and the demo reset
// hook. Everything else (FHIR, Connect records/webhooks/reconciliation, clinical
// modules, admin/governance APIs, SDK, bridge, lite, USSD, billing, insurance,
// analytics, ...) was deleted with the modules it served.

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check — no authentication required
|--------------------------------------------------------------------------
*/
Route::get('/health', \App\Http\Controllers\Api\HealthCheckController::class);

/*
|--------------------------------------------------------------------------
| OpesCare Connect — partner auth + inventory stock ingest (B2B)
|--------------------------------------------------------------------------
| The only B2B surface the Finder keeps: a partner authenticates with client
| credentials and pushes pharmacy/blood stock rows that power the public
| finders. Sync endpoints persist nothing outside the allow-listed live
| stock tables (availability data provenance).
*/
Route::prefix('v1/connect')->group(function () {

    // Auth token request endpoint (uses POST-body credentials). Throttled by IP
    // via the 'verify' limiter (30/min) to bound credential brute-force.
    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\Connect\AuthController::class, 'issueToken'])
        ->middleware('throttle:verify');

    // RFC 7662 token introspection — the endpoint is itself client-authenticated
    // (X-Client-ID / X-Client-Secret). Returns { active: bool, ...claims }.
    Route::post('/auth/introspect', [\App\Http\Controllers\Api\V1\Connect\AuthController::class, 'introspect'])
        ->middleware(['verify.integration.client', 'throttle:verify']);

    // Authenticated B2B routes group — Bearer JWT (RS256) + per-client rate limit 200 req/min
    // + monthly plan quota (EnforceApiQuota; no-op unless on a finite-quota plan).
    Route::middleware(['auth.bearer', 'throttle.client:200,1', 'api.quota'])->group(function () {

        // Widget session
        Route::post('/widget/sessions', [\App\Http\Controllers\Api\V1\Connect\AuthController::class, 'createWidgetSession']);

        // Inventory Stock Sync — the data feed behind the medicine & blood finders
        Route::post('/inventory/pharmacy-stock/sync', [\App\Http\Controllers\Api\V1\Connect\InventoryController::class, 'syncPharmacyStock']);
        Route::post('/inventory/blood-stock/sync', [\App\Http\Controllers\Api\V1\Connect\InventoryController::class, 'syncBloodStock']);
    });
});

/*
|--------------------------------------------------------------------------
| OpesCare Mobile Patient API Routes (B2C Private)
|--------------------------------------------------------------------------
*/
Route::prefix('mobile')->group(function () {

    // Public version gate — consumed at startup (before login) for forced-update.
    Route::get('/app-config', [\App\Http\Controllers\Api\Mobile\MobileAppConfigController::class, 'show']);

    // Public auth endpoints — rate-limited to 5 requests per minute
    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        // Legacy: phone + PIN → OTP flow
        Route::post('/login', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'login']);
        Route::post('/otp/verify', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'verifyOtp']);
        Route::post('/otp/resend', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'resendOtp']);
        // Primary: email + password → direct token (same credentials as patient portal)
        Route::post('/login-email', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'loginWithCredentials']);
        // Token refresh — accepts valid or recently-expired tokens (7-day grace)
        Route::post('/refresh', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'refresh']);
    });

    // Protected mobile endpoints — require valid patient Bearer token
    Route::middleware('auth.mobile')->group(function () {

        // Patient profile
        Route::get('/me', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getMe']);
        Route::patch('/me', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'updateMe']);
        Route::get('/timeline', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getTimeline']);

        // QR Code
        Route::post('/qr/temporary', [\App\Http\Controllers\Api\Mobile\MobileQrController::class, 'generateTemporary']);

        // Patient Consent loop approvals
        Route::get('/consent-requests', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'listConsentRequests']);
        Route::post('/consent-requests/{id}/approve', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'approveConsent']);
        Route::post('/consent-requests/{id}/deny', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'denyConsent']);
        Route::post('/consents/{id}/revoke', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'revokeConsent']);

        // Access Logs B2C view
        Route::get('/access-logs', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'listAccessLogs']);

        // Patient Correction filings
        Route::post('/correction-requests', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'createCorrectionRequest']);

        // Patient data exports B2C
        Route::post('/data-export-requests', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'createExportRequest']);
        Route::get('/data-export-requests', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'listExportRequests']);
        Route::get('/data-exports/{id}/download', [\App\Http\Controllers\Api\Mobile\MobileGovernanceController::class, 'downloadExport']);

        // Appointments
        // Prescriptions — the mobile app's /mobile/prescriptions calls had no
        // route, so the screen 404'd against this backend.
        Route::get('/prescriptions', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'index']);
        Route::get('/prescriptions/{id}', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'show']);

        Route::get('/appointments', [\App\Http\Controllers\Api\Mobile\MobileAppointmentController::class, 'index']);
        Route::get('/appointments/{id}', [\App\Http\Controllers\Api\Mobile\MobileAppointmentController::class, 'show']);
        Route::post('/appointments', [\App\Http\Controllers\Api\Mobile\MobileAppointmentController::class, 'book']);
        Route::post('/appointments/{id}/cancel', [\App\Http\Controllers\Api\Mobile\MobileAppointmentController::class, 'cancel']);

        // Care facility directory
        Route::get('/facilities', [\App\Http\Controllers\Api\Mobile\MobileFacilityController::class, 'index']);
        Route::get('/facilities/{id}', [\App\Http\Controllers\Api\Mobile\MobileFacilityController::class, 'show']);
        Route::get('/facilities/{id}/slots', [\App\Http\Controllers\Api\Mobile\MobileFacilityController::class, 'slots']);

        // App settings & push tokens
        Route::get('/settings', [\App\Http\Controllers\Api\Mobile\MobileSettingsController::class, 'show']);
        Route::patch('/settings', [\App\Http\Controllers\Api\Mobile\MobileSettingsController::class, 'update']);
        Route::post('/push-tokens', [\App\Http\Controllers\Api\Mobile\MobileSettingsController::class, 'registerPushToken']);
        Route::delete('/push-tokens/{id}', [\App\Http\Controllers\Api\Mobile\MobileSettingsController::class, 'revokePushToken']);

        // Family management (guardians & dependents)
        Route::get('/family',                         [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'index']);
        Route::post('/family',                        [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'store']);
        Route::get('/family/invitations',             [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'invitations']);
        Route::post('/family/invitations',            [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'sendInvitation']);
        Route::delete('/family/invitations/{id}',     [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'cancelInvitation']);

        // Notification centre
        Route::get('/notifications',                  [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'index']);
        Route::get('/notifications/unread-count',     [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read',       [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'markRead']);
        Route::post('/notifications/mark-all-read',   [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'markAllRead']);

        // Pharmacy / Medicine Finder — catalog search, nearby pharmacy stock, reservations
        Route::get('/pharmacy/categories',                  [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'categories']);
        Route::get('/pharmacy/medicines',                   [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'searchMedicines']);
        Route::get('/pharmacy/medicines/{id}',              [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'showMedicine']);
        Route::get('/pharmacy/nearby',                      [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'nearbyPharmacies']);
        Route::get('/pharmacy/reservations',                [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'listReservations']);
        Route::post('/pharmacy/reservations',               [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'reserve']);
        Route::post('/pharmacy/reservations/{id}/cancel',   [\App\Http\Controllers\Api\Mobile\MobilePharmacyController::class, 'cancelReservation']);
    });
});

/*
|--------------------------------------------------------------------------
| OpesCare Demo API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('demo')->group(function () {
    Route::post('/reset', function () {
        if (!config('demo.enabled')) {
            abort(403, 'Demo mode disabled');
        }
        \Illuminate\Support\Facades\Artisan::call('opescare:demo:reset');
        return response()->json(['message' => 'Demo reset successfully']);
    });
});

/*
|--------------------------------------------------------------------------
| OpesCare Verified Care Access Map API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1/care-map')->group(function () {
    Route::get('/facilities', [\App\Http\Controllers\Api\V1\CareMapController::class, 'index']);
    Route::get('/facilities/{id}', [\App\Http\Controllers\Api\V1\CareMapController::class, 'show']);
    Route::get('/search', [\App\Http\Controllers\Api\V1\CareMapController::class, 'index']);
    Route::get('/nearby', [\App\Http\Controllers\Api\V1\CareMapController::class, 'index']);
    Route::get('/pharmacies/medicine-search', [\App\Http\Controllers\Api\V1\CareMapController::class, 'searchMedicine']);
    Route::get('/labs/test-search', [\App\Http\Controllers\Api\V1\CareMapController::class, 'searchTests']);
    Route::get('/blood/search', [\App\Http\Controllers\Api\V1\CareMapController::class, 'searchBlood']);
    Route::get('/emergency', [\App\Http\Controllers\Api\V1\CareMapController::class, 'searchEmergency']);

    // SEAL BREAK, 2026-09-02, on the project owner's explicit instruction.
    // This group was `auth:sanctum`; sanctum was never installed, so all four
    // routes 500'd. `web` + `auth` gives the browser POSTs from the public
    // listing page a session to authenticate against (the api group has no
    // StartSession). See the platform's routes/api.php for the full history.
    Route::middleware(['web', 'auth'])->group(function () {
        Route::post('/facilities/{id}/save', [\App\Http\Controllers\Api\V1\CareMapController::class, 'saveFacility']);
        Route::post('/facilities/{id}/report', [\App\Http\Controllers\Api\V1\CareMapController::class, 'reportFacility']);
        Route::post('/facilities/{id}/claim', [\App\Http\Controllers\Api\V1\CareMapController::class, 'claimFacility']);

        // Partner syncs
        Route::post('/partner/facilities/{id}/stock-sync', [\App\Http\Controllers\Api\V1\CareMapController::class, 'partnerStockSync']);
    });

    // Admin actions — require admin role
    Route::middleware(['auth', 'api.admin'])->group(function () {
        Route::post('/admin/facilities/{id}/verify', [\App\Http\Controllers\Api\V1\CareMapController::class, 'adminVerifyFacility']);
        Route::post('/admin/facilities/{id}/suspend', [\App\Http\Controllers\Api\V1\CareMapController::class, 'adminSuspendFacility']);
    });
});

/*
|--------------------------------------------------------------------------
| Blood Bank Inventory (facility write path for the Blood Finder)
|--------------------------------------------------------------------------
| KEPT while the rest of v1/inventory (items, purchase orders, audits) was
| deleted: BloodInventoryController is the staff blood-entry write path, and
| BloodAvailabilityProjector republishes the public Blood Finder after
| every write.
*/
Route::prefix('v1/inventory')->middleware(\App\Http\Middleware\VerifyIntegrationClient::class)->group(function () {
    Route::get('/blood',                         [\App\Http\Controllers\Api\V1\BloodInventoryController::class, 'index']);
    Route::get('/blood/summary',                 [\App\Http\Controllers\Api\V1\BloodInventoryController::class, 'summary']);
    Route::post('/blood',                        [\App\Http\Controllers\Api\V1\BloodInventoryController::class, 'upsert']);
    Route::post('/blood/{item}/adjust',          [\App\Http\Controllers\Api\V1\BloodInventoryController::class, 'adjust']);
    Route::patch('/blood/{item}/flags',          [\App\Http\Controllers\Api\V1\BloodInventoryController::class, 'setFlags']);
});
