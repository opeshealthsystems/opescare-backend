<?php

namespace App\Support;

/**
 * The triage priority vocabulary — and the one rule about who is allowed to
 * pick one.
 *
 * ── ACUITY IS A CLINICIAN'S ACT ────────────────────────────────────────────
 *
 * Nothing in this class derives a priority from a number. There is no
 * `suggest()`, no `fromVitals()`, no weighting table, and that absence is the
 * design, not an omission. A triage priority computed from `triage_vital_signs`
 * would be a clinical decision taken by a function, on readings whose bands
 * (App\Support\VitalBandResolver) are ADULT bands, advisory by their own
 * doc-block, and invalid for children and in pregnancy. The desk shows the
 * nurse the readings and, where the platform honestly has a band for them, an
 * advisory per-reading flag. The nurse — a person, at the bedside, who can see
 * the patient — picks the priority. That choice is then recorded as a
 * `triage_scores` row with `scoring_system = 'manual'`, so the record itself
 * says a human assigned it.
 *
 * The five levels are the ones already written into this codebase, by
 * App\Models\TriageScore::priorityLabel() and TriageReassessment::isUpgrade().
 * They are not re-invented here; they are centralised here so that the desk,
 * the reassessment and the queue all read one list.
 */
final class TriageAcuity
{
    public const P1 = 'P1_immediate';
    public const P2 = 'P2_urgent';
    public const P3 = 'P3_less_urgent';
    public const P4 = 'P4_standard';
    public const P5 = 'P5_non_urgent';

    /**
     * Most urgent first — the order the form and the queue present.
     *
     * @return list<string>
     */
    public const LEVELS = [self::P1, self::P2, self::P3, self::P4, self::P5];

    /**
     * Ascending urgency, matching TriageReassessment::isUpgrade()'s array so
     * the two can never disagree about which way is "up".
     *
     * @return list<string>
     */
    public const ASCENDING = [self::P5, self::P4, self::P3, self::P2, self::P1];

    public static function isValid(?string $level): bool
    {
        return $level !== null && in_array($level, self::LEVELS, true);
    }

    /** The translation key for a level, or null for a value this class does not know. */
    public static function labelKey(?string $level): ?string
    {
        return self::isValid($level) ? 'triage.acuity_levels.' . $level : null;
    }

    /**
     * A row/badge severity for one level: critical | warning | info | neutral.
     * `neutral` covers a value this class does not recognise — three rows in
     * `triage_records` predate this feature and hold '3' and 'V' in
     * `acuity_score`. They are legacy data, not a vocabulary, and they get no
     * colour rather than a guessed one.
     */
    public static function severity(?string $level): string
    {
        return match ($level) {
            self::P1 => 'critical',
            self::P2 => 'warning',
            self::P3 => 'info',
            self::P4, self::P5 => 'neutral',
            default  => 'neutral',
        };
    }

    /** Ascending rank, or null when the level is not one of the five. */
    public static function rank(?string $level): ?int
    {
        $index = array_search($level, self::ASCENDING, true);

        return $index === false ? null : (int) $index;
    }

    /**
     * Did the priority go UP (more urgent)? Null when either side is a value
     * outside the vocabulary — an unknown legacy acuity is not silently
     * treated as the bottom of the scale.
     */
    public static function isEscalation(?string $previous, ?string $new): ?bool
    {
        $from = self::rank($previous);
        $to   = self::rank($new);

        return ($from === null || $to === null) ? null : $to > $from;
    }
}
