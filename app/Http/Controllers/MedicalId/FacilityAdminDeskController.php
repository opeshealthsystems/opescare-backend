<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Services\Portal\FacilityAdminDeskService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * FacilityAdminDeskController — the facility administrator's own desk.
 *
 * Two pages and three writes, all under /facility/team/… because that is the
 * one portal bucket EnsurePortalAccess admits the facility-administration
 * roles to (everything under /admin/* is platform god-mode). The gate is the
 * existing `capability:facility_team`; no capability was invented for this.
 *
 * THE FACILITY IS NEVER TAKEN FROM THE REQUEST. It comes from
 * PortalContextService::facilityId(); a member of another facility is a 404,
 * not a 403 that confirms the id exists. THE ROLE COMES FROM AN ALLOW-LIST:
 * FacilityStaffInvite::INVITABLE_ROLES, the same list the invite form uses,
 * so this desk can neither hand out nor take away an administration role.
 */
class FacilityAdminDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $ctx,
        private readonly FacilityAdminDeskService $desk,
    ) {}

    private function facilityId(): string
    {
        $facilityId = $this->ctx->facilityId();

        abort_if($facilityId === null, 403, 'No facility context for this account.');

        return $facilityId;
    }

    // ── Overview ─────────────────────────────────────────────────────────

    public function overview(): View
    {
        $facilityId = $this->facilityId();

        $facility = Facility::query()->whereKey($facilityId)->first()
            ?? Facility::withoutGlobalScopes()->whereKey($facilityId)->first();

        return view('portals.facility.admin.overview', [
            'facility' => $facility,
            'day'      => now(),
            'kpis'     => $this->desk->overview($facilityId, now()),
        ]);
    }

    // ── Member page ──────────────────────────────────────────────────────

    public function member(string $userId): View
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        $postings = FacilityRoleAssignment::query()
            ->with(['role', 'facility' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('user_id', $member->id)
            ->orderByDesc('is_active')
            ->orderByDesc('assigned_at')
            ->get();

        $credentials = $member->providerCredentials()
            ->orderByRaw('verified_at IS NULL DESC')
            ->orderBy('expiry_date')
            ->get();

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_profile_view',
            resourceType: 'User',
            resourceId:   $member->id,
        );

        return view('portals.facility.admin.member', [
            'member'      => $member,
            'facilityId'  => $facilityId,
            'roleHere'    => $this->desk->roleNameAt($facilityId, $member),
            'assignment'  => $this->desk->activeAssignment($facilityId, $member->id),
            'postings'    => $postings,
            'credentials' => $credentials,
            'profile'     => $member->staffProfile,
            'manageable'  => $this->desk->manageable($facilityId, Auth::user(), $member),
            'roles'       => Role::query()->whereIn('name', FacilityStaffInvite::INVITABLE_ROLES)->orderBy('name')->get(),
        ]);
    }

    // ── Writes ───────────────────────────────────────────────────────────

    public function changeRole(Request $request, string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', FacilityStaffInvite::INVITABLE_ROLES)],
        ]);

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        $role = Role::where('name', $validated['role'])->first();
        if (! $role) {
            return back()->with('error', __('team.role_unknown'));
        }

        [$before, $after] = $this->desk->changeRole($facilityId, $member, $role, Auth::id());

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_role_changed',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['role' => $before],
                'after_state'  => ['role' => $after],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __('facility_admin.role_changed', ['role' => $this->roleLabel($after)]));
    }

    public function deactivate(string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        if ($this->desk->activeAssignment($facilityId, $member->id) === null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.already_inactive'));
        }

        $suspended = $this->desk->deactivate($facilityId, $member);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_deactivated',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['posting' => 'active'],
                'after_state'  => ['posting' => 'ended', 'account_suspended' => $suspended],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __($suspended ? 'facility_admin.deactivated_and_suspended' : 'facility_admin.deactivated'));
    }

    public function reactivate(string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if ($this->desk->activeAssignment($facilityId, $member->id) !== null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.already_active'));
        }

        // manageable() reads the role the person LAST held here (the account
        // role is the fallback), so a deactivated administrator stays out of
        // reach of this desk exactly as an active one is.
        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        $role = $this->desk->reactivate($facilityId, $member, Auth::id());

        if ($role === null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('team.role_unknown'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_reactivated',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['posting' => 'ended'],
                'after_state'  => ['posting' => 'active', 'role' => $role],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __('facility_admin.reactivated', ['role' => $this->roleLabel($role)]));
    }

    private function roleLabel(?string $roleName): string
    {
        if ($roleName === null || $roleName === '') {
            return '';
        }

        $label = __('team.roles.' . $roleName);

        return is_string($label) && $label !== 'team.roles.' . $roleName ? $label : $roleName;
    }
}
