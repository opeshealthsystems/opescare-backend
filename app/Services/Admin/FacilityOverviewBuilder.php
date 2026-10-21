<?php

namespace App\Services\Admin;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityReadinessScore;
use App\Models\MedicinePharmacyStock;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\User;
use App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService;
use App\Services\Billing\Xaf;
use App\Services\Subscriptions\SubscriptionPlanCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the platform admin's facility detail page shows, read from real
 * rows only. A section with nothing behind it comes back null / empty and the
 * view hides it: this page never invents a number.
 *
 * Every read is a fixed number of bounded queries (no per-row lookups), so the
 * page cost does not grow with the facility's staff, patients or invoices.
 */
class FacilityOverviewBuilder
{
    private const ACTIVITY_LIMIT = 8;

    public function __construct(private readonly FacilityReadinessScoringService $scoring) {}

    public function build(Facility $facility): array
    {
        $id      = (string) $facility->id;
        $listing = $this->listing($id);
        $staff   = $this->staff($id);

        return [
            'listing'      => $listing,
            'claims'       => $this->claims($id, $listing['id'] ?? null),
            'staff'        => $staff,
            'kpis'         => $this->kpis($id, $staff),
            'readiness'    => $this->readiness($id),
            'activity'     => $this->activity($id),
            'wards'        => $this->wards($id),
            'subscription' => $this->subscription($id),
        ];
    }

    /** The public directory listing joined to this facility (care_facilities.facility_id). */
    private function listing(string $id): ?array
    {
        $row = CareFacility::query()->where('facility_id', $id)->orderByDesc('updated_at')->first();
        if ($row === null) {
            return null;
        }

        // Live stock: only rows whose source is on the provenance allow-list.
        $liveStock = MedicinePharmacyStock::query()
            ->where('care_facility_id', $row->id)
            ->reportedByRealSource()
            ->count();

        return [
            'id'                  => (string) $row->id,
            'name'                => $row->facility_name,
            'listing_status'      => $row->listing_status,
            'verification_status' => $row->verification_status,
            'license_status'      => $row->license_status,
            'integration_status'  => $row->integration_status,
            'claimed'             => $row->claimed_by_user_id !== null,
            'claimed_at'          => $row->claimed_at,
            'region'              => $row->region,
            'city'                => $row->city,
            'address'             => $row->address,
            'phone'               => $row->phone_primary,
            'email'               => $row->email,
            'latitude'            => $row->latitude,
            'longitude'           => $row->longitude,
            'has_coordinates'     => $row->latitude !== null && $row->longitude !== null,
            'model'               => $row,
            'live_stock'          => $liveStock,
        ];
    }

    /** Claims for this facility or its listing, newest first (max 5). */
    private function claims(string $id, ?string $listingId): Collection
    {
        $claims = FacilityClaim::query()
            ->where(function ($q) use ($id, $listingId) {
                $q->where('facility_id', $id)->orWhere('created_facility_id', $id);
                if ($listingId !== null) {
                    $q->orWhere('care_facility_id', $listingId);
                }
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $reviewers = $this->userNames($claims->pluck('reviewed_by')->all());

        return $claims->map(fn (FacilityClaim $c) => [
            'status'         => $c->claim_status?->value,
            'status_label'   => $c->claim_status?->label(),
            'claimant'       => $c->claimant_name,
            'has_document'   => $c->hasDocument(),
            'phone_verified' => $c->isPhoneVerified(),
            'reviewer'       => $reviewers[(string) $c->reviewed_by] ?? null,
            'reviewed_at'    => $c->reviewed_at,
            'submitted_at'   => $c->submitted_at ?? $c->created_at,
        ]);
    }

    /**
     * Active staff grouped by role: primary-facility users plus live role
     * assignments, deduplicated. Pending invites per role. MFA counts only for
     * roles that must use a second factor.
     */
    private function staff(string $id): array
    {
        $members = "(select id as user_id, role_id from users where primary_facility_id = ?
                     union
                     select user_id, role_id from facility_role_assignments
                      where facility_id = ? and is_active = true and (expires_at is null or expires_at > now()))";
        $activeUser = "coalesce(u.status, 'active') not in ('suspended', 'inactive', 'deleted', 'disabled')";

        // One query: per-role rows plus a grand total row (grouping set ()).
        $grouped = DB::select(
            "select r.name as role, grouping(r.name) as is_total, count(distinct u.id) as active,
                    count(distinct u.id) filter (where u.two_factor_confirmed_at is not null) as mfa
               from {$members} s
               join users u on u.id = s.user_id
               left join roles r on r.id = s.role_id
              where {$activeUser}
              group by grouping sets ((r.name), ())",
            [$id, $id]
        );
        $byRole = array_filter($grouped, fn ($r) => (int) $r->is_total === 0);
        $totalRow = array_values(array_filter($grouped, fn ($r) => (int) $r->is_total === 1))[0] ?? null;
        $total = (int) ($totalRow->active ?? 0);

        $invites = DB::table('facility_staff_invites as i')
            ->leftJoin('roles as r', 'r.id', '=', 'i.role_id')
            ->where('i.facility_id', $id)
            ->whereNull('i.accepted_at')
            ->whereNull('i.revoked_at')
            ->where('i.expires_at', '>', now())
            ->groupBy('r.name')
            ->selectRaw('r.name as role, count(*) as n')
            ->pluck('n', 'role')
            ->map(fn ($n) => (int) $n);

        $mfaRoles = array_merge(User::ALWAYS_MFA_ROLES, (array) config('mfa.required_roles', []));

        $rows = [];
        $mfaRequired = 0;
        $mfaConfirmed = 0;
        foreach ($byRole as $r) {
            $needsMfa = in_array($r->role, $mfaRoles, true);
            if ($needsMfa) {
                $mfaRequired  += (int) $r->active;
                $mfaConfirmed += (int) $r->mfa;
            }
            $rows[(string) $r->role] = [
                'role'     => (string) $r->role,
                'active'   => (int) $r->active,
                'invited'  => $invites[(string) $r->role] ?? 0,
                'mfa'      => $needsMfa ? (int) $r->mfa : null,
            ];
        }
        // Roles with only invites outstanding still get a row.
        foreach ($invites as $role => $n) {
            if (! isset($rows[(string) $role])) {
                $rows[(string) $role] = ['role' => (string) $role, 'active' => 0, 'invited' => $n, 'mfa' => null];
            }
        }
        uasort($rows, fn ($a, $b) => $b['active'] <=> $a['active']);

        return [
            'rows'          => array_values($rows),
            'total'         => $total,
            'invites'       => (int) $invites->sum(),
            'mfa_required'  => $mfaRequired,
            'mfa_confirmed' => $mfaConfirmed,
        ];
    }

    private function kpis(string $id, array $staff): array
    {
        $since = now()->subDays(30);
        $now   = now();

        $activePatients = (int) (DB::selectOne(
            'select count(*) as n from (
                select patient_id from appointments where facility_id = ? and scheduled_at between ? and ? and patient_id is not null
                union
                select patient_id from encounters where facility_id = ? and coalesce(started_at, created_at) between ? and ? and patient_id is not null
             ) p',
            [$id, $since, $now, $id, $since, $now]
        )->n ?? 0);

        $appt = DB::table('appointments')
            ->where('facility_id', $id)
            ->whereBetween('scheduled_at', [$since, $now])
            ->selectRaw("count(*) as total, count(*) filter (where status = 'no_show' or no_show_at is not null) as no_show")
            ->first();
        $apptTotal = (int) ($appt->total ?? 0);

        $inv = DB::table('invoices')
            ->where('facility_id', $id)
            ->whereNull('voided_at')
            ->whereNotIn('status', ['draft', 'paid', 'void', 'voided', 'cancelled', 'refunded'])
            ->where('balance_amount', '>', 0)
            ->selectRaw('count(*) as n, coalesce(sum(balance_amount), 0) as due')
            ->first();

        return [
            'staff'            => $staff['total'],
            'invites'          => $staff['invites'],
            'active_patients'  => $activePatients,
            'registered'       => Patient::where('facility_id', $id)->count(),
            'appointments'     => $apptTotal,
            'no_show_rate'     => $apptTotal > 0 ? (int) round(((int) $appt->no_show / $apptTotal) * 100) : null,
            'open_invoices'    => (int) ($inv->n ?? 0),
            'balance_due'      => Xaf::format(Xaf::int($inv->due ?? 0)),
        ];
    }

    /** Read-only: the live checks plus the last saved snapshot. Never writes. */
    private function readiness(string $id): array
    {
        $eval   = $this->scoring->evaluate($id);
        $checks = $eval['checks'];

        return [
            'score'  => $eval['score'],
            'done'   => count(array_filter($checks)),
            'total'  => count($checks),
            'checks' => $checks,
            'latest' => FacilityReadinessScore::latestFor($id),
        ];
    }

    /** Last 8 events across the three facility-keyed audit trails. */
    private function activity(string $id): Collection
    {
        $events = collect();

        foreach (DB::table('audit_events')->where('facility_id', $id)->orderByDesc('created_at')
            ->limit(self::ACTIVITY_LIMIT)->get(['action_type', 'resource_type', 'actor_id', 'created_at']) as $e) {
            $events->push(['kind' => 'audit', 'action' => $e->action_type, 'detail' => $e->resource_type,
                'actor' => $e->actor_id, 'at' => $e->created_at]);
        }
        foreach (DB::table('facility_update_audits')->where('facility_id', $id)->orderByDesc('created_at')
            ->limit(self::ACTIVITY_LIMIT)->get(['field_changed', 'actor_id', 'created_at']) as $e) {
            $events->push(['kind' => 'update', 'action' => 'facility_updated', 'detail' => $e->field_changed,
                'actor' => $e->actor_id, 'at' => $e->created_at]);
        }
        foreach (DB::table('go_live_audits')->where('facility_id', $id)->orderByDesc('created_at')
            ->limit(self::ACTIVITY_LIMIT)->get(['action', 'performed_by', 'created_at']) as $e) {
            $events->push(['kind' => 'golive', 'action' => $e->action, 'detail' => null,
                'actor' => $e->performed_by, 'at' => $e->created_at]);
        }

        $events = $events->sortByDesc(fn ($e) => (string) $e['at'])->take(self::ACTIVITY_LIMIT)->values();
        $names  = $this->userNames($events->pluck('actor')->all());

        return $events->map(fn ($e) => $e + [
            'actor_name' => $names[(string) $e['actor']] ?? null,
            'when'       => $e['at'] ? \Illuminate\Support\Carbon::parse($e['at']) : null,
        ]);
    }

    private function wards(string $id): ?array
    {
        $row = DB::selectOne(
            "select count(distinct w.id) as wards, count(b.id) as beds,
                    count(b.id) filter (where b.status = 'occupied') as occupied
               from wards w left join beds b on b.ward_id = w.id
              where w.facility_id = ?",
            [$id]
        );
        if ((int) ($row->wards ?? 0) === 0) {
            return null;
        }

        return ['wards' => (int) $row->wards, 'beds' => (int) $row->beds, 'occupied' => (int) $row->occupied];
    }

    /** A facility's own plan: subscriber_type 'facility' + subscriber_id = facility id. */
    private function subscription(string $id): ?array
    {
        $sub = OrganizationSubscription::query()
            ->with('plan:id,name')
            ->where('subscriber_type', SubscriptionPlanCatalog::SUBSCRIBER_FACILITY)
            ->where('subscriber_id', $id)
            ->latest('created_at')
            ->first();

        if ($sub === null) {
            return null;
        }

        return [
            'plan'       => $sub->plan?->name,
            'status'     => $sub->status instanceof \BackedEnum ? $sub->status->value : $sub->status,
            'interval'   => $sub->interval instanceof \BackedEnum ? $sub->interval->value : $sub->interval,
            'period_end' => $sub->current_period_end,
        ];
    }

    /** @return array<string,string> id => name, one query. */
    private function userNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids),
            fn ($v) => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v))));
        if ($ids === []) {
            return [];
        }

        return User::query()->withoutGlobalScopes()->whereIn('id', $ids)->pluck('name', 'id')
            ->mapWithKeys(fn ($n, $k) => [(string) $k => $n])->all();
    }
}
