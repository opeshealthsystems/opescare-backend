<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\PartnerGovernanceController;

/*
|--------------------------------------------------------------------------
| Partner Governance API Routes
|--------------------------------------------------------------------------
|
| All partner governance routes require:
|  - VerifyIntegrationClient: valid B2B API client credential
|  - RequireApiAdminRole (api.admin): caller must be a platform/partner admin
|
| [RBAC FIX] Previously the entire partner-governance group had NO middleware.
| approvePartner, suspendPartner, certifyIntegration, enableProduction were
| fully public — any HTTP client could call them with no authentication at all.
|
*/

// Partner trust-level smoke endpoint — authenticates via X-Partner-ID through
// VerifyPartnerTrustLevel (a separate partner-facing mechanism), NOT via the
// admin client-credential stack below. Intentionally outside the admin group.
Route::get('/partner-governance/test-access', function () {
    return response()->json(['status' => 'success']);
})->middleware(\App\Http\Middleware\VerifyPartnerTrustLevel::class);

Route::prefix('partner-governance')
    ->middleware(['verify.integration.client', 'api.admin'])
    ->group(function () {

        // Partner Applications
        Route::get('/partners', [PartnerGovernanceController::class, 'listPartners']);
        Route::post('/partners/{id}/approve', [PartnerGovernanceController::class, 'approvePartner']);
        Route::post('/partners/{id}/suspend', [PartnerGovernanceController::class, 'suspendPartner']);

        // Partner Documents
        Route::post('/partners/{id}/documents/{documentId}/verify', [PartnerGovernanceController::class, 'verifyDocument']);

        // Partner Agreements
        Route::post('/partners/{id}/agreements/{agreementId}/mark-signed', [PartnerGovernanceController::class, 'markAgreementSigned']);

        // Integrations
        Route::post('/partners/{id}/integrations/{integrationId}/certify', [PartnerGovernanceController::class, 'certifyIntegration']);
        Route::post('/partners/{id}/integrations/{integrationId}/enable-production', [PartnerGovernanceController::class, 'enableProduction']);

        // Risk scoring
        Route::post('/partners/{id}/risk-events', [PartnerGovernanceController::class, 'recordRiskEvent']);

        // Stubs — to be implemented when permission/integration/case services are ready
        Route::get('/partners/{id}/documents', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/partners/{id}/agreements', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/partners/{id}/contribution-permissions', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/partners/{id}/access-permissions', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/partners/{id}/integrations', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/cases', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Not yet implemented.'], 501);
        });
    });
