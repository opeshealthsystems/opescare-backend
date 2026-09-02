<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Lab Orders & Results
 *
 * Authenticated patients retrieve their own lab history.
 * This endpoint is read-only from the patient side.
 */
class MobileLabController extends Controller
{
    /**
     * List lab orders for the authenticated patient.
     *
     * GET /api/mobile/labs
     * Query params: status, limit (default 20), page (default 1)
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $status    = $request->query('status');
        $limit     = min((int) $request->query('limit', 20), 100);

        $query = LabOrder::where('patient_id', $patientId)
            ->with(['results', 'facility:id,name'])
            ->orderByDesc('ordered_at');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($limit);

        return response()->json([
            'data'       => $orders->map(fn ($o) => $this->formatOrder($o)),
            'pagination' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Get detail of a single lab order including all result parameters.
     *
     * GET /api/mobile/labs/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $order = LabOrder::where('id', $id)
            ->where('patient_id', $patientId)
            ->with(['results', 'facility:id,name'])
            ->firstOrFail();

        return response()->json(['data' => $this->formatOrderDetail($order)]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function formatOrder(LabOrder $o): array
    {
        return [
            'id'            => $o->id,
            'test_name'     => $o->test_name,
            'test_code'     => $o->test_code,
            'urgency'       => $o->urgency,
            'status'        => $o->status,
            'status_color'  => $o->statusColor(),
            'facility_name' => $o->facility?->name,
            'ordered_at'    => $o->ordered_at?->toIso8601String(),
            'resulted_at'   => $o->resulted_at?->toIso8601String(),
            'result_count'  => $o->results->count(),
            'has_abnormal'  => $o->results->some(fn ($r) => $r->isAbnormal()),
        ];
    }

    private function formatOrderDetail(LabOrder $o): array
    {
        $base = $this->formatOrder($o);
        $base['clinical_indication'] = $o->clinical_indication;
        $base['notes']               = $o->notes;
        $base['collected_at']        = $o->collected_at?->toIso8601String();
        $base['results']             = $o->results->map(fn ($r) => [
            'id'              => $r->id,
            'parameter_name'  => $r->parameter_name,
            'value'           => $r->value,
            'unit'            => $r->unit,
            'reference_range' => $r->reference_range,
            'flag'            => $r->flag,
            'flag_label'      => $r->flagLabel(),
            'is_abnormal'     => $r->isAbnormal(),
            'notes'           => $r->notes,
            'resulted_at'     => $r->resulted_at?->toIso8601String(),
        ])->values();

        return $base;
    }

    /**
     * Resolve patient ID from mobile auth context.
     *
     * In a full auth implementation this would read from the JWT/session.
     * Resolved from the auth.mobile middleware attribute.
     */
    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
