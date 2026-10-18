<?php

use App\Http\Controllers\MedicalId\ImagingDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Imaging desk — web portal routes
|--------------------------------------------------------------------------
|
| The imaging half of a diagnostic centre (docs/superpowers/specs/
| 2026-10-13-diagnostic-centre-dashboard-design.md). Loaded from
| AppServiceProvider::boot() beside routes/lab.php, for the same reason: parallel
| work on separate features must not collide in routes/web.php.
|
| THE GATE: web, auth, mfa.verified, portal.access, then `capability:imaging`
| (config/staff_capabilities.php). A refusal is a 403 role_forbidden, never a
| 404. Facility scope is read by the controller from PortalContextService and
| an order at another facility 404s.
|
| `portals.imaging.reports.index` is a placeholder (the worklist filtered to
| orders awaiting a report) until the report desk lands; the NAME is fixed.
|
| ROUTE ORDER: `orders/new` precedes `orders/{id}`.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:imaging'])->group(function () {
    Route::get('/facility/imaging', [ImagingDeskController::class, 'index'])
        ->name('portals.imaging.index');

    // The report desk's queue (ImagingReportController, see the
    // "Imaging report desk" block in routes/web.php for the rest of that desk).
    Route::get('/facility/imaging/reports', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'index'])
        ->middleware('capability:imaging_report')
        ->name('portals.imaging.reports.index');

    Route::get('/facility/imaging/orders/new', [ImagingDeskController::class, 'create'])
        ->name('portals.imaging.create');

    Route::post('/facility/imaging/orders', [ImagingDeskController::class, 'store'])
        ->name('portals.imaging.store');

    Route::get('/facility/imaging/orders/{id}', [ImagingDeskController::class, 'show'])
        ->name('portals.imaging.show');

    Route::post('/facility/imaging/orders/{id}/schedule', [ImagingDeskController::class, 'schedule'])
        ->name('portals.imaging.schedule');

    Route::post('/facility/imaging/orders/{id}/start', [ImagingDeskController::class, 'start'])
        ->name('portals.imaging.start');

    Route::post('/facility/imaging/orders/{id}/complete', [ImagingDeskController::class, 'complete'])
        ->name('portals.imaging.complete');

    Route::post('/facility/imaging/orders/{id}/cancel', [ImagingDeskController::class, 'cancel'])
        ->name('portals.imaging.cancel');
});
