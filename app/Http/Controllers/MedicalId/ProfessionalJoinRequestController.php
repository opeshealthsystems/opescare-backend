<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\FacilityJoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityJoinRequest;
use App\Models\FacilityRoleAssignment;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The clinician's half of joining a facility.
 *
 * Until now the only route onto a facility's team was the facility inviting
 * you. A doctor who signed up at /signup/professional had no way to say "I work
 * at Hôpital Central de Yaoundé, please add me" and simply sat on
 * /professional/profile with no facility context forever.
 *
 * Lives at /professional/* and NOT behind facility.context for the same reason
 * ProfessionalProfileController does: the people who need this page are by
 * definition the people with no facility yet.
 *
 * THE REQUESTER DOES NOT CHOOSE THEIR ROLE. `requested_role` is free text and
 * is written to a column nothing reads when granting. What is granted comes
 * from FacilityStaffController::approveJoinRequest, from an allow-list, chosen
 * by the approving facility. The facility_id on THIS side does come from the
 * request — it has to, the requester is picking who to ask — but it only ever
 * creates a pending row asking for nothing. On the deciding side it comes from
 * the session and nowhere else.
 */
class ProfessionalJoinRequestController extends Controller
{
    /** Most facilities offered on one page of the picker. */
    public const FACILITY_PICKER_LIMIT = 50;

    public function __construct(private readonly PortalContextService $ctx) {}

    // ── The requester's page ─────────────────────────────────────────────

    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        // Patients, guardians and platform accounts have their own areas.
        if (! $user->isProfessional()) {
            return redirect('/');
        }

        $term = trim((string) $request->input('q', ''));

        $facilities = collect();
        if ($term !== '') {
            // PostgreSQL ILIKE, with the LIKE metacharacters escaped so a name
            // containing % or _ searches for itself rather than for everything.
            $facilities = Facility::query()
                ->select('id', 'name', 'type')
                ->where('name', 'ilike', '%' . addcslashes($term, '%_\\') . '%')
                ->orderBy('name')
                ->limit(self::FACILITY_PICKER_LIMIT)
                ->get();
        }

        $requests = FacilityJoinRequest::query()
            ->with(['facility', 'grantedRole'])
            ->where('user_id', $user->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('portals.professional.facilities', [
            'user'          => $user,
            'facilities'    => $facilities,
            'query'         => $term,
            'requests'      => $requests,
            'pickerLimit'   => self::FACILITY_PICKER_LIMIT,
            'currentTeams'  => FacilityRoleAssignment::query()
                ->active()
                ->with(['facility', 'role'])
                ->where('user_id', $user->id)
                ->get(),
        ]);
    }

    // ── Ask to join ──────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessional(), 403);

        $validated = $request->validate([
            'facility_id'    => ['required', 'uuid'],
            // A HINT for the reviewer, capped and free-form on purpose. It is
            // never matched against roles, and the approving admin picks the
            // real role from the allow-list regardless of what is typed here.
            'requested_role' => ['nullable', 'string', 'max:' . FacilityJoinRequest::MAX_REQUESTED_ROLE_LENGTH],
            'message'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Resolved through the model, so the demo-isolation global scope applies
        // — `exists:facilities,id` would reach past it into the other dataset.
        $facility = Facility::query()->whereKey($validated['facility_id'])->first();

        if (! $facility) {
            return back()->withInput()->with('error', __('team.join_facility_unknown'));
        }

        // Already on this team? Then there is nothing to ask for.
        $alreadyOnTeam = FacilityRoleAssignment::query()
            ->active()
            ->where('user_id', $user->id)
            ->where('facility_id', $facility->id)
            ->exists() || $user->primary_facility_id === $facility->id;

        if ($alreadyOnTeam) {
            return back()->withInput()->with('error', __('team.join_already_member'));
        }

        $existing = FacilityJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('facility_id', $facility->id)
            ->orderByDesc('created_at')
            ->first();

        if ($existing && $existing->isPending()) {
            return back()->withInput()->with('error', __('team.join_already_pending'));
        }

        // A decline that can be resubmitted a second later is not a decline.
        // The cooling-off period is the requester's only limit; the facility can
        // always short-circuit it by issuing an invitation.
        $cooldown = FacilityJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('facility_id', $facility->id)
            ->where('status', FacilityJoinRequestStatus::Declined->value)
            ->orderByDesc('decided_at')
            ->first();

        if ($cooldown && $cooldown->isInDeclineCooldown()) {
            return back()->withInput()->with('error', __('team.join_cooldown', [
                'date' => $cooldown->cooldownEndsAt()?->isoFormat('LL') ?? '',
            ]));
        }

        $joinRequest = FacilityJoinRequest::create([
            'facility_id'    => $facility->id,
            'user_id'        => $user->id,
            'requested_role' => $validated['requested_role'] ?? null,
            'message'        => $validated['message'] ?? null,
            'status'         => FacilityJoinRequestStatus::Pending->value,
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_join_request_created',
            resourceType: 'FacilityJoinRequest',
            resourceId:   $joinRequest->id,
            extra:        ['facility_id' => $facility->id],
        );

        return redirect()->route('professional.join')->with('success', __('team.join_submitted', [
            'facility' => $facility->name,
        ]));
    }

    // ── Withdraw ─────────────────────────────────────────────────────────

    /**
     * Withdraw one's own pending request. Scoped to the requester, so this is
     * not a way to cancel somebody else's; terminal, so a withdrawn request
     * cannot be reopened — asking again means a new row.
     */
    public function withdraw(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessional(), 403);

        $joinRequest = FacilityJoinRequest::where('user_id', $user->id)->findOrFail($id);

        if (! $joinRequest->isPending()) {
            return redirect()->route('professional.join')->with('error', __('team.join_already_decided'));
        }

        $joinRequest->forceFill([
            'status'     => FacilityJoinRequestStatus::Withdrawn->value,
            'decided_by' => $user->id,
            'decided_at' => now(),
        ])->save();

        $this->ctx->auditPatientAccess(
            actionType:   'facility_join_request_withdrawn',
            resourceType: 'FacilityJoinRequest',
            resourceId:   $joinRequest->id,
            extra:        ['facility_id' => $joinRequest->facility_id],
        );

        return redirect()->route('professional.join')->with('success', __('team.join_withdrawn'));
    }
}
