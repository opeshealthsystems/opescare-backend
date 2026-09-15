<?php

// Registered from AppServiceProvider. Routes for code imported from the platform
// (docs/PLATFORM_IMPORTS.md). The middleware stack is declared here next to the routes it gates.

use App\Http\Controllers\MedicalId\FacilityGoLiveReadinessController;
use App\Http\Controllers\MedicalId\OnboardingPortalController;
use App\Http\Controllers\MedicalId\SecurityOperationsController;
use Illuminate\Support\Facades\Route;

/*
 * Group D — facility go-live readiness, onboarding portal, security operations.
 *
 * Same stack as the existing /admin/security desk in routes/web.php:
 * RequirePlatformAdmin makes everything under /admin/* platform tier, so a
 * facility-tier admin gets 403. Nothing here reads a facility id from a header,
 * body or session: the facility is the route id of a platform-wide page.
 *
 * The existing Finder routes /admin/security, /admin/security/emergency-access
 * (break-glass review), /admin/security/incidents and /admin/security/audit are
 * NOT redeclared. Breach steps live under /admin/security/breaches because
 * POST /admin/security/incidents/{id} already exists and would swallow
 * /admin/security/incidents/breaches.
 */
Route::middleware(['web', 'auth', 'mfa.verified', 'platform.admin'])->group(function () {
    // Onboarding portal
    Route::get('/admin/onboarding', [OnboardingPortalController::class, 'index'])->name('portals.admin.onboarding');
    Route::get('/admin/onboarding/{facility}', [OnboardingPortalController::class, 'show'])->name('portals.admin.onboarding.show');
    Route::post('/admin/onboarding/{facility}/mark', [OnboardingPortalController::class, 'markItem'])->name('portals.admin.onboarding.mark');
    Route::post('/admin/onboarding/{facility}/approve', [OnboardingPortalController::class, 'approve'])->name('portals.admin.onboarding.approve');

    // Readiness score + checklist for one facility
    Route::get('/admin/facilities/{id}/readiness', [FacilityGoLiveReadinessController::class, 'show'])->name('portals.admin.facilities.readiness');
    Route::post('/admin/facilities/{id}/readiness/score', [FacilityGoLiveReadinessController::class, 'computeScore'])->name('portals.admin.facilities.readiness.score');

    // Access review
    Route::get('/admin/security/access-review', [SecurityOperationsController::class, 'accessReviews'])->name('portals.admin.security.access_review');
    Route::post('/admin/security/access-review', [SecurityOperationsController::class, 'initiateAccessReview'])->name('portals.admin.security.access_review.store');
    Route::post('/admin/security/access-review/{id}/complete', [SecurityOperationsController::class, 'completeAccessReview'])->name('portals.admin.security.access_review.complete');

    // Suspicious access
    Route::get('/admin/security/suspicious-access', [SecurityOperationsController::class, 'suspiciousAccess'])->name('portals.admin.security.suspicious_access');
    Route::post('/admin/security/suspicious-access/scan', [SecurityOperationsController::class, 'runDetection'])->name('portals.admin.security.suspicious_access.scan');
    Route::post('/admin/security/suspicious-access/{id}/review', [SecurityOperationsController::class, 'reviewFlag'])->name('portals.admin.security.suspicious_access.review');

    // Breach workflow (open → contain → notify authority → close)
    Route::get('/admin/security/breaches', [SecurityOperationsController::class, 'breaches'])->name('portals.admin.security.breaches');
    Route::post('/admin/security/breaches', [SecurityOperationsController::class, 'openBreach'])->name('portals.admin.security.breaches.store');
    Route::post('/admin/security/breaches/{id}/contain', [SecurityOperationsController::class, 'containBreach'])->name('portals.admin.security.breaches.contain');
    Route::post('/admin/security/breaches/{id}/notify', [SecurityOperationsController::class, 'notifyBreach'])->name('portals.admin.security.breaches.notify');
    Route::post('/admin/security/breaches/{id}/close', [SecurityOperationsController::class, 'closeBreach'])->name('portals.admin.security.breaches.close');

    // Compliance export (metadata / aggregates only)
    Route::get('/admin/security/compliance-export', [SecurityOperationsController::class, 'complianceExport'])->name('portals.admin.security.compliance_export');
    Route::post('/admin/security/compliance-export', [SecurityOperationsController::class, 'downloadComplianceExport'])->name('portals.admin.security.compliance_export.download');
});
