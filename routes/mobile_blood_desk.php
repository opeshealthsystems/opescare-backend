<?php

use App\Http\Controllers\Api\Mobile\MobileBloodDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — BLOOD-BANK STAFF DESK
|
| The receiving end of a patient's blood request, behind the credential staff
| actually hold.
|
| Before this file the ONLY receiver was
| App\Http\Controllers\Api\V1\BloodRequestQueueController on
| routes/blood_bank.php, gated by VerifyIntegrationClient — and
| `integration_clients` has zero rows in production, so nobody could reach it.
| There was no staff screen anywhere. A request sat `pending` for 24 hours and
| was then retired by the hourly sweep, seen by no one. Nothing here replaces
| that partner surface; it is the same behaviour, same service, same enum,
| offered to a logged-in member of staff.
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_doctor_referrals.php).
|
| NOTE the explicit 'api/mobile' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the automatic
| '/api' prefix that routes/api.php gets from bootstrap/app.php, so it has to
| be written out here or every client call 404s.
| Verify with: php artisan route:list --path=blood-desk
|
| WHY `blood-desk` AND NOT `blood`: routes/mobile_blood.php already registers
| the PATIENT surface — GET/POST /api/mobile/blood/requests, GET
| /api/mobile/blood/requests/{id}, POST /api/mobile/blood/requests/{id}/cancel
| — behind auth.mobile. Laravel keeps ONE handler per method+URI, whichever
| registers last, so mounting the desk on `blood` would silently hand the
| patient's own requests to the staff controller (or the reverse, depending on
| load order). routes/mobile_pharmacy_desk.php hit exactly this and documented
| it; a sibling prefix costs one base-path constant and keeps both surfaces
| whole.
|
| Auth is auth.provider (AuthenticateMobileProvider): the staff member's user
| id and facility_id always come from the bearer token via
| $request->attributes, never from the request body, query string, header or
| session, and never from a fallback default.
|
| Role gate (config/staff_capabilities.php), listed AFTER auth.provider because
| RequireStaffCapability reads the provider_user_id attribute that middleware
| sets:
|   - capability:blood_inventory — the existing blood capability (doctors,
|     nurses, supervisors, front desk, lab). No new capability is invented:
|     the authority that reads the blood shelf is the authority that answers a
|     request against it. Everyone else gets 403 {error_code: role_forbidden},
|     never a 404.
|
| The 404-vs-403 split runs the other way for RECORDS: a request addressed to a
| DIFFERENT blood bank is 404, because answering 403 would already confirm that
| a patient asked somewhere else for blood.
|
| ROUTE ORDER: `summary` is a literal sibling of `requests`, and every
| `requests/{id}/…` action ends in a literal segment, so nothing here can be
| swallowed by a wildcard. whereUuid keeps a malformed id from reaching
| Postgres as a bad uuid cast (22P02), which would surface as a 500 instead of
| the honest 404 — blood_requests.id is a uuid (App\Models\BloodRequest uses
| HasUuids).
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/blood-desk')
    ->middleware(['auth.provider', 'capability:blood_inventory'])
    ->group(function () {

        // Counts by status for the desk home. Counts only — no patient field
        // is touched, so this is the one read that raises no access-log entry.
        Route::get('summary', [MobileBloodDeskController::class, 'summary']);

        // The queue for THIS facility. ?status= | ?scope=open|all | ?limit=
        // Scoped in the controller to the token's facility; there is no
        // parameter that widens it to another one, so it cannot be turned into
        // a cross-facility or national listing. There is no patient search on
        // this surface at all.
        Route::get('requests', [MobileBloodDeskController::class, 'index']);
        Route::get('requests/{id}', [MobileBloodDeskController::class, 'show'])->whereUuid('id');

        // The lifecycle. pending → confirmed → ready → fulfilled, or rejected.
        // Which moves are legal is App\Enums\BloodRequestStatus's own
        // canTransitionTo(), applied inside BloodRequestService::decide(); an
        // illegal move is 409 and changes nothing. Nothing on this surface
        // deletes a request, edits its history, or moves it backwards — and
        // confirming reserves NO stock: it is the facility saying "yes, come".
        Route::post('requests/{id}/decide', [MobileBloodDeskController::class, 'decide'])->whereUuid('id');
        Route::post('requests/{id}/ready', [MobileBloodDeskController::class, 'ready'])->whereUuid('id');
        Route::post('requests/{id}/fulfil', [MobileBloodDeskController::class, 'fulfil'])->whereUuid('id');
    });
