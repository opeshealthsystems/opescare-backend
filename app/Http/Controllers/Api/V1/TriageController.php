<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TriageRecord;
use App\Modules\Triage\Services\TriageService;
use App\Modules\Triage\Services\TriageScoringService;
use App\Modules\Triage\Services\EmergencyWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TriageController — Triage & Emergency Workflow API.
 *
 * CDSS SAFETY RULE: Triage scores are advisory only.
 * The system assists the triage nurse/clinician — it does not replace their judgment.
 * No automated clinical decision may be taken solely on a computed triage score.
 */
class TriageController extends Controller
{
    public function __construct(
        private TriageService          $triage,
        private TriageScoringService   $scoring,
        private EmergencyWorkflowService $emergency
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_id'             => ['required', 'uuid'],
            'patient_id'           => ['nullable', 'uuid'],
            'facility_id'          => ['nullable', 'uuid'],
            'presenting_complaint' => ['required', 'string', 'max:1000'],
            'pain_score'           => ['nullable', 'integer', 'min:0', 'max:10'],
            'pregnancy_status'     => ['nullable', 'string'],
            'acuity_score'         => ['nullable', 'string'],
            'vitals'               => ['nullable', 'array'],
            'vitals.blood_pressure_systolic'  => ['nullable', 'integer'],
            'vitals.blood_pressure_diastolic' => ['nullable', 'integer'],
            'vitals.pulse'                    => ['nullable', 'integer'],
            'vitals.temperature'              => ['nullable', 'numeric'],
            'vitals.oxygen_saturation'        => ['nullable', 'numeric'],
            'vitals.respiratory_rate'         => ['nullable', 'integer'],
            'vitals.weight_kg'                => ['nullable', 'numeric'],
        ]);

        $record = $this->triage->recordTriage($validated, $request->user()->id);

        return response()->json($record, 201);
    }

    public function score(Request $request, string $triageId): JsonResponse
    {
        $validated = $request->validate([
            'scoring_system'  => ['required', 'in:manchester,esi,manual'],
            'component_data'  => ['nullable', 'array'],
            'priority_level'  => ['required_if:scoring_system,manual', 'nullable', 'string'],
        ]);

        $triageRecord = TriageRecord::findOrFail($triageId);
        $actorId      = $request->user()->id;
        $components   = $validated['component_data'] ?? [];

        $score = match ($validated['scoring_system']) {
            'manchester' => $this->scoring->scoreManchesterTriage($triageRecord, $components, $actorId),
            'esi'        => $this->scoring->scoreEsi($triageRecord, $components, $actorId),
            'manual'     => $this->scoring->scoreManual($triageRecord, $validated['priority_level'], $actorId, $components ?: null),
        };

        return response()->json([
            'triage_id'   => $triageId,
            'score'       => $score,
            'disclaimer'  => 'Advisory score only. Clinical judgment of the assessing clinician takes precedence.',
        ]);
    }

    public function reassess(Request $request, string $triageId): JsonResponse
    {
        $validated = $request->validate([
            'reason'  => ['required', 'string'],
            'vitals'  => ['nullable', 'array'],
            'new_acuity_score' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->triage->reassess($triageId, $validated, $request->user()->id)
        );
    }

    public function escalateToEmergency(Request $request, string $triageId): JsonResponse
    {
        $validated = $request->validate([
            'reason'   => ['required', 'string'],
            'location' => ['nullable', 'string'],
        ]);

        $triageRecord = TriageRecord::findOrFail($triageId);

        return response()->json(
            $this->triage->escalateEmergency($triageRecord->visit_id, $validated['reason'], $request->user()->id)
        );
    }

    public function listActive(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        return response()->json(
            $this->triage->listActiveForFacility($facilityId)
        );
    }
}
