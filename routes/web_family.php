<?php

use App\Http\Controllers\MedicalId\FamilyController;
use App\Http\Controllers\MedicalId\PatientPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Family / dependents — web portal routes
|--------------------------------------------------------------------------
| Registered from AppServiceProvider so parallel work does not collide in
| routes/web.php. The middleware stack below is the patient-portal group's
| stack in routes/web.php, copied verbatim; the inner group adds
| `guardian.context`, which turns session('guardian_viewing_patient_id')
| into the `viewing_patient` / `guardian_link` request attributes so every
| page here shows the DEPENDENT's record while a guardian is switched.
|
| The record pages (immunisations, timeline, documents, allergies,
| conditions, care plans, access logs, the printable Health ID card) had
| controller methods and no routes at all; they all resolve the viewing
| patient already, so wiring them here makes them dependent-aware at once.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal'])->group(function () {

    // ── Guardian dashboard + invitations addressed to me ───────────────
    // Outside `guardian.context` on purpose: these pages are about the
    // signed-in user's own links, whichever record happens to be open.
    Route::get('/patient/dependents', [FamilyController::class, 'guardianDashboard'])->name('portals.guardian.dashboard');

    Route::get('/patient/family/invitations',                 [FamilyController::class, 'invitationsForMe'])->name('portals.patient.family.invitations');
    Route::post('/patient/family/invitations/{id}/accept',    [FamilyController::class, 'acceptIncoming'])->name('portals.patient.family.invitations.accept');
    Route::post('/patient/family/invitations/{id}/decline',   [FamilyController::class, 'declineIncoming'])->name('portals.patient.family.invitations.decline');

    // ── Record pages that follow the switch ────────────────────────────
    Route::middleware(['guardian.context'])->group(function () {
        Route::get('/patient/immunizations',           [PatientPortalController::class, 'immunizations'])->name('portals.patient.immunizations');
        Route::get('/patient/timeline',                [PatientPortalController::class, 'timeline'])->name('portals.patient.timeline');
        Route::get('/patient/documents',               [PatientPortalController::class, 'documents'])->name('portals.patient.documents');
        Route::get('/patient/documents/{id}/download', [PatientPortalController::class, 'documentDownload'])->name('portals.patient.documents.download');

        // Documents a patient can take away with them. Each is rendered from
        // the record at the moment of the request and never stored, so the
        // sheet is always current and there is no copy of anyone's record
        // sitting on a disk waiting to go stale or leak. `{type}` is one of
        // PatientDocumentService::types(); `{record}` names the lab order,
        // prescription or admission the sheet is about.
        Route::get('/patient/documents/{type}/{record}.pdf', [PatientPortalController::class, 'renderDocument'])
            ->where('type', '[a-z_]{4,32}')
            ->whereUuid('record')
            ->middleware('throttle:portal.qr')
            ->name('portals.patient.documents.render');

        Route::get('/patient/documents/{type}.pdf', [PatientPortalController::class, 'renderDocument'])
            ->where('type', '[a-z_]{4,32}')
            ->middleware('throttle:portal.qr')
            ->name('portals.patient.documents.render-whole');

        Route::get('/patient/allergies',               [PatientPortalController::class, 'allergies'])->name('portals.patient.allergies');
        Route::get('/patient/clinical-history',        [PatientPortalController::class, 'clinicalHistory'])->name('portals.patient.clinical');
        Route::get('/patient/care-plans',              [PatientPortalController::class, 'carePlans'])->name('portals.patient.care-plans');
        Route::get('/patient/access-logs',             [PatientPortalController::class, 'accessLogs'])->name('portals.patient.access-logs');
        // The printable CR80 card mints a card QR, so it shares the QR rate limit.
        Route::get('/patient/health-id-card.pdf',      [PatientPortalController::class, 'downloadHealthIdCard'])
            ->middleware('throttle:portal.qr')
            ->name('portals.patient.health-id-card');
    });
});
