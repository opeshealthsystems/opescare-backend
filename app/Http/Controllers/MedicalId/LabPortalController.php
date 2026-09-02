<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;

class LabPortalController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    /**
     * The laboratory this request belongs to.
     *
     * Resolution comes from the signed-in session only — the resolution
     * RequireFacilityContext already guarantees for every route in this group.
     * The previous `?? Facility::value('id')` tail read like a safe default but
     * returned whichever row Postgres handed back first out of 345, so a lab
     * tech whose session had lost its facility silently read — and resulted —
     * another hospital's specimens.
     *
     * The single-facility fallback is honoured only when there genuinely is
     * exactly one facility, the condition that made it safe. With more than one
     * and no resolved context there is no correct guess, and
     * RequireFacilityContext is bypassed for platform-admin roles so this path
     * is reachable; it fails closed instead.
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
            . 'which laboratory these orders belong to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function dashboard()
    {
        $facilityId = $this->facilityId();

        $stats = [
            'pending'    => LabOrder::where('facility_id', $facilityId)
                                ->where('status', 'pending')
                                ->count(),
            'collected'  => LabOrder::where('facility_id', $facilityId)
                                ->where('status', 'collected')
                                ->count(),
            'processing' => LabOrder::where('facility_id', $facilityId)
                                ->where('status', 'processing')
                                ->count(),
            'resulted'   => LabOrder::where('facility_id', $facilityId)
                                ->where('status', 'resulted')
                                ->whereDate('resulted_at', today())
                                ->count(),
            'urgent'     => LabOrder::where('facility_id', $facilityId)
                                ->where('urgency', 'urgent')
                                ->whereNotIn('status', ['resulted', 'cancelled'])
                                ->count(),
            // lab_results has no facility_id — scope via the parent lab order.
            'abnormal'   => LabResult::whereHas('labOrder', fn ($o) => $o->where('facility_id', $facilityId))
                                ->whereIn('flag', ['H', 'HH', 'L', 'LL', 'abnormal'])
                                ->whereDate('created_at', today())
                                ->count(),
        ];

        $urgentOrders = LabOrder::with('patient')
            ->where('facility_id', $facilityId)
            ->where('urgency', 'urgent')
            ->whereNotIn('status', ['resulted', 'cancelled'])
            ->orderBy('ordered_at')
            ->limit(6)
            ->get();

        $recentResults = LabResult::with(['patient', 'labOrder'])
            ->whereHas('labOrder', fn ($o) => $o->where('facility_id', $facilityId))
            ->orderByDesc('resulted_at')
            ->limit(6)
            ->get();

        return view('portals.lab.dashboard', compact('stats', 'urgentOrders', 'recentResults'));
    }

    // ------------------------------------------------------------------
    // Work Queue — pending / in-progress orders
    // ------------------------------------------------------------------

    public function orders(Request $req)
    {
        $facilityId = $this->facilityId();

        $q = LabOrder::with('patient')
            ->where('facility_id', $facilityId);

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        } else {
            $q->whereNotIn('status', ['resulted', 'cancelled']);
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

        $orders = $q->orderByRaw("CASE urgency WHEN 'urgent' THEN 0 ELSE 1 END")
                    ->orderBy('ordered_at')
                    ->paginate(25)
                    ->withQueryString();

        return view('portals.lab.orders', compact('orders'));
    }

    // ------------------------------------------------------------------
    // Results — view and enter results
    // ------------------------------------------------------------------

    public function results(Request $req)
    {
        $facilityId = $this->facilityId();

        // lab_results has no facility_id — scope via the parent lab order.
        $q = LabResult::with(['patient', 'labOrder'])
            ->whereHas('labOrder', fn ($o) => $o->where('facility_id', $facilityId));

        if ($flag = $req->input('flag')) {
            $q->where('flag', $flag);
        }

        if ($search = $req->input('search')) {
            $q->where(function ($sq) use ($search) {
                $sq->where('parameter_name', 'like', "%{$search}%")
                   ->orWhereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%"));
            });
        }

        $results = $q->orderByDesc('resulted_at')->paginate(30)->withQueryString();

        return view('portals.lab.results', compact('results'));
    }

    // ------------------------------------------------------------------
    // Sample Tracking — orders in collection / received state
    // ------------------------------------------------------------------

    public function samples(Request $req)
    {
        $facilityId = $this->facilityId();

        $pending = LabOrder::with('patient')
            ->where('facility_id', $facilityId)
            ->where('status', 'pending')
            ->orderBy('ordered_at')
            ->limit(50)
            ->get();

        $collected = LabOrder::with('patient')
            ->where('facility_id', $facilityId)
            ->where('status', 'collected')
            ->orderByDesc('collected_at')
            ->limit(50)
            ->get();

        return view('portals.lab.samples', compact('pending', 'collected'));
    }

    // ------------------------------------------------------------------
    // Mark sample collected
    // ------------------------------------------------------------------

    public function markCollected(Request $req, string $id)
    {
        $facilityId = $this->facilityId();

        $order = LabOrder::where('facility_id', $facilityId)->findOrFail($id);
        $order->status       = 'collected';
        $order->collected_at = now();
        $order->save();

        $this->ctx->auditPatientAccess(
            actionType:   'lab_sample_collected',
            resourceType: 'LabOrder',
            resourceId:   $order->id,
            patientId:    $order->patient_id,
        );

        return back()->with('success', __('flash.sample_collected'));
    }

    // ------------------------------------------------------------------
    // Mark order in processing
    // ------------------------------------------------------------------

    public function markProcessing(Request $req, string $id)
    {
        $facilityId = $this->facilityId();

        $order = LabOrder::where('facility_id', $facilityId)->findOrFail($id);
        $order->status = 'processing';
        $order->save();

        $this->ctx->auditPatientAccess(
            actionType:   'lab_order_processing',
            resourceType: 'LabOrder',
            resourceId:   $order->id,
            patientId:    $order->patient_id,
        );

        return back()->with('success', __('flash.order_moved_processing'));
    }

    // ------------------------------------------------------------------
    // Enter a result for an order
    // ------------------------------------------------------------------

    /** GET — result-entry form for a collected/processing order. */
    public function enterResultForm(Request $req, string $id)
    {
        $facilityId = $this->facilityId();
        $order = LabOrder::with('patient')->where('facility_id', $facilityId)->findOrFail($id);

        abort_if(!in_array($order->status, ['collected', 'processing', 'resulted']), 422, 'This order is not ready for results.');

        $this->ctx->auditPatientAccess(
            actionType:   'lab_result_entry_view',
            resourceType: 'LabOrder',
            resourceId:   $order->id,
            patientId:    $order->patient_id,
        );

        return view('portals.lab.result_entry', compact('order'));
    }

    /** POST — store a result and mark the order resulted. */
    public function storeResult(Request $req, string $id)
    {
        $facilityId = $this->facilityId();
        $order = LabOrder::where('facility_id', $facilityId)->findOrFail($id);

        abort_if(!in_array($order->status, ['collected', 'processing', 'resulted']), 422, 'This order is not ready for results.');

        $data = $req->validate([
            'parameter_name'  => 'required|string|max:160',
            'value'           => 'required|string|max:160',
            'unit'            => 'nullable|string|max:40',
            'reference_range' => 'nullable|string|max:80',
            'flag'            => 'nullable|in:N,H,L,HH,LL,abnormal',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $result = LabResult::create([
            'lab_order_id'    => $order->id,
            // The patient comes off the facility-scoped order, never from input.
            'patient_id'      => $order->patient_id,
            'parameter_name'  => $data['parameter_name'],
            'value'           => $data['value'],
            'unit'            => $data['unit'] ?? null,
            'reference_range' => $data['reference_range'] ?? null,
            'flag'            => $data['flag'] ?? 'N',
            'notes'           => $data['notes'] ?? null,
            'verified_by'     => (string) (auth()->id() ?? ''),
            'resulted_at'     => now(),
        ]);

        $order->update(['status' => 'resulted', 'resulted_at' => now()]);

        $this->ctx->auditPatientAccess(
            actionType:   'lab_result_entered',
            resourceType: 'LabResult',
            resourceId:   $result->id,
            patientId:    $order->patient_id,
        );

        return redirect()->route('portals.lab.results')->with('success', __('flash.lab_result_entered'));
    }
}
