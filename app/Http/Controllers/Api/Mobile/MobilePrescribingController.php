<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — writing prescriptions from the app.
 *
 * The native equivalent of the prescriber's desk (PrescriberController). Both
 * surfaces are thin: the clinical domain — immutability, the audit trail, the
 * RX document — lives in PrescriptionService, and neither controller writes a
 * Prescription or PrescriptionItem itself. That is what keeps the web desk and
 * the app from drifting apart.
 *
 * Every route here sits behind `auth.provider`, so:
 *   facility_id   ← $request->attributes 'provider_facility_id'
 *   prescribed_by ← $request->attributes 'provider_user_id'
 * Both are stamped by the middleware from the token. Neither is ever read from
 * the body, and there is no parameter that would let a caller name either one.
 *
 * Patient selection is an EXACT Health ID match and nothing else. No name
 * search, no prefix, no listing — patient enumeration is forbidden
 * platform-wide, and the prescriber's desk follows the same rule.
 */
class MobilePrescribingController extends Controller
{
    /**
     * Look one patient up by their exact Health ID.
     *
     * GET /api/mobile/doctor/patients/lookup?health_id=CM-HID-XXXX-XXXX-XXXX
     *
     * Note that Patient carries the IsDemoRecord global scope: on a demo
     * deployment this resolves demo patients only, and a real patient reading
     * as "not found" here is the isolation working, not a bug. The scope is
     * deliberately not bypassed.
     */
    public function lookupPatient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'health_id' => 'required|string|max:64',
        ]);

        // Case-folding is normalisation of one whole identifier, not a fuzzy
        // match: the comparison stays an equality on the full Health ID.
        $healthId = strtoupper(trim($validated['health_id']));

        $patient = Patient::where('health_id', $healthId)->first();

        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        return response()->json([
            'data' => $this->formatPatient($patient),
        ], 200);
    }

    /**
     * Issue a prescription.
     *
     * POST /api/mobile/doctor/prescriptions
     */
    public function store(Request $request, PrescriptionService $prescriptions): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'            => 'required|uuid|exists:patients,id',
            'notes'                 => 'nullable|string|max:1000',
            'validity_days'         => 'nullable|integer|min:1|max:365',
            'items'                 => 'required|array|min:1',
            'items.*.drug_name'     => 'required|string|max:200',
            'items.*.medicine_id'   => 'nullable|uuid',
            'items.*.dose'          => 'nullable|string|max:100',
            'items.*.frequency'     => 'nullable|string|max:100',
            'items.*.route'         => 'nullable|string|max:50',
            'items.*.duration_days' => 'nullable|integer|min:1|max:365',
            'items.*.quantity'      => 'nullable|integer|min:1|max:1000',
        ]);

        $facilityId  = $this->facilityId($request);
        $prescriberId = $this->prescriberId($request);

        try {
            $prescription = $prescriptions->issue([
                'patient_id'    => $validated['patient_id'],
                'facility_id'   => $facilityId,
                'prescribed_by' => $prescriberId,
                'notes'         => $validated['notes'] ?? null,
                'validity_days' => $validated['validity_days'] ?? null,
                'items'         => $validated['items'],
            ], $prescriberId);
        } catch (ModelNotFoundException $e) {
            // PrescriptionService::normaliseItem() resolves medicine_id against
            // the national catalogue and fails loudly on an unknown id, rather
            // than handing the pharmacy an unmatchable line.
            return response()->json(['message' => __('api.prescription_unknown_medicine')], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('api.prescription_issue_failed')], 422);
        }

        return response()->json([
            'message' => __('api.prescription_issued'),
            'data'    => $this->formatPrescription(
                $prescription->load(['items', 'patient'])
            ),
        ], 201);
    }

    /**
     * Prescriptions this clinician wrote at this facility, newest first.
     *
     * GET /api/mobile/doctor/prescriptions?status=&limit=
     *
     * Scoped by BOTH facility_id and prescribed_by: a clinician's app inbox is
     * their own work, not the facility's whole prescribing log.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 20), 1), 100);

        $query = Prescription::with(['items', 'patient'])
            ->where('facility_id', $this->facilityId($request))
            ->where('prescribed_by', $this->prescriberId($request))
            ->orderByDesc('prescribed_at');

        // Narrowing an already-scoped list, not a search.
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $page = $query->paginate($limit);

        return response()->json([
            'data'       => $page->getCollection()->map(fn (Prescription $p) => $this->formatPrescription($p))->values(),
            'pagination' => [
                'total'        => $page->total(),
                'per_page'     => $page->perPage(),
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
            ],
        ], 200);
    }

    /**
     * Void a prescription with a documented reason.
     *
     * POST /api/mobile/doctor/prescriptions/{id}/void
     *
     * Clinical events are immutable: nothing is deleted or overwritten here.
     * The service records the reason, stamps who voided it and when, and keeps
     * the prescription and its lines intact.
     */
    public function void(Request $request, string $id, PrescriptionService $prescriptions): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // prescriptions.id is a uuid column; a malformed id makes Postgres raise
        // 22P02, which would surface as a 500. Not found is the honest answer.
        if (! Str::isUuid($id)) {
            return response()->json(['message' => __('api.prescription_not_found')], 404);
        }

        $prescription = Prescription::where('facility_id', $this->facilityId($request))->find($id);

        if (! $prescription) {
            return response()->json(['message' => __('api.prescription_not_found')], 404);
        }

        try {
            $prescription = $prescriptions->void($prescription, $validated['reason'], $this->prescriberId($request));
        } catch (\LogicException $e) {
            // Already dispensed, voided, amended… — a terminal state admits no
            // further transition.
            return response()->json(['message' => __('api.prescription_not_voidable')], 409);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('api.prescription_void_failed')], 422);
        }

        return response()->json([
            'message' => __('api.prescription_voided'),
            'data'    => $this->formatPrescription($prescription->load(['items', 'patient'])),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility this session may write to — set by AuthenticateMobileProvider
     * from the token. Platform rule: facility_id comes only from the request
     * attributes, never from a header, body, query string or session.
     */
    private function facilityId(Request $request): ?string
    {
        return $request->attributes->get('provider_facility_id');
    }

    /** The clinician's user id, likewise stamped by the middleware. */
    private function prescriberId(Request $request): ?string
    {
        return $request->attributes->get('provider_user_id');
    }

    /**
     * The minimum a prescriber needs to be sure they have the right person.
     * No contact details, no address, no clinical history — this endpoint
     * identifies a patient, it does not disclose their record.
     *
     * @return array<string,mixed>
     */
    private function formatPatient(Patient $patient): array
    {
        return [
            'id'            => $patient->id,
            'health_id'     => $patient->health_id,
            'first_name'    => $patient->first_name,
            'last_name'     => $patient->last_name,
            'full_name'     => trim($patient->first_name . ' ' . $patient->last_name),
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'sex'           => $patient->sex,
            'blood_group'   => $patient->blood_group,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatPrescription(Prescription $prescription): array
    {
        $items = $prescription->relationLoaded('items')
            ? $prescription->items
            : $prescription->items()->get();

        return [
            'id'            => $prescription->id,
            'reference'     => $prescription->reference,
            'status'        => $prescription->status,
            'notes'         => $prescription->notes,
            'prescribed_at' => $prescription->prescribed_at?->toIso8601String(),
            'expires_at'    => $prescription->expires_at?->toIso8601String(),
            'voided_at'     => $prescription->voided_at?->toIso8601String(),
            'void_reason'   => $prescription->void_reason,
            'patient'       => [
                'id'        => $prescription->patient_id,
                // The patient relation is demo-scoped like every other read of
                // it; a null name means scoped out, not missing.
                'name'      => $prescription->patient
                    ? trim($prescription->patient->first_name . ' ' . $prescription->patient->last_name)
                    : null,
                'health_id' => $prescription->patient?->health_id,
            ],
            'item_count'    => $items->count(),
            'item_summary'  => $items->pluck('drug_name')->filter()->implode(', '),
            'items'         => $items->map(fn ($item) => [
                'id'            => $item->id,
                'drug_name'     => $item->drug_name,
                'medicine_id'   => $item->medicine_id,
                'dose'          => $item->dose,
                'frequency'     => $item->frequency,
                'route'         => $item->route,
                'duration_days' => $item->duration_days,
                'quantity'      => $item->quantity,
                'status'        => $item->status,
            ])->values(),
        ];
    }
}
