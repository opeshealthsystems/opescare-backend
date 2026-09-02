<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityReadinessScore;
use App\Modules\FacilityReadiness\Services\FacilityGoLiveService;
use App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class FacilityGoLiveReadinessController extends Controller
{
    /**
     * Render the admin go-live readiness page (web view).
     * Resolves the active facility from session/primary, falling back to the
     * first facility. When no facility exists, renders a clean empty state.
     */
    public function index(Request $request, FacilityGoLiveService $service)
    {
        $user = $request->user();
        $facilityId = session('active_facility_id') ?? ($user->primary_facility_id ?? null);

        $facility = $facilityId
            ? Facility::find($facilityId)
            : Facility::query()->orderBy('created_at')->first();

        if (! $facility) {
            return response()->view('portals.admin.go_live_readiness_empty', [], 200);
        }

        $readiness = $service->getOrCreateReadiness($facility->id);

        return view('portals.admin.go_live_readiness', [
            'facility'     => $facility,
            'readiness'    => $readiness,
            'missingItems' => $service->missingItems($readiness),
            'labels'       => $service->checklistLabels(),
        ]);
    }

    public function show(Facility $facility, FacilityGoLiveService $service): JsonResponse
    {
        return response()->json([
            'data' => $this->serialize($service->getOrCreateReadiness($facility->id), $service),
        ]);
    }

    public function store(Facility $facility, Request $request, FacilityGoLiveService $service): JsonResponse
    {
        $readiness = $service->getOrCreateReadiness($facility->id, $request->input('actor_id'));

        return response()->json(['data' => $this->serialize($readiness, $service)], 201);
    }

    public function markItem(Facility $facility, string $item, Request $request, FacilityGoLiveService $service): JsonResponse
    {
        $request->validate([
            'complete' => ['required', 'boolean'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        $readiness = $service->getOrCreateReadiness($facility->id, $request->input('actor_id'));

        try {
            $readiness = $service->markItem($readiness, $item, $request->boolean('complete'), $request->input('actor_id'));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($readiness, $service)]);
    }

    public function approve(Facility $facility, Request $request, FacilityGoLiveService $service): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
            'approval_note' => ['required', 'string', 'max:2000'],
        ]);

        $readiness = $service->getOrCreateReadiness($facility->id, $validated['actor_id']);

        try {
            $readiness = $service->approveGoLive($readiness, $validated['actor_id'], $validated['approval_note']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($readiness, $service)]);
    }

    /**
     * Compute and store a fresh readiness score for a facility.
     * POST /v1/admin/facilities/{facility}/readiness-score
     * Body: { computed_by?: uuid }
     */
    public function computeScore(Facility $facility, Request $request): JsonResponse
    {
        $request->validate([
            'computed_by' => ['nullable', 'uuid'],
        ]);

        $scoringService = app(FacilityReadinessScoringService::class);
        $score = $scoringService->calculateAndStore(
            $facility->id,
            $request->input('computed_by')
        );

        return response()->json([
            'message' => __('api.readiness_score_computed'),
            'data'    => $this->serializeScore($score),
        ], 201);
    }

    /**
     * Retrieve the most recent readiness score for a facility.
     * GET /v1/admin/facilities/{facility}/readiness-score
     */
    public function latestScore(Facility $facility): JsonResponse
    {
        $score = FacilityReadinessScore::where('facility_id', $facility->id)
            ->latest()
            ->first();

        if (! $score) {
            return response()->json([
                'message' => __('api.no_readiness_score'),
                'data'    => null,
            ], 404);
        }

        return response()->json(['data' => $this->serializeScore($score)]);
    }

    private function serializeScore(FacilityReadinessScore $score): array
    {
        return [
            'id'            => $score->id,
            'facility_id'   => $score->facility_id,
            'overall_score' => $score->overall_score,
            'staff_score'   => $score->staff_score,
            'config_score'  => $score->config_score,
            'data_score'    => $score->data_score,
            'support_score' => $score->support_score,
            'is_ready'      => $score->is_ready,
            'computed_by'   => $score->computed_by,
            'computed_at'   => $score->created_at?->toISOString(),
        ];
    }

    private function serialize($readiness, FacilityGoLiveService $service): array
    {
        return [
            'id' => $readiness->id,
            'facility_id' => $readiness->facility_id,
            'status' => $readiness->status,
            'can_go_live' => $readiness->can_go_live,
            'checklist' => $readiness->checklist_json,
            'checklist_labels' => $service->checklistLabels(),
            'missing_items' => $service->missingItems($readiness),
            'risks' => $service->risks($readiness),
            'approved_by' => $readiness->approved_by,
            'approved_at' => optional($readiness->approved_at)->toISOString(),
            'approval_note' => $readiness->approval_note,
        ];
    }
}
