<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Services\Patient\Passport\PageSource;
use App\Support\ResultFlagResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Page 11 — Laboratory Results.
 *
 * ── THE FLAG IS NOT THIS FILE'S TO DECIDE ─────────────────────────────────
 *
 * Every high / low / critical word and every coloured pill on this page comes
 * from App\Support\ResultFlagResolver and from nowhere else. That class exists
 * because three readers of `lab_results` once classified the same row three
 * different ways, and a potassium of 8.1 mmol/L reached the patient as
 * "critical" while it reached the ordering clinician as "the laboratory
 * recorded no flags". A passport page that did its own arithmetic would be a
 * fourth reader and would reopen exactly that hole — so this page performs no
 * comparison of its own, anywhere. It asks the resolver and prints the answer.
 *
 * Two consequences of the resolver's contract are load-bearing here:
 *
 *  1. PUBLISHED RANGES ARE BANDED BY AGE AND SEX, so `matchPublished()` is
 *     handed the patient's `sex` and `date_of_birth`. Without the date of
 *     birth an adult band never matches, the published critical bounds are
 *     never consulted, and a panic value quietly reads as unflagged. The
 *     ranges are loaded once for the whole page, not once per row.
 *
 *  2. `unflagged` IS NOT `normal`. It means nobody compared the value to
 *     anything — no laboratory flag, no printed interval, no published range.
 *     Those rows get the passport's plain "Not recorded" and NO pill, because
 *     a green pill there would be a reassurance the record never gave. The "At
 *     a glance" tiles count them separately for the same reason: ten results
 *     of which three were never compared is not ten checked results.
 *
 * ── WHAT IS NEVER HIDDEN ──────────────────────────────────────────────────
 *
 * The results table keeps to a recent window — the most recent orders, taken
 * whole so a panel is never split down the middle. The CRITICAL ALERTS block
 * above it does not: it scans the patient's entire laboratory history, so no
 * row limit can ever be the reason a panic value is missing from this page.
 * That is the same rule the allergies page follows, for the same reason.
 *
 * ── WHAT THE APPROVED SHEETS SHOW THAT THIS DEPLOYMENT CANNOT SUPPLY ──────
 *
 * The sheets carry trend charts, a per-analyte time series, a result-status
 * donut, test-category counts, a signed PDF report with its bench comments and
 * biochemist signature, "View" / "Download" / "Share" actions, and a printed
 * interpretation note. None of those are the patient's record: the charts are
 * a rendering the passport block API has no shape for, the categories are not
 * recorded (`lab_orders` has no discipline column), and the note is printed
 * safety guidance rather than anything a laboratory said about this person.
 * They are left out rather than approximated.
 */
class LaboratoryPage extends PageSource
{
    /**
     * The resolver's four severities → the design's pill tones. `info` is the
     * resolver's word for "nothing was compared", and it deliberately gets no
     * pill at all rather than a neutral-looking one.
     */
    private const TONES = [
        'critical' => 'danger',
        'warning'  => 'warning',
        'success'  => 'success',
        'info'     => null,
    ];

    public function build(Patient $patient): array
    {
        // The whole laboratory history, panels intact. It is read in full
        // because the critical-alert scan below must not be able to miss a
        // panic value that fell outside a recent window.
        $orders = LabOrder::with(['results' => fn ($q) => $q->orderBy('created_at')])
            ->where('patient_id', $patient->id)
            ->orderByRaw('COALESCE(resulted_at, collected_at, ordered_at) DESC NULLS LAST')
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $analytes = $orders->flatMap(fn (LabOrder $order) => $order->results);

        // One resolution per analyte, computed once and reused by all three
        // blocks, so the same row cannot read one way in the alert table and
        // another way in the results table.
        $flags = $this->resolveAll($analytes, $patient);

        $sections = [];

        if ($analytes->isNotEmpty()) {
            $sections[] = $this->glance($analytes, $flags);
        }

        $critical = $this->criticalRows($orders, $flags);

        if ($critical !== []) {
            $sections[] = $this->table(
                __('passport.sections.critical_alerts'),
                [
                    __('passport.columns.test'),
                    __('passport.columns.result'),
                    __('passport.columns.range'),
                    __('passport.columns.reported'),
                ],
                $critical,
                'red',
                'alert-triangle'
            );
        }

        $recent = $orders->take(self::RECENT_LIMIT);

        $results = $this->resultRows($recent, $flags);

        if ($results !== []) {
            $sections[] = $this->table(
                __('passport.sections.recent_results'),
                [
                    __('passport.columns.test'),
                    __('passport.columns.result'),
                    __('passport.columns.unit'),
                    __('passport.columns.range'),
                    __('passport.columns.flag'),
                    __('passport.columns.reported'),
                ],
                $results,
                'blue',
                'flask'
            );
        }

        $sections[] = $this->table(
            __('passport.sections.lab_orders'),
            [
                __('passport.columns.test'),
                __('passport.columns.ordered'),
                __('passport.columns.reported'),
                __('passport.columns.status'),
            ],
            $this->orderRows($recent, $flags),
            'teal',
            'clipboard'
        );

        return $sections;
    }

    // ── Flags ────────────────────────────────────────────────────────────

    /**
     * The resolver's verdict on every analyte, keyed by result id.
     *
     * The published ranges are fetched in ONE query for the whole page and the
     * match is made in memory by the resolver, exactly as the clinician's
     * results inbox does it — the two surfaces must agree, and a list must not
     * become a query per row.
     *
     * @param  Collection<int,LabResult>  $analytes
     * @return array<string,array<string,mixed>>
     */
    private function resolveAll(Collection $analytes, Patient $patient): array
    {
        if ($analytes->isEmpty()) {
            return [];
        }

        $ranges = $this->publishedRanges($analytes);

        // Age band and sex both narrow which published range applies. The date
        // of birth is decrypted by the model's accessor; handing it over is
        // what lets an adult band — and with it the critical bounds — match at
        // all.
        $sex = $patient->sex ?? null;
        $dob = $patient->date_of_birth ?? null;

        $resolved = [];

        foreach ($analytes as $analyte) {
            $published = ResultFlagResolver::matchPublished($analyte, $ranges, $sex, $dob);

            $resolved[$analyte->id] = ResultFlagResolver::forResult($analyte, $published);
        }

        return $resolved;
    }

    /**
     * The published ranges that could apply to these analytes, in one query.
     *
     * @param  Collection<int,LabResult>  $analytes
     * @return Collection<int,ReferenceRange>
     */
    private function publishedRanges(Collection $analytes): Collection
    {
        $names = $analytes
            ->map(fn (LabResult $r) => strtolower(trim((string) $r->parameter_name)))
            ->filter()
            ->unique()
            ->values();

        $loincs = $analytes
            ->map(fn (LabResult $r) => trim((string) $r->loinc_code))
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
                    $query->orWhereIn(DB::raw('lower(test_name)'), $names->all());
                    $query->orWhereIn(DB::raw('lower(test_code)'), $names->all());
                }

                if ($loincs->isNotEmpty()) {
                    $query->orWhereIn('loinc_code', $loincs->all());
                }
            })
            ->get();
    }

    /** The design's pill tone for a resolver flag — the resolver's own severity, recoloured. */
    private function tone(string $flag): ?string
    {
        return self::TONES[ResultFlagResolver::severity($flag)] ?? null;
    }

    /** The resolver's flag in the platform's own laboratory vocabulary. */
    private function flagLabel(string $flag): string
    {
        return __('labs.flag.' . $flag);
    }

    // ── Blocks ───────────────────────────────────────────────────────────

    /**
     * The sheet's opening tiles. The breakdown is the resolver's answers
     * counted, never a re-reading of the flag column: `critical` is separated
     * from the other abnormals because it is the one that needs someone today,
     * and `unflagged` is separated from `normal` because they are different
     * claims. A category with nothing in it is left off rather than printed as
     * a reassuring zero.
     *
     * @param  Collection<int,LabResult>            $analytes
     * @param  array<string,array<string,mixed>>    $flags
     */
    private function glance(Collection $analytes, array $flags): array
    {
        $counts = [
            ResultFlagResolver::CRITICAL  => 0,
            'abnormal'                    => 0,
            ResultFlagResolver::NORMAL    => 0,
            ResultFlagResolver::UNFLAGGED => 0,
        ];

        foreach ($analytes as $analyte) {
            $flag = $flags[$analyte->id]['flag'] ?? ResultFlagResolver::UNFLAGGED;

            $bucket = match (true) {
                $flag === ResultFlagResolver::CRITICAL => ResultFlagResolver::CRITICAL,
                ResultFlagResolver::isAbnormal($flag)  => 'abnormal',
                $flag === ResultFlagResolver::NORMAL   => ResultFlagResolver::NORMAL,
                default                                => ResultFlagResolver::UNFLAGGED,
            };

            $counts[$bucket]++;
        }

        $tiles = [
            ['value' => $analytes->count(), 'label' => __('passport.stats.results')],
        ];

        if ($counts[ResultFlagResolver::CRITICAL] > 0) {
            $tiles[] = [
                'value' => $counts[ResultFlagResolver::CRITICAL],
                'label' => $this->flagLabel(ResultFlagResolver::CRITICAL),
                'tone'  => 'danger',
            ];
        }

        if ($counts['abnormal'] > 0) {
            $tiles[] = [
                'value' => $counts['abnormal'],
                'label' => $this->flagLabel(ResultFlagResolver::ABNORMAL),
                'tone'  => 'warning',
            ];
        }

        if ($counts[ResultFlagResolver::NORMAL] > 0) {
            $tiles[] = [
                'value' => $counts[ResultFlagResolver::NORMAL],
                'label' => $this->flagLabel(ResultFlagResolver::NORMAL),
                'tone'  => 'success',
            ];
        }

        if ($counts[ResultFlagResolver::UNFLAGGED] > 0) {
            $tiles[] = [
                'value' => $counts[ResultFlagResolver::UNFLAGGED],
                'label' => $this->flagLabel(ResultFlagResolver::UNFLAGGED),
            ];
        }

        return $this->stats(__('passport.sections.summary'), $tiles, 'blue', 'flask');
    }

    /**
     * Every critical analyte in the record, newest first and never truncated.
     * The value itself carries the red pill, because the value is what the
     * reader has to act on.
     *
     * Walked panel by panel rather than over the flattened analytes so the
     * release date can fall back to the order's own without lazy-loading a
     * relation per row.
     *
     * @param  Collection<int,LabOrder>           $orders
     * @param  array<string,array<string,mixed>>  $flags
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function criticalRows(Collection $orders, array $flags): array
    {
        $rows = [];

        foreach ($orders as $order) {
            foreach ($order->results as $analyte) {
                $resolution = $flags[$analyte->id] ?? null;

                if (($resolution['flag'] ?? null) !== ResultFlagResolver::CRITICAL) {
                    continue;
                }

                $rows[] = [
                    $this->cell((string) $analyte->parameter_name),
                    $this->cell($this->measurement($analyte), 'danger'),
                    $this->cell($this->range($analyte, $resolution)),
                    $this->cell($this->date($analyte->resulted_at ?: $order->resulted_at)),
                ];
            }
        }

        return $rows;
    }

    /**
     * The analyte table, panel by panel, most recent panel first. Rows keep
     * the order the laboratory released them in so a full blood count still
     * reads as a full blood count.
     *
     * @param  Collection<int,LabOrder>           $orders
     * @param  array<string,array<string,mixed>>  $flags
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function resultRows(Collection $orders, array $flags): array
    {
        $rows = [];

        foreach ($orders as $order) {
            foreach ($order->results as $analyte) {
                $resolution = $flags[$analyte->id] ?? null;
                $flag       = $resolution['flag'] ?? ResultFlagResolver::UNFLAGGED;

                $rows[] = [
                    $this->cell((string) $analyte->parameter_name),
                    $this->cell((string) $analyte->value),
                    $this->cell((string) $analyte->unit),
                    $this->cell($this->range($analyte, $resolution)),
                    // "Nothing was compared" prints as the passport's own
                    // blank, with no pill. It is not a normal result.
                    $flag === ResultFlagResolver::UNFLAGGED
                        ? $this->cell('')
                        : $this->cell($this->flagLabel($flag), $this->tone($flag)),
                    $this->cell($this->date($analyte->resulted_at ?: $order->resulted_at)),
                ];
            }
        }

        return $rows;
    }

    /**
     * The panels themselves — what was asked for, when, and how the panel
     * came back. The sheet's history table reads its Status column as the
     * result, not as the workflow state, so that is what this shows: the worst
     * flag the resolver returned for any analyte on the panel.
     *
     * An order the record has cancelled or voided says so instead, and one
     * with nothing released yet says it is awaiting results — neither of which
     * is a statement about a value.
     *
     * @param  Collection<int,LabOrder>           $orders
     * @param  array<string,array<string,mixed>>  $flags
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function orderRows(Collection $orders, array $flags): array
    {
        return $orders->map(fn (LabOrder $order) => [
            $this->cell((string) $order->test_name),
            $this->cell($this->date($order->ordered_at)),
            $this->cell($this->date($order->resulted_at)),
            $this->panelStatus($order, $flags),
        ])->values()->all();
    }

    /**
     * @param  array<string,array<string,mixed>>  $flags
     * @return array{v:string,pill?:string}
     */
    private function panelStatus(LabOrder $order, array $flags): array
    {
        if ($order->isTerminal()) {
            return $this->cell(
                $this->statusLabel((string) $order->status),
                $this->statusTone((string) $order->status)
            );
        }

        $flag = $this->worstFlag($order, $flags);

        if ($flag === null) {
            // The platform's own word for an order with no released results.
            return $this->cell(__('labs.flag.pending'));
        }

        return $flag === ResultFlagResolver::UNFLAGGED
            ? $this->cell($this->flagLabel($flag))
            : $this->cell($this->flagLabel($flag), $this->tone($flag));
    }

    /**
     * The worst thing the resolver said about this panel, or null when nothing
     * has been released against it.
     *
     * The precedence is the results inbox's own — one critical analyte makes
     * the whole panel critical, and "the laboratory said normal" is claimed
     * only when it actually said so. No value is compared here; every input is
     * a flag the resolver already returned.
     *
     * @param  array<string,array<string,mixed>>  $flags
     */
    private function worstFlag(LabOrder $order, array $flags): ?string
    {
        if ($order->results->isEmpty()) {
            return null;
        }

        $sawAbnormal = false;
        $sawNormal   = false;

        foreach ($order->results as $analyte) {
            $flag = $flags[$analyte->id]['flag'] ?? ResultFlagResolver::UNFLAGGED;

            if ($flag === ResultFlagResolver::CRITICAL) {
                return ResultFlagResolver::CRITICAL;
            }

            if (ResultFlagResolver::isAbnormal($flag)) {
                $sawAbnormal = true;
            } elseif ($flag === ResultFlagResolver::NORMAL) {
                $sawNormal = true;
            }
        }

        return match (true) {
            $sawAbnormal => ResultFlagResolver::ABNORMAL,
            $sawNormal   => ResultFlagResolver::NORMAL,
            default      => ResultFlagResolver::UNFLAGGED,
        };
    }

    // ── Cells ────────────────────────────────────────────────────────────

    /** The value with its unit, for the alert table's single value column. */
    private function measurement(LabResult $analyte): string
    {
        return trim(((string) $analyte->value) . ' ' . ((string) $analyte->unit));
    }

    /**
     * The interval this result was read against.
     *
     * The laboratory's own printed text wins, verbatim — it is what the
     * laboratory sent. Only when the laboratory sent none, and the resolver
     * fell back to a published range, are those bounds shown instead: a
     * derived flag with no visible interval beside it is unexplainable, and
     * the published row is a real record, not a guess. Where neither exists
     * the cell is honestly blank.
     *
     * @param  array<string,mixed>|null  $resolution
     */
    private function range(LabResult $analyte, ?array $resolution): string
    {
        $printed = trim((string) $analyte->reference_range);

        if ($printed !== '') {
            return $printed;
        }

        if (($resolution['ref_source'] ?? null) !== ResultFlagResolver::SOURCE_PUBLISHED) {
            return '';
        }

        $low  = $resolution['ref_low'] ?? null;
        $high = $resolution['ref_high'] ?? null;

        return match (true) {
            $low !== null && $high !== null => $this->number($low) . ' – ' . $this->number($high),
            $high !== null                  => '≤ ' . $this->number($high),
            $low !== null                   => '≥ ' . $this->number($low),
            default                         => '',
        };
    }

    /** A bound printed without the decimal padding the column stores it with. */
    private function number(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

        return $text === '' || $text === '-' ? '0' : $text;
    }
}
