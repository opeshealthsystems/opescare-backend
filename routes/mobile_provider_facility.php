<?php

use App\Http\Controllers\Api\Mobile\MobileProviderFacilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — which facility the clinician is acting as
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_doctor_schedule.php and
| routes/mobile_providers.php). Add exactly this, next to the others:
|
|     // Mobile provider facility-switch routes — same rationale as above.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_provider_facility.php'));
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=mobile/provider/facilit
|
| Auth is auth.provider (AuthenticateMobileProvider), which stamps
| `provider_user_id`, `provider_facility_id` and `provider_token` onto the
| request attributes from the bearer token.
|
| ── THE ONE ROUTE ON THIS SURFACE THAT READS A FACILITY FROM A BODY ───────
| POST /api/mobile/provider/facility takes `facility_id` from the request, and
| it is the ONLY endpoint in the mobile API that may. It is safe because the
| body only NOMINATES: MobileProviderFacilityController checks the id against
| the clinician's real postings server-side (FacilityMembershipService) and
| answers 403 for anything else, so the call can move a clinician within the
| facilities they already work at and can never add one. Every other route
| still reads its facility from $request->attributes and nowhere else — do not
| copy this pattern.
|
| Switching REVOKES the presented token and returns a new one, per the intent
| recorded on the provider_access_tokens migration ("a clinician who moves
| facility gets a new session rather than a token that silently follows them").
|
| The throttle is on the write only, and it is about token minting rather than
| about facilities: each successful switch issues a bearer, so an unbounded
| endpoint is an unbounded token factory for anyone holding one valid session.
|
| It keys on IP, not on the clinician: `auth.provider` sets request ATTRIBUTES
| and never populates $request->user(), which is what the default limiter would
| read. A whole hospital can therefore share one bucket — which is why the
| ceiling is 60/minute rather than something tight. It is a cap on abuse, not a
| usage quota; a human tapping between two hospitals will never approach it.
| A per-token limiter would need a named RateLimiter registered in
| AppServiceProvider, which this feature deliberately does not touch.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.provider')->group(function () {

    // The clinician's own postings — never the facility directory.
    Route::get('provider/facilities', [MobileProviderFacilityController::class, 'index']);

    // Act as another facility. Body: { facility_id }. Returns a NEW token.
    Route::post('provider/facility', [MobileProviderFacilityController::class, 'switch'])
        ->middleware('throttle:60,1');
});
