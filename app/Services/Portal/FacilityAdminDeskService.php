<?php

namespace App\Services\Portal;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\FacilityJoinRequest;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use App\Services\Queue\PatientQueueService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * FacilityAdminDeskService — the numbers a facility administrator answers
 * for, and the two roster actions the team page never had.
 *
 * Every figure here is a count or a sum over rows that already carry THIS
 * facility's id. Nothing is estimated, projected or defaulted: a facility
 * with no wards gets no bed tile (null), not "0% occupancy"; a day with no
 * lab result gets a null turnaround, not zero minutes.
 *
 * The facility id is always a parameter. This class never reads the session
 * or the request, so the controller is the one place that decides whose
 * facility is being looked at (PortalContextService::facilityId()).
 */
class FacilityAdminDeskService
{
    public function __construct(private readonly PatientQueueService $queue) {}

    // ── Overview ─────────────────────────────────────────────────────────

    /**
     * @return array{
     *   appointments: array{today:int, checked_in:int, no_show:int, cancelled:int},
     *   queue: array{checked_in:int, waiting:int, active:int, seen:int, left:int, median_wait_seconds:int|null, longest_wait_seconds:int|null, median_service_seconds:int|null},
     *   lab: array{open:int, resulted_today:int, median_turnaround_seconds:int|null},
     *   beds: array{wards:int, beds:int, occupied:int, available:int, active_admissions:int, occupancy_pct:int|null}|null,
     *   billing: array{collected_today:float, outstanding:float, issued_today:int},
     *   team: array{active:int, open_invites:int, pending_joins:int}
     * }
     */
    public function overview(string $facilityId, CarbonInterface $day): array
    {
        return [
            'appointments' => $this->appointments($facilityId, $day),
            'queue'        => $this->queue->summaryFor($facilityId, $day),
            'lab'          => $this->lab($facilityId, $day),
            'beds'         => $this->beds($facilityId),
            'billing'      => $this->billing($facilityId, $day),
            'team'         => $this->team($facilityId),
        ];
    }

    private function appointments(string $facilityId, CarbonInterface $day): array
    {
        $row = DB::table('appointments')
            ->selectRaw(
                'COUNT(*) AS today, '
                . 'COUNT(*) FILTER (WHERE checked_in_at IS NOT NULL) AS checked_in, '
                . "COUNT(*) FILTER (WHERE status = 'no_show') AS no_show, "
                . "COUNT(*) FILTER (WHERE status = 'cancelled') AS cancelled"
            )
            ->where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->first();

        return [
            'today'      => (int) ($row->today ?? 0),
            'checked_in' => (int) ($row->checked_in ?? 0),
            'no_show'    => (int) ($row->no_show ?? 0),
            'cancelled'  => (int) ($row->cancelled ?? 0),
        ];
    }

    private function lab(string $facilityId, CarbonInterface $day): array
    {
        $open = LabOrder::query()
            ->where('facility_id', $facilityId)
            ->whereIn('status', LabOrder::OPEN_STATUSES)
            ->count();

        // Turnaround = ordered → resulted, median over the panels released
        // today. Postgres ordered-set aggregate; no MySQL date function.
        $row = DB::table('lab_orders')
            ->selectRaw(
                'COUNT(*) AS resulted_today, '
                . 'PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (resulted_at - ordered_at))) '
                . 'FILTER (WHERE ordered_at IS NOT NULL) AS median_turnaround_seconds'
            )
            ->where('facility_id', $facilityId)
            ->whereNotNull('resulted_at')
            ->whereBetween('resulted_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->first();

        $median = $row->median_turnaround_seconds ?? null;

        return [
            'open'                      => $open,
            'resulted_today'            => (int) ($row->resulted_today ?? 0),
            'median_turnaround_seconds' => $median === null ? null : (int) round((float) $median),
        ];
    }

    /**
     * Null when the facility has no wards at all: the ward desk has not been
     * built and no seeder writes these tables, so the tile stays off the page
     * rather than reporting "0 beds" as if that were a measurement.
     */
    private function beds(string $facilityId): ?array
    {
        $wards = Ward::query()->where('facility_id', $facilityId)->where('is_active', true)->count();

        if ($wards === 0) {
            return null;
        }

        $wardIds = Ward::query()->where('facility_id', $facilityId)->where('is_active', true)->pluck('id');

        $beds      = Bed::query()->whereIn('ward_id', $wardIds)->count();
        $occupied  = Bed::query()->whereIn('ward_id', $wardIds)->where('status', 'occupied')->count();
        $available = Bed::query()->whereIn('ward_id', $wardIds)->where('status', 'available')->count();

        $admissions = Admission::query()->where('facility_id', $facilityId)->where('status', 'active')->count();

        return [
            'wards'             => $wards,
            'beds'              => $beds,
            'occupied'          => $occupied,
            'available'         => $available,
            'active_admissions' => $admissions,
            'occupancy_pct'     => $beds > 0 ? (int) round($occupied * 100 / $beds) : null,
        ];
    }

    private function billing(string $facilityId, CarbonInterface $day): array
    {
        $today = [$day->copy()->startOfDay(), $day->copy()->endOfDay()];

        $collected = Invoice::query()
            ->where('facility_id', $facilityId)
            ->whereBetween('paid_at', $today)
            ->sum('paid_amount');

        $outstanding = Invoice::query()
            ->where('facility_id', $facilityId)
            ->whereNotIn('status', ['void', 'cancelled'])
            ->sum('balance_amount');

        $issued = Invoice::query()
            ->where('facility_id', $facilityId)
            ->whereBetween('created_at', $today)
            ->count();

        return [
            'collected_today' => (float) $collected,
            'outstanding'     => (float) $outstanding,
            'issued_today'    => $issued,
        ];
    }

    private function team(string $facilityId): array
    {
        return [
            'active' => FacilityRoleAssignment::query()->active()->where('facility_id', $facilityId)
                ->distinct('user_id')->count('user_id'),
            'open_invites' => FacilityStaffInvite::query()->where('facility_id', $facilityId)
                ->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count(),
            'pending_joins' => FacilityJoinRequest::query()->where('facility_id', $facilityId)->pending()->count(),
        ];
    }

    // ── Roster ───────────────────────────────────────────────────────────

    /**
     * A member of THIS facility's team, or null.
     *
     * Membership is what the team page shows: home facility here, or an
     * assignment here (active OR ended — an administrator must still be able
     * to open the page of someone they just deactivated, to reactivate them).
     * Anyone else is not a member and the caller answers 404, so the page
     * never confirms that a user id exists at another facility.
     */
    public function member(string $facilityId, string $userId): ?User
    {
        $user = User::query()->with('role')->whereKey($userId)->first();

        if (! $user) {
            return null;
        }

        $hasAssignment = FacilityRoleAssignment::query()
            ->where('facility_id', $facilityId)
            ->where('user_id', $user->id)
            ->exists();

        return ($user->primary_facility_id === $facilityId || $hasAssignment) ? $user : null;
    }

    /** The live posting here, if any. */
    public function activeAssignment(string $facilityId, string $userId): ?FacilityRoleAssignment
    {
        return FacilityRoleAssignment::query()
            ->with('role')
            ->active()
            ->where('facility_id', $facilityId)
            ->where('user_id', $userId)
            ->orderByDesc('assigned_at')
            ->first();
    }

    /** The role this person holds AT this facility (assignment first, account role as fallback). */
    public function roleNameAt(string $facilityId, User $user): ?string
    {
        return $this->activeAssignment($facilityId, $user->id)?->role?->name ?? $user->role?->name;
    }

    /**
     * May the acting administrator change this member's role or posting?
     *
     * Not themselves — the last administrator must not be able to lock the
     * facility out by mistake. And not anyone whose role here is outside the
     * invitable allow-list: that is the facility-administration tier, platform
     * roles and multi-facility roles, none of which this desk may hand out, so
     * none of which it may take away either.
     */
    public function manageable(string $facilityId, User $actor, User $member): bool
    {
        if ($actor->id === $member->id) {
            return false;
        }

        $role = $this->roleNameAt($facilityId, $member);

        return $role !== null && in_array($role, FacilityStaffInvite::INVITABLE_ROLES, true);
    }

    /**
     * Change the role held at this facility. Returns [before, after] role names.
     *
     * The posting row is replaced, not edited in place: the table is
     * UNIQUE(user_id, facility_id, role_id), so the old row is ended and the
     * new role's row is created or revived. When this is the person's home
     * facility their account role follows, exactly as approveJoinRequest does
     * for a self-registered clinician.
     */
    public function changeRole(string $facilityId, User $member, Role $newRole, string $actorId): array
    {
        return DB::transaction(function () use ($facilityId, $member, $newRole, $actorId) {
            $before = $this->roleNameAt($facilityId, $member);

            FacilityRoleAssignment::query()
                ->where('facility_id', $facilityId)
                ->where('user_id', $member->id)
                ->where('role_id', '!=', $newRole->id)
                ->update(['is_active' => false]);

            FacilityRoleAssignment::updateOrCreate(
                ['user_id' => $member->id, 'facility_id' => $facilityId, 'role_id' => $newRole->id],
                ['is_active' => true, 'expires_at' => null, 'assigned_by' => $actorId, 'assigned_at' => now()]
            );

            if ($member->primary_facility_id === $facilityId) {
                $member->role_id = $newRole->id;
                $member->save();
            }

            return [$before, $newRole->name];
        });
    }

    /**
     * End the posting here. Returns whether the ACCOUNT was suspended too.
     *
     * User::roleAtFacility() falls back to the account role when no active
     * assignment exists, so ending the posting alone would leave a home-
     * facility member able to sign in with the same access as before. When
     * this is their home facility and they hold no other live posting, the
     * account is suspended (login refuses `suspended`). A clinician who also
     * works elsewhere keeps their account: only this facility's door closes.
     */
    public function deactivate(string $facilityId, User $member): bool
    {
        return DB::transaction(function () use ($facilityId, $member) {
            FacilityRoleAssignment::query()
                ->where('facility_id', $facilityId)
                ->where('user_id', $member->id)
                ->update(['is_active' => false]);

            $elsewhere = FacilityRoleAssignment::query()
                ->active()
                ->where('user_id', $member->id)
                ->where('facility_id', '!=', $facilityId)
                ->exists();

            if ($member->primary_facility_id === $facilityId && ! $elsewhere) {
                $member->status = 'suspended';
                $member->save();

                return true;
            }

            return false;
        });
    }

    /**
     * Reopen the posting. The most recent ended row for this facility is
     * revived (so the person returns in the role they left with); a home-
     * facility account suspended by deactivate() is reactivated.
     */
    public function reactivate(string $facilityId, User $member, string $actorId): ?string
    {
        return DB::transaction(function () use ($facilityId, $member, $actorId) {
            $last = FacilityRoleAssignment::query()
                ->with('role')
                ->where('facility_id', $facilityId)
                ->where('user_id', $member->id)
                ->orderByDesc('assigned_at')
                ->first();

            $roleId = $last?->role_id ?? $member->role_id;

            if ($roleId === null) {
                return null;
            }

            FacilityRoleAssignment::updateOrCreate(
                ['user_id' => $member->id, 'facility_id' => $facilityId, 'role_id' => $roleId],
                ['is_active' => true, 'expires_at' => null, 'assigned_by' => $actorId, 'assigned_at' => now()]
            );

            if ($member->primary_facility_id === $facilityId && $member->status === 'suspended') {
                $member->status = 'active';
                $member->save();
            }

            return Role::query()->whereKey($roleId)->value('name');
        });
    }
}
