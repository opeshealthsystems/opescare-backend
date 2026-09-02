<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Staff\Services\StaffService;
use App\Modules\Staff\Services\RosterService;
use App\Modules\Staff\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StaffController — Staff, HR & Shift Management API.
 *
 * Covers staff profiles, credentials, duty rosters, shift assignments,
 * leave requests, and professional license management.
 */
class StaffController extends Controller
{
    public function __construct(
        private StaffService  $staff,
        private RosterService $roster,
        private LeaveService  $leave
    ) {}

    // ── Staff Profiles ─────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        return response()->json(
            $this->staff->listStaff($facilityId, $request->all())
        );
    }

    public function show(string $staffId): JsonResponse
    {
        return response()->json($this->staff->getStaffProfile($staffId));
    }

    public function updateProfile(Request $request, string $staffId): JsonResponse
    {
        $validated = $request->validate([
            'first_name'        => ['nullable', 'string'],
            'last_name'         => ['nullable', 'string'],
            'email'             => ['nullable', 'email'],
            'phone'             => ['nullable', 'string'],
            'job_title'         => ['nullable', 'string'],
            'department'        => ['nullable', 'string'],
            'staff_category'    => ['nullable', 'string'],
            'employment_type'   => ['nullable', 'string'],
            'contract_end_date' => ['nullable', 'date'],
            'notes'             => ['nullable', 'string'],
        ]);

        return response()->json($this->staff->updateStaffProfile($staffId, $validated));
    }

    // ── Duty Roster ────────────────────────────────────────────────────────

    public function getRoster(Request $request): JsonResponse
    {
        $requestedFacilityId  = $request->input('facility_id');
        $authorizedFacilityId = $request->attributes->get('facility_id');

        // Enforce facility ownership: if the middleware has scoped this client to a
        // specific facility, the requested facility_id must match.
        if ($authorizedFacilityId && $requestedFacilityId && $requestedFacilityId !== $authorizedFacilityId) {
            return response()->json([
                'error'   => 'ACCESS_DENIED',
                'message' => __('api.roster_access_denied'),
            ], 403);
        }

        // Fall back to the authorized facility if none supplied
        $facilityId = $requestedFacilityId ?? $authorizedFacilityId;

        if (!$facilityId) {
            return response()->json([
                'error'   => 'validation_error',
                'message' => __('api.facility_id_required'),
            ], 422);
        }

        // Single roster requested → return it with its assignments
        if ($rosterId = $request->input('roster_id')) {
            return response()->json($this->roster->getRosterWithAssignments($rosterId));
        }

        return response()->json(
            $this->roster->listRosters($facilityId, [
                'department' => $request->input('department'),
                'status'     => $request->input('status'),
            ])
        );
    }

    public function assignShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'duty_roster_id'   => ['required', 'uuid'],
            'staff_profile_id' => ['required', 'uuid'],
            'staff_shift_id'   => ['required', 'uuid'],
            'work_date'        => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
        ]);

        $rosterId = $validated['duty_roster_id'];
        unset($validated['duty_roster_id']);

        return response()->json(
            $this->roster->addAssignment($rosterId, $request->user()->id, $validated),
            201
        );
    }

    public function removeShift(Request $request, string $shiftId): JsonResponse
    {
        $this->roster->removeAssignment($shiftId);
        return response()->json(['message' => __('api.shift_assignment_removed')]);
    }

    // ── Leave Requests ─────────────────────────────────────────────────────

    public function requestLeave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type' => ['required', 'in:annual,sick,maternity,paternity,compassionate,unpaid'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string'],
        ]);

        $profile = \App\Models\StaffProfile::where('user_id', $request->user()->id)->first();
        if (!$profile) {
            return response()->json([
                'error'   => 'not_found',
                'message' => __('api.no_staff_profile_linked'),
            ], 404);
        }

        return response()->json(
            $this->leave->requestLeave($profile->id, $validated),
            201
        );
    }

    public function approveLeave(Request $request, string $leaveId): JsonResponse
    {
        return response()->json(
            $this->leave->approveLeave($leaveId, $request->user()->id, $request->input('notes'))
        );
    }

    public function rejectLeave(Request $request, string $leaveId): JsonResponse
    {
        return response()->json(
            $this->leave->rejectLeave($leaveId, $request->user()->id, $request->input('reason'))
        );
    }
}
