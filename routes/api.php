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

    // Provider (clinician) sessions. Separate from the patient auth block above
    // because these mint a provider token, which acts on other people's records
    // within one facility rather than on the holder's own record.
    Route::post('/auth/provider/login', [\App\Http\Controllers\Api\Mobile\MobileProviderAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth.provider')->group(function () {
        Route::post('/auth/provider/logout', [\App\Http\Controllers\Api\Mobile\MobileProviderAuthController::class, 'logout']);
        Route::get('/auth/provider/me', [\App\Http\Controllers\Api\Mobile\MobileProviderAuthController::class, 'me']);

        // Everything below the two session routes is role-gated with
        // `capability:` (config/staff_capabilities.php). auth.provider runs
        // first and stamps provider_user_id; the gate reads the role from there
        // and answers 403 {error_code: role_forbidden} — never a 404.

        // Identify a patient — every professional at the facility may.
        // Patient lookup is an exact Health ID match only (no enumeration).
        Route::get('/doctor/patients/lookup', [\App\Http\Controllers\Api\Mobile\MobilePrescribingController::class, 'lookupPatient'])
            ->middleware('capability:identify_patient');
        // The camera path to the same patient: the app posts what it decoded from
        // the patient's Health ID QR. Throttled like a scanner, not like a search.
        Route::post('/doctor/patients/scan', [\App\Http\Controllers\Api\Mobile\MobileProviderScanController::class, 'scan'])
            ->middleware(['throttle:30,1', 'capability:identify_patient']);

        // Prescribing from the app — the native half of the prescriber's desk.
        // The list is for prescribers and the pharmacy counter (dispense);
        // writing or voiding a script is for prescribers only.
        Route::get('/doctor/prescriptions', [\App\Http\Controllers\Api\Mobile\MobilePrescribingController::class, 'index'])
            ->middleware('capability:prescribe,dispense');
        Route::post('/doctor/prescriptions', [\App\Http\Controllers\Api\Mobile\MobilePrescribingController::class, 'store'])
            ->middleware('capability:prescribe');
        Route::post('/doctor/prescriptions/{id}/void', [\App\Http\Controllers\Api\Mobile\MobilePrescribingController::class, 'void'])
            ->middleware('capability:prescribe');
    });

    // Protected mobile endpoints — require valid patient Bearer token
    // The patient's photo, served to an <img> that cannot carry a bearer
    // token: the SIGNATURE is the authorisation, minted for 60 minutes by
    // PatientPhoto::urlFor() and checked here by the `signed` middleware.
    Route::get('/patients/{patient}/photo', [\App\Http\Controllers\Api\Mobile\MobilePatientPhotoController::class, 'show'])
        ->middleware('signed')
        ->name('mobile.patient.photo');

    Route::middleware('auth.mobile')->group(function () {

        // Patient profile
        Route::get('/me', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getMe']);
        Route::patch('/me', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'updateMe']);
        Route::post('/me/photo', [\App\Http\Controllers\Api\Mobile\MobilePatientPhotoController::class, 'upload']);
        Route::delete('/me/photo', [\App\Http\Controllers\Api\Mobile\MobilePatientPhotoController::class, 'destroy']);
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
        // ── Routes restored 2026-09-04 ────────────────────────────────────────
        // The extraction pruned these from this file but KEPT their controllers,
        // so the app called endpoints that 404'd. Each was verified against this
        // product's schema before wiring; several controllers were fixed first.

        // Health ID card — the app's Health ID tab.
        Route::get('/health-id-card', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getHealthIdCard']);

        // Clinical record. Retracted (entered-in-error) rows are filtered out so
        // voided data never reads back as a live condition or an administered dose.
        Route::get('/allergies', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getAllergies']);
        Route::get('/clinical', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getClinical']);
        Route::get('/immunizations', [\App\Http\Controllers\Api\Mobile\MobilePatientController::class, 'getImmunizations']);

        // Official documents.
        Route::get('/documents', [\App\Http\Controllers\Api\Mobile\MobileDocumentController::class, 'index']);
        Route::get('/documents/{id}', [\App\Http\Controllers\Api\Mobile\MobileDocumentController::class, 'show']);

        // Care plans.
        Route::get('/care-plans', [\App\Http\Controllers\Api\Mobile\MobileCarePlanController::class, 'index']);
        Route::get('/care-plans/{id}', [\App\Http\Controllers\Api\Mobile\MobileCarePlanController::class, 'show']);

        // Referrals.
        Route::get('/referrals', [\App\Http\Controllers\Api\Mobile\MobileReferralController::class, 'index']);

        // Medical-record export. POST (not GET) to match the client and to keep the
        // include_* toggles out of a query string that lands in access logs.
        // Throttled: /api/* carries no default throttle and this is the largest
        // single disclosure of a patient's record.
        Route::post('/medical-records/export/pdf', [\App\Http\Controllers\Api\Mobile\MedicalRecordExportController::class, 'exportPdf'])->middleware('throttle:10,1');
        Route::post('/medical-records/export/fhir', [\App\Http\Controllers\Api\Mobile\MedicalRecordExportController::class, 'exportFhir'])->middleware('throttle:10,1');

        // Patient <-> provider messaging. Thread ids are bigints, so {id} is
        // digit-constrained — an unconstrained segment reaches Postgres as a
        // bigint cast and 500s where it should 404.
        Route::get('/messages/threads', [\App\Http\Controllers\Api\Mobile\MobileMessagingController::class, 'index']);
        Route::post('/messages/threads', [\App\Http\Controllers\Api\Mobile\MobileMessagingController::class, 'start'])->middleware('throttle:10,1');
        Route::get('/messages/threads/{id}', [\App\Http\Controllers\Api\Mobile\MobileMessagingController::class, 'show'])->whereNumber('id');
        Route::post('/messages/threads/{id}/messages', [\App\Http\Controllers\Api\Mobile\MobileMessagingController::class, 'sendMessage'])->whereNumber('id')->middleware('throttle:30,1');

        // Support. /support/contact is a GET returning contact details + the
        // category list the composer needs — not a contact-form POST.
        Route::get('/support/contact', [\App\Http\Controllers\Api\Mobile\MobileSupportController::class, 'contact']);
        Route::get('/support/tickets', [\App\Http\Controllers\Api\Mobile\MobileSupportController::class, 'tickets']);
        Route::post('/support/tickets', [\App\Http\Controllers\Api\Mobile\MobileSupportController::class, 'store']);
        Route::get('/support/tickets/{id}', [\App\Http\Controllers\Api\Mobile\MobileSupportController::class, 'show']);
        Route::post('/support/tickets/{id}/messages', [\App\Http\Controllers\Api\Mobile\MobileSupportController::class, 'sendMessage']);

        // Limited encrypted offline mode: authorises a device to hold PHI locally.
        // NOTE: /queue writes sync_jobs rows that nothing in the Finder consumes yet
        // (the facility-side worker was pruned) — the policy half is fully functional.
        Route::post('/offline/policies', [\App\Http\Controllers\Api\Mobile\OfflineSyncController::class, 'createPolicy']);
        Route::post('/offline/policies/{policy}/queue', [\App\Http\Controllers\Api\Mobile\OfflineSyncController::class, 'queue']);

        // Prescriptions — the mobile app's /mobile/prescriptions calls had no
        // route, so the screen 404'd against this backend.
        Route::get('/prescriptions', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'index']);
        Route::get('/prescriptions/{id}', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'show']);

        // Presenting a script at a pharmacy counter: the patient mints a code on
        // demand and can withdraw it again. Never minted on read - a live token is
        // a standing disclosure of a medication list.
        Route::post('/prescriptions/{id}/share', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'share']);
        Route::delete('/prescriptions/{id}/share', [\App\Http\Controllers\Api\Mobile\MobilePrescriptionController::class, 'withdrawShare']);

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
        Route::post('/family',                        [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/family/invitations',             [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'invitations']);
        Route::post('/family/invitations',            [\App\Http\Controllers\Api\Mobile\MobileFamilyController::class, 'sendInvitation'])->middleware('throttle:10,1');
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
