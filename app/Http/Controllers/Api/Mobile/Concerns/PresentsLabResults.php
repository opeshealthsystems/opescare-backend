<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shaping for the mobile laboratory endpoints — and, more importantly, the one
 * place that decides what a result is ALLOWED to claim.
 *
 * Three rules govern everything below. They exist because a laboratory result
 * rendered wrongly is a safety bug, not a cosmetic one.
 *
 *  1. NOTHING IS INVENTED. A reference range is printed only if the laboratory
 *     sent one with the result, or the platform holds a published range for
 *     that analyte, that sex and that age band. Where neither exists the API
 *     returns `ref_low: null, ref_high: null, reference: null` and the screen
 *     prints "Not provided". It never prints a plausible interval.
 *
 *  2. "NO FLAG" IS NEVER "NORMAL". A value with no laboratory flag and no
 *     range to compare against is `unflagged` — a distinct state the app
 *     renders as "Not recorded". Calling it normal would be the API asserting
 *     a clinical judgement nobody made.
 *
 *  3. ABNORMAL AND CRITICAL ARE ALWAYS CARRIED. `flag`, `is_abnormal` and the
 *     order's `overall_flag` are computed from the laboratory's own flag first
 *     and only then from a real range. The trend and report screens key their
 *     red banners off these, so a critical value that arrived as `normal`
 *     would be silently hidden from the patient.
 *
 * Precedence for a single result's flag:
 *     laboratory's own `flag` column
 *   → derived from the laboratory's own free-text reference range
 *   → derived from a published `reference_ranges` row (this is the only source
 *     that carries CRITICAL bounds, so it is the only source that can raise a
 *     value to `critical` on its own)
 *   → `unflagged`.
 */
trait PresentsLabResults
{
    /**
     * The contract's per-result flags. `unflagged` is deliberately NOT in the
     * abnormal set and deliberately NOT `normal` — it means "no comparison was
     * made", which is a third thing.
     */
    private const ABNORMAL_FLAGS = ['low', 'high', 'abnormal', 'critical'];

    // ── Patient ──────────────────────────────────────────────────────────────

    /**
     * The authenticated patient, and only ever the authenticated patient.
     *
     * `patient_id` is set on the request attributes by AuthenticateMobilePatient
     * from the bearer token. It is never read from the route, the query string,
     * a header or the body, so one patient can never address another's results.
     */
    protected function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        return $patientId;
    }

    /**
     * The patient block a printed report and the collection screen carry.
     *
     * Returns null rather than a half-filled stub when the patient row cannot
     * be loaded, and leaves `age` / `dob` null when the date of birth is absent
     * or undecryptable — an age is never estimated to fill the field.
     *
     * @return array<string, mixed>|null
     */
    protected function presentPatient(?Patient $patient): ?array
    {
        if ($patient === null) {
            return null;
        }

        $dob = $this->patientDateOfBirth($patient);

        return [
            'name'      => trim($patient->first_name . ' ' . $patient->last_name),
            'health_id' => $patient->health_id,
            'sex'       => $patient->sex,
            'age'       => $dob?->age,
            'dob'       => $dob?->toDateString(),
        ];
    }

    /**
     * `patients.date_of_birth` is stored encrypted (see Patient's accessor). A
     * row written under a different APP_KEY throws on read; that is a missing
     * date, not a 500 for the whole report.
     */
    protected function patientDateOfBirth(?Patient $patient): ?Carbon
    {
        if ($patient === null) {
            return null;
        }

        try {
            $dob = $patient->date_of_birth;
        } catch (\Throwable) {
            return null;
        }

        return $dob ? Carbon::parse($dob) : null;
    }

    // ── Orders ───────────────────────────────────────────────────────────────

    /**
     * One lab order plus its results, in the shape `PortalLabOrder` expects.
     *
     * @param  Collection<int, LabResult>  $results
     * @param  Collection<int, ReferenceRange>  $ranges  Pre-loaded published ranges (see loadReferenceRanges).
     * @return array<string, mixed>
     */
    protected function presentOrder(
        LabOrder $order,
        Collection $results,
        Collection $ranges,
        ?string $sex,
        ?Carbon $dob
    ): array {
        $presented = $results
            ->map(fn (LabResult $result) => $this->presentResult($result, $ranges, $sex, $dob))
            ->values()
            ->all();

        return [
            'id'             => (string) $order->id,
            'name'           => $order->test_name,
            'code'           => $this->blankToNull($order->test_code),
            'order_type'     => 'laboratory',
            'priority'       => $this->presentPriority($order->urgency),
            'status'         => $this->presentStatus($order->status),
            // The laboratory's own commentary on the results, if it wrote any.
            // Never a sentence this API composed about what the numbers mean.
            'result_summary' => $this->resultSummary($results),
            'ordered_at'     => $order->ordered_at?->toIso8601String(),
            'encounter_id'   => $order->visit_id !== null ? (string) $order->visit_id : null,
            'overall_flag'   => $this->overallFlag($presented),
            'result_count'   => count($presented),
            'results'        => $presented,
        ];
    }

    /**
     * The detail shape — the same order plus who ordered it and the note they
     * attached.
     *
     * @param  Collection<int, LabResult>  $results
     * @param  Collection<int, ReferenceRange>  $ranges
     * @return array<string, mixed>
     */
    protected function presentOrderDetail(
        LabOrder $order,
        Collection $results,
        Collection $ranges,
        ?string $sex,
        ?Carbon $dob,
        ?string $orderedByName
    ): array {
        $base = $this->presentOrder($order, $results, $ranges, $sex, $dob);

        $base['ordered_by_name'] = $this->blankToNull($orderedByName);
        $base['clinical_notes']  = $this->blankToNull($order->notes);

        return $base;
    }

    /**
     * Local order statuses → the four the app knows. Anything unrecognised
     * falls to `ordered`, the least-committed of the four: an unknown status
     * must never be reported as a finished, released result.
     */
    protected function presentStatus(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'resulted', 'completed', 'final', 'released', 'verified' => 'completed',
            'processing', 'collected', 'in_progress', 'in_lab', 'received', 'partial' => 'in_progress',
            'cancelled', 'canceled', 'voided', 'entered_in_error', 'rejected' => 'cancelled',
            default => 'ordered',
        };
    }

    /** Local urgency → the app's three priorities. */
    protected function presentPriority(?string $urgency): string
    {
        return match (strtolower(trim((string) $urgency))) {
            'stat', 'immediate', 'emergency' => 'stat',
            'urgent', 'priority'             => 'urgent',
            default                          => 'routine',
        };
    }

    /**
     * The order-level roll-up.
     *
     * `pending` (no results released yet) and `unflagged` (results released,
     * but nothing to compare them against) are separate from `normal`, because
     * a patient reading "Normal" on a panel nobody has interpreted has been
     * told something untrue.
     *
     * @param  array<int, array<string, mixed>>  $results
     */
    protected function overallFlag(array $results): string
    {
        if ($results === []) {
            return 'pending';
        }

        $flags = array_column($results, 'flag');

        if (in_array('critical', $flags, true)) {
            return 'critical';
        }

        foreach (['low', 'high', 'abnormal'] as $flag) {
            if (in_array($flag, $flags, true)) {
                return 'abnormal';
            }
        }

        return in_array('normal', $flags, true) ? 'normal' : 'unflagged';
    }

    /**
     * The laboratory's own notes on the individual results, joined into the
     * summary the detail and report screens show under "Interpretation".
     *
     * Only text a human actually wrote reaches this field. When the laboratory
     * attached no commentary it is null and the section is simply absent —
     * this API does not narrate a patient's results back to them.
     *
     * @param  Collection<int, LabResult>  $results
     */
    protected function resultSummary(Collection $results): ?string
    {
        $notes = $results
            ->map(function (LabResult $result) {
                $note = $this->blankToNull($result->notes);

                return $note === null ? null : trim($result->parameter_name . ': ' . $note);
            })
            ->filter()
            ->values();

        return $notes->isEmpty() ? null : $notes->implode("\n");
    }

    // ── Results ──────────────────────────────────────────────────────────────

    /**
     * One analyte, in the shape `PortalLabResult` expects.
     *
     * @param  Collection<int, ReferenceRange>  $ranges
     * @return array<string, mixed>
     */
    protected function presentResult(
        LabResult $result,
        Collection $ranges,
        ?string $sex,
        ?Carbon $dob
    ): array {
        $value   = trim((string) $result->value);
        $numeric = $this->parseNumeric($value);

        // What the LABORATORY sent with this result, verbatim.
        $reference = $this->blankToNull($result->reference_range);

        // 1. The laboratory's own bounds, read out of its own text.
        [$low, $high] = $this->parseReferenceBounds($reference);

        // 2. Only if the laboratory sent none: a published range for this
        //    analyte, this sex and this age band.
        $range = null;
        if ($low === null && $high === null) {
            $range = $this->matchReferenceRange($result, $ranges, $sex, $dob);
            if ($range !== null) {
                $low  = $range->normal_low !== null ? (float) $range->normal_low : null;
                $high = $range->normal_high !== null ? (float) $range->normal_high : null;
            }
        } else {
            // Bounds came from the laboratory, but a published row may still
            // hold the CRITICAL thresholds, which no free-text range carries.
            $range = $this->matchReferenceRange($result, $ranges, $sex, $dob);
        }

        $flag = $this->resolveFlag($result->flag, $numeric, $low, $high, $range);

        return [
            'id'            => (string) $result->id,
            'analyte'       => $result->parameter_name,
            'value'         => $value,
            'numeric_value' => $numeric,
            'unit'          => $this->blankToNull($result->unit),
            'reference'     => $reference,
            'ref_low'       => $low,
            'ref_high'      => $high,
            'flag'          => $flag,
            'is_abnormal'   => in_array($flag, self::ABNORMAL_FLAGS, true),
            // Who signed it off. Resolved by the caller where a name is
            // available; null rather than a placeholder when it is not.
            'entered_by'    => null,
            'entered_at'    => $result->resulted_at?->toIso8601String(),
        ];
    }

    /**
     * The flag, in the precedence documented at the top of this trait.
     *
     * Returns `unflagged` — never `normal` — when there is nothing to go on.
     */
    protected function resolveFlag(
        ?string $labFlag,
        ?float $numeric,
        ?float $low,
        ?float $high,
        ?ReferenceRange $range
    ): string {
        // 1. The laboratory's own verdict always wins. It saw the sample.
        $mapped = $this->mapLabFlag($labFlag);
        if ($mapped !== null) {
            return $mapped;
        }

        // Nothing numeric to compare, or nothing to compare it against.
        if ($numeric === null) {
            return 'unflagged';
        }

        // 2. A published range is the only source of critical bounds, so it is
        //    checked first even when the laboratory supplied normal bounds.
        if ($range !== null) {
            $criticalHigh = $range->critical_high !== null ? (float) $range->critical_high : null;
            $criticalLow  = $range->critical_low !== null ? (float) $range->critical_low : null;

            if ($criticalHigh !== null && $numeric > $criticalHigh) {
                return 'critical';
            }

            if ($criticalLow !== null && $numeric < $criticalLow) {
                return 'critical';
            }
        }

        // 3. Normal bounds — the laboratory's own if it sent any, else the
        //    published ones.
        if ($high !== null && $numeric > $high) {
            return 'high';
        }

        if ($low !== null && $numeric < $low) {
            return 'low';
        }

        if ($low !== null || $high !== null) {
            return 'normal';
        }

        // A value, but no range from anywhere. Say so.
        return 'unflagged';
    }

    /**
     * The laboratory's `flag` column → the app's vocabulary.
     *
     * Returns null when the column is empty OR carries a code this API does not
     * recognise. Null means "derive it"; it must never collapse to `normal`,
     * because an unrecognised code could be a critical one from a laboratory
     * whose dictionary this codebase has not met yet.
     */
    protected function mapLabFlag(?string $flag): ?string
    {
        $normalised = strtolower(trim((string) $flag));

        if ($normalised === '') {
            return null;
        }

        return match ($normalised) {
            'hh', 'll', 'aa', 'c', 'crit', 'critical', 'panic' => 'critical',
            'h', 'high', 'above'                               => 'high',
            'l', 'low', 'below'                                => 'low',
            'a', 'abn', 'abnormal', 'pos', 'positive', 'r', 'reactive' => 'abnormal',
            'n', 'norm', 'normal', 'neg', 'negative', 'nr', 'non-reactive', 'wnl' => 'normal',
            default => null,
        };
    }

    /**
     * The value as a number, or null when it is not one.
     *
     * Free-text results ("Negative", "Trace", "Not detected") are common and
     * legitimate; they are simply not chartable, and returning null is what
     * keeps them out of the trend rather than being coerced to 0.
     *
     * Accepts the comma decimal separator used in French-language reports.
     */
    protected function parseNumeric(?string $value): ?float
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        // Strip a leading comparator a laboratory may prefix ("<0.01", "> 200").
        // The number is still the honest magnitude; the comparator survives in
        // `value`, which is what the screen prints.
        $raw = preg_replace('/^\s*[<>≤≥]=?\s*/u', '', $raw) ?? $raw;
        $raw = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $raw);

        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Numeric bounds read out of the laboratory's own free-text range.
     *
     * This is reading what the laboratory sent, not inventing a range: the text
     * is already on the result. Anything the patterns do not recognise
     * ("Negative", "See comment") yields no bounds, and the free text is still
     * returned verbatim as `reference` so the screen can print it.
     *
     * @return array{0: float|null, 1: float|null}
     */
    protected function parseReferenceBounds(?string $reference): array
    {
        $text = trim((string) $reference);

        if ($text === '') {
            return [null, null];
        }

        $number = '(-?\d+(?:[.,]\d+)?)';

        // "12.0 – 16.0", "4,0-11,0", "70 to 100", "3 à 5"
        if (preg_match('/' . $number . '\s*(?:-|–|—|\.\.\.|\.\.|to|à|a)\s*' . $number . '/iu', $text, $m) === 1) {
            return [$this->parseNumeric($m[1]), $this->parseNumeric($m[2])];
        }

        // "< 200", "≤ 5.7", "less than 40"
        if (preg_match('/(?:<|≤|<=|less than|inf(?:érieur|erieur)?(?:\s*à)?)\s*' . $number . '/iu', $text, $m) === 1) {
            return [null, $this->parseNumeric($m[1])];
        }

        // "> 40", "≥ 1.0", "greater than 60"
        if (preg_match('/(?:>|≥|>=|greater than|sup(?:érieur|erieur)?(?:\s*à)?)\s*' . $number . '/iu', $text, $m) === 1) {
            return [$this->parseNumeric($m[1]), null];
        }

        return [null, null];
    }

    // ── Published reference ranges ───────────────────────────────────────────

    /**
     * Every active published range that could apply to the analytes in this
     * payload, in ONE query. Matching happens in PHP (see matchReferenceRange)
     * so a result set of any size costs one round trip, not one per analyte.
     *
     * @param  Collection<int, LabResult>  $results
     * @return Collection<int, ReferenceRange>
     */
    protected function loadReferenceRanges(Collection $results): Collection
    {
        $names = $results
            ->map(fn (LabResult $r) => $this->normaliseAnalyte($r->parameter_name))
            ->filter()
            ->unique()
            ->values();

        $loincs = $results
            ->map(fn (LabResult $r) => $this->blankToNull($r->loinc_code))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty() && $loincs->isEmpty()) {
            return collect();
        }

        return ReferenceRange::query()
            ->where('is_active', true)
            ->where(function ($query) use ($names, $loincs) {
                if ($names->isNotEmpty()) {
                    $query->orWhereIn(\Illuminate\Support\Facades\DB::raw('lower(test_name)'), $names->all());
                    $query->orWhereIn(\Illuminate\Support\Facades\DB::raw('lower(test_code)'), $names->all());
                }

                if ($loincs->isNotEmpty()) {
                    $query->orWhereIn('loinc_code', $loincs->all());
                }
            })
            ->get();
    }

    /**
     * The best published range for one result.
     *
     * A LOINC match beats a name match (a code is unambiguous, a name is not).
     * An exact sex / age-band match beats an `all` match. A range whose sex or
     * age band contradicts this patient is discarded outright rather than used
     * as an approximation — a paediatric interval applied to an adult would
     * flag healthy values as abnormal, and vice versa.
     *
     * @param  Collection<int, ReferenceRange>  $ranges
     */
    protected function matchReferenceRange(
        LabResult $result,
        Collection $ranges,
        ?string $sex,
        ?Carbon $dob
    ): ?ReferenceRange {
        if ($ranges->isEmpty()) {
            return null;
        }

        $loinc    = $this->blankToNull($result->loinc_code);
        $analyte  = $this->normaliseAnalyte($result->parameter_name);
        $sexNorm  = $sex !== null ? strtolower(trim($sex)) : null;
        $ageGroup = $dob !== null ? $this->ageGroup($dob) : null;

        $best      = null;
        $bestScore = -1;

        foreach ($ranges as $range) {
            $score = 0;

            if ($loinc !== null && $range->loinc_code === $loinc) {
                $score += 100;
            } elseif ($analyte !== null && (
                strtolower((string) $range->test_name) === $analyte
                || strtolower((string) $range->test_code) === $analyte
            )) {
                $score += 50;
            } else {
                continue; // Not this analyte at all.
            }

            $rangeSex = strtolower(trim((string) $range->sex));
            if ($rangeSex !== '' && $rangeSex !== 'all') {
                if ($sexNorm === null || $rangeSex !== $sexNorm) {
                    continue; // Written for a different sex — not applicable.
                }
                $score += 10;
            }

            $rangeAge = strtolower(trim((string) $range->age_group));
            if ($rangeAge !== '' && $rangeAge !== 'all') {
                if ($ageGroup === null || $rangeAge !== $ageGroup) {
                    continue; // Written for a different age band — not applicable.
                }
                $score += 5;
            }

            if ($score > $bestScore) {
                $best      = $range;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * The age bands the `reference_ranges` table is written in. Identical to
     * App\Services\Lab\ReferenceRangeService::ageGroup() so a range published
     * through that service is found by this one.
     */
    protected function ageGroup(Carbon $dob): string
    {
        $days   = (int) abs(now()->diffInDays($dob));
        $months = (int) abs(now()->diffInMonths($dob));
        $years  = (int) abs(now()->diffInYears($dob));

        return match (true) {
            $days <= 28   => 'neonate',
            $months <= 12 => 'infant',
            $years <= 12  => 'child',
            $years <= 18  => 'adolescent',
            $years <= 64  => 'adult',
            default       => 'geriatric',
        };
    }

    /** Case- and whitespace-insensitive analyte key, for name matching. */
    protected function normaliseAnalyte(?string $name): ?string
    {
        $normalised = strtolower(trim((string) $name));

        return $normalised === '' ? null : $normalised;
    }

    // ── Small helpers ────────────────────────────────────────────────────────

    /** An empty or whitespace-only string is an absent value, not a value. */
    protected function blankToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * The `{ data, pagination }` envelope every finder list endpoint returns.
     *
     * @param  array<int, mixed>  $rows
     * @return array<string, mixed>
     */
    protected function paginated(array $rows, int $total, int $perPage, int $page): array
    {
        return [
            // array_values, not $rows directly: a list envelope must serialise as
            // a JSON ARRAY, and a filtered $rows (see the flag filter in
            // MobileLabController) can arrive with non-sequential keys, which
            // json_encode would emit as an OBJECT — silently changing the shape
            // the client parses. It also keeps the api-response ratchet quiet,
            // whose heuristic cannot tell an already-presented array from a raw
            // Eloquent model; these rows are shaped by presentOrders() first.
            'data'       => array_values($rows),
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / max(1, $perPage))),
            ],
        ];
    }
}
