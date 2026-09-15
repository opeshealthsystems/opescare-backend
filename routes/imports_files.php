<?php

// Registered from AppServiceProvider. Routes for code imported from the platform
// (docs/PLATFORM_IMPORTS.md). The middleware stack is declared here next to the routes it gates.

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\RadiologyReportController;
use App\Http\Controllers\MedicalId\FileStorageController;
use App\Http\Controllers\MedicalId\PatientFileController;
use App\Http\Middleware\VerifyIntegrationClient;

/*
|--------------------------------------------------------------------------
| Group A — patient file attachments + radiology report release
|--------------------------------------------------------------------------
| Imported from the platform's /portals/staff/files (FileStorageController)
| and api.php v1/radiology (RadiologyReportController).
|
| Facility desk: the chart's stack (routes/chart.php) + capability:clinical_chart.
| The controller adds the chart's patient gate (no basis -> 404 + denied row)
| and scopes every file to DeskFacilityContext (another facility's file -> 404).
| The download route is `signed`: the list page mints 5-minute links.
| There is no DELETE route — a file is voided with a reason, bytes kept.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:clinical_chart'])->group(function () {
    Route::get('/facility/patients/{patientId}/files', [FileStorageController::class, 'index'])
        ->name('portals.files.index');
    Route::get('/facility/patients/{patientId}/files/upload', [FileStorageController::class, 'create'])
        ->name('portals.files.create');
    Route::post('/facility/patients/{patientId}/files', [FileStorageController::class, 'store'])
        ->name('portals.files.store');

    Route::get('/facility/files/{id}', [FileStorageController::class, 'download'])
        ->middleware('signed')
        ->name('portals.files.download');
    Route::post('/facility/files/{id}/void', [FileStorageController::class, 'void'])
        ->name('portals.files.void');
    Route::post('/facility/files/{id}/release', [FileStorageController::class, 'release'])
        ->name('portals.files.release');
});

/*
| Patient portal: the stack of routes/web_family.php's record pages (incl.
| guardian.context). Signed, short-lived links from /patient/documents.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal', 'guardian.context'])->group(function () {
    Route::get('/patient/files/{id}', [PatientFileController::class, 'download'])
        ->middleware('signed')
        ->name('portals.patient.files.download');
});

/*
| Integration API — the platform's v1/radiology routes, moved out of the sealed
| routes/api.php. Same middleware as the platform (VerifyIntegrationClient puts
| facility_id on the request attributes). Files loaded this way do not get the
| automatic /api prefix, so it is spelled out.
*/
Route::prefix('api/v1/radiology')->middleware(['api', VerifyIntegrationClient::class])->group(function () {
    Route::post('reports',                                [RadiologyReportController::class, 'store']);
    Route::get('reports/{id}',                            [RadiologyReportController::class, 'show']);
    Route::patch('reports/{id}/finalize',                 [RadiologyReportController::class, 'finalize']);
    Route::patch('reports/{id}/amend',                    [RadiologyReportController::class, 'amend']);
    Route::post('reports/{id}/distribute',                [RadiologyReportController::class, 'distribute']);
    Route::get('facilities/{facilityId}/reports/pending', [RadiologyReportController::class, 'pending']);
});
