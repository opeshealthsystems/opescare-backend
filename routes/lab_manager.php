<?php

use App\Http\Controllers\MedicalId\LabManagerDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laboratory manager desk — web portal routes
|--------------------------------------------------------------------------
|
| The four things docs/LAB_DASHBOARD_AUDIT.md found a laboratory could not
| do from the browser: see how the lab is doing (dashboard), answer the
| requests patients send it (requests), refuse a bad sample (reject), and
| hand a walk-in patient a printed report (report).
|
| Registered from AppServiceProvider::boot(), like routes/lab.php. The
| middleware stack is declared here, next to the routes it gates, and it is
| the SAME stack as routes/lab.php:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open a facility portal at all
|   capability:lab_results    the role, AT THE ACTIVE FACILITY, works the
|                             bench (labtech, lab_scientist, lab_manager,
|                             lab_validator — config/staff_capabilities.php).
|                             A refusal is a 403 `role_forbidden`.
|   capability:lab_results,sample_collection
|                             rejection only — the collector who sees the
|                             haemolysed tube is the one who refuses it.
|
| Facility scope is NOT in this file and must not be: the controller reads it
| from PortalContextService, never from the request, and a row at another
| facility 404s.
|
| No route here consults billing. Care is never gated on payment.
|
| ROUTE ORDER: `/dashboard` and `/requests` are literal segments under
| /facility/lab that routes/lab.php's `orders/{id}` cannot swallow; the two
| order routes add a segment after `{id}`, so nothing collides.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {

    Route::middleware('capability:lab_results')->group(function () {
        Route::get('/facility/lab/dashboard', [LabManagerDeskController::class, 'dashboard'])
            ->name('portals.lab_manager.dashboard');

        Route::get('/facility/lab/requests', [LabManagerDeskController::class, 'requests'])
            ->name('portals.lab_manager.requests');

        Route::post('/facility/lab/requests/{id}/approve', [LabManagerDeskController::class, 'approveRequest'])
            ->name('portals.lab_manager.requests.approve');

        Route::post('/facility/lab/requests/{id}/decline', [LabManagerDeskController::class, 'declineRequest'])
            ->name('portals.lab_manager.requests.decline');

        // The laboratory telephoned a critical value to a named person.
        // Closes the alert once (a one-way latch); nothing else ever did.
        Route::post('/facility/lab/alerts/{id}/callback', [LabManagerDeskController::class, 'criticalCallback'])
            ->name('portals.lab_manager.critical_callback');

        // Generated now, never stored — the patient's own Secure sheet.
        Route::get('/facility/lab/orders/{id}/report', [LabManagerDeskController::class, 'report'])
            ->name('portals.lab_manager.report');
    });

    Route::middleware('capability:lab_results,sample_collection')->group(function () {
        // Final: `rejected` is terminal on the model. Allowed only before any result.
        Route::post('/facility/lab/orders/{id}/reject', [LabManagerDeskController::class, 'reject'])
            ->name('portals.lab_manager.reject');
    });
});
