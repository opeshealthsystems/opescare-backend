<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\DutyRoster;
use App\Models\Facility;
use App\Models\LeaveRequest;
use App\Models\RosterAssignment;
use App\Models\StaffProfile;
use App\Models\StaffShift;
use App\Modules\Staff\Services\StaffService;
use App\Modules\Staff\Services\RosterService;
use App\Modules\Staff\Services\LeaveService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * StaffHRPortalController — one facility's roster, shifts and leave book.
 *
 * Two rules, and every method below obeys both.
 *
 * 1. THE FACILITY COMES FROM THE SESSION. `facilityId()` reads the context
 *    RequireFacilityContext established for this request; it never asks the
 *    facilities table who to be. The old helper here was
 *    `Facility::value('id')` — whichever row Postgres handed back first out of
 *    345 — which is how a clerk in one town published another town's data.
 *
 * 2. EVERY ROW LOOKUP IS SCOPED TOO. Resolving the right facility buys nothing
 *    if the next line is `Model::findOrFail($id)`: the id arrives in the URL,
 *    so an unscoped lookup lets a user at facility A publish A's roster… and
 *    also publish B's, by pasting B's id. Each action below re-fetches its
 *    record with `where('facility_id', …)` and 404s when it does not belong
 *    here, and the ids submitted in a form body (staff, shift, leave subject)
 *    are checked the same way before they are written to anything.
 */
class StaffHRPortalController extends Controller
{
    public function __construct(
        private StaffService         $staffService,
        private RosterService        $rosterService,
        private LeaveService         $leaveService,
        private PortalContextService $context,
    ) {}

    // ── Helpers ──────────────────────────────────────────────

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    /**
     * The facility this request acts for — session-resolved, never table-scanned.
     *
     * The single-facility fallback is honoured only when there genuinely is
     * exactly one facility, which is the condition that made it safe. With more
     * than one and no resolved context (a platform-tier account, which
     * RequireFacilityContext waves through) there is no safe guess, so this
     * fails closed rather than picking a hospital at random.
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
            . 'whose staff record this is. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /**
     * A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500.
     */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /** A staff profile, only if it belongs to the acting facility. */
    private function staffProfileAtFacility(string $id, string $facilityId): StaffProfile
    {
        return StaffProfile::where('id', $this->assertUuid($id))
            ->where('facility_id', $facilityId)
            ->firstOrFail();
    }

    /** A duty roster, only if it belongs to the acting facility. */
    private function rosterAtFacility(string $id, string $facilityId): DutyRoster
    {
        return DutyRoster::where('id', $this->assertUuid($id))
            ->where('facility_id', $facilityId)
            ->firstOrFail();
    }

    /** A leave request, only if its subject is on the acting facility's payroll. */
    private function leaveRequestAtFacility(string $id, string $facilityId): LeaveRequest
    {
        return LeaveRequest::where('id', $this->assertUuid($id))
            ->whereHas('staffProfile', fn ($q) => $q->where('facility_id', $facilityId))
            ->firstOrFail();
    }

    // ── Staff Directory ───────────────────────────────────────

    public function directory(Request $request): View
    {
        $facilityId = $this->facilityId();

        $staff = $this->staffService->listStaff($facilityId, $request->only([
            'status', 'department', 'staff_category', 'search',
        ]));

        $departments = StaffProfile::where('facility_id', $facilityId)
            ->whereNotNull('department')
            ->distinct()->pluck('department')->sort()->values();

        return view('portals.staff.hr.directory', compact('staff', 'departments'));
    }

    public function directoryStore(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'staff_category'   => 'required|string',
            'employment_type'  => 'required|string',
        ]);

        $facilityId = $this->facilityId();
        $data = $request->except(['_token']);

        // The facility is never taken from the form: whatever a tampered
        // facility_id field says, the row is stamped with the session's facility.
        unset($data['facility_id']);

        if (empty($data['employee_number'])) {
            $data['employee_number'] = $this->staffService->generateEmployeeNumber($facilityId);
        }

        $this->staffService->createStaffProfile($facilityId, $data);

        return redirect()->route('portals.staff.hr.directory')
            ->with('success', __('flash.staff_member_added'));
    }

    public function directoryStatus(Request $request, string $id): RedirectResponse
    {
        $request->validate(['status' => 'required|string']);

        $profile = $this->staffProfileAtFacility($id, $this->facilityId());

        try {
            $this->staffService->updateStaffStatus($profile->id, $request->input('status'));
            return redirect()->route('portals.staff.hr.directory')
                ->with('success', __('flash.staff_status_updated'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.directory')
                ->with('error', $e->getMessage());
        }
    }

    public function addLicense(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'profession'    => 'required|string',
            'license_number'=> 'required|string|max:100',
            'issuing_body'  => 'required|string|max:200',
        ]);

        $profile = $this->staffProfileAtFacility($id, $this->facilityId());

        $this->staffService->addLicense($profile->id, $request->except(['_token']));

        return redirect()->route('portals.staff.hr.directory')
            ->with('success', __('flash.staff_license_added'));
    }

    // ── Shifts ────────────────────────────────────────────────

    public function shifts(): View
    {
        $facilityId = $this->facilityId();
        $shifts = $this->rosterService->listShifts($facilityId);
        return view('portals.staff.hr.shifts', compact('shifts'));
    }

    public function shiftsStore(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        $data = $request->except(['_token']);
        unset($data['facility_id']);

        $this->rosterService->createShift($this->facilityId(), $data);

        return redirect()->route('portals.staff.hr.shifts')
            ->with('success', __('flash.shift_created'));
    }

    public function shiftsToggle(string $id): RedirectResponse
    {
        $shift = StaffShift::where('id', $this->assertUuid($id))
            ->where('facility_id', $this->facilityId())
            ->firstOrFail();

        $this->rosterService->toggleShiftStatus($shift->id);

        return redirect()->route('portals.staff.hr.shifts')
            ->with('success', __('flash.shift_status_updated'));
    }

    // ── Rosters ───────────────────────────────────────────────

    public function roster(Request $request): View
    {
        $facilityId = $this->facilityId();
        $rosters = $this->rosterService->listRosters($facilityId, $request->only(['department', 'status']));

        $staff  = StaffProfile::where('facility_id', $facilityId)
            ->where('status', 'active')
            ->orderBy('last_name')->get();
        $shifts = StaffShift::where('facility_id', $facilityId)
            ->where('status', 'active')->get();

        $departments = StaffProfile::where('facility_id', $facilityId)
            ->whereNotNull('department')
            ->distinct()->pluck('department')->sort()->values();

        return view('portals.staff.hr.roster', compact('rosters', 'staff', 'shifts', 'departments'));
    }

    public function rosterStore(Request $request): RedirectResponse
    {
        $request->validate([
            'department'   => 'required|string',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $data = $request->except(['_token']);
        unset($data['facility_id']);

        $this->rosterService->createRoster(
            $this->facilityId(),
            $this->demoActorId(),
            $data
        );

        return redirect()->route('portals.staff.hr.roster')
            ->with('success', __('flash.roster_created'));
    }

    public function rosterPublish(string $id): RedirectResponse
    {
        $roster = $this->rosterAtFacility($id, $this->facilityId());

        try {
            $this->rosterService->publishRoster($roster->id);
            return redirect()->route('portals.staff.hr.roster')
                ->with('success', __('flash.roster_published'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.roster')
                ->with('error', $e->getMessage());
        }
    }

    public function rosterArchive(string $id): RedirectResponse
    {
        $roster = $this->rosterAtFacility($id, $this->facilityId());

        try {
            $this->rosterService->archiveRoster($roster->id);
            return redirect()->route('portals.staff.hr.roster')
                ->with('success', __('flash.roster_archived'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.roster')
                ->with('error', $e->getMessage());
        }
    }

    public function rosterAssign(Request $request, string $rosterId): RedirectResponse
    {
        $request->validate([
            'staff_profile_id' => 'required|uuid',
            'staff_shift_id'   => 'required|uuid',
            'work_date'        => 'required|date',
        ]);

        $facilityId = $this->facilityId();

        // All three sides of an assignment have to be ours: the roster it lands
        // in, the person being rostered, and the shift they are put on. Checking
        // only the roster would still let a neighbouring facility's nurse be
        // written into our duty book by id.
        $roster  = $this->rosterAtFacility($rosterId, $facilityId);
        $profile = $this->staffProfileAtFacility($request->input('staff_profile_id'), $facilityId);

        StaffShift::where('id', $request->input('staff_shift_id'))
            ->where('facility_id', $facilityId)
            ->firstOrFail();

        try {
            $data = $request->except(['_token']);
            $data['staff_profile_id'] = $profile->id;

            $this->rosterService->addAssignment(
                $roster->id,
                $this->demoActorId(),
                $data
            );
            return redirect()->route('portals.staff.hr.roster')
                ->with('success', __('flash.assignment_added'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.roster')
                ->with('error', $e->getMessage());
        }
    }

    public function rosterUnassign(string $assignmentId): RedirectResponse
    {
        $assignment = RosterAssignment::where('id', $this->assertUuid($assignmentId))
            ->whereHas('dutyRoster', fn ($q) => $q->where('facility_id', $this->facilityId()))
            ->firstOrFail();

        $this->rosterService->removeAssignment($assignment->id);

        return redirect()->route('portals.staff.hr.roster')
            ->with('success', __('flash.assignment_removed'));
    }

    // ── Leave ─────────────────────────────────────────────────

    public function leave(Request $request): View
    {
        $facilityId = $this->facilityId();
        $requests   = $this->leaveService->listLeaveRequests($facilityId, $request->only(['status', 'leave_type']));

        $staff = StaffProfile::where('facility_id', $facilityId)
            ->where('status', 'active')
            ->orderBy('last_name')->get();

        return view('portals.staff.hr.leave', compact('requests', 'staff'));
    }

    public function leaveStore(Request $request): RedirectResponse
    {
        $request->validate([
            'staff_profile_id' => 'required|uuid',
            'leave_type'       => 'required|string',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
        ]);

        // Leave is booked against a person, and the person has to be ours.
        $profile = $this->staffProfileAtFacility(
            $request->input('staff_profile_id'),
            $this->facilityId()
        );

        $this->leaveService->requestLeave(
            $profile->id,
            $request->except(['_token', 'staff_profile_id'])
        );

        return redirect()->route('portals.staff.hr.leave')
            ->with('success', __('flash.leave_request_submitted'));
    }

    public function leaveApprove(Request $request, string $id): RedirectResponse
    {
        $leave = $this->leaveRequestAtFacility($id, $this->facilityId());

        try {
            $this->leaveService->approveLeave($leave->id, $this->demoActorId(), $request->input('review_notes'));
            return redirect()->route('portals.staff.hr.leave')
                ->with('success', __('flash.leave_approved'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.leave')
                ->with('error', $e->getMessage());
        }
    }

    public function leaveReject(Request $request, string $id): RedirectResponse
    {
        $leave = $this->leaveRequestAtFacility($id, $this->facilityId());

        try {
            $this->leaveService->rejectLeave($leave->id, $this->demoActorId(), $request->input('review_notes'));
            return redirect()->route('portals.staff.hr.leave')
                ->with('success', __('flash.leave_rejected'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.leave')
                ->with('error', $e->getMessage());
        }
    }

    public function leaveWithdraw(string $id): RedirectResponse
    {
        $leave = $this->leaveRequestAtFacility($id, $this->facilityId());

        try {
            $this->leaveService->withdrawLeave($leave->id);
            return redirect()->route('portals.staff.hr.leave')
                ->with('success', __('flash.leave_request_withdrawn'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.hr.leave')
                ->with('error', $e->getMessage());
        }
    }
}
