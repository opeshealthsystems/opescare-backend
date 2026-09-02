<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;

/**
 * Facility-level clinical overview pages accessible to hospital_admin,
 * clinic_admin, facility_admin, and facility_ceo roles from /portals/admin.
 *
 * These are READ-ONLY register views. Actual clinical actions remain in
 * /portals/staff (for clinical staff) and the dedicated pharmacy/lab portals.
 */
class FacilityClinicalController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    /**
     * The facility whose clinical register is being read.
     *
     * Both pages list identified patients' prescriptions and lab orders, so the
     * facility has to be the one the reader is actually signed in to. The old
     * `?? Facility::value('id')` tail answered "which facility?" with whichever
     * row Postgres returned first out of 345 — an admin whose session had no
     * facility read a stranger hospital's clinical register, and /portals/admin
     * is explicitly exempted from RequireFacilityContext's redirect, so the
     * path was reachable in normal use.
     *
     * The single-facility fallback holds only where it is unambiguous. Anything
     * else fails closed rather than guessing.
     */
    private function facilityId(): string
    {
        $resolved = $this->ctx->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'whose clinical register to show. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    // ------------------------------------------------------------------
    // Prescription Register
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

        if ($from = $req->input('from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $req->input('to')) {
            $q->whereDate('created_at', '<=', $to);
        }

        $prescriptions = $q->orderByDesc('created_at')->paginate(30)->withQueryString();

        $summary = [
            'active'              => Prescription::where('facility_id', $facilityId)->where('status', 'active')->count(),
            'dispensed_today'     => Prescription::where('facility_id', $facilityId)->where('status', 'dispensed')->whereDate('dispensed_at', today())->count(),
            'partially_dispensed' => Prescription::where('facility_id', $facilityId)->where('status', 'partially_dispensed')->count(),
            'expired'             => Prescription::where('facility_id', $facilityId)->where('status', 'expired')->count(),
        ];

        $this->ctx->auditPatientAccess(
            actionType:   'facility_prescription_register_view',
            resourceType: 'Prescription',
        );

        return view('portals.admin.clinical.prescriptions', compact('prescriptions', 'summary'));
    }

    // ------------------------------------------------------------------
    // Lab Orders Register
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

        $orders = $q->orderByDesc('created_at')->paginate(30)->withQueryString();

        $summary = [
            'pending'    => LabOrder::where('facility_id', $facilityId)->where('status', 'pending')->count(),
            'processing' => LabOrder::where('facility_id', $facilityId)->where('status', 'processing')->count(),
            'resulted'   => LabOrder::where('facility_id', $facilityId)->where('status', 'resulted')->whereDate('resulted_at', today())->count(),
            'urgent'     => LabOrder::where('facility_id', $facilityId)->where('urgency', 'urgent')->whereNotIn('status', ['resulted', 'cancelled'])->count(),
        ];

        $this->ctx->auditPatientAccess(
            actionType:   'facility_lab_order_register_view',
            resourceType: 'LabOrder',
        );

        return view('portals.admin.clinical.lab_orders', compact('orders', 'summary'));
    }
}
