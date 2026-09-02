<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptions) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'                   => 'required|uuid',
            'prescriber_id'                => 'required|uuid',
            'visit_id'                     => 'nullable|uuid',
            'items'                        => 'required|array|min:1',
            'items.*.medicine_id'          => 'nullable|uuid|exists:medicines,id',
            'items.*.drug_name'            => 'required_without:items.*.medicine_id|nullable|string|max:200',
            'items.*.drug_code'            => 'nullable|string|max:50',
            'items.*.dosage'               => 'required|string|max:100',
            'items.*.frequency'            => 'required|string|max:100',
            'items.*.duration'             => 'nullable|string|max:100',
            'items.*.quantity'             => 'nullable|integer|min:1',
            'items.*.route'                => 'nullable|string|max:50',
            'items.*.notes'                => 'nullable|string',
            'notes'                        => 'nullable|string',
            'expires_at'                   => 'nullable|date',
        ]);

        // One write path with the staff portal — PrescriptionService normalises
        // the `dosage`/`duration` aliases this endpoint has always accepted and
        // persists the prescriber, which mass assignment used to drop silently.
        $prescription = $this->prescriptions->issue([
            'patient_id'    => $validated['patient_id'],
            'facility_id'   => $facilityId,
            'visit_id'      => $validated['visit_id'] ?? null,
            'prescribed_by' => $validated['prescriber_id'],
            'notes'         => $validated['notes'] ?? null,
            'expires_at'    => $validated['expires_at'] ?? null,
            'items'         => $validated['items'],
        ], $validated['prescriber_id']);

        return response()->json(['data' => $prescription->load('items')], 201);
    }

    public function show(Prescription $prescription): JsonResponse
    {
        return response()->json(['data' => $prescription->load(['items', 'prescriber', 'patient'])]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $prescriptions = Prescription::where('facility_id', $facilityId)
            ->with(['patient', 'prescriber'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $prescriptions]);
    }
}
