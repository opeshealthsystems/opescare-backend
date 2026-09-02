<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdvanceDirectiveResource;
use App\Models\AdvanceDirective;
use App\Services\Clinical\AdvanceDirectiveService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvanceDirectiveController extends Controller
{
    public function __construct(
        private readonly AdvanceDirectiveService $service,
        private readonly DocumentIssuanceService $issuance,
    ) {}

    /** GET /api/v1/patients/{patientId}/advance-directives */
    public function index(string $patientId): JsonResponse
    {
        $directives = $this->service->getActiveForPatient($patientId);
        return response()->json(['data' => AdvanceDirectiveResource::collection($directives)]);
    }

    /** POST /api/v1/patients/{patientId}/advance-directives */
    public function store(Request $request, string $patientId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'directive_type'                => 'required|in:dnr,living_will,healthcare_proxy,polst,organ_donation,other',
            'effective_date'                => 'required|date',
            'expiry_date'                   => 'nullable|date|after:effective_date',
            'document_path'                 => 'nullable|string|max:500',
            'witness_name'                  => 'nullable|string|max:255',
            'witness_date'                  => 'nullable|date',
            'healthcare_proxy_name'         => 'nullable|string|max:255',
            'healthcare_proxy_phone'        => 'nullable|string|max:30',
            'healthcare_proxy_relationship' => 'nullable|string|max:100',
            'instructions'                  => 'nullable|string',
        ]);

        $directive = $this->service->register(array_merge($validated, ['patient_id' => $patientId, 'facility_id' => $facilityId]));

        if ($validated['directive_type'] === 'dnr') {
            try {
                $directiveId = is_array($directive) ? ($directive['id'] ?? null) : ($directive->id ?? null);
                $this->issuance->issueFromModel(
                    'DNR',
                    'Do Not Resuscitate Order',
                    [
                        'directive_id'   => $directiveId,
                        'patient_id'     => $patientId,
                        'effective_date' => $validated['effective_date'],
                        'expiry_date'    => $validated['expiry_date'] ?? null,
                        'witness_name'   => $validated['witness_name'] ?? null,
                        'instructions'   => $validated['instructions'] ?? null,
                    ],
                    $facilityId,
                    $patientId,
                    null,
                    $request->user()?->id,
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => AdvanceDirectiveResource::make($directive)], 201);
    }

    /** GET /api/v1/patients/{patientId}/advance-directives/{id} */
    public function show(string $patientId, string $id): JsonResponse
    {
        $directive = AdvanceDirective::where('patient_id', $patientId)->findOrFail($id);
        return response()->json(['data' => AdvanceDirectiveResource::make($directive)]);
    }

    /** DELETE /api/v1/patients/{patientId}/advance-directives/{id} — revoke */
    public function destroy(Request $request, string $patientId, string $id): JsonResponse
    {
        $actorId = $request->attributes->get('integration_client_id') ?? ($request->user()?->id ?? null);
        if (!$actorId) {
            return response()->json(['message' => __('api.actor_unresolved'), 'error_code' => 'ACTOR_UNRESOLVABLE'], 403);
        }

        $directive = $this->service->revoke($id, $actorId);
        return response()->json(['data' => AdvanceDirectiveResource::make($directive), 'message' => __('api.directive_revoked')]);
    }
}
