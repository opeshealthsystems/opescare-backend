<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyIntegrationClient;

/*
|--------------------------------------------------------------------------
| Clinical Module Routes — Group 3 + wired module controllers
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file. All routes use VerifyIntegrationClient middleware
| so facility_id is always resolved from the bearer token context.
|--------------------------------------------------------------------------
*/

Route::middleware([VerifyIntegrationClient::class])->prefix('v1')->group(function () {

    // ── Prescriptions ─────────────────────────────────────────────────────
    Route::get('prescriptions',                    [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'index']);
    Route::post('prescriptions',                   [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'store']);
    Route::get('prescriptions/{prescription}',     [\App\Http\Controllers\Api\V1\PrescriptionController::class, 'show']);

    // ── Death Certificates ────────────────────────────────────────────────
    Route::get('death-records',                    [\App\Http\Controllers\Api\V1\DeathCertificateController::class, 'index']);
    Route::post('death-records',                   [\App\Http\Controllers\Api\V1\DeathCertificateController::class, 'store']);
    Route::get('death-records/{record}',           [\App\Http\Controllers\Api\V1\DeathCertificateController::class, 'show']);
    Route::post('death-records/{record}/certify',  [\App\Http\Controllers\Api\V1\DeathCertificateController::class, 'certify']);

    // ── Mortuary ──────────────────────────────────────────────────────────
    Route::get('mortuary',                                                  [\App\Http\Controllers\Api\V1\MortuaryController::class, 'index']);
    Route::post('mortuary/admit',                                           [\App\Http\Controllers\Api\V1\MortuaryController::class, 'admit']);
    Route::get('mortuary/{record}',                                         [\App\Http\Controllers\Api\V1\MortuaryController::class, 'show']);
    Route::post('mortuary/{record}/autopsy',                                [\App\Http\Controllers\Api\V1\MortuaryController::class, 'createAutopsy']);
    Route::post('mortuary/{record}/release-burial-permit',                  [\App\Http\Controllers\Api\V1\MortuaryController::class, 'releaseBurialPermit']);
    Route::post('mortuary/{record}/identify',                               [\App\Http\Controllers\Api\V1\MortuaryController::class, 'identifyBody']);

    // ── MDR-TB ────────────────────────────────────────────────────────────
    Route::get('mdr-cases',                        [\App\Http\Controllers\Api\V1\MdrCaseController::class, 'index']);
    Route::post('mdr-cases',                       [\App\Http\Controllers\Api\V1\MdrCaseController::class, 'store']);

    // ── HIV Counselling ───────────────────────────────────────────────────
    Route::get('hiv-counselling',                  [\App\Http\Controllers\Api\V1\HivCounsellingController::class, 'index']);
    Route::post('hiv-counselling',                 [\App\Http\Controllers\Api\V1\HivCounsellingController::class, 'store']);

    // ── AEFI Reports ──────────────────────────────────────────────────────
    Route::get('aefi-reports',                     [\App\Http\Controllers\Api\V1\AefiController::class, 'index']);
    Route::post('aefi-reports',                    [\App\Http\Controllers\Api\V1\AefiController::class, 'store']);

    // ── Palliative Care ───────────────────────────────────────────────────
    Route::get('palliative-care',                  [\App\Http\Controllers\Api\V1\PalliativeCareController::class, 'index']);
    Route::post('palliative-care',                 [\App\Http\Controllers\Api\V1\PalliativeCareController::class, 'store']);

    // ── Occupational Health ───────────────────────────────────────────────
    Route::get('occupational-health',              [\App\Http\Controllers\Api\V1\OccupationalHealthController::class, 'index']);
    Route::post('occupational-health',             [\App\Http\Controllers\Api\V1\OccupationalHealthController::class, 'store']);

    // ── Medical Certificates ──────────────────────────────────────────────
    Route::post('medical-certificates',            [\App\Http\Controllers\Api\V1\MedicalCertificateController::class, 'issue']);

    // ── Surgical Reports ──────────────────────────────────────────────────
    Route::post('surgical-reports',                [\App\Http\Controllers\Api\V1\SurgicalReportController::class, 'store']);

    // ── Postnatal Care ────────────────────────────────────────────────────
    Route::post('postnatal-visits',                [\App\Http\Controllers\Api\V1\MaternityController::class, 'recordPostnatalVisit']);

    // ── Psychiatric Assessments ───────────────────────────────────────────
    Route::get('psychiatric-assessments',          [\App\Http\Controllers\Api\V1\PsychiatricAssessmentController::class, 'index']);
    Route::post('psychiatric-assessments',         [\App\Http\Controllers\Api\V1\PsychiatricAssessmentController::class, 'store']);
    Route::get('psychiatric-assessments/{assessment}', [\App\Http\Controllers\Api\V1\PsychiatricAssessmentController::class, 'show']);

    // ── Adverse Drug Reaction Reports ─────────────────────────────────────
    Route::get('adr-reports',                      [\App\Http\Controllers\Api\V1\AdrReportController::class, 'index']);
    Route::post('adr-reports',                     [\App\Http\Controllers\Api\V1\AdrReportController::class, 'store']);
    Route::get('adr-reports/{report}',             [\App\Http\Controllers\Api\V1\AdrReportController::class, 'show']);

    // ── Mortuary extended (autopsy consent, embalming, body release) ──────
    Route::post('mortuary/{record}/autopsy-consent', [\App\Http\Controllers\Api\V1\MortuaryController::class, 'recordAutopsyConsent']);
    Route::post('mortuary/{record}/embalm',          [\App\Http\Controllers\Api\V1\MortuaryController::class, 'recordEmbalming']);

    // ── Perioperative (anaesthesia, surgical safety checklist, postop) ────
    Route::get('perioperative',                    [\App\Http\Controllers\Api\V1\PerioperativeController::class, 'index']);
    Route::post('perioperative',                   [\App\Http\Controllers\Api\V1\PerioperativeController::class, 'store']);
    Route::get('perioperative/{record}',           [\App\Http\Controllers\Api\V1\PerioperativeController::class, 'show']);

    // ── Specialty Diagnostics (echo, ECG, endoscopy) ──────────────────────
    Route::get('specialty-diagnostics',            [\App\Http\Controllers\Api\V1\SpecialtyDiagnosticsController::class, 'index']);
    Route::post('specialty-diagnostics',           [\App\Http\Controllers\Api\V1\SpecialtyDiagnosticsController::class, 'store']);
    Route::get('specialty-diagnostics/{report}',   [\App\Http\Controllers\Api\V1\SpecialtyDiagnosticsController::class, 'show']);

    // ── Nursing Records (MAR, progress, handover, wound, incident, etc.) ──
    Route::get('nursing-records',                  [\App\Http\Controllers\Api\V1\NursingRecordController::class, 'index']);
    Route::post('nursing-records',                 [\App\Http\Controllers\Api\V1\NursingRecordController::class, 'store']);
    Route::get('nursing-records/{record}',         [\App\Http\Controllers\Api\V1\NursingRecordController::class, 'show']);

    // ── Allied Health (physio, OT, speech, nutrition, social work) ────────
    Route::get('allied-health',                    [\App\Http\Controllers\Api\V1\AlliedHealthController::class, 'index']);
    Route::post('allied-health',                   [\App\Http\Controllers\Api\V1\AlliedHealthController::class, 'store']);
    Route::get('allied-health/{assessment}',       [\App\Http\Controllers\Api\V1\AlliedHealthController::class, 'show']);

    // ── Pediatric Records (newborn, child health, growth chart, stillbirth)
    Route::get('pediatric',                        [\App\Http\Controllers\Api\V1\PediatricController::class, 'index']);
    Route::post('pediatric',                       [\App\Http\Controllers\Api\V1\PediatricController::class, 'store']);
    Route::get('pediatric/{record}',               [\App\Http\Controllers\Api\V1\PediatricController::class, 'show']);

    // ── Special Care (ICU, NICU, dialysis, chemotherapy) ─────────────────
    Route::get('special-care',                     [\App\Http\Controllers\Api\V1\SpecialCareController::class, 'index']);
    Route::post('special-care',                    [\App\Http\Controllers\Api\V1\SpecialCareController::class, 'store']);
    Route::get('special-care/{record}',            [\App\Http\Controllers\Api\V1\SpecialCareController::class, 'show']);

    // ── Clinical Review (MDR, PMV, CMN, VBA, AER, MLR, NDR, MAL) ─────────
    Route::get('clinical-reviews',                 [\App\Http\Controllers\Api\V1\ClinicalReviewController::class, 'index']);
    Route::post('clinical-reviews',                [\App\Http\Controllers\Api\V1\ClinicalReviewController::class, 'store']);
    Route::get('clinical-reviews/{record}',        [\App\Http\Controllers\Api\V1\ClinicalReviewController::class, 'show']);

    // ── Ward Admin (LAMA, TRF, REQ, PCF, PCS, DPR, MRC, BTR, BBR, DGL, ARV, FIT, ORT, CPR, MHI)
    Route::get('ward-admin',                       [\App\Http\Controllers\Api\V1\WardAdminController::class, 'index']);
    Route::post('ward-admin',                      [\App\Http\Controllers\Api\V1\WardAdminController::class, 'store']);
    Route::get('ward-admin/{record}',              [\App\Http\Controllers\Api\V1\WardAdminController::class, 'show']);

    // ── Lab / Pathology (LAB, PATH, PMR) ──────────────────────────────────
    Route::get('lab-reports',                      [\App\Http\Controllers\Api\V1\LabPathController::class, 'index']);
    Route::post('lab-reports',                     [\App\Http\Controllers\Api\V1\LabPathController::class, 'store']);
    Route::get('lab-reports/{report}',             [\App\Http\Controllers\Api\V1\LabPathController::class, 'show']);
    Route::post('lab-reports/{report}/finalize',   [\App\Http\Controllers\Api\V1\LabPathController::class, 'finalize']);
});
