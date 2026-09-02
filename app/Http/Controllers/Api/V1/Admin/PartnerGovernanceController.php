<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Services\PartnerApplicationService;
use App\Modules\Partners\Services\PartnerRiskScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerGovernanceController extends Controller
{
    private PartnerApplicationService $applicationService;
    private PartnerRiskScoreService   $riskScores;

    public function __construct(
        PartnerApplicationService $applicationService,
        PartnerRiskScoreService   $riskScores
    ) {
        $this->applicationService = $applicationService;
        $this->riskScores         = $riskScores;
    }

    public function listPartners(Request $request)
    {
        // Simple listing logic
        $status = $request->query('status');
        
        $query = Partner::query()->with(['documents', 'agreements'])->orderBy('created_at', 'desc');
        
        if ($status) {
            $query->where('status', $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function approvePartner(Request $request, string $id)
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();
        
        $partner = $this->applicationService->approveApplication($partner);

        return response()->json([
            'status' => 'success',
            'message' => __('api.partner_application_approved'),
            'partner' => $partner
        ]);
    }

    public function suspendPartner(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $partner = Partner::where('uuid', $id)->firstOrFail();
        
        $partner = $this->applicationService->suspendPartner($partner, $request->input('reason'));

        return response()->json([
            'status' => 'success',
            'message' => __('api.partner_suspended'),
            'partner' => $partner
        ]);
    }

    public function verifyDocument(Request $request, string $id, string $documentId)
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();
        $document = \App\Modules\Partners\Models\PartnerDocument::where('id', $documentId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        $service = app(\App\Modules\Partners\Services\PartnerVerificationService::class);
        $document = $service->verifyDocument($document, $request->user()?->id ?? 'system', $request->input('notes'));

        return response()->json([
            'status' => 'success',
            'message' => __('api.document_verified'),
            'document' => $document
        ]);
    }

    public function markAgreementSigned(Request $request, string $id, string $agreementId)
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();
        $agreement = \App\Modules\Partners\Models\PartnerAgreement::where('id', $agreementId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        $service = app(\App\Modules\Partners\Services\PartnerAgreementService::class);
        $agreement = $service->markAgreementSigned($agreement, $request->user()?->id ?? 'system');

        return response()->json([
            'status' => 'success',
            'message' => __('api.agreement_signed'),
            'agreement' => $agreement
        ]);
    }

    public function certifyIntegration(Request $request, string $id, string $integrationId)
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();
        $integration = \App\Modules\Partners\Models\PartnerIntegration::where('id', $integrationId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        $service = app(\App\Modules\Partners\Services\PartnerIntegrationGovernanceService::class);
        $integration = $service->certifyIntegration($integration, $request->user()?->id ?? 'system');

        return response()->json([
            'status' => 'success',
            'message' => __('api.integration_certified'),
            'integration' => $integration
        ]);
    }

    public function enableProduction(Request $request, string $id, string $integrationId)
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();
        $integration = \App\Modules\Partners\Models\PartnerIntegration::where('id', $integrationId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        $service = app(\App\Modules\Partners\Services\PartnerIntegrationGovernanceService::class);
        
        try {
            $integration = $service->enableProduction($integration, $request->user()?->id ?? 'system');
            return response()->json([
                'status' => 'success',
                'message' => __('api.production_api_enabled'),
                'integration' => $integration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Record a risk event against a partner.
     * Automatically escalates to critical governance if score threshold exceeded.
     * Body: { risk_factor, severity: low|medium|high|critical, score_delta, actor_id? }
     */
    public function recordRiskEvent(Request $request, string $id): JsonResponse
    {
        $partner = Partner::where('uuid', $id)->firstOrFail();

        $validated = $request->validate([
            'risk_factor'  => ['required', 'string', 'max:255'],
            'severity'     => ['required', 'in:low,medium,high,critical'],
            'score_delta'  => ['required', 'integer', 'min:1', 'max:100'],
            'actor_id'     => ['nullable', 'string'],
        ]);

        $riskScore = $this->riskScores->recordRiskEvent(
            $partner,
            $validated['risk_factor'],
            $validated['severity'],
            $validated['score_delta'],
            $validated['actor_id'] ?? $request->user()?->id ?? 'api'
        );

        return response()->json([
            'status'  => 'success',
            'message' => $riskScore->risk_level === 'critical'
                ? 'Risk event recorded. Partner escalated to critical — governance case opened and partner suspended.'
                : 'Risk event recorded.',
            'data'    => $riskScore,
        ], 201);
    }
}
