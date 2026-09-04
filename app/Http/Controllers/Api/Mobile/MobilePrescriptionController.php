<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionAccessService;
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

        // prescriptions.id is a uuid column: a malformed id makes Postgres raise
        // 22P02, which the generic JSON handler turns into a 500. Not found is the
        // honest answer.
        if (! \Illuminate\Support\Str::isUuid($id)) {
            abort(404);
        }

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
            'reference'     => $p->reference,
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

    /**
     * Mint a presentable access code for this prescription.
     *
     * POST /api/mobile/prescriptions/{id}/share
     *
     * This is what turns a record in the app into something a patient can hold up
     * at a counter: a short reference plus a URL a pharmacist can scan. The patient
     * asks for it explicitly - nothing is minted on page load, because every live
     * token is a standing disclosure of a medication list.
     */
    public function share(Request $request, string $id): JsonResponse
    {
        $prescription = $this->findOwnedPrescription($request, $id);

        // A script that must not be dispensed must not be presentable either -
        // a QR is read at a counter as permission, and handing out a scannable
        // code for a voided or lapsed order invites exactly the dispense the
        // void was recorded to prevent.
        if (! $prescription->isDispensable()) {
            return response()->json([
                'error'   => 'prescription_not_shareable',
                'message' => 'This prescription can no longer be presented to a pharmacy.',
            ], 422);
        }

        $grant = app(PrescriptionAccessService::class)
            ->currentOrIssue($prescription, $this->resolvePatientId($request));

        return response()->json(['data' => [
            'reference'  => $prescription->reference,
            'verify_url' => $grant['verify_url'],
            'expires_at' => $grant['model']->expires_at?->toIso8601String(),
        ]]);
    }

    /**
     * Withdraw every live code for this prescription - "stop sharing this".
     *
     * DELETE /api/mobile/prescriptions/{id}/share
     *
     * Revokes all of them, not just the newest: a patient who taps this means the
     * printed copy on the kitchen table should stop working too.
     */
    public function withdrawShare(Request $request, string $id): JsonResponse
    {
        $prescription = $this->findOwnedPrescription($request, $id);

        $revoked = app(PrescriptionAccessService::class)->revokeAllFor($prescription);

        return response()->json(['data' => [
            'revoked' => $revoked,
        ]]);
    }

    /**
     * The prescription, or 404 - never another patient's, and never a 500 from a
     * malformed uuid hitting a Postgres uuid column.
     */
    private function findOwnedPrescription(Request $request, string $id): Prescription
    {
        if (! \Illuminate\Support\Str::isUuid($id)) {
            abort(404);
        }

        return Prescription::where('id', $id)
            ->where('patient_id', $this->resolvePatientId($request))
            ->firstOrFail();
    }

    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
