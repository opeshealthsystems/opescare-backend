<?php

// Registered from AppServiceProvider. Web desk added from the facility audits
// (docs/audits/*_DASHBOARD_AUDIT_2.md). The middleware stack is declared here next to the routes it gates.

use App\Http\Controllers\MedicalId\ReferralDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Referral desk — web (C-3 / HC-3)
|--------------------------------------------------------------------------
|
| The web twin of routes/mobile_doctor_referrals.php. The lifecycle is NOT
| implemented here or in the controller: both surfaces call
| App\Services\Referral\ReferralDeskService.
|
|   web, auth, mfa.verified, portal.access   the portal preamble
|   capability:clinical_chart                referring a patient is a clinical
|                                            act on the chart; the receiving
|                                            side's clinical staff accept,
|                                            reject and complete under the same
|                                            capability. WHICH party may make a
|                                            move is the service's decision.
|
| Facility scope: ReferralDeskController reads DeskFacilityContext only. A
| referral this facility is not a party to answers 404. Nothing here deletes
| or edits a referral; cancel is how one ends.
|
| ROUTE ORDER: /new precedes /{id}, and {id} is whereUuid.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:clinical_chart'])->group(function () {
    Route::get('/facility/referrals', [ReferralDeskController::class, 'index'])
        ->name('portals.referrals.index');

    Route::get('/facility/referrals/new', [ReferralDeskController::class, 'create'])
        ->name('portals.referrals.create');

    Route::post('/facility/referrals', [ReferralDeskController::class, 'store'])
        ->name('portals.referrals.store');

    Route::get('/facility/referrals/{id}', [ReferralDeskController::class, 'show'])
        ->whereUuid('id')
        ->name('portals.referrals.show');

    Route::post('/facility/referrals/{id}/{action}', [ReferralDeskController::class, 'transition'])
        ->whereUuid('id')
        ->whereIn('action', ['send', 'accept', 'reject', 'complete', 'cancel'])
        ->name('portals.referrals.transition');
});
