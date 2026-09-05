<?php

use App\Http\Controllers\Api\Mobile\MobileFacilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Facility register state
|
| One endpoint, and it exists to tell the truth about the two facility
| registers before a patient spends a search finding it out:
|
|   care_facilities  — the public directory (2 728 active listings)
|   facilities       — the operational tenant a listing links to when its
|                      operator has signed up (468 listings are linked)
|
| GET /api/mobile/facility-registry/summary answers "how much of what I am
| browsing can actually do something?" — directory total, connected total,
| bookable total, publishing-a-roster total, plus the same split per facility
| type. The per-listing answer is already on GET /api/mobile/facilities
| (`connected`, `provider_count`, `open_slot_count`); this is the denominator
| that stops "Not connected" reading as a bug.
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_providers.php).
|
| REGISTRATION — add this to AppServiceProvider::boot(), next to the other
| mobile route files (this file does NOT register itself):
|
|     // Mobile facility-register summary — same rationale as above.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_facility_registry.php'));
|
| THEN regenerate the API spec in the same commit:
|
|     php artisan opescare:generate-openapi
|
| Tests\Feature\Architecture\OpenApiContractTest::test_spec_is_in_sync_with_routes
| fails the deploy gate on any route that is registered but absent from
| openapi.json. Registering this file without regenerating turns the build red.
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=facility-registry
|
| Auth is auth.mobile (AuthenticateMobilePatient), matching the rest of the
| /api/mobile/facilities surface. The payload is aggregate counts over the
| public directory only — no patient data is read, and no facility is named.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('facility-registry/summary', [MobileFacilityController::class, 'registrySummary']);
});
