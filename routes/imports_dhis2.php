<?php

// Registered from AppServiceProvider. Routes for code imported from the platform
// (docs/PLATFORM_IMPORTS.md). The middleware stack is declared here next to the routes it gates.
//
// Group E — DHIS2 aggregate reporting to MINSANTE.
//
//   /admin/public-health/dhis2/*   platform tier only (platform.admin =
//       RequirePlatformAdmin). Draft -> review -> approve -> push; every step is
//       a POST and is audited. Nothing here is facility-scoped and nothing is
//       patient-level: drafts hold counts per facility per indicator only.

use App\Http\Controllers\MedicalId\AdminDhis2Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'throttle:portal'])->group(function () {
    Route::get('/admin/public-health/dhis2', [AdminDhis2Controller::class, 'index'])->name('portals.admin.dhis2');
    Route::post('/admin/public-health/dhis2/drafts', [AdminDhis2Controller::class, 'generate'])->name('portals.admin.dhis2.generate');
    Route::post('/admin/public-health/dhis2/test-connection', [AdminDhis2Controller::class, 'testConnection'])->name('portals.admin.dhis2.test');
    Route::get('/admin/public-health/dhis2/drafts/{id}', [AdminDhis2Controller::class, 'show'])->whereUuid('id')->name('portals.admin.dhis2.drafts.show');
    Route::post('/admin/public-health/dhis2/drafts/{id}/regenerate', [AdminDhis2Controller::class, 'regenerate'])->whereUuid('id')->name('portals.admin.dhis2.drafts.regenerate');
    Route::post('/admin/public-health/dhis2/drafts/{id}/approve', [AdminDhis2Controller::class, 'approve'])->whereUuid('id')->name('portals.admin.dhis2.drafts.approve');
    Route::post('/admin/public-health/dhis2/drafts/{id}/push', [AdminDhis2Controller::class, 'push'])->whereUuid('id')->name('portals.admin.dhis2.drafts.push');
    Route::get('/admin/public-health/dhis2/mappings', [AdminDhis2Controller::class, 'mappings'])->name('portals.admin.dhis2.mappings');
    Route::post('/admin/public-health/dhis2/mappings/org-units', [AdminDhis2Controller::class, 'saveOrgUnit'])->name('portals.admin.dhis2.mappings.org-unit');
    Route::post('/admin/public-health/dhis2/mappings/data-elements', [AdminDhis2Controller::class, 'saveDataElement'])->name('portals.admin.dhis2.mappings.data-element');
});
