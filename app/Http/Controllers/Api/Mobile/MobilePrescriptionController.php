<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Prescriptions
 *
 * Read-only view of the authenticated patient's prescriptions and items.
 */
class MobilePrescriptionController extends Controller
{
    /**
     * List prescriptions.
     *
     * GET /api/mobile/prescriptions
     * Query params: status, limit (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $status    = $request->query('status');
        $limit     = min((int) $request->query('limit', 20), 100);

        $query = Prescription::where('patient_id', $patientId)
            ->with(['items', 'facility:id,name'])
            ->orderByDesc('prescribed_at');

        if ($status) {
            $query->where('status', $status);
        }

        $prescriptions = $query->paginate($limit);

        return response()->json([
            'data'       => $prescriptions->map(fn ($p) => $this->formatPrescription($p)),
            'pagination' => [
                'total'        => $prescriptions->total(),
                'per_page'     => $prescriptions->perPage(),
                'current_page' => $prescriptions->currentPage(),
                'last_page'    => $prescriptions->lastPage(),
            ],
        ]);
    }

    /**
     * Detail of a single prescription including all items.
     *
     * GET /api/mobile/prescriptions/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $prescription = Prescription::where('id', $id)
            ->where('patient_id', $patientId)
            ->with(['items', 'facility:id,name'])
            ->firstOrFail();

        return response()->json(['data' => $this->formatPrescriptionDetail($prescription)]);
    }

    // -------------------------------------------------------------------------

    private function formatPrescription(Prescription $p): array
    {
        return [
            'id'            => $p->id,
            'facility_name' => $p->facility?->name,
            'status'        => $p->status,
            'status_color'  => $p->statusColor(),
            'item_count'    => $p->items->count(),
            'prescribed_at' => $p->prescribed_at?->toIso8601String(),
            'dispensed_at'  => $p->dispensed_at?->toIso8601String(),
            'expires_at'    => $p->expires_at?->toIso8601String(),
        ];
    }

    private function formatPrescriptionDetail(Prescription $p): array
    {
        $base = $this->formatPrescription($p);
        $base['notes'] = $p->notes;
        $base['items'] = $p->items->map(fn ($i) => [
            'id'             => $i->id,
            'drug_name'      => $i->drug_name,
            'drug_code'      => $i->drug_code,
            'dose'           => $i->dose,
            'frequency'      => $i->frequency,
            'route'          => $i->route,
            'duration_days'  => $i->duration_days,
            'quantity'       => $i->quantity,
            'status'         => $i->status,
            'is_dispensed'   => $i->isDispensed(),
            'dispensed_at'   => $i->dispensed_at?->toIso8601String(),
            'dispense_notes' => $i->dispense_notes,
        ])->values();

        return $base;
    }

    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
