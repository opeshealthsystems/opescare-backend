<?php

use App\Http\Controllers\Api\Mobile\MobileConsentRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Clinician Routes — ASKING a patient for access to their record
|
| The missing half of the consent loop. The patient's half already existed
| (GET /api/mobile/consent-requests, POST .../approve, .../deny in
| routes/api.php → MobileGovernanceController) but nothing could create a
| ConsentRequest, so that inbox was structurally always empty and
| MobileDoctorChartController's fifth access basis — `consent_grant` — could
| never be reached. These routes are what a facility uses to ask.
|
| Loaded from AppServiceProvider::boot() rather than added to routes/api.php,
| so parallel work on separate features cannot collide in one file (same
| pattern as routes/mobile_vitals.php and routes/mobile_doctor_chart.php):
|
|     // Mobile clinician consent-request routes — same rationale as above.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_consent_requests.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=mobile/doctor/consent-requests
|
| Auth is auth.provider (AuthenticateMobileProvider), NOT auth.mobile: this is
| the FACILITY side. The middleware stamps `provider_user_id` and
| `provider_facility_id` on the request attributes and the controller reads
| both from there and nowhere else — there is no facility_id in any rule, path
| or header read in that file, so a caller cannot raise a request in someone
| else's name.
|
| There is deliberately no patient search here and no patient id in any path.
| A request names its patient by EXACT Health ID in the body, resolved by
| equality on the whole identifier — patient enumeration is forbidden
| platform-wide, and "who could I ask for access?" is precisely the query that
| would leak the register.
|
| The `{consentRequest}` segment is constrained to the shape of a uuid so a
| malformed id is a 404 from the router rather than a Postgres cast error, and
| the literal segment `options` can never be shadowed by it whatever order the
| routes are registered in.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.provider')->group(function () {
    Route::prefix('doctor/consent-requests')->group(function () {
        // The vocabulary the app renders (purposes, scopes, limits), so the
        // phone does not hard code a list that can drift from the validator.
        Route::get('options', [MobileConsentRequestController::class, 'options']);

        // This facility's own outbox — what it has asked for, and where each
        // ask stands. Facility-scoped from the token; no patient parameter.
        Route::get('/', [MobileConsentRequestController::class, 'index']);

        // Ask a named patient. The one that makes basis 5 reachable.
        Route::post('/', [MobileConsentRequestController::class, 'store']);

        Route::get('{consentRequest}', [MobileConsentRequestController::class, 'show'])
            ->where('consentRequest', '[0-9a-fA-F-]{36}');

        // Take an ask back — a mistyped Health ID must not lock the facility
        // out of asking the right patient until the wrong request expires.
        Route::post('{consentRequest}/withdraw', [MobileConsentRequestController::class, 'withdraw'])
            ->where('consentRequest', '[0-9a-fA-F-]{36}');
    });
});
