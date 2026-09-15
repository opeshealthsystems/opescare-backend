<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Modules\ClinicalDecisionSupport\Services\DrugInteractionService;
use App\Services\Clinical\PrescriptionService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\JsonResponse;
use App\Services\Portal\DeskFacilityContext;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The prescriber's desk — where a clinician actually WRITES a prescription.
 *
 * The clinical domain (issue / dispense / void / amend, audit, RX document) all
 * lives in PrescriptionService; this controller is only the facility-scoped web
 * surface over it. The pharmacy side (dispensing) is PharmacyPortalController.
 *
 * Patient selection is deliberately NOT a name search: platform policy forbids
 * patient enumeration, so a prescriber either arrives with a patient_id or looks
 * the patient up by their EXACT Health ID — the same rule the staff desk and the
 * verify screen follow.
 */
class PrescriberController extends Controller
{
    /** Administration routes offered on the form. */
    public const ROUTES = ['oral', 'IV', 'IM', 'subcutaneous', 'topical', 'inhaled', 'rectal', 'ophthalmic'];

    /** Prescriptions issued at this facility. */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $query = Prescription::with(['patient', 'items'])
            ->where('facility_id', $facilityId);

        // Filter an existing, already-facility-scoped list — not an enumeration.
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $prescriptions = $query->orderByDesc('prescribed_at')->paginate(25)->withQueryString();

        return view('portals.prescriber.index', [
            'prescriptions' => $prescriptions,
            'status'        => $status ?? null,
        ]);
    }

    /**
     * The write-a-prescription form. The patient comes from an explicit id or an
     * exact Health ID lookup; without one the form shows only the lookup box.
     */
    public function create(Request $request): View
    {
        $patient = null;
        $notFound = false;

        // Both follow a merge: a retired Health ID or record id still opens
        // the surviving record. A prescriber must not write a script against
        // a shell that holds none of the patient's history.
        if ($id = $request->input('patient_id')) {
            $patient = Patient::resolveId((string) $id);
        } elseif ($healthId = trim((string) $request->input('health_id'))) {
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;
        }

        return view('portals.prescriber.create', [
            'patient'     => $patient,
            'notFound'    => $notFound,
            'routes'      => self::ROUTES,
            'medicines'   => Medicine::query()->orderBy('name')->limit(500)->get(['id', 'name', 'strength']),
            'facilityId'  => $this->facilityId(),
        ]);
    }

    /** Issue the prescription. */
    public function store(Request $request, PrescriptionService $prescriptions): RedirectResponse
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
            'interactions_acknowledged' => 'nullable|boolean',
        ]);

        // Drop blank rows the form may submit, then require at least one real line.
        $items = array_values(array_filter(
            $validated['items'],
            fn ($item) => filled($item['drug_name'] ?? null)
        ));

        if (empty($items)) {
            return back()->withInput()->with('error', __('prescriber.needs_one_item'));
        }

        // Same rule as the app's prescriber (MobilePrescribingController):
        // a prescription mints a `treating_clinician` relationship, so standing
        // with the patient is established before one can be written.
        if (! ClinicalRelationshipGate::evaluate((string) auth()->id(), $this->facilityId(), $validated['patient_id'])->holds()) {
            return back()->withInput()->with('error', __('prescriber.no_relationship'));
        }

        try {
            $prescription = $prescriptions->issue([
                'patient_id'    => $validated['patient_id'],
                'facility_id'   => $this->facilityId(),
                'prescribed_by' => auth()->id(),
                'notes'         => $validated['notes'] ?? null,
                'validity_days' => $validated['validity_days'] ?? null,
                'items'         => $items,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('prescriber.issue_failed'));
        }

        // Drug interactions (imported CDSS): a WARNING, never a block. The
        // prescription above is already saved; what the prescriber was shown
        // and whether they ticked the acknowledgement goes into the audit trail.
        $alerts = $this->interactionAlerts(
            array_column($items, 'drug_name'),
            $validated['patient_id'],
            $prescription->id,
        );

        $redirect = redirect()->route('portals.prescriber.show', $prescription->id)
            ->with('success', __('prescriber.issued'));

        if ($alerts !== []) {
            $acknowledged = (bool) ($validated['interactions_acknowledged'] ?? false);

            app(PortalContextService::class)->auditPatientAccess(
                actionType:   'prescription_interaction_warning',
                resourceType: 'Prescription',
                resourceId:   $prescription->id,
                patientId:    $validated['patient_id'],
                extra:        [
                    'facility_id' => $this->facilityId(),
                    'reason'      => $acknowledged ? 'acknowledged_by_prescriber' : 'shown_not_acknowledged',
                    'after_state' => [
                        'acknowledged' => $acknowledged,
                        'interactions' => array_map(fn ($a) => [
                            'drug_a'   => $a['drug_a'],
                            'drug_b'   => $a['drug_b'],
                            'severity' => $a['severity'],
                        ], $alerts),
                    ],
                ],
            );

            $redirect->with('warning', trans_choice('cdss.saved_with_warnings', count($alerts), ['count' => count($alerts)]));
        }

        return $redirect;
    }

    /**
     * POST /facility/prescriptions/interaction-check — JSON warnings for the
     * medicines on the form, against each other and (only when the prescriber
     * has standing with the patient) the patient's active prescriptions.
     */
    public function interactionCheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'nullable|uuid',
            'drugs'      => 'required|array|max:30',
            'drugs.*'    => 'nullable|string|max:200',
        ]);

        $alerts = $this->interactionAlerts(
            array_values(array_filter($validated['drugs'], fn ($d) => filled($d))),
            $validated['patient_id'] ?? null,
        );

        return response()->json([
            'data' => array_map(fn ($a) => [
                'drug_a'         => $a['drug_a'],
                'drug_b'         => $a['drug_b'],
                'severity'       => $a['severity'],
                'severity_label' => __('cdss.severity_' . $a['severity']),
                'message'        => __('cdss.pair_' . str_replace('|', '_', $a['key'])),
            ], $alerts),
        ]);
    }

    /**
     * Interaction pairs that involve at least one medicine on the form. Pairs
     * made only of already-active medicines are not this prescriber's decision.
     *
     * @param  list<string>  $drugNames  the medicines being written now
     * @return list<array>
     */
    private function interactionAlerts(array $drugNames, ?string $patientId, ?string $excludePrescriptionId = null): array
    {
        $current = array_map(fn ($n) => ['name' => (string) $n], $drugNames);

        // Active prescriptions are patient data: read only with standing.
        $active = [];
        if ($patientId && ClinicalRelationshipGate::evaluate((string) auth()->id(), $this->facilityId(), $patientId)->holds()) {
            $active = \App\Models\PrescriptionItem::query()
                ->whereHas('prescription', fn ($q) => $q
                    ->where('patient_id', $patientId)
                    ->whereIn('status', ['active', 'partially_dispensed'])
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->when($excludePrescriptionId, fn ($q) => $q->where('id', '!=', $excludePrescriptionId)))
                ->pluck('drug_name')
                ->filter()
                ->map(fn ($n) => ['name' => (string) $n])
                ->all();
        }

        $onForm = fn (string $drug) => collect($current)->contains(
            fn ($c) => preg_match('/\b' . preg_quote($drug, '/') . '\b/i', $c['name']) === 1
        );

        return array_values(array_filter(
            app(DrugInteractionService::class)->checkInteractions(array_merge($current, $active)),
            fn ($a) => $onForm($a['drug_a']) || $onForm($a['drug_b']),
        ));
    }

    /** One prescription, with its lines and dispense state. */
    public function show(string $id): View
    {
        $prescription = Prescription::with(['patient', 'items'])
            ->where('facility_id', $this->facilityId())
            ->findOrFail($id);

        return view('portals.prescriber.show', compact('prescription'));
    }

    /**
     * Void a prescription with a documented reason. Clinical events are never
     * hard-deleted — the service records the reason and keeps the record.
     */
    public function void(Request $request, string $id, PrescriptionService $prescriptions): RedirectResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);

        $prescription = Prescription::where('facility_id', $this->facilityId())->findOrFail($id);

        try {
            $prescriptions->void($prescription, $validated['reason'], auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', __('prescriber.void_failed'));
        }

        return redirect()->route('portals.prescriber.show', $id)
            ->with('success', __('prescriber.voided'));
    }

    /**
     * The facility this prescriber is writing for. Mirrors the pharmacy portal's
     * resolution so both halves of the module agree on scope.
     */
    private function facilityId(): string
    {
        // DeskFacilityContext applies the portal's posting rule (session, then
        // the one posting, else the selector). The previous last resort was
        // Facility::value('id') — the FIRST facility in the table — so an
        // account with no posting listed, and wrote scripts for, a facility it
        // does not work at. No posting now closes the desk with a named 403.
        $id = app(DeskFacilityContext::class)->resolve();

        abort_if($id === null || $id === '', 403, __('prescriber.no_facility'));

        return (string) $id;
    }
}
