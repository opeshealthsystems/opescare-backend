<?php

use App\Http\Controllers\MedicalId\QueueDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient queue — web portal routes
|--------------------------------------------------------------------------
|
| `queues`, `queue_tickets`, `queue_status_histories`, `appointment_check_ins`
| and their siblings shipped with the operational MVP and had no route and no
| screen: a dashboard tile read `queue_tickets` and could never be non-zero.
| This file is the door onto the waiting room.
|
| Loaded from AppServiceProvider::boot(), like routes/triage.php and the ~40
| other per-feature route files, rather than added to the shared
| routes/web.php — parallel work on separate features cannot then collide.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open this portal path at all
|                             ('facility/queue' in EnsurePortalAccess admits
|                             the desk roles AND facility administrators)
|   capability:queue          the role, AT THE ACTIVE FACILITY, may run the
|                             room (config/staff_capabilities.php). A refusal
|                             is a 403 `role_forbidden` — never a 404.
|
| Facility scope is NOT in this file and must not be: it is a property of the
| DATA. QueueDeskController reads `facility_id` from PortalContextService,
| never from the request, and a ticket at another facility 404s.
|
| Every transition is a POST; there is no PUT/PATCH and no DELETE anywhere in
| this feature. A ticket moves forward by appending history rows and setting
| a timestamp that was null; nothing is ever rewritten or removed.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:queue'])->group(function () {

    // The live queue — most urgent first, then longest waiting — plus the
    // day's closed tickets and measured wait figures.
    Route::get('/facility/queue', [QueueDeskController::class, 'index'])
        ->name('portals.queue.index');

    // Check-in: exact Health ID, or one of today's bookings at this facility.
    // `/check-in` precedes `/{id}` so it is never swallowed.
    Route::get('/facility/queue/check-in', [QueueDeskController::class, 'create'])
        ->name('portals.queue.create');

    Route::post('/facility/queue', [QueueDeskController::class, 'store'])
        ->name('portals.queue.store');

    Route::get('/facility/queue/{id}', [QueueDeskController::class, 'show'])
        ->name('portals.queue.show');

    // Transitions. Each writes its own timestamp once and appends history.
    Route::post('/facility/queue/{id}/call', [QueueDeskController::class, 'call'])
        ->name('portals.queue.call');

    Route::post('/facility/queue/{id}/start', [QueueDeskController::class, 'start'])
        ->name('portals.queue.start');

    Route::post('/facility/queue/{id}/complete', [QueueDeskController::class, 'complete'])
        ->name('portals.queue.complete');

    Route::post('/facility/queue/{id}/no-show', [QueueDeskController::class, 'noShow'])
        ->name('portals.queue.no_show');

    Route::post('/facility/queue/{id}/cancel', [QueueDeskController::class, 'cancel'])
        ->name('portals.queue.cancel');
});
