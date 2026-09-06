<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionService;
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

        return redirect()->route('portals.prescriber.show', $prescription->id)
            ->with('success', __('prescriber.issued'));
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
    private function facilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id
            ?? Facility::value('id');
    }
}
