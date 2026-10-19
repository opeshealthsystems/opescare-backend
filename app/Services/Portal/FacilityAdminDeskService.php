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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Null when the facility has no wards at all: no seeder writes these
     * tables, so the tile stays off the page rather than reporting "0 beds"
     * as if that were a measurement. The figures are the SAME rows the ward
     * desk lists (wardBoard()), summed — one query, one truth.
     */
    private function beds(string $facilityId): ?array
    {
        $board  = $this->wardBoard($facilityId);
        $active = array_values(array_filter($board['wards'], fn (array $w) => $w['is_active']));

        if ($active === []) {
            return null;
        }

        $beds      = array_sum(array_column($active, 'beds'));
        $occupied  = array_sum(array_column($active, 'occupied'));
        $available = array_sum(array_column($active, 'available'));

        return [
            'wards'             => count($active),
            'beds'              => $beds,
            'occupied'          => $occupied,
            'available'         => $available,
            'active_admissions' => $board['active_admissions'],
            'occupancy_pct'     => $beds > 0 ? (int) round($occupied * 100 / $beds) : null,
        ];
    }

    // ── Wards & beds (H-5 writer) ────────────────────────────────────────

    /** Bed status the desk writes for "out of service" (schema: available | occupied | maintenance | reserved). */
    public const BED_OUT_OF_SERVICE = 'maintenance';
    public const BED_IN_SERVICE     = 'available';

    /**
     * Every ward of this facility with its bed counts.
     *
     * OCCUPANCY COMES FROM ADMISSIONS: a bed is occupied when an admission
     * of this facility sits on it with status `active` and no discharge, or
     * when the bed row itself is flagged `occupied` (the flag the admission
     * writer will set the day it exists). Until admissions are recorded the
     * occupied column is 0 and the page says why. Nothing is estimated.
     *
     * @return array{wards: list<array{id:string,name:string,ward_type:string,total_beds:int,is_active:bool,beds:int,occupied:int,available:int,out_of_service:int,occupancy_pct:int|null}>, active_admissions:int}
     */
    public function wardBoard(string $facilityId): array
    {
        $rows = DB::table('wards')
            ->leftJoin('beds', 'beds.ward_id', '=', 'wards.id')
            ->leftJoin('admissions', function ($join) use ($facilityId) {
                $join->on('admissions.bed_id', '=', 'beds.id')
                    ->where('admissions.facility_id', '=', $facilityId)
                    ->where('admissions.status', '=', 'active')
                    ->whereNull('admissions.discharged_at');
            })
            ->where('wards.facility_id', $facilityId)
            ->groupBy('wards.id', 'wards.name', 'wards.ward_type', 'wards.total_beds', 'wards.is_active', 'wards.created_at')
            ->orderBy('wards.created_at')
            ->selectRaw(
                'wards.id, wards.name, wards.ward_type, wards.total_beds, wards.is_active, '
                . 'COUNT(DISTINCT beds.id) AS beds, '
                . "COUNT(DISTINCT beds.id) FILTER (WHERE admissions.id IS NOT NULL OR beds.status = 'occupied') AS occupied, "
                . 'COUNT(DISTINCT beds.id) FILTER (WHERE beds.status = ?) AS out_of_service, '
                . 'COUNT(DISTINCT beds.id) FILTER (WHERE admissions.id IS NULL AND beds.status = ?) AS available',
                [self::BED_OUT_OF_SERVICE, self::BED_IN_SERVICE]
            )
            ->get();

        $wards = [];
        foreach ($rows as $r) {
            $beds    = (int) $r->beds;
            $wards[] = [
                'id'             => (string) $r->id,
                'name'           => (string) $r->name,
                'ward_type'      => (string) $r->ward_type,
                'total_beds'     => (int) $r->total_beds,
                'is_active'      => (bool) $r->is_active,
                'beds'           => $beds,
                'occupied'       => (int) $r->occupied,
                'available'      => (int) $r->available,
                'out_of_service' => (int) $r->out_of_service,
                'occupancy_pct'  => $beds > 0 ? (int) round((int) $r->occupied * 100 / $beds) : null,
            ];
        }

        return [
            'wards'             => $wards,
            'active_admissions' => Admission::query()->where('facility_id', $facilityId)
                ->where('status', 'active')->whereNull('discharged_at')->count(),
        ];
    }

    /** A ward of THIS facility or null — the caller answers 404, never confirming a foreign id. */
    public function ward(string $facilityId, string $wardId): ?Ward
    {
        if (! Str::isUuid($wardId)) {
            return null;
        }

        return Ward::query()->where('facility_id', $facilityId)->whereKey($wardId)->first();
    }

    /** Beds of one ward, in label order. */
    public function bedsOf(Ward $ward): Collection
    {
        return Bed::query()->where('ward_id', $ward->id)->orderBy('bed_number')->get();
    }

    public function createWard(string $facilityId, string $name, string $type, int $capacity): Ward
    {
        return Ward::create([
            'facility_id' => $facilityId,
            'name'        => $name,
            'ward_type'   => $type,
            'total_beds'  => $capacity,
            'is_active'   => true,
        ]);
    }

    /**
     * Add N beds labelled PREFIX-NN, numbering on past the labels already in
     * the ward so a second batch never collides with the first (UNIQUE
     * ward_id + bed_number). Capacity grows to fit. Returns the labels created.
     *
     * @return list<string>
     */
    public function addBeds(Ward $ward, int $count, string $prefix): array
    {
        return DB::transaction(function () use ($ward, $count, $prefix) {
            $existing = Bed::query()->where('ward_id', $ward->id)->pluck('bed_number')->flip();
            $labels   = [];
            $n        = 0;

            while (count($labels) < $count) {
                $n++;
                $label = sprintf('%s-%02d', $prefix, $n);
                if ($existing->has($label)) {
                    continue;
                }
                Bed::create(['ward_id' => $ward->id, 'bed_number' => $label, 'status' => self::BED_IN_SERVICE]);
                $labels[] = $label;
            }

            $now = $existing->count() + $count;
            if ($ward->total_beds < $now) {
                $ward->total_beds = $now;
                $ward->save();
            }

            return $labels;
        });
    }

    /**
     * Take a bed out of service or bring it back. Returns [before, after]
     * status, or null when the bed is occupied (an occupied bed is a clinical
     * fact this desk does not override) or already in the requested state.
     *
     * @return array{0:string,1:string}|null
     */
    public function setBedService(Bed $bed, bool $inService): ?array
    {
        $after = $inService ? self::BED_IN_SERVICE : self::BED_OUT_OF_SERVICE;

        if ($bed->status === $after || $bed->status === 'occupied') {
            return null;
        }

        $before      = $bed->status;
        $bed->status = $after;
        $bed->save();

        return [$before, $after];
    }

    // ── Reports (H-6) ────────────────────────────────────────────────────

    /** Longest range a report may span, in days (inclusive). */
    public const REPORT_MAX_DAYS = 366;

    public const REPORT_TABLES = ['appointments', 'queue', 'lab', 'billing', 'staff'];

    /**
     * Every report table for the range. Each row is already flat for the
     * page and the CSV: counts, medians and whole-franc sums only — no
     * patient, no name, no Health ID, no free text from any record.
     *
     * @return array{
     *   appointments: array{statuses:list<string>, days:list<array<string,int|string>>, total:int, no_show:int, no_show_rate_pct:int|null},
     *   queue: list<array{day:string,checked_in:int,seen:int,left:int,median_wait_seconds:int|null}>,
     *   lab: list<array{day:string,released:int,median_turnaround_seconds:int|null}>,
     *   billing: list<array{day:string,issued:int,issued_amount:int,collected:int,collected_amount:int}>,
     *   staff: list<array{role:string,headcount:int}>
     * }
     */
    public function reports(string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'appointments' => $this->appointmentsByDay($facilityId, $from, $to),
            'queue'        => $this->queueByDay($facilityId, $from, $to),
            'lab'          => $this->labByDay($facilityId, $from, $to),
            'billing'      => $this->billingByDay($facilityId, $from, $to),
            'staff'        => $this->staffByRole($facilityId),
        ];
    }

    /**
     * One report table as [headers, rows] for a CSV writer. Headers are
     * column keys (the page translates them); rows are scalars only.
     *
     * @return array{0: list<string>, 1: list<list<int|string|null>>}
     */
    public function reportTable(string $table, string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($table === 'appointments') {
            $a       = $this->appointmentsByDay($facilityId, $from, $to);
            $headers = array_merge(['day'], $a['statuses'], ['total']);
            $rows    = array_map(fn (array $d) => array_map(fn ($h) => $d[$h], $headers), $a['days']);

            return [$headers, $rows];
        }

        [$headers, $data] = match ($table) {
            'queue'   => [['day', 'checked_in', 'seen', 'left', 'median_wait_seconds'], $this->queueByDay($facilityId, $from, $to)],
            'lab'     => [['day', 'released', 'median_turnaround_seconds'], $this->labByDay($facilityId, $from, $to)],
            'billing' => [['day', 'issued', 'issued_amount', 'collected', 'collected_amount'], $this->billingByDay($facilityId, $from, $to)],
            'staff'   => [['role', 'headcount'], $this->staffByRole($facilityId)],
            default   => throw new \InvalidArgumentException("Unknown report table {$table}"),
        };

        $rows = array_map(fn (array $d) => array_map(fn ($h) => $d[$h] ?? '', $headers), $data);

        return [$headers, $rows];
    }

    private function range(CarbonInterface $from, CarbonInterface $to): array
    {
        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    private function appointmentsByDay(string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        // Postgres: TO_CHAR, never DATE_FORMAT. Statuses are whatever the
        // rows carry, in a fixed display order, then anything unexpected.
        $rows = DB::table('appointments')
            ->selectRaw("TO_CHAR(scheduled_at, 'YYYY-MM-DD') AS day, status, COUNT(*) AS n")
            ->where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', $this->range($from, $to))
            ->groupByRaw("TO_CHAR(scheduled_at, 'YYYY-MM-DD'), status")
            ->orderBy('day')
            ->get();

        $known    = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'no_show', 'cancelled'];
        $seen     = $rows->pluck('status')->unique()->values()->all();
        $statuses = array_values(array_unique(array_merge(array_intersect($known, $seen), array_diff($seen, $known))));

        $days = [];
        foreach ($rows as $r) {
            $days[$r->day] ??= array_merge(['day' => $r->day], array_fill_keys($statuses, 0), ['total' => 0]);
            $days[$r->day][$r->status] = (int) $r->n;
            $days[$r->day]['total']   += (int) $r->n;
        }

        $total  = array_sum(array_column($days, 'total'));
        $noShow = array_sum(array_column($days, 'no_show'));

        return [
            'statuses'         => $statuses,
            'days'             => array_values($days),
            'total'            => $total,
            'no_show'          => $noShow,
            'no_show_rate_pct' => $total > 0 ? (int) round($noShow * 100 / $total) : null,
        ];
    }

    /**
     * One PatientQueueService::summaryFor() per day: the only queue read this
     * class makes, and only counts and medians come back. Days with no
     * check-in are omitted (nothing to report, not "0 min").
     */
    private function queueByDay(string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        $out = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $s = $this->queue->summaryFor($facilityId, $day);
            if ($s['checked_in'] === 0) {
                continue;
            }
            $out[] = [
                'day'                 => $day->toDateString(),
                'checked_in'          => $s['checked_in'],
                'seen'                => $s['seen'],
                'left'                => $s['left'],
                'median_wait_seconds' => $s['median_wait_seconds'],
            ];
        }

        return $out;
    }

    private function labByDay(string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('lab_orders')
            ->selectRaw(
                "TO_CHAR(resulted_at, 'YYYY-MM-DD') AS day, COUNT(*) AS released, "
                . 'PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (resulted_at - ordered_at))) '
                . 'FILTER (WHERE ordered_at IS NOT NULL) AS median_turnaround_seconds'
            )
            ->where('facility_id', $facilityId)
            ->whereNotNull('resulted_at')
            ->whereBetween('resulted_at', $this->range($from, $to))
            ->groupByRaw("TO_CHAR(resulted_at, 'YYYY-MM-DD')")
            ->orderBy('day')
            ->get();

        return $rows->map(fn ($r) => [
            'day'                       => $r->day,
            'released'                  => (int) $r->released,
            'median_turnaround_seconds' => $r->median_turnaround_seconds === null ? null : (int) round((float) $r->median_turnaround_seconds),
        ])->all();
    }

    /** Whole francs (XAF exponent 0). Issued by created_at as the overview does; collected by paid_at. */
    private function billingByDay(string $facilityId, CarbonInterface $from, CarbonInterface $to): array
    {
        $issued = DB::table('invoices')
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') AS day, COUNT(*) AS n, COALESCE(SUM(patient_responsibility_amount), 0) AS amount")
            ->where('facility_id', $facilityId)
            ->whereBetween('created_at', $this->range($from, $to))
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD')")
            ->get()->keyBy('day');

        $collected = DB::table('invoices')
            ->selectRaw("TO_CHAR(paid_at, 'YYYY-MM-DD') AS day, COUNT(*) AS n, COALESCE(SUM(paid_amount), 0) AS amount")
            ->where('facility_id', $facilityId)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', $this->range($from, $to))
            ->groupByRaw("TO_CHAR(paid_at, 'YYYY-MM-DD')")
            ->get()->keyBy('day');

        $days = $issued->keys()->merge($collected->keys())->unique()->sort()->values();

        return $days->map(fn (string $day) => [
            'day'              => $day,
            'issued'           => (int) ($issued[$day]->n ?? 0),
            'issued_amount'    => (int) round((float) ($issued[$day]->amount ?? 0)),
            'collected'        => (int) ($collected[$day]->n ?? 0),
            'collected_amount' => (int) round((float) ($collected[$day]->amount ?? 0)),
        ])->all();
    }

    /** Active postings at this facility (same rule as FacilityRoleAssignment::active()), one person once per role. */
    private function staffByRole(string $facilityId): array
    {
        return DB::table('facility_role_assignments')
            ->join('roles', 'roles.id', '=', 'facility_role_assignments.role_id')
            ->where('facility_role_assignments.facility_id', $facilityId)
            ->where('facility_role_assignments.is_active', true)
            ->where(fn ($q) => $q->whereNull('facility_role_assignments.expires_at')->orWhere('facility_role_assignments.expires_at', '>', now()))
            ->groupBy('roles.name')
            ->orderBy('roles.name')
            ->selectRaw('roles.name AS role, COUNT(DISTINCT facility_role_assignments.user_id) AS headcount')
            ->get()
            ->map(fn ($r) => ['role' => (string) $r->role, 'headcount' => (int) $r->headcount])
            ->all();
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

    // ── Read-only invoices (H-4) ─────────────────────────────────────────

    /**
     * This facility's invoices, newest first, with the patient's name.
     *
     * READ ONLY. `capability:billing` gates the writes of /facility/billing in
     * the same group as its reads, so it was not widened to administrators;
     * this list is the administrator's view of the till and offers no action.
     * BillingDeskService exposes no read method, so the read is here, scoped
     * by `invoices.facility_id` exactly like the overview totals.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<Invoice>
     */
    public function invoices(string $facilityId, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Invoice::query()
            ->where('facility_id', $facilityId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /** One of this facility's invoices with its lines, or null (never another facility's). */
    public function invoice(string $facilityId, string $id): ?Invoice
    {
        return Invoice::query()
            ->where('facility_id', $facilityId)
            ->with('items')
            ->find($id);
    }

    /** @return array<string, \App\Models\Patient> keyed by patient id, demo scope lifted for ids already ours */
    public function patientsFor(iterable $invoices): array
    {
        $ids = collect($invoices)->pluck('patient_id')->filter()->unique()->values()->all();

        return $ids === []
            ? []
            : \App\Models\Patient::withoutGlobalScopes()->whereIn('id', $ids)->get()->keyBy('id')->all();
    }

    // ── Departments (H-5, read only) ─────────────────────────────────────

    /**
     * This facility's active staff grouped by `staff_profiles.department`.
     *
     * `department` is a FREE-TEXT string today — there is no Department model
     * and no departments table — so the grouping is by the exact string as
     * typed (trimmed). Nothing is normalised or merged: "Cardiology" and
     * "cardiology" are two rows, which is precisely the picture the
     * administrator needs before a schema decision is taken. A profile with
     * no department lands in the '' bucket, which the view labels unassigned.
     *
     * The role shown is the one held AT THIS FACILITY (active assignment),
     * falling back to the account role — the same rule as User::roleAtFacility().
     *
     * @return array<string, array{count:int, roles:array<string,int>, members:list<array{user_id:string,name:string,role:?string,job_title:?string}>}>
     */
    public function departments(string $facilityId): array
    {
        $profiles = \App\Models\StaffProfile::query()
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->get();

        // StaffProfile declares no user() relation; one query, keyed by id.
        $users = User::query()
            ->with('role')
            ->whereIn('id', $profiles->pluck('user_id')->unique()->all())
            ->get()
            ->keyBy(fn (User $u) => (string) $u->getKey());

        $rolesHere = FacilityRoleAssignment::query()
            ->active()
            ->where('facility_id', $facilityId)
            ->with('role')
            ->get()
            ->keyBy(fn (FacilityRoleAssignment $a) => (string) $a->user_id);

        $groups = [];

        foreach ($profiles as $profile) {
            $user = $users->get((string) $profile->user_id);
            if ($user === null) {
                continue;
            }
            $key  = trim((string) ($profile->department ?? ''));
            $role = $rolesHere->get((string) $profile->user_id)?->role ?? $user->role;
            $name = $role?->name;

            $groups[$key] ??= ['count' => 0, 'roles' => [], 'members' => []];
            $groups[$key]['count']++;
            if ($name !== null) {
                $groups[$key]['roles'][$name] = ($groups[$key]['roles'][$name] ?? 0) + 1;
            }
            $groups[$key]['members'][] = [
                'user_id'   => (string) $profile->user_id,
                'name'      => (string) $user->name,
                'role'      => $name,
                'job_title' => $profile->job_title,
            ];
        }

        // Named departments alphabetically, the unassigned bucket last.
        $unassigned = $groups[''] ?? null;
        unset($groups['']);
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        if ($unassigned !== null) {
            $groups[''] = $unassigned;
        }

        return $groups;
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
