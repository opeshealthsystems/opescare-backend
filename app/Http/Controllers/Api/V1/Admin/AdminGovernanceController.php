<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccessLogResource;
use App\Http\Resources\CorrectionRequestResource;
use App\Http\Resources\CountryPolicyResource;
use App\Http\Resources\DataExportRequestResource;
use App\Http\Resources\EmergencyAccessEventResource;
use App\Http\Resources\SecurityIncidentResource;
use Illuminate\Http\Request;
use App\Modules\Governance\Services\EmergencyAccessService;
use App\Modules\Governance\Services\CorrectionRequestService;
use App\Modules\Governance\Services\DataExportService;
use App\Modules\Governance\Services\CountryPolicyService;
use App\Models\AccessLog;
use App\Models\EmergencyAccessEvent;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Models\SecurityIncident;
use App\Models\CountryPolicy;
use Carbon\Carbon;

class AdminGovernanceController extends Controller
{
    private $emergencyService;
    private $correctionService;
    private $exportService;
    private $policyService;

    public function __construct(
        EmergencyAccessService $emergencyService,
        CorrectionRequestService $correctionService,
        DataExportService $exportService,
        CountryPolicyService $policyService
    ) {
        $this->emergencyService = $emergencyService;
        $this->correctionService = $correctionService;
        $this->exportService = $exportService;
        $this->policyService = $policyService;
    }

    public function listAccessLogs(Request $request)
    {
        $paginated = AccessLog::latest()->paginate(50);
        return response()->json(['data' => AccessLogResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function listEmergencyAccessReviews(Request $request)
    {
        $paginated = EmergencyAccessEvent::latest()->paginate(50);
        return response()->json(['data' => EmergencyAccessEventResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function reviewEmergencyAccess(Request $request, $id)
    {
        $request->validate([
            'review_status' => 'required|in:approved,rejected,suspected_abuse,confirmed_abuse,cleared',
            'comment'       => 'nullable|string|max:2000',
        ]);

        $reviewerId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$reviewerId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $status = $request->input('review_status');
        $comment = $request->input('comment');

        $event = $this->emergencyService->reviewEmergencyAccess($id, $reviewerId, $status, $comment);

        return response()->json($event, 200);
    }

    public function listCorrectionRequests(Request $request)
    {
        $paginated = CorrectionRequest::latest()->paginate(50);
        return response()->json(['data' => CorrectionRequestResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function approveCorrectionRequest(Request $request, $id)
    {
        $reviewerId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$reviewerId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $corr = $this->correctionService->approveRequest($id, $reviewerId);

        return response()->json($corr, 200);
    }

    public function rejectCorrectionRequest(Request $request, $id)
    {
        $reviewerId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$reviewerId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $corr = $this->correctionService->rejectRequest($id, $reviewerId);

        return response()->json($corr, 200);
    }

    public function listExportRequests(Request $request)
    {
        $paginated = DataExportRequest::latest()->paginate(50);
        return response()->json(['data' => DataExportRequestResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function approveExportRequest(Request $request, $id)
    {
        $approverId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$approverId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $exp = $this->exportService->approveExport($id, $approverId);

        return response()->json($exp, 200);
    }

    public function rejectExportRequest(Request $request, $id)
    {
        $actorId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$actorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $exp = DataExportRequest::findOrFail($id);
        $exp->update([
            'status'      => 'rejected',
            'rejected_by' => $actorId,
            'rejected_at' => now(),
        ]);

        return response()->json(DataExportRequestResource::make($exp), 200);
    }

    public function listSecurityIncidents(Request $request)
    {
        $paginated = SecurityIncident::latest()->paginate(50);
        return response()->json(['data' => SecurityIncidentResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function createSecurityIncident(Request $request)
    {
        $request->validate([
            'incident_type' => 'required|string|max:100',
            'severity'      => 'required|in:low,medium,high,critical',
            'summary'       => 'required|string|max:5000',
        ]);

        $inc = new SecurityIncident();
        $inc->incident_type = $request->input('incident_type');
        $inc->severity = $request->input('severity');
        $inc->summary = $request->input('summary');
        $inc->status = 'new';
        $inc->detected_at = Carbon::now();
        $inc->save();

        return response()->json(SecurityIncidentResource::make($inc), 201);
    }

    public function containSecurityIncident(Request $request, $id)
    {
        $actorId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$actorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $inc = SecurityIncident::findOrFail($id);
        $inc->status = 'contained';
        $inc->contained_at = Carbon::now();
        $inc->contained_by = $actorId;
        $inc->save();

        return response()->json(SecurityIncidentResource::make($inc), 200);
    }

    public function resolveSecurityIncident(Request $request, $id)
    {
        $actorId = $request->attributes->get('provider_id') ?? $request->attributes->get('integration_client_id');
        if (!$actorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $inc = SecurityIncident::findOrFail($id);
        $inc->status = 'resolved';
        $inc->resolved_at = Carbon::now();
        $inc->resolved_by = $actorId;
        $inc->save();

        return response()->json(SecurityIncidentResource::make($inc), 200);
    }

    public function listCountryPolicies(Request $request)
    {
        $paginated = CountryPolicy::latest()->paginate(50);
        return response()->json(['data' => CountryPolicyResource::collection($paginated->items()), 'meta' => ['total' => $paginated->total(), 'per_page' => $paginated->perPage(), 'current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage()]], 200);
    }

    public function createCountryPolicy(Request $request)
    {
        $validated = $request->validate([
            'country_code'  => 'required|string|size:2',
            'name'          => 'required|string|max:255',
            'version'       => 'required|string|max:50',
            'settings_json' => 'nullable|array',
        ]);

        $policy = new CountryPolicy();
        $policy->country_code = strtoupper($validated['country_code']);
        $policy->name = $validated['name'];
        $policy->version = $validated['version'];
        $policy->effective_from = Carbon::now();
        $policy->settings_json = $validated['settings_json'] ?? [];
        $policy->status = 'draft';
        $policy->save();

        return response()->json(CountryPolicyResource::make($policy), 201);
    }

    public function updateCountryPolicy(Request $request, $id)
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'version'       => 'sometimes|string|max:50',
            'settings_json' => 'sometimes|array',
            'status'        => 'sometimes|string|in:draft,active,archived',
        ]);

        $policy = CountryPolicy::findOrFail($id);
        $policy->update($validated);

        return response()->json(CountryPolicyResource::make($policy), 200);
    }

    public function publishCountryPolicy(Request $request, $id)
    {
        $policy = CountryPolicy::findOrFail($id);
        $published = $this->policyService->publishPolicy(
            $policy->country_code,
            $policy->name,
            $policy->version,
            $policy->settings_json
        );

        return response()->json($published, 200);
    }
}
