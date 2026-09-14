<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\FacilityJoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityJoinRequest;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Models\User;
use App\Services\Facility\StaffInviteService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
 *
 * Both rules apply verbatim to the second half of this controller, the
 * clinician-initiated JOIN REQUEST queue. A professional asks to join from
 * /professional/facilities and may type whatever they like into
 * `requested_role`; nothing here reads it. Approval takes the facility from the
 * session and the role from the same allow-list, so the requester's own opinion
 * of their seniority has no effect on what they are granted.
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

        // Someone can be on this team two ways: it is their home facility
        // (primary_facility_id), or they hold a live FacilityRoleAssignment here
        // — which is what an approved join request writes for a clinician who
        // already has a home facility elsewhere. Listing only the first would
        // have the team page quietly omit the people it just admitted.
        $assignedRoleIds = FacilityRoleAssignment::query()
            ->active()
            ->where('facility_id', $facilityId)
            ->pluck('role_id', 'user_id');

        $staff = User::query()
            ->with('role')
            ->where(fn ($q) => $q
                ->where('primary_facility_id', $facilityId)
                ->orWhereIn('id', $assignedRoleIds->keys()))
            ->orderBy('name')
            ->get();

        // The role someone holds AT THIS FACILITY, which is not necessarily the
        // role on their account when they also work somewhere else.
        $roleNames = Role::query()
            ->whereIn('id', $assignedRoleIds->values()->unique()->all())
            ->pluck('name', 'id');

        $facilityRoleFor = $assignedRoleIds
            ->map(fn ($roleId) => $roleNames[$roleId] ?? null)
            ->filter()
            ->all();

        $invites = FacilityStaffInvite::query()
            ->with(['role', 'inviter'])
            ->where('facility_id', $facilityId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $joinRequests = FacilityJoinRequest::query()
            ->with(['user.role', 'grantedRole', 'decider'])
            ->where('facility_id', $facilityId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('portals.facility.team', [
            'staff'           => $staff,
            'facilityRoleFor' => $facilityRoleFor,
            'invites'         => $invites,
            'joinRequests'    => $joinRequests,
            'pendingJoins'    => $joinRequests->filter->isPending()->count(),
            'roles'           => $this->invitableRoles(),
        ]);
    }

    // ── Mail ─────────────────────────────────────────────────────────────

    /**
     * Try to email an invitation, and report honestly on what happened.
     *
     * THE LINK IS THE CHANNEL OF RECORD; this is a convenience on top of it.
     * Production relays direct to MX from a VPS address with an empty relayhost,
     * and opescare.cloud publishes a nullMX so a bounce has nowhere to go — the
     * failure mode is therefore "silently filed as spam", not "error". The most
     * this method can ever truthfully report is that the local MTA accepted the
     * message for delivery, which is what the wording of team.invite_mail_*
     * says. It never claims the mail arrived.
     *
     * Never throws. A dead mail server must not cost the facility its invite,
     * so every failure is caught, logged at warning level, and turned into a
     * message telling the issuer to send the link themselves.
     *
     * @return array{ok: bool, message: string}
     */
    private function mailInvite(FacilityStaffInvite $invite, string $rawToken): array
    {
        // The mail itself lives in StaffInviteService so the lead-conversion
        // desk (LeadConversionService) sends the very same invitation.
        return app(StaffInviteService::class)->mail($invite, $rawToken, Auth::user()?->name);
    }

    /** Bilingual label for a role name, falling back to the raw name. */
    private function roleLabel(?string $roleName): string
    {
        return StaffInviteService::roleLabel($roleName);
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

        // Mail is attempted AFTER the invite and the audit row exist, and its
        // outcome is reported separately from the invite's. The link below is
        // returned either way — that is the mechanism; email is the courtesy.
        $mail = $this->mailInvite($invite, $rawToken);

        return redirect()
            ->route('portals.facility.team')
            ->with('success', __('team.invite_created'))
            ->with('mail_status', $mail['message'])
            ->with('mail_status_ok', $mail['ok'])
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

        $mail = $this->mailInvite($invite->fresh(), $rawToken);

        return redirect()
            ->route('portals.facility.team')
            ->with('success', __('team.invite_reissued'))
            ->with('mail_status', $mail['message'])
            ->with('mail_status_ok', $mail['ok'])
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

    // ── Join requests: the facility's decision ───────────────────────────

    /**
     * Approve a clinician's request to join this facility, in a role THIS
     * facility chooses.
     *
     * Three things about this method are load-bearing.
     *
     * 1. The facility comes from facilityId() — the session context — exactly as
     *    it does for invitations. A `facility_id` in the request body is not
     *    read, not validated, and not present in the form; the scoped
     *    `where('facility_id', $facilityId)->findOrFail($id)` means a request
     *    belonging to another facility is a 404 and not an approval.
     *
     * 2. The role comes from the approver's form field, validated against
     *    FacilityStaffInvite::INVITABLE_ROLES. The requester's `requested_role`
     *    is never consulted — it is free text shown to the reviewer. This is the
     *    whole reason the two live in different columns.
     *
     * 3. The write is idempotent under concurrency. The row is taken with
     *    lockForUpdate and re-checked for `pending` inside the transaction, so a
     *    double submit decides once; and the team membership is written with
     *    updateOrCreate, so even a lost race cannot produce two assignments or
     *    a unique-violation 500.
     */
    public function approveJoinRequest(Request $request, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            // Same allow-list, same rule shape, same reason as store().
            'role' => ['required', 'string', 'in:' . implode(',', FacilityStaffInvite::INVITABLE_ROLES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if (! $role) {
            return redirect()->route('portals.facility.team')->with('error', __('team.role_unknown'));
        }

        $joinRequest = FacilityJoinRequest::where('facility_id', $facilityId)->findOrFail($id);

        try {
            $outcome = DB::transaction(function () use ($joinRequest, $facilityId, $role, $validated) {
                /** @var FacilityJoinRequest|null $locked */
                $locked = FacilityJoinRequest::whereKey($joinRequest->id)->lockForUpdate()->first();

                if (! $locked || ! $locked->isPending()) {
                    return 'already_decided';
                }

                $user = User::with('role')->whereKey($locked->user_id)->lockForUpdate()->first();

                if (! $user) {
                    return 'user_missing';
                }

                // The membership itself. updateOrCreate because the table is
                // UNIQUE(user_id, facility_id, role_id) and a person who left and
                // came back must revive their row, not collide with it.
                FacilityRoleAssignment::updateOrCreate(
                    [
                        'user_id'     => $user->id,
                        'facility_id' => $facilityId,
                        'role_id'     => $role->id,
                    ],
                    [
                        'is_active'   => true,
                        'expires_at'  => null,
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                    ]
                );

                // Only an account with NO home facility, whose current role is
                // itself invitable, gets its home facility and account role
                // rewritten — that is the self-registered clinician this feature
                // exists for. Anyone else keeps both: a facility admin must not
                // be able to demote a platform account, or move a doctor's home
                // facility, by approving a request the doctor sent.
                if ($user->primary_facility_id === null
                    && in_array($user->role?->name, FacilityStaffInvite::INVITABLE_ROLES, true)) {
                    $user->primary_facility_id = $facilityId;
                    $user->role_id             = $role->id;
                    $user->save();
                }

                $locked->forceFill([
                    'status'          => FacilityJoinRequestStatus::Approved->value,
                    'granted_role_id' => $role->id,
                    'decided_by'      => Auth::id(),
                    'decided_at'      => now(),
                    'decision_note'   => $validated['note'] ?? null,
                ])->save();

                return 'approved';
            });
        } catch (\Throwable $e) {
            Log::error('facility_join_request_approval_failed', [
                'request_id' => $joinRequest->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('portals.facility.team')->with('error', __('team.join_action_failed'));
        }

        if ($outcome !== 'approved') {
            return redirect()->route('portals.facility.team')->with('error', __('team.join_already_decided'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'facility_join_request_approved',
            resourceType: 'FacilityJoinRequest',
            resourceId:   $joinRequest->id,
            extra: [
                'facility_id'  => $facilityId,
                'after_state'  => ['granted_role' => $role->name, 'user_id' => $joinRequest->user_id],
            ],
        );

        return redirect()->route('portals.facility.team')->with('success', __('team.join_approved', [
            'role' => $this->roleLabel($role->name),
        ]));
    }

    /**
     * Decline a request. Terminal: there is no transition back to pending, so a
     * declined request cannot quietly become an approved one later.
     */
    public function declineJoinRequest(Request $request, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $joinRequest = FacilityJoinRequest::where('facility_id', $facilityId)->findOrFail($id);

        try {
            $outcome = DB::transaction(function () use ($joinRequest, $validated) {
                /** @var FacilityJoinRequest|null $locked */
                $locked = FacilityJoinRequest::whereKey($joinRequest->id)->lockForUpdate()->first();

                if (! $locked || ! $locked->isPending()) {
                    return 'already_decided';
                }

                $locked->forceFill([
                    'status'        => FacilityJoinRequestStatus::Declined->value,
                    'decided_by'    => Auth::id(),
                    'decided_at'    => now(),
                    'decision_note' => $validated['note'] ?? null,
                ])->save();

                return 'declined';
            });
        } catch (\Throwable $e) {
            Log::error('facility_join_request_decline_failed', [
                'request_id' => $joinRequest->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('portals.facility.team')->with('error', __('team.join_action_failed'));
        }

        if ($outcome !== 'declined') {
            return redirect()->route('portals.facility.team')->with('error', __('team.join_already_decided'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'facility_join_request_declined',
            resourceType: 'FacilityJoinRequest',
            resourceId:   $joinRequest->id,
            extra: ['facility_id' => $facilityId],
        );

        return redirect()->route('portals.facility.team')->with('success', __('team.join_declined'));
    }
}
