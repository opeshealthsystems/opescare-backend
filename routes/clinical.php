<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyIntegrationClient;

/*
|--------------------------------------------------------------------------
| Clinical Module Routes — OpesCare Finder extraction
|--------------------------------------------------------------------------
| Surface reduced from the platform on 2026-09-02; the platform's sealed
| route files remain authoritative for the platform.
|
| Only the prescriptions endpoints survive: a prescription is the clinical
| artefact the Medicine Finder journey starts from (issued by a clinician,
| dispensed at a pharmacy, found by the patient). Everything else in the
| platform's clinical.php (death records, mortuary, MDR-TB, HIV counselling,
| AEFI, palliative, occupational health, certificates, surgical, postnatal,
| psychiatric, ADR, perioperative, specialty diagnostics) was deleted with
| its modules.
|
| Loaded from AppServiceProvider::boot() — this file must exist even when
| empty. VerifyIntegrationClient puts facility_id on request attributes.
*/

Route::middleware([VerifyIntegrationClient::class])->prefix('v1')->group(function () {

    // ── Prescriptions ─────────────────────────────────────────────────────
    Route::get('prescriptions',                    [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'index']);
    Route::post('prescriptions',                   [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'store']);
    Route::get('prescriptions/{prescription}',     [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'show']);
});
