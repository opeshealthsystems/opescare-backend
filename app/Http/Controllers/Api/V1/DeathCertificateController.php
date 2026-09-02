<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeathRecordResource;
use App\Models\DeathRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeathCertificateController extends Controller
{
    public function __construct(
        private readonly DocumentIssuanceService $documentIssuanceService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_context_required')], 403);
        }

        $validated = $request->validate([
            'patient_id'             => ['required', 'uuid'],
            'certifying_doctor_id'   => ['required', 'uuid'],
            'deceased_at'            => ['required', 'date'],
            'place_of_death'         => ['required', 'in:hospital,home,other,unknown'],
            'manner_of_death'        => ['required', 'in:natural,accident,homicide,suicide,undetermined,pending_investigation'],
            'primary_cause'          => ['required', 'string'],
            'secondary_causes'       => ['nullable', 'array'],
            'duration_primary'       => ['nullable', 'string', 'max:50'],
            'contributing_conditions' => ['nullable', 'string'],
            'was_autopsy_performed'  => ['nullable', 'boolean'],
            'notes'                  => ['nullable', 'string'],
        ]);

        $validated['facility_id'] = $facilityId;

        $record = DeathRecord::create($validated + ['status' => 'draft']);

        try {
            $this->documentIssuanceService->issueFromModel(
                'DTH',
                'Death Certificate',
                [
                    'death_record_id'      => $record->id,
                    'patient_id'           => $validated['patient_id'],
                    'deceased_at'          => $record->deceased_at,
                    'place_of_death'       => $record->place_of_death,
                    'manner_of_death'      => $record->manner_of_death,
                    'primary_cause'        => $record->primary_cause,
                    'certifying_doctor_id' => $validated['certifying_doctor_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['certifying_doctor_id'],
            );
        } catch (\Throwable $e) {
            // Document issuance failure is non-fatal; log and continue
            \Illuminate\Support\Facades\Log::warning('DeathCertificate: document issuance failed', [
                'death_record_id' => $record->id,
                'error'           => $e->getMessage(),
            ]);
        }

        return response()->json(['data' => DeathRecordResource::make($record)], 201);
    }

    public function certify(Request $request, DeathRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'registrar_id' => ['required', 'uuid'],
        ]);

        $record->update([
            'status'        => 'certified',
            'registrar_id'  => $validated['registrar_id'],
            'registered_at' => now(),
        ]);

        if ($facilityId) {
            try {
                $this->documentIssuanceService->issueFromModel(
                    'DSU',
                    'Death Summary',
                    [
                        'death_record_id'  => $record->id,
                        'patient_id'       => $record->patient_id,
                        'deceased_at'      => $record->deceased_at,
                        'primary_cause'    => $record->primary_cause,
                        'manner_of_death'  => $record->manner_of_death,
                        'registered_at'    => now()->toISOString(),
                        'registrar_id'     => $validated['registrar_id'],
                    ],
                    $facilityId,
                    $record->patient_id,
                    null,
                    $validated['registrar_id'],
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => DeathRecordResource::make($record)]);
    }

    public function show(Request $request, DeathRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json([
            'data' => $record->load(['patient', 'certifyingDoctor', 'facility']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_context_required')], 403);
        }

        $records = DeathRecord::where('facility_id', $facilityId)
            ->orderByDesc('deceased_at')
            ->get();

        return response()->json(['data' => DeathRecordResource::collection($records)]);
    }
}
