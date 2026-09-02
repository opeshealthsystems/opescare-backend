<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsentRequestResource;
use App\Http\Resources\DataExportRequestResource;
use App\Models\AccessLog;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Modules\Governance\Services\ConsentService;
use App\Modules\Governance\Services\CorrectionRequestService;
use App\Modules\Governance\Services\DataExportService;
use Illuminate\Http\Request;

/**
 * MobileGovernanceController
 *
 * Handles patient-facing governance actions (consent, data corrections, exports)
 * from the mobile app. The mobile app authenticates via AuthenticateMobilePatient
 * middleware which injects patient_id and user_id into request attributes.
 *
 * SECURITY:
 *  - user_id / patient_id MUST come from $request->attributes (set by middleware).
 *    NEVER from request body, query string, or hardcoded fallback.
 *  - Hardcoded UUID fallbacks '00000000-...' have been removed entirely.
 *    Missing identity → 401 IDENTITY_UNRESOLVABLE.
 */
class MobileGovernanceController extends Controller
{
    public function __construct(
        private readonly ConsentService $consentService,
        private readonly CorrectionRequestService $correctionService,
        private readonly DataExportService $exportService
    ) {}

    // ── Consent ───────────────────────────────────────────────────────────────

    public function listConsentRequests(Request $request)
    {
        $patientId = $request->attributes->get('patient_id');
        if (!$patientId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_patient')], 401);
        }

        $requests = ConsentRequest::where('patient_id', $patientId)
            ->with(['requestingFacility:id,name,type', 'grant'])
            ->latest()
            ->get();

        return response()->json(ConsentRequestResource::collection($requests), 200);
    }

    public function approveConsent(Request $request, $id)
    {
        // [C-1 FIX] user_id from middleware — never from request body fallback
        $userId = $request->attributes->get('user_id') ?? $request->attributes->get('patient_user_id');
        if (!$userId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_user')], 401);
        }

        if (! $this->ownsConsentRequest($request, $id)) {
            return response()->json(['error' => 'NOT_FOUND', 'message' => __('api.not_found')], 404);
        }

        $grant = $this->consentService->approveConsent($id, $userId);

        return response()->json([
            'status'           => 'granted',
            'consent_grant_id' => $grant->id,
            'message'          => __('api.consent_request_approved'),
        ], 200);
    }

    public function denyConsent(Request $request, $id)
    {
        if (! $this->ownsConsentRequest($request, $id)) {
            return response()->json(['error' => 'NOT_FOUND', 'message' => __('api.not_found')], 404);
        }

        $consentRequest = $this->consentService->denyConsent($id);

        return response()->json([
            'status'             => 'denied',
            'consent_request_id' => $consentRequest->id,
            'message'            => __('api.consent_request_denied'),
        ], 200);
    }

    public function revokeConsent(Request $request, $id)
    {
        // [C-1 FIX] user_id from middleware — never from request body fallback
        $userId = $request->attributes->get('user_id') ?? $request->attributes->get('patient_user_id');
        if (!$userId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_user')], 401);
        }

        $patientId = $request->attributes->get('patient_id');
        $ownsGrant = $patientId && ConsentGrant::where('id', $id)
            ->where('patient_id', $patientId)
            ->exists();

        if (! $ownsGrant) {
            return response()->json(['error' => 'NOT_FOUND', 'message' => __('api.not_found')], 404);
        }

        $grant = $this->consentService->revokeConsent($id, $userId);

        return response()->json([
            'status'           => 'revoked',
            'consent_grant_id' => $grant->id,
            'message'          => __('api.consent_grant_revoked'),
        ], 200);
    }

    /**
     * ConsentService resolves consent records by bare findOrFail(), so without
     * this check any authenticated patient could approve, deny or revoke another
     * patient's consent simply by passing their record id (IDOR). Ownership is
     * always taken from the middleware-set patient_id, never from input.
     */
    private function ownsConsentRequest(Request $request, string $id): bool
    {
        $patientId = $request->attributes->get('patient_id');

        return (bool) $patientId && ConsentRequest::where('id', $id)
            ->where('patient_id', $patientId)
            ->exists();
    }

    // ── Access logs ───────────────────────────────────────────────────────────

    public function listAccessLogs(Request $request)
    {
        $patientId = $request->attributes->get('patient_id');
        if (!$patientId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_patient')], 401);
        }

        $logs = AccessLog::where('patient_id', $patientId)
            ->with('facility:id,name,type')
            ->latest()
            ->paginate(50);

        return response()->json($logs, 200);
    }

    // ── Correction requests ───────────────────────────────────────────────────

    public function createCorrectionRequest(Request $request)
    {
        $userId = $request->attributes->get('user_id') ?? $request->attributes->get('patient_user_id');
        $patientId = $request->attributes->get('patient_id');

        if (!$userId || !$patientId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_both')], 401);
        }

        $validated = $request->validate([
            'resource_type'          => ['required', 'string', 'max:100'],
            'resource_id'            => ['required', 'string', 'uuid'],
            'reason'                 => ['required', 'string', 'min:10', 'max:2000'],
            'supporting_document_id' => ['nullable', 'string', 'uuid'],
        ]);

        $corr = $this->correctionService->createRequest(
            $patientId,
            $userId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['reason'],
            $validated['supporting_document_id'] ?? null
        );

        return response()->json($corr, 201);
    }

    // ── Data export ───────────────────────────────────────────────────────────

    public function createExportRequest(Request $request)
    {
        $userId = $request->attributes->get('user_id') ?? $request->attributes->get('patient_user_id');
        $patientId = $request->attributes->get('patient_id');

        if (!$userId || !$patientId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_both')], 401);
        }

        $validated = $request->validate([
            'export_type' => ['required', 'string', 'in:full_record,encounters,prescriptions,lab_results,imaging'],
            'scope'       => ['nullable', 'array'],
        ]);

        $exp = $this->exportService->requestExport(
            $patientId,
            $userId,
            $validated['export_type'],
            $validated['scope'] ?? []
        );

        return response()->json($exp, 201);
    }

    public function listExportRequests(Request $request)
    {
        $patientId = $request->attributes->get('patient_id');
        if (!$patientId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_patient')], 401);
        }

        $requests = DataExportRequest::where('patient_id', $patientId)
            ->latest()
            ->get();

        return response()->json(DataExportRequestResource::collection($requests), 200);
    }

    public function downloadExport(Request $request, $id)
    {
        // [C-1 FIX] user_id from middleware — never from query string fallback
        $userId = $request->attributes->get('user_id') ?? $request->attributes->get('patient_user_id');
        if (!$userId) {
            return response()->json(['error' => 'IDENTITY_UNRESOLVABLE', 'message' => __('api.identity_unresolvable_user')], 401);
        }

        try {
            $export = $this->exportService->downloadExport($id, $userId);

            return response()->json([
                'status'   => 'downloaded',
                'export'   => $export,
                'message'  => __('api.file_downloaded'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
