<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff-facing clinical pages for /portals/staff.
 *
 * Gives doctors, nurses, and specialists a facility-wide view of all
 * prescriptions and lab orders — complementing the per-visit consult workflow —
 * and, since the prescribing form landed here, the place a clinician actually
 * issues a prescription. Before that the chain started nowhere: staff could see
 * a prescription register, patients could see their prescriptions, and
 * pharmacies could dispense — but nothing in the portal could create one.
 */
class StaffClinicalController extends Controller
{
    public function __construct(
        private readonly PortalContextService $ctx,
        private readonly PrescriptionService $prescriptions,
    ) {}

    private function facilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id
            ?? Facility::value('id');
    }

    /**
     * The facility a clinical record may be written against.
     *
     * Deliberately narrower than facilityId(): the `Facility::value('id')`
     * fallback there resolves to "whatever facility is first in the table",
     * which is harmless for an empty register but would let a user with no
     * facility context prescribe in a stranger's name.
     */
    private function writableFacilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id;
    }

    // ------------------------------------------------------------------
    // Prescriptions register
    // ------------------------------------------------------------------

    public function prescriptions(Request $req)
    {
        $facilityId = $this->facilityId();

        $q = Prescription::with(['patient', 'items'])
            ->where('facility_id', $facilityId);

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }

        if ($search = $req->input('search')) {
            $q->whereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%"));
        }

        $prescriptions = $q->orderByDesc('created_at')->paginate(25)->withQueryString();

        $summary = [
            'active'              => Prescription::where('facility_id', $facilityId)->where('status', 'active')->count(),
            'dispensed_today'     => Prescription::where('facility_id', $facilityId)->where('status', 'dispensed')->whereDate('dispensed_at', today())->count(),
            'partially_dispensed' => Prescription::where('facility_id', $facilityId)->where('status', 'partially_dispensed')->count(),
            'expired'             => Prescription::where('facility_id', $facilityId)->where('status', 'expired')->count(),
        ];

        return view('portals.staff.clinical.prescriptions', compact('prescriptions', 'summary'));
    }

    // ------------------------------------------------------------------
    // Prescribing — the first link in the chain
    // ------------------------------------------------------------------

    /** GET — the prescribing form. */
    public function prescriptionCreate(Request $req)
    {
        $facilityId = $this->writableFacilityId();
        abort_if(! $facilityId, 403, 'No facility context — a prescription must be issued by a facility.');

        return view('portals.staff.clinical.prescription_create', [
            'patients'  => $this->patientsForFacility($facilityId),
            'medicines' => Medicine::active()->orderBy('name')->get([
                'id', 'name', 'generic_name', 'strength', 'form', 'prescription_required', 'is_controlled',
            ]),
            'routes'          => self::ADMINISTRATION_ROUTES,
            'defaultValidity' => PrescriptionService::DEFAULT_VALIDITY_DAYS,
        ]);
    }

    /** Administration routes offered on the form. */
    public const ADMINISTRATION_ROUTES = [
        'oral', 'iv', 'im', 'sc', 'topical', 'inhalation', 'rectal', 'ophthalmic', 'otic', 'nasal',
    ];

    /** POST — issue the prescription. */
    public function prescriptionStore(Request $req)
    {
        $facilityId = $this->writableFacilityId();
        abort_if(! $facilityId, 403, 'No facility context — a prescription must be issued by a facility.');

        $validated = $req->validate([
            'patient_id'             => 'required|uuid|exists:patients,id',
            'visit_id'               => 'nullable|uuid',
            'notes'                  => 'nullable|string|max:2000',
            'validity_days'          => 'nullable|integer|min:1|max:365',
            'items'                  => 'required|array|min:1|max:20',
            'items.*.medicine_id'    => 'required|uuid|exists:medicines,id',
            'items.*.dose'           => 'nullable|string|max:100',
            'items.*.frequency'      => 'required|string|max:100',
            'items.*.route'          => 'nullable|string|max:50',
            'items.*.duration_days'  => 'nullable|integer|min:1|max:365',
            'items.*.quantity'       => 'nullable|integer|min:1|max:1000',
        ]);

        // Same guard the rest of the staff portal applies: the record must
        // belong to the facility the clinician is acting in. A prescription is
        // a patient-data write, so this is enforced server-side rather than
        // trusting the id that came back from the form.
        $patient = Patient::findOrFail($validated['patient_id']);
        abort_unless(
            $this->patientIsReachable($patient->id, $facilityId),
            403,
            'This patient is not registered at, treated by, or consented to this facility.'
        );

        try {
            $prescription = $this->prescriptions->issue([
                'patient_id'    => $patient->id,
                'facility_id'   => $facilityId,
                'visit_id'      => $validated['visit_id'] ?? null,
                'prescribed_by' => $this->ctx->actorId(),
                'notes'         => $validated['notes'] ?? null,
                'validity_days' => $validated['validity_days'] ?? null,
                'items'         => $validated['items'],
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('flash.prescription_issue_failed'));
        }

        // Audited the same way every other patient-data write in the portals is
        // (see PatientPortalController::bookAppointment). PrescriptionService
        // emits the domain event; this records the portal action that caused it.
        $this->ctx->auditPatientAccess(
            actionType:   'staff_prescription_issued',
            resourceType: 'Prescription',
            resourceId:   $prescription->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.staff.prescriptions')
            ->with('success', __('flash.prescription_issued'));
    }

    /**
     * POST — void a prescription with a documented reason.
     *
     * There is no "edit" and no "delete" here by design: a prescription is an
     * immutable clinical event. A mistake is voided (or marked entered-in-error)
     * and, if therapy still needs to change, a new prescription is issued.
     */
    public function prescriptionVoid(Request $req, string $id)
    {
        $facilityId = $this->writableFacilityId();
        abort_if(! $facilityId, 403);

        $validated = $req->validate([
            'void_reason' => 'required|string|min:5|max:500',
            'entered_in_error' => 'nullable|boolean',
        ]);

        $prescription = Prescription::where('facility_id', $facilityId)->findOrFail($id);

        try {
            $enteredInError = (bool) ($validated['entered_in_error'] ?? false);

            $enteredInError
                ? $this->prescriptions->markEnteredInError($prescription, $validated['void_reason'], $this->ctx->actorId())
                : $this->prescriptions->void($prescription, $validated['void_reason'], $this->ctx->actorId());
        } catch (\Throwable $e) {
            return back()->with('error', __('flash.prescription_void_failed'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'staff_prescription_voided',
            resourceType: 'Prescription',
            resourceId:   $prescription->id,
            patientId:    $prescription->patient_id,
            extra:        ['reason' => $validated['void_reason']],
        );

        return redirect()->route('portals.staff.prescriptions')
            ->with('success', __('flash.prescription_voided'));
    }

    // ------------------------------------------------------------------
    // Patient reachability — one predicate, used by the form and the write
    // ------------------------------------------------------------------

    /**
     * Patients this facility may prescribe for: registered here, seen here, or
     * covered by an active consent grant to this facility.
     *
     * No free-text name search is offered — patient LIKE search is an
     * enumeration vector and is prohibited platform-wide.
     */
    private function patientsForFacility(string $facilityId)
    {
        return Patient::query()
            ->where(fn ($q) => $this->reachablePatientConstraint($q, $facilityId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'health_id', 'date_of_birth', 'sex']);
    }

    private function patientIsReachable(string $patientId, string $facilityId): bool
    {
        return Patient::query()
            ->whereKey($patientId)
            ->where(fn ($q) => $this->reachablePatientConstraint($q, $facilityId))
            ->exists();
    }

    private function reachablePatientConstraint($query, string $facilityId)
    {
        return $query
            ->where('patients.facility_id', $facilityId)
            ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('visits')
                ->whereColumn('visits.patient_id', 'patients.id')
                ->where('visits.facility_id', $facilityId))
            ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('appointments')
                ->whereColumn('appointments.patient_id', 'patients.id')
                ->where('appointments.facility_id', $facilityId))
            ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('consent_grants')
                ->whereColumn('consent_grants.patient_id', 'patients.id')
                ->where('consent_grants.facility_id', $facilityId)
                ->where('consent_grants.status', 'active')
                ->where('consent_grants.expires_at', '>=', now()));
    }

    // ------------------------------------------------------------------
    // Lab orders register
    // ------------------------------------------------------------------

    public function labOrders(Request $req)
    {
        $facilityId = $this->facilityId();

        $q = LabOrder::with('patient')
            ->where('facility_id', $facilityId);

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }

        if ($urgency = $req->input('urgency')) {
            $q->where('urgency', $urgency);
        }

        if ($search = $req->input('search')) {
            $q->where(function ($sq) use ($search) {
                $sq->where('test_name', 'like', "%{$search}%")
                   ->orWhereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%"));
            });
        }

        $orders = $q->orderByDesc('created_at')->paginate(25)->withQueryString();

        $summary = [
            'pending'    => LabOrder::where('facility_id', $facilityId)->where('status', 'pending')->count(),
            'processing' => LabOrder::where('facility_id', $facilityId)->where('status', 'processing')->count(),
            'resulted'   => LabOrder::where('facility_id', $facilityId)->where('status', 'resulted')->whereDate('resulted_at', today())->count(),
            'urgent'     => LabOrder::where('facility_id', $facilityId)->where('urgency', 'urgent')->whereNotIn('status', ['resulted', 'cancelled'])->count(),
        ];

        return view('portals.staff.clinical.lab_orders', compact('orders', 'summary'));
    }
}
