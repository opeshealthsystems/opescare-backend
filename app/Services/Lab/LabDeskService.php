<?php

namespace App\Services\Lab;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The laboratory's side of a lab order — the LAB DESK.
 *
 * A lab account does one job: work the facility's orders (collect the sample,
 * enter the results, release them) and answer the patient's test requests.
 * Every rule that job has to obey lives here, not in the controllers, so the
 * clinician app, a future web bench and the console all get the same answers.
 *
 *  1. SCOPE IS THE TOKEN'S FACILITY. Every read and write is narrowed to
 *     `lab_orders.facility_id` / `lab_test_requests.facility_id` = the
 *     facility stamped on the provider token by AuthenticateMobileProvider.
 *     Callers pass that id in; nothing here reads a request. An order at
 *     another facility is "not found", never "forbidden" — the two answers
 *     are deliberately the same so existence is not disclosed.
 *
 *  2. RESULTS ARE APPEND-ONLY. A LabResult row is never updated by this
 *     service. A correction is a NEW row for the same analyte carrying a note
 *     that says so; the results inbox re-raises the clinician's acknowledgement
 *     the moment the order's content signature moves, which is exactly what a
 *     corrected value should do.
 *
 *  3. RELEASE CANNOT BE UNDONE. `resulted` is the end of the bench's road; a
 *     second release is refused with 409 already_released. Retraction (void /
 *     entered-in-error) is a different clinical act and is not offered here.
 *
 *  4. FLAGS ARE THE CONTRACT'S FOUR — H | L | N | C. `C` is STORED as
 *     `CRITICAL`, because that is the one spelling both readers of this table
 *     already treat as critical: the doctor's inbox (CRITICAL / PANIC / HH /
 *     LL) and the patient's results (c / crit / critical / hh / ll). Storing a
 *     bare `C` would have shown the patient a critical and the clinician an
 *     unclassified "info" row. LabResult::deskFlag() maps it back on the way
 *     out.
 *
 *  5. NOTHING IS INVENTED. A reference interval is filled in only from a
 *     published LabReferenceRange row that matches the analyte (by LOINC code
 *     or by name) and, when known, the patient's sex and age. Where none
 *     exists the field stays null and the app prints "Not provided".
 *
 * Refusals are thrown as HttpResponseException carrying the JSON the app
 * expects ({message, error_code}), so a controller cannot forget to translate
 * one into the right status code.
 */
class LabDeskService
{
    /** The contract's flag vocabulary. */
    public const FLAGS = ['H', 'L', 'N', 'C'];

    /** How the contract's `C` is stored — see rule 4 above. */
    public const STORED_CRITICAL = 'CRITICAL';

    /** Request statuses the desk may list. */
    public const REQUEST_STATUSES = ['submitted', 'approved', 'declined', 'cancelled', 'all'];

    /** The stored spellings each urgency filter matches. */
    private const URGENCY_SYNONYMS = [
        'stat'    => ['stat', 'immediate', 'emergency'],
        'urgent'  => ['urgent', 'priority'],
        'routine' => ['routine', 'normal', ''],
    ];

    /** Worklist ordering: stat, then urgent, then routine. */
    private const URGENCY_RANK_SQL = "case when lower(coalesce(lab_orders.urgency, '')) in ('stat','immediate','emergency') then 0 "
        . "when lower(coalesce(lab_orders.urgency, '')) in ('urgent','priority') then 1 else 2 end";

    /** How many rows one list answers with. */
    private const MAX_ROWS = 500;

    /** Memoised once per process: does lab_orders carry verified_by yet? */
    private static ?bool $ordersHaveVerifiedBy = null;

    // ──────────────────────────────────────────────────────────────────
    // Facility & home
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility the token names. Read past the demo partition on purpose:
     * the id came from the token, not from the caller, and a null name on the
     * home screen would only hide where the bench is standing.
     */
    public function facility(string $facilityId): ?Facility
    {
        return Facility::query()->withoutGlobalScope('isolate_demo')->find($facilityId);
    }

    /**
     * The home screen's figures for one facility.
     *
     * @return array{facility: array{id: string, name: string|null}, counts: array<string, int>}
     */
    public function home(string $facilityId): array
    {
        $facility = $this->facility($facilityId);

        $count = fn (array $statuses): int => $this->statusIn(
            LabOrder::query()->where('facility_id', $facilityId),
            $statuses
        )->count();

        $urgent = $this->statusIn(LabOrder::query()->where('facility_id', $facilityId), LabOrder::OPEN_STATUSES)
            ->whereIn(DB::raw('lower(lab_orders.urgency)'), [...self::URGENCY_SYNONYMS['stat'], ...self::URGENCY_SYNONYMS['urgent']])
            ->count();

        $resultedToday = $this->statusIn(LabOrder::query()->where('facility_id', $facilityId), LabOrder::RELEASED_STATUSES)
            ->where('resulted_at', '>=', now()->startOfDay())
            ->count();

        $requestsPending = LabTestRequest::query()
            ->where('facility_id', $facilityId)
            ->where('status', 'submitted')
            ->count();

        return [
            'facility' => [
                'id'   => $facilityId,
                'name' => $facility?->name,
            ],
            'counts' => [
                'awaiting_collection' => $count(LabOrder::AWAITING_COLLECTION_STATUSES),
                'awaiting_results'    => $count(LabOrder::COLLECTED_STATUSES),
                'in_progress'         => $count(LabOrder::IN_PROGRESS_STATUSES),
                'resulted_today'      => $resultedToday,
                'urgent'              => $urgent,
                'requests_pending'    => $requestsPending,
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Orders — reads
    // ──────────────────────────────────────────────────────────────────

    /**
     * The worklist.
     *
     * No status = everything still open (ordered, collected, in progress),
     * most urgent first and oldest first within an urgency, because the
     * order that has waited longest is the one the bench should pick up.
     * `resulted` lists the released orders newest first.
     *
     * @return EloquentCollection<int, LabOrder>
     */
    public function orders(string $facilityId, ?string $status = null, ?string $urgency = null): EloquentCollection
    {
        $query = $this->baseQuery($facilityId);

        $this->statusIn($query, match ($status) {
            'ordered'     => LabOrder::AWAITING_COLLECTION_STATUSES,
            'collected'   => LabOrder::COLLECTED_STATUSES,
            'in_progress' => LabOrder::IN_PROGRESS_STATUSES,
            'resulted'    => LabOrder::RELEASED_STATUSES,
            default       => LabOrder::OPEN_STATUSES,
        });

        if ($urgency !== null && isset(self::URGENCY_SYNONYMS[$urgency])) {
            $query->whereIn(DB::raw("lower(coalesce(lab_orders.urgency, ''))"), self::URGENCY_SYNONYMS[$urgency]);
        }

        if ($status === 'resulted') {
            $query->orderByDesc('resulted_at')->orderByDesc('ordered_at');
        } else {
            $query->orderByRaw(self::URGENCY_RANK_SQL)->orderBy('ordered_at');
        }

        return $query->limit(self::MAX_ROWS)->get();
    }

    /**
     * One order inside the facility's scope, or null. A malformed id is null
     * too — lab_orders.id is a uuid column and Postgres would otherwise raise
     * 22P02, which surfaces as a 500 instead of a 404.
     */
    public function findOrder(string $facilityId, string $id): ?LabOrder
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return $this->baseQuery($facilityId)->find($id);
    }

    /** The order with everything the detail screen shows, freshly loaded. */
    public function detail(LabOrder $order): LabOrder
    {
        return $order->refresh()->load([
            'patient',
            'orderedBy',
            'verifiedBy',
            'results' => fn ($q) => $q->orderBy('resulted_at')->orderBy('parameter_name'),
        ])->loadCount('results');
    }

    /**
     * Published intervals for the order's test and every analyte already
     * entered on it, for this patient's sex and age where known.
     *
     * @return Collection<int, array{parameter_name: string, low: float|null, high: float|null, unit: string|null, loinc_code: string|null}>
     */
    public function referenceRanges(LabOrder $order): Collection
    {
        $results = $order->relationLoaded('results') ? $order->results : $order->results()->get();

        $names = $results->pluck('parameter_name')->push($order->test_name);
        $codes = $results->pluck('loinc_code')->push($order->test_code);

        return $this->lookupRanges($names, $codes, $order->patient);
    }

    // ──────────────────────────────────────────────────────────────────
    // Orders — writes
    // ──────────────────────────────────────────────────────────────────

    /**
     * The sample is in. ordered → collected, once.
     */
    public function collect(LabOrder $order, string $userId): LabOrder
    {
        $this->refuseIfClosed($order);

        if ($order->isReleased() || $order->deskStatus() !== 'ordered') {
            $this->refuse('already_collected', 409, __('lab_api.already_collected'));
        }

        $order->forceFill([
            'status'       => 'collected',
            'collected_at' => now(),
        ])->save();

        return $this->detail($order);
    }

    /**
     * Enter results. Every row is a NEW LabResult — nothing is updated. The
     * order moves to in_progress unless it is already released, in which case
     * the new rows are corrections and the released status stands.
     *
     * @param  list<array<string, mixed>>  $rows  validated by the controller
     */
    public function addResults(LabOrder $order, array $rows, string $userId): LabOrder
    {
        $this->refuseIfClosed($order);

        $ranges = $this->lookupRanges(
            collect($rows)->pluck('parameter_name'),
            collect($rows)->pluck('loinc_code'),
            $order->patient
        );

        $byName = $ranges->keyBy(fn (array $r) => mb_strtolower(trim($r['parameter_name'])));
        $byCode = $ranges->filter(fn (array $r) => filled($r['loinc_code']))
            ->keyBy(fn (array $r) => strtoupper(trim((string) $r['loinc_code'])));

        DB::transaction(function () use ($order, $rows, $userId, $byName, $byCode) {
            /** @var LabOrder $locked */
            $locked = LabOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $now    = now();

            foreach ($rows as $row) {
                $name  = trim((string) $row['parameter_name']);
                $code  = filled($row['loinc_code'] ?? null) ? strtoupper(trim((string) $row['loinc_code'])) : null;
                $range = $byName->get(mb_strtolower($name)) ?? ($code !== null ? $byCode->get($code) : null);

                // No model events here: the family listener would tell every
                // guardian "a new result is available" per analyte typed. They
                // hear once, on release (notifyReleased).
                LabResult::withoutEvents(fn () => LabResult::create([
                    'lab_order_id'    => $locked->id,
                    'patient_id'      => $locked->patient_id,
                    'parameter_name'  => $name,
                    'value'           => trim((string) $row['value']),
                    'unit'            => $this->blankToNull($row['unit'] ?? null) ?? ($range['unit'] ?? null),
                    'reference_range' => $this->blankToNull($row['reference_range'] ?? null) ?? $this->formatRange($range),
                    'flag'            => $this->storedFlag($row['flag'] ?? null),
                    'notes'           => $this->blankToNull($row['notes'] ?? null),
                    'verified_by'     => $userId,
                    'resulted_at'     => $now,
                    'loinc_code'      => $code ?? ($range['loinc_code'] ?? null),
                    'loinc_display'   => null,
                    'is_demo'         => (bool) $locked->is_demo,
                ]));
            }

            if (! $locked->isReleased()) {
                $locked->forceFill(['status' => 'in_progress'])->save();
            }
        });

        return $this->detail($order);
    }

    /**
     * Release the panel to the patient and the ordering clinician. Final.
     */
    public function release(LabOrder $order, string $userId): LabOrder
    {
        $this->refuseIfClosed($order);

        if ($order->isReleased()) {
            $this->refuse('already_released', 409, __('lab_api.already_released'));
        }

        DB::transaction(function () use ($order, $userId) {
            /** @var LabOrder $locked */
            $locked = LabOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Two taps racing: the second one serialises here and finds the
            // first one's release already on the row.
            if ($locked->isReleased()) {
                $this->refuse('already_released', 409, __('lab_api.already_released'));
            }

            if (! LabResult::query()->where('lab_order_id', $locked->id)->exists()) {
                $this->refuse('no_results', 422, __('lab_api.no_results'));
            }

            $fields = ['status' => 'resulted', 'resulted_at' => now()];

            if ($this->ordersHaveVerifiedBy()) {
                $fields['verified_by'] = $userId;
            }

            $locked->forceFill($fields)->save();

            // Who signed the panel off. Only rows nobody has verified yet — a
            // verifier already recorded on an analyte is not overwritten.
            LabResult::query()
                ->where('lab_order_id', $locked->id)
                ->whereNull('verified_by')
                ->update(['verified_by' => $userId]);
        });

        $released = $this->detail($order);

        $this->notifyReleased($released, $userId);

        // Partner webhooks (lab_result.released) — queued, signed, never fatal.
        \App\Modules\Connect\Services\WebhookEventPublisher::labResultReleased($released);

        return $released;
    }

    // ──────────────────────────────────────────────────────────────────
    // Patient test requests
    // ──────────────────────────────────────────────────────────────────

    /**
     * Requests the patients of this facility have submitted to it.
     *
     * @return EloquentCollection<int, LabTestRequest>
     */
    public function requests(string $facilityId, string $status = 'submitted'): EloquentCollection
    {
        $query = LabTestRequest::query()
            ->where('facility_id', $facilityId)
            ->with(['patient', 'reviewer']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query
            ->orderByRaw("case when lower(priority) = 'stat' then 0 when lower(priority) = 'urgent' then 1 else 2 end")
            ->orderBy('submitted_at')
            ->limit(self::MAX_ROWS)
            ->get();
    }

    public function findRequest(string $facilityId, string $id): ?LabTestRequest
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return LabTestRequest::query()
            ->where('facility_id', $facilityId)
            ->with(['patient', 'reviewer'])
            ->find($id);
    }

    /**
     * Approve: the request becomes a real LabOrder at THIS facility, ordered by
     * the reviewer, at the urgency the patient asked for. The request records
     * the decision and the order it produced. The patient is told.
     */
    public function approveRequest(LabTestRequest $request, string $facilityId, string $userId): LabTestRequest
    {
        $this->refuseIfDecided($request);

        DB::transaction(function () use ($request, $facilityId, $userId) {
            /** @var LabTestRequest $locked */
            $locked = LabTestRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            $this->refuseIfDecided($locked);

            $order = LabOrder::create([
                'patient_id'          => $locked->patient_id,
                'facility_id'         => $facilityId,
                'visit_id'            => null,
                'ordered_by'          => $userId,
                'test_name'           => $locked->test_name,
                'test_code'           => $locked->test_code,
                'urgency'             => $this->urgencyFromPriority($locked->priority),
                'status'              => 'ordered',
                'clinical_indication' => $locked->clinical_notes,
                'notes'               => null,
                'ordered_at'          => now(),
                'is_demo'             => (bool) $locked->is_demo,
            ]);

            $locked->forceFill([
                'status'       => 'approved',
                'reviewed_by'  => $userId,
                'reviewed_at'  => now(),
                'lab_order_id' => $order->id,
            ])->save();
        });

        $decided = $request->refresh()->load(['patient', 'reviewer', 'labOrder']);

        $this->notifyRequestDecided($decided, null);

        return $decided;
    }

    /**
     * Decline, with the reason the patient will read.
     */
    public function declineRequest(LabTestRequest $request, string $userId, string $reason): LabTestRequest
    {
        $this->refuseIfDecided($request);

        DB::transaction(function () use ($request, $userId, $reason) {
            /** @var LabTestRequest $locked */
            $locked = LabTestRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            $this->refuseIfDecided($locked);

            $locked->forceFill([
                'status'          => 'declined',
                'reviewed_by'     => $userId,
                'reviewed_at'     => now(),
                'decision_reason' => trim($reason),
            ])->save();
        });

        $decided = $request->refresh()->load(['patient', 'reviewer']);

        $this->notifyRequestDecided($decided, trim($reason));

        return $decided;
    }

    // ──────────────────────────────────────────────────────────────────
    // Query building
    // ──────────────────────────────────────────────────────────────────

    /**
     * Every order read starts here: THIS facility, with the people the list
     * names. Patient and User carry the IsDemoRecord global scope; it is
     * deliberately left in place, the same way the results inbox does — on a
     * demo host a name reading null is that isolation working.
     */
    private function baseQuery(string $facilityId): Builder
    {
        return LabOrder::query()
            ->where('lab_orders.facility_id', $facilityId)
            ->with(['patient', 'orderedBy', 'verifiedBy'])
            ->withCount('results');
    }

    /** Statuses are free text; compare them folded. */
    private function statusIn(Builder $query, array $statuses): Builder
    {
        return $query->whereIn(DB::raw('lower(lab_orders.status)'), $statuses);
    }

    // ──────────────────────────────────────────────────────────────────
    // Reference ranges
    // ──────────────────────────────────────────────────────────────────

    /**
     * Published LabReferenceRange rows for a set of analytes, matched by LOINC
     * code or by name (case-insensitive), narrowed to the patient's sex and
     * age where the patient is known. One row per analyte: a sex-specific
     * interval beats an `all` one.
     *
     * @param  Collection<int, mixed>  $names
     * @param  Collection<int, mixed>  $codes
     * @return Collection<int, array{parameter_name: string, low: float|null, high: float|null, unit: string|null, loinc_code: string|null}>
     */
    private function lookupRanges(Collection $names, Collection $codes, ?Patient $patient): Collection
    {
        $names = $names->filter(fn ($n) => filled($n))->map(fn ($n) => mb_strtolower(trim((string) $n)))->unique()->values();
        $codes = $codes->filter(fn ($c) => filled($c))->map(fn ($c) => strtoupper(trim((string) $c)))->unique()->values();

        if ($names->isEmpty() && $codes->isEmpty()) {
            return collect();
        }

        try {
            $rows = LabReferenceRange::query()
                ->where(function ($q) use ($names, $codes) {
                    if ($names->isNotEmpty()) {
                        $q->whereIn(DB::raw('lower(test_name)'), $names->all());
                    }
                    if ($codes->isNotEmpty()) {
                        $q->orWhereIn(DB::raw('upper(loinc_code)'), $codes->all());
                    }
                })
                ->get();
        } catch (\Throwable $e) {
            // No published ranges is an honest answer; a missing table is not a
            // reason to refuse a result.
            Log::debug('lab desk: reference range lookup failed', ['error' => $e->getMessage()]);

            return collect();
        }

        $sex = $patient?->sex ? strtolower((string) $patient->sex) : null;
        $age = $this->patientAge($patient);

        return $rows
            ->filter(function (LabReferenceRange $r) use ($sex, $age) {
                $gender = strtolower((string) $r->gender);
                if ($gender !== '' && $gender !== 'all' && $sex !== null && $gender !== $sex) {
                    return false;
                }
                if ($age !== null && (($r->age_min !== null && $age < (int) $r->age_min) || ($r->age_max !== null && $age > (int) $r->age_max))) {
                    return false;
                }

                return true;
            })
            // Sex-specific first, so unique() keeps it over the `all` row.
            ->sortBy(fn (LabReferenceRange $r) => strtolower((string) $r->gender) === 'all' ? 1 : 0)
            ->unique(fn (LabReferenceRange $r) => mb_strtolower(trim((string) $r->test_name)))
            ->values()
            ->map(fn (LabReferenceRange $r) => [
                'parameter_name' => (string) $r->test_name,
                'low'            => $r->normal_low !== null ? (float) $r->normal_low : null,
                'high'           => $r->normal_high !== null ? (float) $r->normal_high : null,
                'unit'           => $this->blankToNull($r->unit),
                'loinc_code'     => $this->blankToNull($r->loinc_code),
            ]);
    }

    /** "3.5–5.1" from a matched range, or null when there is nothing to print. */
    private function formatRange(?array $range): ?string
    {
        if ($range === null || ($range['low'] === null && $range['high'] === null)) {
            return null;
        }

        $fmt = fn (?float $v): string => $v === null ? '' : rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');

        if ($range['low'] === null) {
            return '<' . $fmt($range['high']);
        }
        if ($range['high'] === null) {
            return '>' . $fmt($range['low']);
        }

        return $fmt($range['low']) . '–' . $fmt($range['high']);
    }

    /**
     * `patients.date_of_birth` is encrypted; a row written under another
     * APP_KEY throws on read. That is an unknown age, not a failed request.
     */
    private function patientAge(?Patient $patient): ?int
    {
        if ($patient === null) {
            return null;
        }

        try {
            $dob = $patient->date_of_birth;
        } catch (\Throwable) {
            return null;
        }

        return $dob ? Carbon::parse($dob)->age : null;
    }

    // ──────────────────────────────────────────────────────────────────
    // Flags, urgency, small helpers
    // ──────────────────────────────────────────────────────────────────

    /** The contract's H|L|N|C → what is written to lab_results.flag. */
    private function storedFlag(mixed $flag): ?string
    {
        $flag = strtoupper(trim((string) $flag));

        return match ($flag) {
            ''  => null,
            'C' => self::STORED_CRITICAL,
            default => $flag,
        };
    }

    /** The patient's advisory priority → the order's urgency vocabulary. */
    private function urgencyFromPriority(?string $priority): string
    {
        return match (strtolower(trim((string) $priority))) {
            'stat', 'immediate', 'emergency' => 'stat',
            'urgent', 'priority'             => 'urgent',
            default                          => 'routine',
        };
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ordersHaveVerifiedBy(): bool
    {
        return self::$ordersHaveVerifiedBy ??= Schema::hasColumn('lab_orders', 'verified_by');
    }

    // ──────────────────────────────────────────────────────────────────
    // Refusals
    // ──────────────────────────────────────────────────────────────────

    private function refuseIfClosed(LabOrder $order): void
    {
        if (! $order->isTerminal()) {
            return;
        }

        $status = strtolower(trim((string) $order->status));
        $word   = __('lab_api.order_status.' . $status);
        if (str_starts_with($word, 'lab_api.')) {
            $word = $status;
        }

        $this->refuse('order_closed', 409, __('lab_api.order_closed', ['status' => $word]));
    }

    private function refuseIfDecided(LabTestRequest $request): void
    {
        if (! $request->isDecided()) {
            return;
        }

        $word = __('lab_api.request_status.' . $request->status);
        if (str_starts_with($word, 'lab_api.')) {
            $word = (string) $request->status;
        }

        $this->refuse('already_decided', 409, __('lab_api.already_decided', ['status' => $word]));
    }

    /** @return never */
    private function refuse(string $errorCode, int $status, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'message'    => $message,
            'error_code' => $errorCode,
        ], $status));
    }

    // ──────────────────────────────────────────────────────────────────
    // Bells
    // ──────────────────────────────────────────────────────────────────

    /**
     * A released panel: the patient's bell (+ push, when the patient push
     * channel exists on this estate) and the ordering clinician's bell. Each
     * is best-effort and logged on failure — the release is already committed
     * and a failed notification must not report it as failed.
     */
    private function notifyReleased(LabOrder $order, string $userId): void
    {
        $facilityName = $this->facility($order->facility_id)?->name ?? '';
        $critical     = $order->results->contains(fn (LabResult $r) => $r->deskFlag() === 'C');

        // The bell is written to the patient's own feed by id — that is not a
        // disclosure to the bench, so the demo partition is not consulted here.
        $patient = $this->patientForNotification($order->patient_id);

        // Guardians of a dependent hear about the release exactly once.
        rescue(fn () => (new \App\Listeners\NotifyGuardiansOfPatientEvent())
            ->handleLabResult((object) ['patient_id' => $order->patient_id]), null, false);

        if ($patient !== null) {
            $locale = $this->patientLocale($patient->id);
            $title  = __('lab_api.notify.result_ready_title', ['test' => $order->test_name], $locale);
            $body   = __('lab_api.notify.result_ready_body', ['facility' => $facilityName], $locale);

            $this->bellPatient(
                $patient,
                'lab_result_available',
                $title,
                $body,
                $critical ? 'high' : 'normal',
                '/labs/' . $order->id,
                __('lab_api.notify.result_ready_action', [], $locale),
                ['lab_order_id' => $order->id]
            );

            $this->pushPatient($patient, 'lab_result', $title, $body, [
                'lab_order_id' => $order->id,
                'action_url'   => '/labs/' . $order->id,
            ]);
        }

        if (blank($order->ordered_by)) {
            return;
        }

        $patientName = $order->patient
            ? trim($order->patient->first_name . ' ' . $order->patient->last_name)
            : ($patient?->health_id ?? '');

        try {
            Notify::toUser(
                (string) $order->ordered_by,
                'lab_result_released',
                'lab',
                [
                    'title'        => __('lab_api.notify.clinician_title', ['test' => $order->test_name]),
                    'message'      => __($critical ? 'lab_api.notify.clinician_body_critical' : 'lab_api.notify.clinician_body', [
                        'patient'  => $patientName,
                        'test'     => $order->test_name,
                        'facility' => $facilityName,
                    ]),
                    'url'          => '/clinician/results/lab-' . $order->id,
                    'lab_order_id' => $order->id,
                    'patient_id'   => $order->patient_id,
                    'facility_id'  => $order->facility_id,
                    'is_critical'  => $critical,
                ],
                $critical ? 'critical' : 'normal',
                $userId,
                'LabOrder',
                (string) $order->id,
            );
        } catch (\Throwable $e) {
            Log::warning('lab desk: clinician bell failed', ['lab_order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** Approved or declined: the patient is told either way. */
    private function notifyRequestDecided(LabTestRequest $request, ?string $reason): void
    {
        $patient = $this->patientForNotification($request->patient_id);

        if ($patient === null) {
            return;
        }

        $facilityName = $this->facility((string) $request->facility_id)?->name ?? '';
        $locale       = $this->patientLocale($patient->id);
        $approved     = $request->status === 'approved';

        $title = __($approved ? 'lab_api.notify.request_approved_title' : 'lab_api.notify.request_declined_title', [], $locale);
        $body  = $approved
            ? __('lab_api.notify.request_approved_body', ['facility' => $facilityName, 'test' => $request->test_name], $locale)
            : __('lab_api.notify.request_declined_body', ['facility' => $facilityName, 'test' => $request->test_name, 'reason' => (string) $reason], $locale);

        $type = $approved ? 'lab_request_approved' : 'lab_request_declined';

        $this->bellPatient(
            $patient,
            $type,
            $title,
            $body,
            'normal',
            '/labs',
            __('lab_api.notify.request_action', [], $locale),
            array_filter([
                'lab_test_request_id' => $request->id,
                'lab_order_id'        => $request->lab_order_id,
            ])
        );

        $this->pushPatient($patient, 'lab_request', $title, $body, array_filter([
            'lab_test_request_id' => $request->id,
            'lab_order_id'        => $request->lab_order_id,
            'action_url'          => '/labs',
        ]));
    }

    /**
     * A rejected sample: the patient hears a new sample is needed, the ordering
     * clinician hears why. Public so the manager's desk (LabManagerDeskService)
     * tells people through the same bells as a release, not a second copy.
     */
    public function notifySampleRejected(LabOrder $order, string $reason, string $userId): void
    {
        $facilityName = $this->facility($order->facility_id)?->name ?? '';
        $patient      = $this->patientForNotification($order->patient_id);

        if ($patient !== null) {
            $locale = $this->patientLocale($patient->id);
            $title  = __('lab_manager.notify.rejected_patient_title', ['test' => $order->test_name], $locale);
            $body   = __('lab_manager.notify.rejected_patient_body', ['facility' => $facilityName, 'reason' => $reason], $locale);

            $this->bellPatient($patient, 'lab_sample_rejected', $title, $body, 'normal', '/labs/' . $order->id,
                __('lab_api.notify.result_ready_action', [], $locale), ['lab_order_id' => $order->id]);

            $this->pushPatient($patient, 'lab_sample_rejected', $title, $body, [
                'lab_order_id' => $order->id,
                'action_url'   => '/labs/' . $order->id,
            ]);
        }

        if (blank($order->ordered_by)) {
            return;
        }

        try {
            Notify::toUser(
                (string) $order->ordered_by,
                'lab_sample_rejected',
                'lab',
                [
                    'title'        => __('lab_manager.notify.rejected_clinician_title', ['test' => $order->test_name]),
                    'message'      => __('lab_manager.notify.rejected_clinician_body', [
                        'test'     => $order->test_name,
                        'facility' => $facilityName,
                        'reason'   => $reason,
                    ]),
                    'url'          => '/clinician/results/lab-' . $order->id,
                    'lab_order_id' => $order->id,
                    'patient_id'   => $order->patient_id,
                    'facility_id'  => $order->facility_id,
                ],
                'normal',
                $userId,
                'LabOrder',
                (string) $order->id,
            );
        } catch (\Throwable $e) {
            Log::warning('lab desk: rejection bell failed', ['lab_order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * One row in Laravel's `notifications` table — the feed the patient app's
     * bell reads (MobileNotificationController::scopedQuery), in the payload
     * shape the real notification classes write.
     *
     * @param  array<string, mixed>  $extra
     */
    private function bellPatient(
        Patient $patient,
        string $type,
        string $title,
        string $message,
        string $severity,
        string $actionUrl,
        string $actionLabel,
        array $extra = []
    ): void {
        try {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => $type,
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->id,
                'data'            => json_encode([
                    'type'         => $type,
                    'title'        => $title,
                    'message'      => $message,
                    'severity'     => $severity,
                    'action_url'   => $actionUrl,
                    'action_label' => $actionLabel,
                ] + $extra, JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('lab desk: patient bell failed', ['patient_id' => $patient->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The patient push channel is added by a sibling change
     * (App\Support\PatientPush::send(Patient, type, title, body, data)). Until
     * it lands this is a no-op; once it does, every release pushes. Guarded by
     * class_exists so neither order of deployment breaks the desk.
     *
     * @param  array<string, mixed>  $data
     */
    private function pushPatient(Patient $patient, string $type, string $title, string $body, array $data): void
    {
        $push = '\App\Support\PatientPush';

        if (! class_exists($push) || ! method_exists($push, 'send')) {
            return;
        }

        try {
            $push::send($patient, $type, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::warning('lab desk: patient push failed', ['patient_id' => $patient->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }

    private function patientForNotification(?string $patientId): ?Patient
    {
        if (blank($patientId)) {
            return null;
        }

        return Patient::query()->withoutGlobalScopes()->find($patientId);
    }

    /** The language the patient reads the app in; the request's locale otherwise. */
    private function patientLocale(string $patientId): string
    {
        try {
            $preferred = DB::table('mobile_app_settings')->where('patient_id', $patientId)->value('preferred_language');
        } catch (\Throwable) {
            $preferred = null;
        }

        $preferred = strtolower(substr((string) $preferred, 0, 2));

        return in_array($preferred, ['en', 'fr'], true) ? $preferred : app()->getLocale();
    }
}
