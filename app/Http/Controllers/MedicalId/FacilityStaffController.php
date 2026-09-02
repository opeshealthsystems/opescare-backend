<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * FacilityStaffController — a facility administrator's own team page.
 *
 * This is the missing half of staff onboarding. Until now the only account
 * creation path was POST /portals/admin/users, which is platform-tier
 * (RequirePlatformAdmin) and which never set primary_facility_id — so even when
 * a platform admin did create a doctor, that doctor hit RequireFacilityContext
 * and bounced to /select-facility with nothing to select.
 *
 * Two rules govern everything below.
 *
 * 1. THE FACILITY IS NEVER TAKEN FROM THE REQUEST. It comes from
 *    PortalContextService::facilityId(), i.e. the session context established
 *    by RequireFacilityContext, and every read and write is scoped to it. There
 *    is deliberately no facility_id form field to tamper with, and no
 *    "first facility in the table" fallback — a Facility::value('id') style
 *    default is how a clerk once published another hospital's data.
 *
 * 2. THE ROLE COMES FROM AN ALLOW-LIST. FacilityStaffInvite::INVITABLE_ROLES
 *    is the whole set a facility admin may issue. Platform, compliance,
 *    insurance, developer and facility-administration roles are simply not in
 *    it, so no request can name one.
 */
class FacilityStaffController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    /**
     * The facility this request acts on.
     *
     * Session-resolved, never request-resolved. A user with no facility context
     * (a platform-tier account that wandered in) gets 403 rather than a
     * silently-chosen facility.
     */
    private function facilityId(): string
    {
        $facilityId = $this->ctx->facilityId();

        abort_if($facilityId === null, 403, 'No facility context for this account.');

        return $facilityId;
    }

    /** The roles this portal is allowed to hand out, as Role records. */
    private function invitableRoles()
    {
        return Role::query()
            ->whereIn('name', FacilityStaffInvite::INVITABLE_ROLES)
            ->orderBy('name')
            ->get();
    }

    // ── Team page ────────────────────────────────────────────────────────

    public function index(): View
    {
        $facilityId = $this->facilityId();

        $staff = User::query()
            ->with('role')
            ->where('primary_facility_id', $facilityId)
            ->orderBy('name')
            ->get();

        $invites = FacilityStaffInvite::query()
            ->with(['role', 'inviter'])
            ->where('facility_id', $facilityId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('portals.facility.team', [
            'staff'   => $staff,
            'invites' => $invites,
            'roles'   => $this->invitableRoles(),
        ]);
    }

    // ── Issue an invite ──────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name'  => ['nullable', 'string', 'max:255'],
            // The allow-list is applied as a validation rule, so a role outside
            // it is rejected before any row is written. `exists` alone would
            // happily accept the id of the super_admin role.
            'role'  => ['required', 'string', 'in:' . implode(',', FacilityStaffInvite::INVITABLE_ROLES)],
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if (! $role) {
            return back()->withInput()->with('error', __('team.role_unknown'));
        }

        if (User::where('email', $validated['email'])->exists()) {
            return back()->withInput()->with('error', __('team.email_taken'));
        }

        $openInvite = FacilityStaffInvite::query()
            ->where('facility_id', $facilityId)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($openInvite) {
            return back()->withInput()->with('error', __('team.invite_already_open'));
        }

        $rawToken = FacilityStaffInvite::generateToken();

        $invite = FacilityStaffInvite::create([
            'facility_id' => $facilityId,
            'role_id'     => $role->id,
            'email'       => $validated['email'],
            'name'        => $validated['name'] ?? null,
            'token_hash'  => FacilityStaffInvite::hashToken($rawToken),
            'invited_by'  => Auth::id(),
            'expires_at'  => now()->addDays(FacilityStaffInvite::TTL_DAYS),
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_staff_invite_issued',
            resourceType: 'FacilityStaffInvite',
            resourceId:   $invite->id,
        );

        // No mail is sent: production has no SMTP host, and an invite that only
        // arrives by email is an invite that silently never arrives. The link
        // is handed straight back to the issuer instead.
        return redirect()
            ->route('portals.facility.team')
            ->with('success', __('team.invite_created'))
            ->with('invite_link', route('invite.accept', $rawToken))
            ->with('invite_link_for', $invite->email);
    }

    // ── Reissue ──────────────────────────────────────────────────────────

    /**
     * Rotate an invite's token and show the new link.
     *
     * The raw token is not recoverable — only its sha256 is stored — so
     * "show me that link again" has to mean "mint a fresh one and invalidate
     * the old". That is strictly better than keeping a replayable credential
     * in the database so it can be re-displayed.
     *
     * Reissuing also clears a revocation: it is the "I revoked that by mistake,
     * invite them again" action. The revoked link itself stays dead — its hash
     * has been overwritten — so nothing that was cancelled becomes usable again.
     * An ACCEPTED invite is never reissuable; that person already has an account.
     */
    public function reissue(string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $invite = FacilityStaffInvite::where('facility_id', $facilityId)->findOrFail($id);

        if ($invite->isAccepted()) {
            return redirect()->route('portals.facility.team')->with('error', __('team.invite_already_used'));
        }

        $rawToken = FacilityStaffInvite::generateToken();

        $invite->forceFill([
            'token_hash' => FacilityStaffInvite::hashToken($rawToken),
            'expires_at' => now()->addDays(FacilityStaffInvite::TTL_DAYS),
            'revoked_at' => null,
            'revoked_by' => null,
        ])->save();

        $this->ctx->auditPatientAccess(
            actionType:   'facility_staff_invite_reissued',
            resourceType: 'FacilityStaffInvite',
            resourceId:   $invite->id,
        );

        return redirect()
            ->route('portals.facility.team')
            ->with('success', __('team.invite_reissued'))
            ->with('invite_link', route('invite.accept', $rawToken))
            ->with('invite_link_for', $invite->email);
    }

    // ── Revoke ───────────────────────────────────────────────────────────

    public function revoke(string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        // Scoped find, not findOrFail-then-check: an invite belonging to another
        // facility must be a 404, not a 403 that confirms the id exists.
        $invite = FacilityStaffInvite::where('facility_id', $facilityId)->findOrFail($id);

        if ($invite->isAccepted()) {
            return redirect()->route('portals.facility.team')->with('error', __('team.invite_already_used'));
        }

        $invite->forceFill([
            'revoked_at' => now(),
            'revoked_by' => Auth::id(),
        ])->save();

        $this->ctx->auditPatientAccess(
            actionType:   'facility_staff_invite_revoked',
            resourceType: 'FacilityStaffInvite',
            resourceId:   $invite->id,
        );

        return redirect()->route('portals.facility.team')->with('success', __('team.invite_revoked'));
    }
}
