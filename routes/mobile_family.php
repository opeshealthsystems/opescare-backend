<?php

use App\Http\Controllers\Api\Mobile\MobileFamilyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Family / Dependents (detail + incoming invitations)
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_vitals.php). The five
| original family routes (list, register, sent invitations, send, cancel)
| stay in routes/api.php; these complete the feature:
|
|   GET  family/members/{linkId}              the dependent's card + records
|   GET  family/invitations/incoming          invitations addressed to ME
|   POST family/invitations/{id}/accept       I consent to the link
|   POST family/invitations/{id}/decline      I refuse it
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix that routes/api.php gets, so the prefix below MUST spell out
| `api/mobile` in full. Verify with:
|     php artisan route:list --path=mobile/family
|
| auth.mobile (AuthenticateMobilePatient + ActAsDependent) sets `patient_id`
| and `patient_user_id` on the request attributes from the bearer token. The
| family surface always belongs to the signed-in account: ActAsDependent
| refuses the X-OpesCare-Dependent header on every `family/*` path.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->middleware('auth.mobile')->group(function () {
    Route::get('family/members/{linkId}', [MobileFamilyController::class, 'show'])
        ->whereUuid('linkId');

    Route::get('family/invitations/incoming', [MobileFamilyController::class, 'incomingInvitations']);

    Route::post('family/invitations/{id}/accept', [MobileFamilyController::class, 'acceptInvitation'])
        ->whereUuid('id');

    Route::post('family/invitations/{id}/decline', [MobileFamilyController::class, 'declineInvitation'])
        ->whereUuid('id');
});
