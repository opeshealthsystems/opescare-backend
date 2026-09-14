<?php

namespace App\Services\Lab;

use App\Models\ClinicalCodeDictionary;
use App\Models\CodeSystemMapping;
use App\Models\CriticalValueAlert;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Support\ResultFlagResolver;
use App\Support\Terminology\ClinicalCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The web-portal LABORATORY DESK (/facility/lab).
 *
 * The bench's own rules already live in LabDeskService, which the clinician
 * app's lab desk uses (routes/mobile_lab_desk.php). This class does not fork
 * them: sample collection and release are DELEGATED to it, so both surfaces
 * move an order through the same states — ordered → collected → in_progress →
 * resulted — with the same refusals. What this class adds is the three things
 * the web desk needs and the API tier never had:
 *
 *  1. DERIVED FLAGS, FROM THE ONE AUTHORITY. A value typed here is placed
 *     against a published `reference_ranges` row through
 *     App\Support\ResultFlagResolver — the same class, and the same table,
 *     the patient's screen and the clinician's results inbox read. That is
 *     the whole point: a critical value must read as critical to the patient,
 *     to the ordering doctor and to the bench, and the only way to guarantee
 *     it is to compute it once, in one place, from one source. The derived
 *     flag is stored with its `flag_source` so no reader can later present it
 *     as the laboratory's own verdict. NOTE: `lab_reference_ranges` (the
 *     older table LabDeskService prints intervals from) is deliberately NOT
 *     consulted for the flag — no patient- or clinician-facing reader uses
 *     it, so deriving from it here would reintroduce exactly the divergence
 *     ResultFlagResolver was written to close.
 *
 *  2. SECOND-PERSON VALIDATION. `resulted` is where the bench's road ends;
 *     the web desk adds one more step, `verified` (already one of
 *     LabOrder::RELEASED_STATUSES, so every existing reader folds it into
 *     "completed" and nothing downstream changes). Only a lab_validator or
 *     lab_manager may take it, and never someone who entered or released any
 *     of the order's own results — a validation by the person being validated
 *     is not a validation.
 *
 *  3. CORRECTIONS ARE NEW ROWS. A LabResult is never UPDATEd by this class —
 *     not before validation and not after. A correction is a fresh row for
 *     the same analyte whose notes name the row it supersedes; the original
 *     survives verbatim. On a validated order a correction also drops the
 *     order back to `resulted`, so the corrected panel has to be validated
 *     again. (LabResult::amend() — the in-place path — exists on the platform
 *     and is not used here, because the validated panel must stay readable
 *     exactly as it was signed off.)
 *
 * NOTHING IS INVENTED. A LOINC code is written only when the order's own
 * test code IS a dictionary LOINC term or an APPROVED code_system_mappings
 * row maps it to one; a unit is stored only as typed; a reference interval
 * only from a published row. Where none exists the field is null and the
 * screen says so.
 *
 * Scope: every read and write is narrowed to the facility id the controller
 * hands in (PortalContextService); an order elsewhere is not found, never
 * forbidden.
 */
class LabDeskWebService
{
    /** The roles that may take the validation step. */
    public const VALIDATOR_ROLES = ['lab_validator', 'lab_manager'];

    /** The worklist's five columns, in bench order. */
    public const STATUSES = ['ordered', 'collected', 'in_progress', 'resulted', 'validated'];

    /** The stored status that means "validated by a second person". */
    public const VALIDATED_STATUS = 'verified';

    /** How a correction row names the row it supersedes, inside `notes`. */
    public const CORRECTS_TAG = '[corrects:%s]';

    /** Worklist ordering: stat, then urgent, then routine — then oldest first. */
    private const URGENCY_RANK_SQL = "case when lower(coalesce(lab_orders.urgency, '')) in ('stat','immediate','emergency') then 0 "
        . "when lower(coalesce(lab_orders.urgency, '')) in ('urgent','priority') then 1 else 2 end";

    public function __construct(private readonly LabDeskService $desk)
    {
    }

    // ──────────────────────────────────────────────────────────────────
    // Reads
    // ──────────────────────────────────────────────────────────────────

    /**
     * The worklist for one status column, most urgent first and oldest first
     * within an urgency. A Health ID narrows it to one patient — EXACT match
     * only, following a merged alias; there is no name search on this desk.
     */
    public function worklist(string $facilityId, string $status, ?string $healthId = null): LengthAwarePaginator
    {
        $query = $this->base($facilityId);

        $this->whereStatus($query, $status);

        if ($healthId !== null && trim($healthId) !== '') {
            $patient = Patient::resolveHealthId($healthId);

            // No patient: an empty list, not a scan of everyone else.
            $query->where('lab_orders.patient_id', $patient?->id ?? Str::uuid()->toString());
        }

        return $query
            ->orderByRaw(self::URGENCY_RANK_SQL)
            ->orderBy('lab_orders.ordered_at')
            ->paginate(25)
            ->withQueryString();
    }

    /** @return array<string, int> open work per column, for the tabs. */
    public function counts(string $facilityId): array
    {
        $counts = [];

        foreach (self::STATUSES as $status) {
            $counts[$status] = $this->whereStatus(
                LabOrder::query()->where('lab_orders.facility_id', $facilityId),
                $status
            )->count();
        }

        return $counts;
    }

    /** Orders still waiting for a sample, oldest first, with the dictionary's specimen where it names one. */
    public function pendingCollection(string $facilityId): LengthAwarePaginator
    {
        $page = $this->whereStatus($this->base($facilityId), 'ordered')
            ->orderByRaw(self::URGENCY_RANK_SQL)
            ->orderBy('lab_orders.ordered_at')
            ->paginate(25)
            ->withQueryString();

        $page->getCollection()->transform(function (LabOrder $order) {
            $order->setAttribute('dictionary_specimen', $this->dictionaryTerm($order)?->specimen);

            return $order;
        });

        return $page;
    }

    /** One order in this facility's scope, or null (also for a malformed id). */
    public function find(string $facilityId, string $id): ?LabOrder
    {
        return $this->desk->findOrder($facilityId, $id);
    }

    /** The desk's column for an order: LabDeskService's four plus `validated`. */
    public function webStatus(LabOrder $order): string
    {
        if (strtolower(trim((string) $order->status)) === self::VALIDATED_STATUS) {
            return 'validated';
        }

        return $order->deskStatus();
    }

    /**
     * Everything the order page shows, resolved once.
     *
     * @return array{
     *     order: LabOrder,
     *     status: string,
     *     rows: Collection<int, array<string, mixed>>,
     *     term: ClinicalCodeDictionary|null,
     *     loinc: string|null,
     *     published: ReferenceRange|null,
     * }
     */
    public function detail(LabOrder $order): array
    {
        $order = $order->refresh()->load([
            'patient',
            'orderedBy',
            'verifiedBy',
            'results' => fn ($q) => $q->orderBy('resulted_at')->orderBy('created_at'),
        ]);

        $ranges = $this->publishedRanges($order->results);
        $sex    = $this->sexOf($order->patient);
        $dob    = $this->dobOf($order->patient);

        $alerts = CriticalValueAlert::query()
            ->whereIn('lab_result_id', $order->results->pluck('id')->all())
            ->with('acknowledgedBy')
            ->get()
            ->keyBy('lab_result_id');

        $superseded = $order->results
            ->map(fn (LabResult $r) => $this->correctedId($r))
            ->filter()
            ->flip();

        $enterers = $this->userNames($order->results->pluck('entered_by')->merge($order->results->pluck('verified_by')));

        $rows = $order->results->map(function (LabResult $r) use ($ranges, $sex, $dob, $alerts, $superseded, $enterers) {
            $resolution = ResultFlagResolver::forResult($r, ResultFlagResolver::matchPublished($r, $ranges, $sex, $dob));

            return [
                'result'        => $r,
                'flag'          => $resolution['flag'],
                'flag_source'   => $resolution['source'],
                'severity'      => ResultFlagResolver::severity($resolution['flag']),
                'ref_low'       => $resolution['ref_low'],
                'ref_high'      => $resolution['ref_high'],
                'critical_low'  => $resolution['critical_low'],
                'critical_high' => $resolution['critical_high'],
                'alert'         => $alerts->get($r->id),
                'superseded'    => $superseded->has($r->id),
                'corrects'      => $this->correctedId($r),
                'entered_by'    => $enterers[(string) $r->getAttribute('entered_by')] ?? null,
                'released_by'   => $enterers[(string) $r->verified_by] ?? null,
            ];
        });

        // The interval the entry form will place a value against — for the
        // ORDERED test, so the bench sees before typing whether a range exists.
        $probe = new LabResult([
            'parameter_name' => $order->test_name,
            'loinc_code'     => $this->loincFor($order),
        ]);

        return [
            'order'     => $order,
            'status'    => $this->webStatus($order),
            'rows'      => $rows,
            'term'      => $this->dictionaryTerm($order),
            'loinc'     => $this->loincFor($order),
            'published' => ResultFlagResolver::matchPublished($probe, $this->publishedRanges(collect([$probe])), $sex, $dob),
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Writes
    // ──────────────────────────────────────────────────────────────────

    /**
     * The sample is in. Delegated to the bench's own transition; the specimen
     * type, when the collector names one, is appended to the order's notes —
     * the one free-text field on `lab_orders` — because the platform's
     * `document_specimen_events` trail hangs off official documents, not off
     * orders, and there is no specimen column to write.
     */
    public function collect(LabOrder $order, string $userId, ?string $specimenType): LabOrder
    {
        $collected = $this->desk->collect($order, $userId);

        $specimenType = $this->blankToNull($specimenType);

        if ($specimenType !== null) {
            $collected->forceFill([
                'notes' => trim(implode("\n", array_filter([
                    $collected->notes,
                    'Specimen: ' . $specimenType . ' (' . now()->toDateTimeString() . ')',
                ]))),
            ])->save();
        }

        return $collected;
    }

    /**
     * Enter one analyte. A NEW row, always. Refused on a validated order —
     * a signed-off panel changes only through a correction, which is also a
     * new row but drops the order back for re-validation.
     *
     * @param  array{parameter_name?: string|null, value: string, unit?: string|null, notes?: string|null}  $data
     */
    public function enterResult(LabOrder $order, array $data, string $userId): LabResult
    {
        $this->refuseIfClosed($order);

        if ($this->webStatus($order) === 'validated') {
            $this->refuse('validated_locked', 409, __('lab_desk.err.validated_locked'));
        }

        return $this->writeRow($order, $data, $userId, null);
    }

    /**
     * Correct a result. The original row is not touched; a new row carries
     * the corrected value and names the one it supersedes. On a validated
     * order the panel returns to `resulted` — the validator signed off the
     * old number, not this one.
     *
     * @param  array{value: string, unit?: string|null, notes?: string|null}  $data
     */
    public function correct(LabOrder $order, LabResult $original, array $data, string $reason, string $userId): LabResult
    {
        $this->refuseIfClosed($order);

        if ($original->lab_order_id !== $order->id) {
            $this->refuse('result_not_found', 404, __('lab_desk.err.result_not_found'));
        }

        $reason = trim($reason);
        if ($reason === '') {
            $this->refuse('reason_required', 422, __('lab_desk.err.reason_required'));
        }

        $data['parameter_name'] = $original->parameter_name;
        $data['notes']          = trim(implode("\n", array_filter([
            sprintf(self::CORRECTS_TAG, $original->id) . ' ' . $reason,
            $this->blankToNull($data['notes'] ?? null),
        ])));

        return $this->writeRow($order, $data, $userId, $original);
    }

    /** Release to the patient and the ordering clinician — the bench's own final step, unchanged. */
    public function release(LabOrder $order, string $userId): LabOrder
    {
        return $this->desk->release($order, $userId);
    }

    /**
     * Second-person validation. Only a validator role; only a released,
     * not-yet-validated order; never by anyone who entered or released one of
     * its results.
     */
    public function validate(LabOrder $order, string $userId, ?string $role): LabOrder
    {
        if (! in_array((string) $role, self::VALIDATOR_ROLES, true)) {
            abort(403, __('api.capability_forbidden'));
        }

        $this->refuseIfClosed($order);

        if ($this->webStatus($order) !== 'resulted') {
            $this->refuse('not_released', 409, __('lab_desk.err.not_released'));
        }

        DB::transaction(function () use ($order, $userId) {
            /** @var LabOrder $locked */
            $locked = LabOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($this->webStatus($locked) !== 'resulted') {
                $this->refuse('not_released', 409, __('lab_desk.err.not_released'));
            }

            $hands = LabResult::query()
                ->where('lab_order_id', $locked->id)
                ->get(['entered_by', 'verified_by'])
                ->flatMap(fn (LabResult $r) => [$r->getAttribute('entered_by'), $r->verified_by])
                ->push($locked->verified_by)
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->unique();

            if ($hands->contains($userId)) {
                $this->refuse('self_validation', 409, __('lab_desk.err.self_validation'));
            }

            $locked->forceFill([
                'status'      => self::VALIDATED_STATUS,
                'verified_by' => $userId,
            ])->save();
        });

        return $order->refresh();
    }

    // ──────────────────────────────────────────────────────────────────
    // Codes, ranges, dictionary
    // ──────────────────────────────────────────────────────────────────

    /**
     * The LOINC code this order's test maps to, or null. Two honest sources
     * and no third: the order's own test code when it IS a dictionary LOINC
     * term, else an APPROVED crosswalk row for it.
     */
    public function loincFor(LabOrder $order): ?string
    {
        $code = trim((string) $order->test_code);

        if ($code !== '' && ClinicalCode::isLoinc($code) && $this->dictionaryRow($code) !== null) {
            return $code;
        }

        try {
            $mapped = CodeSystemMapping::query()
                ->where('resource_type', 'lab_order')
                ->where('status', 'approved')
                ->whereRaw("lower(replace(replace(standard_system, '-', ''), ' ', '')) = 'loinc'")
                ->where(function (Builder $q) use ($code, $order) {
                    if ($code !== '') {
                        $q->whereRaw('lower(local_code) = ?', [strtolower($code)]);
                    }
                    $name = strtolower(trim((string) $order->test_name));
                    if ($name !== '') {
                        $q->orWhereRaw('lower(local_name) = ?', [$name]);
                    }
                })
                ->value('standard_code');
        } catch (\Throwable $e) {
            Log::debug('lab desk: crosswalk lookup failed', ['error' => $e->getMessage()]);
            $mapped = null;
        }

        $mapped = trim((string) $mapped);

        return $mapped !== '' && ClinicalCode::isLoinc($mapped) ? $mapped : null;
    }

    /** The dictionary's LOINC row for the order's test (specimen, scale, example units, French title), or null. */
    public function dictionaryTerm(LabOrder $order): ?ClinicalCodeDictionary
    {
        $code = $this->loincFor($order);

        return $code === null ? null : $this->dictionaryRow($code);
    }

    private function dictionaryRow(string $code): ?ClinicalCodeDictionary
    {
        try {
            return ClinicalCodeDictionary::query()
                ->whereRaw('lower(system) = ?', ['loinc'])
                ->where('code', $code)
                ->where('is_active', true)
                ->first();
        } catch (\Throwable $e) {
            Log::debug('lab desk: dictionary lookup failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Published ranges for a set of analytes — the SAME query the clinician's
     * results inbox runs (MobileResultsInboxController::publishedRanges), so
     * the bench and the doctor place a value against the same rows.
     *
     * @param  Collection<int, LabResult>  $analytes
     * @return Collection<int, ReferenceRange>
     */
    private function publishedRanges(Collection $analytes): Collection
    {
        $names  = $analytes->map(fn ($r) => strtolower(trim((string) $r->parameter_name)))->filter()->unique()->values();
        $loincs = $analytes->map(fn ($r) => trim((string) $r->loinc_code))->filter()->unique()->values();

        if ($names->isEmpty() && $loincs->isEmpty()) {
            return collect();
        }

        try {
            return ReferenceRange::query()
                ->where('is_active', true)
                ->where(function ($query) use ($names, $loincs) {
                    if ($names->isNotEmpty()) {
                        $query->orWhereIn(DB::raw('lower(test_name)'), $names->all());
                        $query->orWhereIn(DB::raw('lower(test_code)'), $names->all());
                    }
                    if ($loincs->isNotEmpty()) {
                        $query->orWhereIn('loinc_code', $loincs->all());
                    }
                })
                ->get();
        } catch (\Throwable $e) {
            Log::debug('lab desk: published range lookup failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * The one writer. Builds the row, derives its flag through the resolver,
     * stores flag + source, inserts WITHOUT model events (the family listener
     * would otherwise tell every guardian per analyte typed; they hear once,
     * on release), moves the order, and raises the critical alert.
     *
     * @param  array<string, mixed>  $data
     */
    private function writeRow(LabOrder $order, array $data, string $userId, ?LabResult $corrects): LabResult
    {
        $name  = $this->blankToNull($data['parameter_name'] ?? null) ?? (string) $order->test_name;
        $value = trim((string) $data['value']);
        $unit  = $this->blankToNull($data['unit'] ?? null);

        // A correction keeps the code of the row it corrects; a fresh analyte
        // takes the order's mapping, if there is one. Never anything typed.
        $loinc = $corrects !== null
            ? $this->blankToNull($corrects->loinc_code)
            : (strcasecmp($name, (string) $order->test_name) === 0 ? $this->loincFor($order) : null);

        $probe = new LabResult(['parameter_name' => $name, 'loinc_code' => $loinc]);
        $probe->setAttribute('value', $value);

        $published  = ResultFlagResolver::matchPublished($probe, $this->publishedRanges(collect([$probe])), $this->sexOf($order->patient), $this->dobOf($order->patient));
        $resolution = ResultFlagResolver::resolve(null, $value, null, $published);

        $result = DB::transaction(function () use ($order, $name, $value, $unit, $loinc, $data, $userId, $published, $resolution, $corrects) {
            /** @var LabOrder $locked */
            $locked = LabOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $row = new LabResult();
            $row->forceFill([
                'lab_order_id'    => $locked->id,
                'patient_id'      => $locked->patient_id,
                'parameter_name'  => $name,
                'value'           => $value,
                'unit'            => $unit,
                'reference_range' => $this->formatRange($published),
                'flag'            => ResultFlagResolver::toStoredFlag($resolution['flag']),
                'flag_source'     => $resolution['source'],
                'notes'           => $this->blankToNull($data['notes'] ?? null),
                'entered_by'      => $userId,
                'verified_by'     => null,
                'resulted_at'     => now(),
                'loinc_code'      => $loinc,
                'loinc_display'   => $loinc !== null ? ($this->dictionaryRow($loinc)?->display) : null,
                'is_demo'         => (bool) $locked->is_demo,
            ]);

            LabResult::withoutEvents(fn () => $row->save());

            if ($corrects !== null && $this->webStatus($locked) === 'validated') {
                // The validator signed off the old number. verified → resulted
                // is a released-to-released move, which the model permits.
                $locked->forceFill(['status' => 'resulted', 'verified_by' => null])->save();
            } elseif (! $locked->isReleased()) {
                $locked->forceFill(['status' => 'in_progress'])->save();
            }

            if ($resolution['flag'] === ResultFlagResolver::CRITICAL) {
                $this->raiseAlert($row, $resolution, $published);
            }

            return $row;
        });

        return $result;
    }

    /**
     * The bench's escalation of a critical value. One row per critical result;
     * the clinician acknowledges it from the results inbox and the desk shows
     * that state. The threshold printed is the published bound the value
     * crossed — never a number from anywhere else.
     *
     * @param  array<string, mixed>  $resolution
     */
    private function raiseAlert(LabResult $result, array $resolution, ?ReferenceRange $published): void
    {
        $direction = ResultFlagResolver::direction($resolution) ?? ResultFlagResolver::HIGH;
        $bound     = $direction === ResultFlagResolver::LOW ? $resolution['critical_low'] : $resolution['critical_high'];
        $unit      = $this->blankToNull($result->unit) ?? $this->blankToNull($published?->unit);

        try {
            CriticalValueAlert::create([
                'lab_result_id'      => $result->id,
                'patient_id'         => $result->patient_id,
                'alert_type'         => $direction === ResultFlagResolver::LOW ? 'critical_low' : 'critical_high',
                'test_name'          => $result->parameter_name,
                'result_value'       => trim($result->value . ' ' . $unit),
                'critical_threshold' => trim($this->number($bound) . ' ' . $unit),
                'acknowledged'       => false,
            ]);
        } catch (\Throwable $e) {
            // The result is already committed; a failed escalation row is
            // logged loudly, never allowed to report the entry as failed.
            Log::error('lab desk: critical value alert failed', ['lab_result_id' => $result->id, 'error' => $e->getMessage()]);
        }
    }

    private function base(string $facilityId): Builder
    {
        return LabOrder::query()
            ->where('lab_orders.facility_id', $facilityId)
            ->with(['patient', 'orderedBy', 'verifiedBy'])
            ->withCount('results');
    }

    /** Statuses are free text; every comparison folds the same way. */
    private function whereStatus(Builder $query, string $status): Builder
    {
        $set = match ($status) {
            'ordered'     => LabOrder::AWAITING_COLLECTION_STATUSES,
            'collected'   => LabOrder::COLLECTED_STATUSES,
            'in_progress' => LabOrder::IN_PROGRESS_STATUSES,
            'resulted'    => array_values(array_diff(LabOrder::RELEASED_STATUSES, [self::VALIDATED_STATUS])),
            'validated'   => [self::VALIDATED_STATUS],
            default       => LabOrder::OPEN_STATUSES,
        };

        return $query->whereIn(DB::raw('lower(lab_orders.status)'), $set);
    }

    /** The id a correction row names, or null for an original entry. */
    private function correctedId(LabResult $result): ?string
    {
        if (preg_match('/\[corrects:([0-9a-f-]{36})\]/i', (string) $result->notes, $m) === 1) {
            return strtolower($m[1]);
        }

        return null;
    }

    /** "3.5–5.1 mmol/L" from a published row, or null when there is nothing to print. */
    private function formatRange(?ReferenceRange $range): ?string
    {
        if ($range === null) {
            return null;
        }

        $low  = $range->normal_low !== null ? (float) $range->normal_low : null;
        $high = $range->normal_high !== null ? (float) $range->normal_high : null;

        if ($low === null && $high === null) {
            return null;
        }

        $text = match (true) {
            $low === null  => '<' . $this->number($high),
            $high === null => '>' . $this->number($low),
            default        => $this->number($low) . '–' . $this->number($high),
        };

        return trim($text . ' ' . (string) $range->unit);
    }

    private function number(?float $v): string
    {
        return $v === null ? '' : rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
    }

    private function sexOf(?Patient $patient): ?string
    {
        $sex = strtolower(trim((string) ($patient?->sex ?? '')));

        return $sex === '' ? null : $sex;
    }

    /** `patients.date_of_birth` is encrypted; a row written under another key reads as "unknown". */
    private function dobOf(?Patient $patient): mixed
    {
        if ($patient === null) {
            return null;
        }

        try {
            return $patient->date_of_birth ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, string> user id → display name */
    private function userNames(Collection $ids): array
    {
        $ids = $ids->filter()->map(fn ($id) => (string) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        try {
            return DB::table('users')->whereIn('id', $ids->all())->pluck('name', 'id')
                ->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function refuseIfClosed(LabOrder $order): void
    {
        if ($order->isTerminal()) {
            $this->refuse('order_closed', 409, __('lab_desk.err.order_closed', ['status' => (string) $order->status]));
        }
    }

    /**
     * Same shape as LabDeskService::refuse(), so the controller translates
     * both with one catch: a JSON body {message, error_code} on the status.
     *
     * @return never
     */
    private function refuse(string $errorCode, int $status, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'message'    => $message,
            'error_code' => $errorCode,
        ], $status));
    }
}
