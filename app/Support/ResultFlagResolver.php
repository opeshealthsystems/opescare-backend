<?php

namespace App\Support;

use App\Models\LabResult;
use App\Models\ReferenceRange;

/**
 * THE ONE place that decides what flag a laboratory result carries — and,
 * just as importantly, WHO SAID SO.
 *
 * Three readers used to classify the same `lab_results` row three different
 * ways: the lab desk stored whatever the bench typed and nothing else, the
 * clinician's results inbox refused to derive anything, and the patient's
 * screen derived a flag from the published reference ranges. A potassium of
 * 6.9 mmol/L with no bench flag therefore reached the patient as "critical"
 * and the ordering clinician as "info — the laboratory recorded no flags".
 * Every reader now calls this class, so the same row means the same thing on
 * every surface.
 *
 * ── THE CONTRACT ──────────────────────────────────────────────────────────
 *
 *     resolve(labFlag, value, labReference, published, storedSource)
 *       → ['flag' => …, 'source' => 'lab'|'range'|'published'|null, …]
 *
 * `flag` is one of critical | high | low | abnormal | normal | unflagged.
 * `source` says where it came from, and a caller MUST carry it to the wire
 * — a flag this class derived is never to be presented as the laboratory's
 * own verdict:
 *
 *   lab        the laboratory recorded it (the bench typed it, or the
 *              analyser sent it). It always wins; it saw the sample.
 *   range      derived from the laboratory's OWN free-text reference
 *              interval ("3.5 - 5.1") — reading what the laboratory sent,
 *              not inventing a range.
 *   published  derived from a published `reference_ranges` row for this
 *              analyte, sex and age band. This is the ONLY source that
 *              carries critical bounds, so it is the only source that can
 *              raise a value to `critical` on its own.
 *   null       `unflagged`: nothing recorded and nothing to compare against.
 *              Never `normal` — "the lab did not say" and "the lab said
 *              normal" are different claims.
 *
 * Precedence, in order:
 *   1. the laboratory's own flag;
 *   2. the published CRITICAL bounds (checked before the laboratory's normal
 *      bounds, because a free-text interval never carries a panic limit);
 *   3. the laboratory's own free-text bounds → high / low / normal;
 *   4. the published normal bounds → high / low / normal;
 *   5. unflagged.
 *
 * A flag the lab desk DERIVED at entry time is stored on the row together
 * with `lab_results.flag_source`; on a later read this class honours that
 * stored source (`storedSource`) rather than calling the derived flag "lab".
 *
 * Nothing here queries the database. The published range is handed in by the
 * caller (App\Services\Lab\ReferenceRangeService matches it), so the same
 * decision is testable without a row and cheap inside a loop.
 */
final class ResultFlagResolver
{
    public const CRITICAL  = 'critical';
    public const HIGH      = 'high';
    public const LOW       = 'low';
    public const ABNORMAL  = 'abnormal';
    public const NORMAL    = 'normal';
    public const UNFLAGGED = 'unflagged';

    /** The contract's flag vocabulary. */
    public const FLAGS = [self::CRITICAL, self::HIGH, self::LOW, self::ABNORMAL, self::NORMAL, self::UNFLAGGED];

    /**
     * Flags that mean "outside what was expected". `unflagged` is deliberately
     * NOT here and deliberately NOT `normal` — it means "no comparison was
     * made", which is a third thing.
     */
    public const ABNORMAL_FLAGS = [self::LOW, self::HIGH, self::ABNORMAL, self::CRITICAL];

    public const SOURCE_LAB       = 'lab';
    public const SOURCE_RANGE     = 'range';
    public const SOURCE_PUBLISHED = 'published';

    /** Every source a flag can carry; what `lab_results.flag_source` may hold. */
    public const SOURCES = [self::SOURCE_LAB, self::SOURCE_RANGE, self::SOURCE_PUBLISHED];

    /** The two sources that mean "this codebase derived it". */
    public const DERIVED_SOURCES = [self::SOURCE_RANGE, self::SOURCE_PUBLISHED];

    /**
     * Resolve one result.
     *
     * @param  string|null          $labFlag       `lab_results.flag`, in any spelling this platform has met
     * @param  string|null          $value         `lab_results.value`, verbatim
     * @param  string|null          $labReference  `lab_results.reference_range`, the laboratory's own free text
     * @param  ReferenceRange|null  $published     the matched published range, if any
     * @param  string|null          $storedSource  `lab_results.flag_source` — honoured when it names a derived source
     * @return array{
     *     flag: string,
     *     source: string|null,
     *     numeric: float|null,
     *     ref_low: float|null,
     *     ref_high: float|null,
     *     ref_source: string|null,
     *     critical_low: float|null,
     *     critical_high: float|null,
     * }
     */
    public static function resolve(
        ?string $labFlag,
        ?string $value,
        ?string $labReference,
        ?ReferenceRange $published = null,
        ?string $storedSource = null
    ): array {
        $numeric = self::parseNumeric($value);

        // The bounds the screen prints: the laboratory's own text first, the
        // published interval only when the laboratory sent none.
        [$labLow, $labHigh] = self::parseReferenceBounds($labReference);
        $refSource = ($labLow !== null || $labHigh !== null) ? self::SOURCE_RANGE : null;
        $low       = $labLow;
        $high      = $labHigh;

        $pubLow  = $published?->normal_low !== null ? (float) $published->normal_low : null;
        $pubHigh = $published?->normal_high !== null ? (float) $published->normal_high : null;

        if ($refSource === null && ($pubLow !== null || $pubHigh !== null)) {
            $refSource = self::SOURCE_PUBLISHED;
            $low       = $pubLow;
            $high      = $pubHigh;
        }

        $criticalLow  = $published?->critical_low !== null ? (float) $published->critical_low : null;
        $criticalHigh = $published?->critical_high !== null ? (float) $published->critical_high : null;

        $shape = static fn (string $flag, ?string $source): array => [
            'flag'          => $flag,
            'source'        => $source,
            'numeric'       => $numeric,
            'ref_low'       => $low,
            'ref_high'      => $high,
            'ref_source'    => $refSource,
            'critical_low'  => $criticalLow,
            'critical_high' => $criticalHigh,
        ];

        // 1. The laboratory's own verdict always wins. A flag the desk derived
        //    and stored keeps the source it was stored with.
        $mapped = self::fromLabFlag($labFlag);
        if ($mapped !== null) {
            $stored = strtolower(trim((string) $storedSource));

            return $shape($mapped, in_array($stored, self::DERIVED_SOURCES, true) ? $stored : self::SOURCE_LAB);
        }

        // Nothing numeric to compare.
        if ($numeric === null) {
            return $shape(self::UNFLAGGED, null);
        }

        // 2. Published critical bounds — the only source of a panic limit.
        if (($criticalHigh !== null && $numeric > $criticalHigh)
            || ($criticalLow !== null && $numeric < $criticalLow)) {
            return $shape(self::CRITICAL, self::SOURCE_PUBLISHED);
        }

        // 3. The laboratory's own normal bounds.
        if ($labLow !== null || $labHigh !== null) {
            return $shape(self::compare($numeric, $labLow, $labHigh), self::SOURCE_RANGE);
        }

        // 4. The published normal bounds.
        if ($pubLow !== null || $pubHigh !== null) {
            return $shape(self::compare($numeric, $pubLow, $pubHigh), self::SOURCE_PUBLISHED);
        }

        // 5. A value, but no range from anywhere. Say so.
        return $shape(self::UNFLAGGED, null);
    }

    /**
     * Resolve a stored row. Reads `flag`, `flag_source`, `value` and
     * `reference_range` off the model; the caller supplies the published range.
     *
     * @return array<string, mixed>  see resolve()
     */
    public static function forResult(LabResult $result, ?ReferenceRange $published = null): array
    {
        return self::resolve(
            $result->flag,
            $result->value,
            $result->reference_range,
            $published,
            $result->getAttribute('flag_source')
        );
    }

    // ── Vocabulary ───────────────────────────────────────────────────────

    /**
     * The laboratory's `flag` column → the contract's vocabulary.
     *
     * Returns null when the column is empty OR carries a code this platform
     * does not recognise. Null means "derive it"; it must never collapse to
     * `normal`, because an unrecognised code could be a critical one from a
     * laboratory whose dictionary this codebase has not met yet.
     */
    public static function fromLabFlag(?string $flag): ?string
    {
        $normalised = strtolower(trim((string) $flag));

        if ($normalised === '') {
            return null;
        }

        return match ($normalised) {
            'hh', 'll', 'aa', 'c', 'crit', 'critical', 'panic'                     => self::CRITICAL,
            'h', 'high', 'above'                                                   => self::HIGH,
            'l', 'low', 'below'                                                    => self::LOW,
            'a', 'abn', 'abnormal', 'pos', 'positive', 'r', 'reactive'             => self::ABNORMAL,
            'n', 'norm', 'normal', 'neg', 'negative', 'nr', 'non-reactive', 'wnl'  => self::NORMAL,
            default                                                                => null,
        };
    }

    /**
     * The contract's flag → what the lab desk writes to `lab_results.flag`.
     *
     * `CRITICAL` rather than a bare `C` because both readers of the column
     * (the inbox and the patient's screen) recognise that spelling and a bare
     * `C` used to reach the inbox as "unclassified". Null for `unflagged`: a
     * derivation that found nothing to say writes nothing.
     */
    public static function toStoredFlag(string $flag): ?string
    {
        return match ($flag) {
            self::CRITICAL => 'CRITICAL',
            self::HIGH     => 'H',
            self::LOW      => 'L',
            self::ABNORMAL => 'A',
            self::NORMAL   => 'N',
            default        => null,
        };
    }

    /** The lab desk's H | L | N | C for a contract flag, or null for `unflagged`. */
    public static function toDeskFlag(string $flag): ?string
    {
        return match ($flag) {
            self::CRITICAL => 'C',
            self::HIGH     => 'H',
            self::LOW      => 'L',
            self::ABNORMAL => 'A',
            self::NORMAL   => 'N',
            default        => null,
        };
    }

    /**
     * The results inbox's row severity for one flag: critical | warning |
     * success | info. `info` is "not recorded", which is NOT `success`.
     */
    public static function severity(string $flag): string
    {
        return match ($flag) {
            self::CRITICAL                            => 'critical',
            self::HIGH, self::LOW, self::ABNORMAL     => 'warning',
            self::NORMAL                              => 'success',
            default                                   => 'info',
        };
    }

    public static function isAbnormal(string $flag): bool
    {
        return in_array($flag, self::ABNORMAL_FLAGS, true);
    }

    /**
     * Which way a CRITICAL value went — `high` | `low` — for the alert row,
     * or null when nothing recorded says. HH / LL carry it themselves; a bare
     * "critical" is placed against whatever bounds exist.
     *
     * @param  array<string, mixed>  $resolution  from resolve()
     */
    public static function direction(array $resolution, ?string $labFlag = null): ?string
    {
        $spelling = strtolower(trim((string) $labFlag));
        if ($spelling === 'hh') {
            return self::HIGH;
        }
        if ($spelling === 'll') {
            return self::LOW;
        }

        $numeric = $resolution['numeric'] ?? null;
        if ($numeric === null) {
            return null;
        }

        foreach ([['critical_high', 'critical_low'], ['ref_high', 'ref_low']] as [$highKey, $lowKey]) {
            $high = $resolution[$highKey] ?? null;
            $low  = $resolution[$lowKey] ?? null;

            if ($high !== null && $numeric > $high) {
                return self::HIGH;
            }
            if ($low !== null && $numeric < $low) {
                return self::LOW;
            }
        }

        return null;
    }

    // ── Parsing ──────────────────────────────────────────────────────────

    /**
     * The value as a number, or null when it is not one.
     *
     * Free-text results ("Negative", "Trace", "Not detected") are common and
     * legitimate; they are simply not comparable, and returning null keeps
     * them out of any comparison rather than coercing them to 0. A leading
     * comparator ("<0.01", "> 200") is stripped: the number is still the
     * honest magnitude and the comparator survives in the printed value.
     * Accepts the comma decimal separator used in French-language reports.
     */
    public static function parseNumeric(?string $value): ?float
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/^\s*[<>≤≥]=?\s*/u', '', $raw) ?? $raw;
        $raw = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $raw);

        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Numeric bounds read out of the laboratory's own free-text range.
     *
     * Reading what the laboratory sent, not inventing a range: the text is
     * already on the result. Anything the patterns do not recognise
     * ("Negative", "See comment") yields no bounds.
     *
     * @return array{0: float|null, 1: float|null}
     */
    public static function parseReferenceBounds(?string $reference): array
    {
        $text = trim((string) $reference);

        if ($text === '') {
            return [null, null];
        }

        $number = '(-?\d+(?:[.,]\d+)?)';

        // "12.0 – 16.0", "4,0-11,0", "70 to 100", "3 à 5"
        if (preg_match('/' . $number . '\s*(?:-|–|—|\.\.\.|\.\.|to|à|a)\s*' . $number . '/iu', $text, $m) === 1) {
            return [self::parseNumeric($m[1]), self::parseNumeric($m[2])];
        }

        // "< 200", "≤ 5.7", "less than 40"
        if (preg_match('/(?:<|≤|<=|less than|inf(?:érieur|erieur)?(?:\s*à)?)\s*' . $number . '/iu', $text, $m) === 1) {
            return [null, self::parseNumeric($m[1])];
        }

        // "> 40", "≥ 1.0", "greater than 60"
        if (preg_match('/(?:>|≥|>=|greater than|sup(?:érieur|erieur)?(?:\s*à)?)\s*' . $number . '/iu', $text, $m) === 1) {
            return [self::parseNumeric($m[1]), null];
        }

        return [null, null];
    }

    /** A number against a pair of bounds (either may be null). */
    private static function compare(float $numeric, ?float $low, ?float $high): string
    {
        if ($high !== null && $numeric > $high) {
            return self::HIGH;
        }

        if ($low !== null && $numeric < $low) {
            return self::LOW;
        }

        return self::NORMAL;
    }
}
