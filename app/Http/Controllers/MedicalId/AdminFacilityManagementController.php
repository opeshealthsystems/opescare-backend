<?php

namespace App\Http\Controllers\MedicalId;

use App\Models\Facility;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Portal\PortalContextService;
use Carbon\Carbon;

class AdminFacilityManagementController
{
    public function __construct(private readonly PortalContextService $ctx) {}

    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $query = Facility::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $facilities = $query->paginate(25)->withQueryString();

        $stats = [
            'total'            => Facility::count(),
            'active'           => Facility::where('status', 'active')->count(),
            'suspended'        => Facility::where('status', 'suspended')->count(),
            'pending_approval' => Facility::where('status', 'pending_approval')->count(),
        ];

        return view('portals.admin.facilities.index', compact('facilities', 'stats'));
    }

    public function show(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);

        // Every section reads real rows only; empty sections are hidden by the view.
        $overview = app(\App\Services\Admin\FacilityOverviewBuilder::class)->build($facility);

        return view('portals.admin.facilities.show', compact('facility', 'overview'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $data = $request->validate([
            'name'         => 'required|max:200',
            'type'         => 'required',
            'region'       => 'nullable',
            'country_code' => 'nullable|max:3',
        ]);

        $data['status'] = 'active';

        if (empty($data['facility_code'])) {
            $region = strtoupper($data['region'] ?? 'XX');
            $data['facility_code'] = 'OP-' . $region . '-FID-' . strtoupper(substr(md5(uniqid()), 0, 4));
        }

        Facility::create($data);

        return redirect()->route('admin.facilities.index')->with('success', __('flash.facility_created'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);

        $data = $request->validate([
            'name'                   => 'sometimes|required|max:200',
            'type'                   => 'sometimes|required',
            'status'                 => 'sometimes|nullable',
            'license_number'         => 'sometimes|nullable',
            'parent_organization_id' => 'sometimes|nullable',
            'region'                 => 'sometimes|nullable',
            'country_code'           => 'sometimes|nullable|max:3',
        ]);

        $facility->update($data);

        return redirect()->back()->with('success', __('flash.facility_updated'));
    }

    public function suspend(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);
        $facility->update(['status' => 'suspended']);

        return redirect()->back()->with('success', __('flash.facility_suspended'));
    }

    public function activate(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);
        $facility->update(['status' => 'active']);

        return redirect()->back()->with('success', __('flash.facility_activated'));
    }

    public function approve(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);

        $updates = ['status' => 'active'];

        if (in_array('approved_at', $facility->getFillable()) || \Illuminate\Support\Facades\Schema::hasColumn($facility->getTable(), 'approved_at')) {
            $updates['approved_at'] = Carbon::now();
        }

        $facility->update($updates);

        return redirect()->back()->with('success', __('flash.facility_approved'));
    }

    /**
     * "Remove" a facility — which DEACTIVATES it and never deletes it.
     *
     * This method used to call $facility->delete(). `facilities` carries no
     * SoftDeletes trait, so that was a hard DELETE, and 61 tables cascade from
     * `facilities`: visits, prescriptions, lab_orders, imaging_orders,
     * immunization_records, radiology_reports and consent_grants among them.
     * The old guard only checked `patients.facility_id`, so a facility whose
     * patients had since been registered elsewhere still passed the check while
     * its visits, prescriptions and consent grants were destroyed with it.
     *
     * A patient's record belongs to the patient, not to the building. The
     * Health ID is portable; closing a facility must not take a person's
     * history with it. So the facility is set to `suspended` — the status the
     * booking path already refuses (PublicPageController), which `activate`
     * already reverses and this screen's own stats tile already counts — and
     * every clinical row keeps its facility_id, so provenance stays readable.
     *
     * The guard on existing patients is gone deliberately: deactivation is safe
     * for a facility with a million records, and refusing it left the only
     * available action on a real facility being the one that destroyed data.
     */
    public function destroy(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $facility = Facility::findOrFail($id);

        if ($facility->status === 'suspended') {
            return redirect()->route('admin.facilities.index')
                ->with('success', __('flash.facility_deactivated'));
        }

        $before = $facility->status;

        $facility->update(['status' => 'suspended']);

        // audit_events.actor_id is a uuid column; PortalContextService::actorId()
        // returns Auth::id() (a uuid) or null — never the email fallback that
        // actorId() on this controller carries for non-uuid contexts.
        $this->ctx->auditPatientAccess(
            actionType:   'admin_facility_deactivated',
            resourceType: 'Facility',
            resourceId:   $facility->id,
            extra: [
                'reason'       => (string) $request->input('reason', 'removed_by_admin'),
                'before_state' => ['status' => $before],
                'after_state'  => ['status' => 'suspended'],
            ],
        );

        return redirect()->route('admin.facilities.index')
            ->with('success', __('flash.facility_deactivated'));
    }
}
