<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\SecurityOperations\Services\AuditExplorerService;
use App\Modules\SecurityOperations\Services\SuspiciousAccessDetectionService;
use App\Modules\SecurityOperations\Services\BreachWorkflowService;
use App\Modules\SecurityOperations\Services\AccessReviewService;
use App\Modules\SecurityOperations\Services\SecurityIncidentService;
use App\Modules\SecurityOperations\Services\ApiAbuseDetectionService;
use App\Modules\SecurityOperations\Services\ComplianceExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SecurityOperationsController — Audit, Compliance & Security Operations Center API.
 *
 * All actions in this controller require audit.view_sensitive or security.manage permission.
 * Access to this controller is itself logged.
 */
class SecurityOperationsController extends Controller
{
    public function __construct(
        private AuditExplorerService             $auditExplorer,
        private SuspiciousAccessDetectionService $suspiciousAccess,
        private BreachWorkflowService            $breachWorkflow,
        private AccessReviewService              $accessReview,
        private SecurityIncidentService          $incidents,
        private ApiAbuseDetectionService         $apiAbuse,
        private ComplianceExportService          $compliance
    ) {}

    // ── Audit Explorer ─────────────────────────────────────────────────────

    public function searchAuditLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id'    => ['nullable', 'uuid'],
            'patient_id'  => ['nullable', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
            'action'      => ['nullable', 'string'],
            'module'      => ['nullable', 'string'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json(
            $this->auditExplorer->search($request->user()->id, $validated)
        );
    }

    // ── Suspicious Access ──────────────────────────────────────────────────

    public function listSuspiciousFlags(Request $request): JsonResponse
    {
        return response()->json(
            \App\Models\SuspiciousAccessFlag::scopeOpen(
                \App\Models\SuspiciousAccessFlag::query()
            )->orderByDesc('created_at')->paginate(50)
        );
    }

    public function reviewFlag(Request $request, string $flagId): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:escalate,dismiss'],
            'notes'  => ['nullable', 'string'],
        ]);

        $flag = \App\Models\SuspiciousAccessFlag::findOrFail($flagId);
        $validated['action'] === 'escalate'
            ? $flag->escalate($request->user()->id)
            : $flag->dismiss($request->user()->id, $validated['notes'] ?? null);

        return response()->json($flag->fresh());
    }

    // ── Breach Reports ─────────────────────────────────────────────────────

    public function openBreach(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string'],
            'affected_count'  => ['nullable', 'integer', 'min:0'],
            'data_types'      => ['nullable', 'array'],
            'initial_severity' => ['required', 'in:low,medium,high,critical'],
        ]);

        return response()->json(
            $this->breachWorkflow->openBreach($validated, $request->user()->id),
            201
        );
    }

    public function markBreachNotified(Request $request, string $breachId): JsonResponse
    {
        return response()->json(
            $this->breachWorkflow->markNotified($breachId, $request->user()->id)
        );
    }

    public function closeBreach(Request $request, string $breachId): JsonResponse
    {
        $validated = $request->validate(['resolution' => ['required', 'string']]);
        return response()->json(
            $this->breachWorkflow->closeBreach($breachId, $request->user()->id, $validated['resolution'])
        );
    }

    // ── Access Reviews ─────────────────────────────────────────────────────

    public function initiateAccessReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'uuid'],
            'reason'         => ['required', 'string'],
        ]);

        return response()->json(
            $this->accessReview->initiateReview(
                $validated['target_user_id'],
                $request->user()->id,
                $validated['reason']
            ),
            201
        );
    }

    public function completeAccessReview(Request $request, string $reviewId): JsonResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'in:retained,modified,revoked'],
            'notes'   => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->accessReview->completeReview($reviewId, $request->user()->id, $validated['outcome'], $validated['notes'] ?? null)
        );
    }

    // ── Compliance Exports ─────────────────────────────────────────────────

    public function requestComplianceExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_type' => ['required', 'in:audit_log,access_review,breach_reports,suspicious_flags,permission_changes'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
        ]);

        $export = $this->compliance->requestExport(
            $validated['export_type'],
            $request->user()->id,
            $validated
        );

        return response()->json(['export_id' => $export->id, 'status' => $export->status], 202);
    }
}
