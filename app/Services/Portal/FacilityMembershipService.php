<?php

namespace App\Services\Portal;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * FacilityMembershipService — "which facilities may this person act at?"
 *
 * A clinician who works at more than one facility (a visiting consultant, a
 * multi-facility doctor, a locum) has more than one POSTING. Until this
 * service existed, three different places each answered that question their
 * own way and disagreed:
 *
 *   • MobileProviderAuthController::resolveFacilityId() took `->value(...)` —
 *     literally the first row Postgres happened to return — and stamped it on
 *     a 30-day token with no way to change it.
 *   • RequireFacilityContext auto-resolved `users.primary_facility_id` and so
 *     never offered a multi-facility user the /select-facility screen.
 *   • PublicPageController::selectableFacilityIds() had the correct answer,
 *     but privately, on the web controller only.
 *
 * One resolver now, used by all of them, so a facility a clinician may act at
 * on the web is exactly the one they may act at on the phone.
 *
 * WHAT COUNTS AS A POSTING (in this order of authority):
 *   1. an ACTIVE `facility_role_assignments` row — the modelled membership,
 *      unique on (user_id, facility_id, role_id) with `is_active` + `expires_at`;
 *   2. an ACTIVE `staff_profiles` row — the HR record, which predates (1) and
 *      is still the only posting many seeded staff have;
 *   3. `users.primary_facility_id` — the single-facility default carried on
 *      the user row itself.
 *
 * A facility outside that set is not a facility this person works at, and
 * every switch path — web POST /select-facility and mobile
 * POST /api/mobile/provider/facility — refuses it server-side. That is what
 * lets the switch endpoint be the ONE deliberate exception to the platform
 * rule that facility_id never comes from a request body: the body names a
 * candidate, this service decides whether it is real.
 *
 * The per-user memo is STATIC rather than per-instance on purpose. Nothing
 * binds this class as a singleton (AppServiceProvider is not this feature's to
 * edit), so `app(FacilityMembershipService::class)` hands out a fresh object
 * every time — and the sidebar asks "is this user multi-facility?" on every
 * portal render, on top of the middleware that already asked. A static memo
 * makes that one set of queries per PHP process-request instead of one per
 * call site. It is dropped when the request ends; `forget()` clears it when a
 * posting changes mid-request.
 */
class FacilityMembershipService
{
    /** @var array<string, list<string>|null> user id → entitled facility ids (null = platform tier) */
    private static array $memo = [];

    /**
     * Which request the memo belongs to.
     *
     * A static cache that outlives its request is a stale-permissions bug
     * waiting for the day this runs under Octane (or any worker that keeps the
     * process alive between requests): a posting withdrawn at 09:00 would still
     * be honoured at 09:05 by whichever worker had already answered for that
     * user. Tying the memo to the bound Request object drops it whenever the
     * request does, which is exactly the lifetime it should have had.
     */
    private static ?string $memoRequest = null;

    /**
     * The facility ids this user genuinely holds a posting at.
     *
     * Returns NULL for platform-tier users (super admin, platform admin …),
     * meaning "unrestricted" — the same contract PublicPageController already
     * used, so the two cannot drift apart. Callers that need a concrete list
     * must handle null explicitly rather than treating it as "none".
     *
     * @return list<string>|null
     */
    public function facilityIdsFor(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if (\App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($user)) {
            return null;
        }

        $this->rotateMemoWithRequest();

        $key = (string) $user->getKey();

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        $ids = [];

        if ($user->primary_facility_id) {
            $ids[] = (string) $user->primary_facility_id;
        }

        foreach (
            FacilityRoleAssignment::query()
                ->where('user_id', $user->getKey())
                ->active()
                ->whereNotNull('facility_id')
                ->pluck('facility_id') as $id
        ) {
            $ids[] = (string) $id;
        }

        foreach (
            StaffProfile::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'active')
                ->whereNotNull('facility_id')
                ->pluck('facility_id') as $id
        ) {
            $ids[] = (string) $id;
        }

        // Guard the uuid columns: a tampered session value or a legacy row that
        // is not a uuid would otherwise blow up the whereIn() below with a
        // Postgres 22P02 cast error and surface as a 500.
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => Str::isUuid($id))));

        return self::$memo[$key] = $ids;
    }

    /**
     * May this user act at this facility? The single membership check every
     * switch path calls before it trusts an id that came in over the wire.
     */
    public function mayActAt(?User $user, ?string $facilityId): bool
    {
        if (! $user || ! $facilityId || ! Str::isUuid($facilityId)) {
            return false;
        }

        $allowed = $this->facilityIdsFor($user);

        // null = platform tier = unrestricted, but the facility still has to exist.
        if ($allowed === null) {
            return Facility::withoutGlobalScope('isolate_demo')->whereKey($facilityId)->exists();
        }

        return in_array($facilityId, $allowed, true);
    }

    /** Does this user have a genuine choice to make? Drives the "Switch facility" nav link. */
    public function hasChoice(?User $user): bool
    {
        $allowed = $this->facilityIdsFor($user);

        // A platform-tier user is not "multi-facility staff" — they have no
        // postings at all and their portals are unscoped, so they are never
        // offered the clinician switcher.
        return $allowed !== null && count($allowed) > 1;
    }

    /**
     * The postings themselves, as rows a UI (web selector or phone list) can
     * render: id, name, type, status, the role held THERE, and whether it is
     * the facility currently in use.
     *
     * The demo global scope is bypassed on purpose. Membership is already the
     * filter — a clinician is only ever shown facilities they are posted at —
     * and leaving the scope on is how a demo account ends up staring at an
     * empty switcher while holding two real postings.
     *
     * @return list<array{id:string,name:string,type:string,status:string,role:?string,role_label:?string,is_current:bool}>
     */
    public function facilitiesFor(?User $user, ?string $currentFacilityId = null): array
    {
        if (! $user) {
            return [];
        }

        $allowed = $this->facilityIdsFor($user);

        $query = Facility::withoutGlobalScope('isolate_demo')->orderBy('name');

        if ($allowed !== null) {
            if ($allowed === []) {
                return [];
            }
            $query->whereIn('id', $allowed);
        }

        // The role held at each facility, resolved in ONE query rather than by
        // calling roleAtFacility() per row. That matters here and nowhere else:
        // a platform-tier user gets the unrestricted list, and this method is
        // what renders /select-facility for them — a per-row lookup would be
        // one query per facility in the country.
        $rolesByFacility = FacilityRoleAssignment::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->whereNotNull('facility_id')
            ->with('role')
            ->get()
            ->keyBy(fn (FacilityRoleAssignment $a) => (string) $a->facility_id);

        return $query->get()->map(function (Facility $facility) use ($user, $currentFacilityId, $rolesByFacility): array {
            // The role recorded AT this facility; the account's global role is
            // the fallback, matching User::roleAtFacility().
            $role = $rolesByFacility->get((string) $facility->getKey())?->role ?? $user->role;

            return [
                'id'         => (string) $facility->getKey(),
                'name'       => (string) $facility->name,
                'type'       => (string) ($facility->type ?? ''),
                'status'     => $facility->status === 'suspended' ? 'suspended' : 'active',
                'role'       => $role?->name,
                'role_label' => $role?->description ?? $role?->name,
                'is_current' => $currentFacilityId !== null
                    && (string) $facility->getKey() === (string) $currentFacilityId,
            ];
        })->values()->all();
    }

    /**
     * The facility a session should DEFAULT to when nobody has chosen yet.
     *
     * Deterministic, which is the whole point: `primary_facility_id` wins when
     * it is a real posting, otherwise the alphabetically first facility the
     * person is posted at. What it is NOT is "whichever row the database
     * returned first" — that is the bug this service replaced, and it handed a
     * doctor a different facility depending on nothing at all.
     */
    public function defaultFacilityIdFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $allowed = $this->facilityIdsFor($user);

        if ($allowed === null || $allowed === []) {
            return $user->primary_facility_id ? (string) $user->primary_facility_id : null;
        }

        $primary = $user->primary_facility_id ? (string) $user->primary_facility_id : null;

        if ($primary !== null && in_array($primary, $allowed, true)) {
            return $primary;
        }

        return Facility::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $allowed)
            ->orderBy('name')
            ->value('id');
    }

    /** Forget the memo for one user — after a posting is added or withdrawn mid-request. */
    public function forget(?User $user): void
    {
        if ($user) {
            unset(self::$memo[(string) $user->getKey()]);
        }
    }

    /** Drop the memo when the request it was built for is no longer the current one. */
    private function rotateMemoWithRequest(): void
    {
        $current = app()->bound('request') ? spl_object_hash(app('request')) : 'no-request';

        if (self::$memoRequest !== $current) {
            self::$memo        = [];
            self::$memoRequest = $current;
        }
    }

    /**
     * Write the switch to the audit trail — who, from where, to where, when,
     * and over which channel.
     *
     * Every switch is audited, on both surfaces, through this one method, so
     * "did anyone change the facility they were acting as?" is a single query
     * on action_type rather than two half-answers in different shapes.
     *
     * `resource_id` is the facility being switched TO because that is the
     * subject of the event; the facility left behind rides in `before_state`,
     * which is what makes the pair readable. `facility_id` is also set to the
     * destination, so the row lands in the destination facility's own audit
     * view — that is the facility whose records the session is about to read.
     *
     * A failed audit write must never cost a clinician their session at the
     * bedside, so a broken audit table is swallowed and logged rather than
     * thrown; the switch itself has already been decided by then.
     */
    public function auditSwitch(
        User $user,
        ?string $fromFacilityId,
        string $toFacilityId,
        string $channel,
        ?string $ipAddress = null,
        ?string $deviceName = null
    ): void {
        try {
            \App\Models\AuditEvent::create([
                'actor_id'      => (string) $user->getKey(),
                'actor_role'    => $user->role?->name,
                'facility_id'   => $toFacilityId,
                'action_type'   => 'facility_context_switch',
                'resource_type' => 'facility',
                'resource_id'   => $toFacilityId,
                'source_system' => 'opescare_' . $channel,
                'ip_address'    => $ipAddress,
                'device_id'     => $deviceName,
                'reason'        => 'Clinician changed the facility they are acting as.',
                'before_state'  => ['facility_id' => $fromFacilityId],
                'after_state'   => ['facility_id' => $toFacilityId],
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('facility_switch_audit_failed', [
                'user_id'     => (string) $user->getKey(),
                'from'        => $fromFacilityId,
                'to'          => $toFacilityId,
                'channel'     => $channel,
                'exception'   => $e->getMessage(),
            ]);
        }
    }
}
