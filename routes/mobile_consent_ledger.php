<?php

use App\Http\Controllers\Api\Mobile\MobileConsentLedgerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Consent Ledger — "who can see my record right now?"
|--------------------------------------------------------------------------
|
| One GET. It is the missing half of a pair that has been broken since the
| consent module shipped:
|
|   POST /api/mobile/consents/{id}/revoke   existed (routes/api.php)
|   GET  /api/mobile/consents               did not
|
| So a patient could revoke a consent grant only if they already knew its
| uuid, and nothing in the API would tell them one. The privacy screen fell
| back to GET /api/mobile/consent-requests, which lists consent REQUESTS and
| finds a grant only through `consent_requests.grant` — every grant whose
| `consent_request_id` is null was invisible, and on this deployment that is
| every grant that exists.
|
| GET /api/mobile/access-logs does not fill the gap either: it says who DID
| open the record, in the past tense. This says who CAN, and hands back the
| id that revoke consumes.
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php and
| routes/mobile_patient_writes.php).
|
| REGISTRATION — add to AppServiceProvider::boot(), alongside the other
| mobile route groups:
|
|     // Mobile consent ledger — the patient's list of who currently holds
|     // access to their record. Pairs with the revoke that already exists in
|     // routes/api.php.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_consent_ledger.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below spells out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/consents
|
| auth.mobile (AuthenticateMobilePatient) stamps `patient_id` onto the
| request attributes from the bearer token. The controller reads it from
| there and nowhere else, and there is no patient identifier in the path,
| query or body — so a `patient_id` supplied by a caller is not overridden,
| it is simply never read, and one patient can never see another's grants.
|
| No throttle beyond the default API budget: this is a read of the caller's
| own consent list, it enumerates nothing, and a patient checking who can see
| their record should never be rate-limited into not checking.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {

    /*
     * GET /api/mobile/consents
     *
     * Three arrays: `active` (live grants, each with the id that
     * POST /api/mobile/consents/{id}/revoke takes), `history` (revoked,
     * expired or otherwise ended — never rendered as live), and
     * `care_relationships` (access that exists WITHOUT a grant, carrying
     * `is_consent_grant: false`, `revocable: false` and no revocable id,
     * because it is not consent and must never be presented as consent).
     */
    Route::get('consents', [MobileConsentLedgerController::class, 'index']);
});
