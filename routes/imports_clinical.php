<?php

// Registered from AppServiceProvider. Routes for code imported from the platform
// (docs/PLATFORM_IMPORTS.md). The middleware stack is declared here next to the routes it gates.

use Illuminate\Support\Facades\Route;

// ── Group C ────────────────────────────────────────────────────────────────

// Drug-interaction check for the prescriber form (advisory JSON). Same gate as
// the prescriber desk; the facility is resolved by DeskFacilityContext inside
// the controller, never taken from the request.
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:prescribe'])->group(function () {
    Route::post('/facility/prescriptions/interaction-check', [\App\Http\Controllers\MedicalId\PrescriberController::class, 'interactionCheck'])
        ->middleware('throttle:portal')
        ->name('portals.prescriber.interaction_check');
});

// KPI dashboard — platform administrators only (/admin/* is platform-tier in
// RequirePlatformAdmin). Aggregates only; the CSV export carries no patient
// identifiers.
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'throttle:portal'])->group(function () {
    Route::get('/admin/kpi',            [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'index'])->name('portals.admin.kpi.index');
    Route::get('/admin/kpi/trend',      [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'trend'])->name('portals.admin.kpi.trend');
    Route::post('/admin/kpi/recompute', [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'recompute'])->name('portals.admin.kpi.recompute');
    Route::get('/admin/kpi/export.csv', [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'export'])->name('portals.admin.kpi.export');
});
