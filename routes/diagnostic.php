<?php

use App\Http\Controllers\MedicalId\DiagnosticCentreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Diagnostic centre landing — web portal route
|--------------------------------------------------------------------------
|
| The one page a diagnostic centre (laboratory + imaging under one roof,
| docs/superpowers/specs/2026-10-13-diagnostic-centre-dashboard-design.md)
| lands on: both halves' figures and the split publication panel. Loaded
| from AppServiceProvider::boot() beside routes/lab_manager.php and
| routes/imaging_desk.php, for the same reason: parallel work on separate
| features must not collide in routes/web.php.
|
| THE GATE: web, auth, mfa.verified, portal.access, then
| `capability:lab_results,imaging` — ANY-OF, as RequireStaffCapability reads
| a comma list (the pharmacy dashboard uses `capability:dispense,pharmacy_stock`
| the same way). A lab manager holds both; a facility administrator at a
| centre holds `imaging`; a receptionist holds neither and gets a 403
| role_forbidden, never a 404.
|
| The FACILITY gate is the controller's: a lab-only facility is redirected to
| its own dashboard (portals.lab_manager.dashboard), never shown a page of
| imaging zeros.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:lab_results,imaging'])->group(function () {
    Route::get('/facility/diagnostic', [DiagnosticCentreController::class, 'dashboard'])
        ->name('portals.diagnostic.dashboard');
});
