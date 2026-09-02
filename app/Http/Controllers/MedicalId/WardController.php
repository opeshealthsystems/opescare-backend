<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\Ward;
use App\Modules\WardManagement\Services\WardService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * WardController — one facility's wards, beds and inpatient admissions.
 *
 * Everything here is patient data, so two things hold on every action.
 *
 * 1. The facility comes from the session (`facilityId()`), never from
 *    `Facility::value('id')` — whichever of 345 rows Postgres returned first.
 *
 * 2. Every id off the URL or the form is re-fetched scoped to that facility.
 *    `WardService::admit()` and `::transfer()` take a bare `bed_id` and
 *    `Bed::findOrFail()` it, and `Bed` has no facility column of its own — it
 *    reaches the facility through its ward — so an unscoped bed id let one
 *    hospital occupy, free, or transfer into another hospital's bed, and
 *    `Admission::findOrFail($id)` let it discharge their patient. Both are
 *    resolved through the ward join below before the service sees them.
 *
 * Reads and writes of admission records are audited through
 * PortalContextService, the same way the patient portal audits its own.
 */
class WardController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * The single-facility fallback holds only when there is exactly one
     * facility. Otherwise there is no safe guess and this 409s rather than
     * admitting a patient into somebody else's ward.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which ward this belongs to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /** An admission, only if it belongs to the acting facility. */
    private function admissionAtFacility(string $id, string $facilityId, array $with = []): Admission
    {
        return Admission::with($with)
            ->where('id', $this->assertUuid($id))
            ->where('facility_id', $facilityId)
            ->firstOrFail();
    }

    /** A bed, only if its ward belongs to the acting facility. */
    private function bedAtFacility(string $id, string $facilityId): Bed
    {
        return Bed::where('id', $this->assertUuid($id))
            ->whereHas('ward', fn ($q) => $q->where('facility_id', $facilityId))
            ->firstOrFail();
    }

    // ── Ward overview / bed map ───────────────────────────────────

    public function index(WardService $svc)
    {
        $facilityId = $this->facilityId();
        $summary    = $svc->occupancySummary($facilityId);
        $wards      = Ward::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->with(['beds.activeAdmission.patient'])
            ->orderBy('name')
            ->get();

        return view('portals.staff.wards.index', compact('summary', 'wards'));
    }

    // ── Create ward ───────────────────────────────────────────────

    public function wardStore(Request $request, WardService $svc)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'ward_type'  => 'required|in:' . implode(',', array_keys(Ward::wardTypes())),
            'total_beds' => 'required|integer|min:1|max:200',
            'floor'      => 'nullable|string|max:20',
            'building'   => 'nullable|string|max:50',
        ]);

        try {
            $svc->createWard(array_merge($request->validated(), [
                'facility_id' => $this->facilityId(),
                'is_active'   => true,
            ]));

            return redirect()->route('portals.staff.wards')->with('success', __('flash.ward_created'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Admissions list ───────────────────────────────────────────

    public function admissions(Request $request)
    {
        $q = Admission::with(['patient', 'bed.ward'])
            ->where('facility_id', $this->facilityId())
            ->orderByDesc('admitted_at');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $admissions = $q->paginate(20)->withQueryString();

        $this->context->auditPatientAccess(
            actionType:   'ward_admission_list_view',
            resourceType: 'Admission',
        );

        return view('portals.staff.wards.admissions', compact('admissions'));
    }

    // ── Admit patient ─────────────────────────────────────────────

    public function admitStore(Request $request, WardService $svc)
    {
        $request->validate([
            'patient_id'      => 'required|string|max:100',
            'bed_id'          => 'required|uuid',
            'admission_reason'=> 'nullable|string|max:500',
            'visit_id'        => 'nullable|string|max:100',
        ]);

        $facilityId = $this->facilityId();

        // The bed decides where the patient physically ends up, and the service
        // takes it on trust. It has to be one of ours.
        $bed = $this->bedAtFacility($request->input('bed_id'), $facilityId);

        try {
            $admission = $svc->admit(array_merge($request->validated(), [
                'facility_id' => $facilityId,
                'bed_id'      => $bed->id,
            ]), $this->demoActorId());

            $this->context->auditPatientAccess(
                actionType:   'ward_patient_admitted',
                resourceType: 'Admission',
                resourceId:   $admission->id,
                patientId:    $admission->patient_id,
            );

            return redirect()->route('portals.staff.wards.admissions')
                ->with('success', __('flash.patient_admitted'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Discharge ─────────────────────────────────────────────────

    public function dischargeStore(Request $request, string $id, WardService $svc)
    {
        $request->validate([
            'discharge_reason'     => 'nullable|string|max:500',
            'discharge_destination'=> 'required|in:home,referral,ama,deceased,transferred',
        ]);

        $admission = $this->admissionAtFacility($id, $this->facilityId());

        try {
            $svc->discharge($admission, $request->validated(), $this->demoActorId());

            $this->context->auditPatientAccess(
                actionType:   'ward_patient_discharged',
                resourceType: 'Admission',
                resourceId:   $admission->id,
                patientId:    $admission->patient_id,
            );

            return redirect()->route('portals.staff.wards.admissions')
                ->with('success', __('flash.patient_discharged'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Transfer ──────────────────────────────────────────────────

    public function transferStore(Request $request, string $id, WardService $svc)
    {
        $request->validate([
            'to_bed_id' => 'required|uuid',
            'reason'    => 'nullable|string|max:300',
        ]);

        $facilityId = $this->facilityId();

        // Both ends of a transfer are ours: the admission being moved and the
        // bed it is moved into.
        $admission = $this->admissionAtFacility($id, $facilityId, ['bed']);
        $toBed     = $this->bedAtFacility($request->input('to_bed_id'), $facilityId);

        try {
            $svc->transfer($admission, $toBed->id, $request->reason, $this->demoActorId());

            $this->context->auditPatientAccess(
                actionType:   'ward_patient_transferred',
                resourceType: 'Admission',
                resourceId:   $admission->id,
                patientId:    $admission->patient_id,
            );

            return redirect()->route('portals.staff.wards.admissions')
                ->with('success', __('flash.bed_transfer_completed'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
