<?php

namespace App\Http\Controllers\Api\V1\PublicHealth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ReportType;
use App\Models\PublicHealthReport;
use App\Models\ReportItem;
use App\Models\ReportReview;
use App\Models\ReportStatusHistory;
use App\Models\ReportVersion;
use App\Models\ReportAssignment;
use App\Models\SubmissionProfile;
use App\Models\ReportSubmission;
use App\Models\ExportFile;
use App\Http\Resources\ReportTypeResource;
use App\Http\Resources\PublicHealthReportResource;
use App\Http\Resources\ReportVersionResource;
use App\Http\Resources\ReportStatusHistoryResource;
use App\Http\Resources\SubmissionProfileResource;
use App\Http\Resources\ReportSubmissionResource;
use App\Modules\PublicHealth\Services\DraftGenerationService;
use App\Modules\PublicHealth\Services\DataQualityCheckService;
use App\Modules\PublicHealth\Services\ExportService;
use App\Services\AuditLogger;

class PublicHealthController extends Controller
{
    private DraftGenerationService $draftService;
    private DataQualityCheckService $qualityService;
    private ExportService $exportService;

    public function __construct(
        DraftGenerationService $draftService,
        DataQualityCheckService $qualityService,
        ExportService $exportService
    ) {
        $this->draftService   = $draftService;
        $this->qualityService = $qualityService;
        $this->exportService  = $exportService;
    }

    /**
     * Resolve operator identity from middleware-set request attributes only.
     * Returns null if no trusted actor can be found.
     */
    private function resolveOperatorId(Request $request): ?string
    {
        if ($id = $request->user()?->id) {
            return $id;
        }
        $clientId = $request->attributes->get('integration_client_id');
        if ($clientId && Str::isUuid($clientId)) {
            return $clientId;
        }
        $providerId = $request->attributes->get('provider_id');
        if ($providerId && Str::isUuid($providerId)) {
            return $providerId;
        }
        return null;
    }

    public function getReportTypes()
    {
        return response()->json(ReportTypeResource::collection(ReportType::where('is_active', true)->get()));
    }

    public function getReports(Request $request)
    {
        $status = $request->query('status');
        $query = PublicHealthReport::with(['reportType', 'facility']);
        if ($status) {
            $query->where('status', $status);
        }
        return response()->json(PublicHealthReportResource::collection($query->get()));
    }

    public function getReport($id)
    {
        $report = PublicHealthReport::with(['reportType', 'facility', 'items', 'qualityChecks'])->find($id);
        if (!$report) {
            return response()->json(['error' => __('api.report_not_found')], 404);
        }
        return response()->json(PublicHealthReportResource::make($report));
    }

    public function generateDrafts(Request $request)
    {
        // [IDOR FIX] facility_id from middleware only — never from request body
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'FACILITY_UNRESOLVABLE', 'message' => __('api.facility_unresolved_bearer_token')], 403);
        }

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date',
        ]);

        $reports = $this->draftService->generateDrafts(
            $facilityId,
            $validated['period_start'],
            $validated['period_end']
        );

        return response()->json([
            'message'         => __('api.draft_reports_generated'),
            'generated_count' => count($reports),
            'reports'         => $reports
        ]);
    }

    public function getQualityChecks($id)
    {
        $report = PublicHealthReport::find($id);
        if (!$report) {
            return response()->json(['error' => __('api.report_not_found')], 404);
        }
        return response()->json($report->qualityChecks);
    }

    public function getDashboard(Request $request)
    {
        $draftsCount    = PublicHealthReport::where('status', 'draft')->count();
        $pendingCount   = PublicHealthReport::where('status', 'pending_review')->count();
        $approvedCount  = PublicHealthReport::where('status', 'approved_for_submission')->count();
        $submittedCount = PublicHealthReport::where('status', 'submitted')->count();

        return response()->json([
            'metrics' => [
                'draft_reports'          => $draftsCount,
                'reports_pending_review' => $pendingCount,
                'approved_for_submission'=> $approvedCount,
                'submitted_reports'      => $submittedCount
            ]
        ]);
    }

    public function getFacilityDashboard(Request $request, $facilityId)
    {
        $clientFacilityId = $request->attributes->get('facility_id');
        if ($clientFacilityId && $clientFacilityId !== $facilityId) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => __('api.analytics_access_denied'),
            ], 403);
        }

        $reports = PublicHealthReport::where('facility_id', $facilityId)->get();
        return response()->json([
            'facility_id'       => $facilityId,
            'total_reports'     => $reports->count(),
            'drafts'            => $reports->where('status', 'draft')->count(),
            'requires_correction' => $reports->where('status', 'requires_correction')->count()
        ]);
    }

    // Phase 2: Governance & Workflow APIs
    public function submitForReview($id, Request $request)
    {
        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $report->status = 'pending_review';
        $report->save();

        ReportStatusHistory::create([
            'report_id'  => $report->id,
            'old_status' => 'draft',
            'new_status' => 'pending_review',
            'changed_by' => $operatorId,
            'reason'     => 'Submitted for public health review.',
            'changed_at' => now()
        ]);

        return response()->json(['status' => 'pending_review', 'message' => __('api.report_submitted_review')]);
    }

    public function assignReport($id, Request $request)
    {
        $request->validate(['assigned_to' => 'required|uuid']);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $assigneeId = $request->input('assigned_to');

        ReportAssignment::create([
            'report_id'         => $report->id,
            'assigned_to'       => $assigneeId,
            'assigned_by'       => $operatorId,
            'assignment_status' => 'assigned',
            'assigned_at'       => now()
        ]);

        return response()->json(['message' => __('api.report_assigned_review')]);
    }

    public function approveReport($id, Request $request)
    {
        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $oldStatus = $report->status;
        $report->status = 'approved_for_submission';
        $report->save();

        ReportReview::create([
            'report_id'   => $report->id,
            'reviewer_id' => $operatorId,
            'action'      => 'approve',
            'comment'     => $request->input('comment', 'Approved.'),
            'reviewed_at' => now()
        ]);

        ReportStatusHistory::create([
            'report_id'  => $report->id,
            'old_status' => $oldStatus,
            'new_status' => 'approved_for_submission',
            'changed_by' => $operatorId,
            'reason'     => $request->input('comment', 'Approved.'),
            'changed_at' => now()
        ]);

        return response()->json(['status' => 'approved_for_submission', 'message' => __('api.report_approved')]);
    }

    public function requestCorrection($id, Request $request)
    {
        $request->validate(['reason' => 'required|string']);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $oldStatus = $report->status;
        $report->status = 'requires_correction';
        $report->requires_correction = true;
        $report->save();

        $reason = $request->input('reason');

        ReportReview::create([
            'report_id'   => $report->id,
            'reviewer_id' => $operatorId,
            'action'      => 'request_correction',
            'comment'     => $reason,
            'reviewed_at' => now()
        ]);

        ReportStatusHistory::create([
            'report_id'  => $report->id,
            'old_status' => $oldStatus,
            'new_status' => 'requires_correction',
            'changed_by' => $operatorId,
            'reason'     => $reason,
            'changed_at' => now()
        ]);

        return response()->json(['status' => 'requires_correction', 'message' => __('api.correction_requested')]);
    }

    public function rejectReport($id, Request $request)
    {
        $request->validate(['reason' => 'required|string']);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $oldStatus = $report->status;
        $report->status = 'rejected';
        $report->save();

        $reason = $request->input('reason');

        ReportReview::create([
            'report_id'   => $report->id,
            'reviewer_id' => $operatorId,
            'action'      => 'reject',
            'comment'     => $reason,
            'reviewed_at' => now()
        ]);

        ReportStatusHistory::create([
            'report_id'  => $report->id,
            'old_status' => $oldStatus,
            'new_status' => 'rejected',
            'changed_by' => $operatorId,
            'reason'     => $reason,
            'changed_at' => now()
        ]);

        return response()->json(['status' => 'rejected', 'message' => __('api.report_rejected')]);
    }

    public function cancelReport($id, Request $request)
    {
        $request->validate(['reason' => 'required|string']);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $oldStatus = $report->status;
        $report->status = 'cancelled';
        $report->save();

        $reason = $request->input('reason');

        ReportStatusHistory::create([
            'report_id'  => $report->id,
            'old_status' => $oldStatus,
            'new_status' => 'cancelled',
            'changed_by' => $operatorId,
            'reason'     => $reason,
            'changed_at' => now()
        ]);

        return response()->json(['status' => 'cancelled', 'message' => __('api.report_cancelled')]);
    }

    public function correctReport($id, Request $request)
    {
        $request->validate([
            'payload' => 'required|array',
            'reason'  => 'required|string'
        ]);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $reason = $request->input('reason');

        // Increment version number and save version history payload
        $currentVersion = ReportVersion::where('report_id', $report->id)->max('version_number') ?? 0;
        $newVersion = $currentVersion + 1;

        ReportVersion::create([
            'report_id'      => $report->id,
            'version_number' => $newVersion,
            'payload_json'   => $report->payload_json ?? [],
            'change_reason'  => $reason,
            'created_by'     => $operatorId,
            'created_at'     => now()
        ]);

        // Save new payload
        $report->payload_json = $request->input('payload');
        $report->status = 'draft';
        $report->requires_correction = false;
        $report->save();

        // Re-run quality checks
        $this->qualityService->runQualityChecks($report);

        return response()->json([
            'status'  => 'draft',
            'version' => $newVersion,
            'message' => __('api.report_updated_versioned')
        ]);
    }

    public function getVersions($id)
    {
        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);
        return response()->json(ReportVersionResource::collection(ReportVersion::where('report_id', $report->id)->get()));
    }

    public function getStatusHistory($id)
    {
        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);
        return response()->json(ReportStatusHistoryResource::collection(ReportStatusHistory::where('report_id', $report->id)->get()));
    }

    public function getReviewQueue()
    {
        $queue = PublicHealthReport::where('status', 'pending_review')->with(['reportType', 'facility'])->get();
        return response()->json(PublicHealthReportResource::collection($queue));
    }

    // Phase 3: Export & Submission APIs
    public function getSubmissionProfiles()
    {
        return response()->json(SubmissionProfileResource::collection(SubmissionProfile::all()));
    }

    public function createSubmissionProfile(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'report_type_id'   => 'required|uuid',
            'destination_type' => 'required|string|max:100',
            'endpoint_url'     => 'required|url',
            'auth_method'      => 'required|string|max:100',
            'payload_format'   => 'required|string|max:50',
        ]);

        $profile = SubmissionProfile::create($validated);
        return response()->json(SubmissionProfileResource::make($profile), 201);
    }

    public function submitReport($id, Request $request)
    {
        $request->validate(['profile_id' => 'required|uuid']);

        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        if ($report->status !== 'approved_for_submission') {
            return response()->json(['error' => __('api.report_must_be_approved')], 400);
        }

        $profile = SubmissionProfile::find($request->input('profile_id'));
        if (!$profile) return response()->json(['error' => __('api.submission_profile_not_found')], 404);

        $submission = ReportSubmission::create([
            'report_id'              => $report->id,
            'submission_profile_id'  => $profile->id,
            'submission_method'      => $profile->destination_type,
            'payload_hash'           => hash('sha256', json_encode($report->payload_json)),
            'status'                 => 'submitted',
            'external_reference'     => 'EXT-' . bin2hex(random_bytes(4)),
            'response_code'          => 200,
            'safe_response_summary'  => 'Accepted by ' . $profile->name,
            'submitted_by'           => $operatorId,
            'submitted_at'           => now(),
            'accepted_at'            => now()
        ]);

        $report->status = 'submitted';
        $report->save();

        return response()->json([
            'status'     => 'submitted',
            'submission' => $submission
        ]);
    }

    public function exportReport($id, Request $request)
    {
        $operatorId = $this->resolveOperatorId($request);
        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $report = PublicHealthReport::find($id);
        if (!$report) return response()->json(['error' => __('api.report_not_found')], 404);

        $export = $this->exportService->exportCsv($report, $operatorId);

        return response()->json([
            'message'    => __('api.export_created_suppression'),
            'export_id'  => $export->id,
            'expires_at' => $export->expires_at
        ]);
    }

    public function getSubmissions($id)
    {
        return response()->json(ReportSubmissionResource::collection(ReportSubmission::where('report_id', $id)->get()));
    }

    public function getIntegrationStatus()
    {
        return response()->json([
            'status'           => 'healthy',
            'active_endpoints' => SubmissionProfile::where('active', true)->count()
        ]);
    }

    public function downloadExport($id)
    {
        $export = ExportFile::find($id);
        if (!$export) {
            return response()->json(['error' => __('api.file_not_found')], 404);
        }

        if (now()->isAfter($export->expires_at)) {
            return response()->json(['error' => __('api.export_file_expired')], 410);
        }

        $export->download_count++;
        $export->save();

        return response()->download($export->file_path);
    }
}
