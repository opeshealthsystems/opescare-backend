<?php

namespace App\Support;

use App\Enums\SelfReportedVitalMeasure;

/**
 * THE ONE place that decides whether a VITAL SIGN is outside its band — and,
 * just as importantly, WHO SAID SO.
 *
 * This is the vital-signs counterpart of App\Support\ResultFlagResolver, and it
 * exists for the same reason. The laboratory resolver was written after three
 * readers of `lab_results` classified the same potassium three different ways
 * and the person who could act on it was the one not told. Vitals were one step
 * away from the same failure: the only adult bands in this codebase were
 * PRIVATE methods on App\Http\Controllers\Api\Mobile\MobileVitalsController
 * (`classify()`, `bloodPressureStatus()`, `glucoseStatus()`), reachable by
 * nothing else, so every other surface either copied them — a second,
 * independently drifting reader — or, like the Health Passport's vitals page,
 * showed no interpretation at all. Both are now unnecessary: every surface asks
 * this class and prints its answer.
 *
 * ── THE CONTRACT ──────────────────────────────────────────────────────────
 *
 *     resolve(measure, value, secondary, unit)
 *       → ['band' => …, 'source' => 'adult_bands'|'lab'|null, …]
 *
 * `band` is one of critical | high | low | abnormal | normal | unknown.
 * `source` says where the judgement came from, and a caller MUST carry it —
 * a band this class derived is never to be presented as a clinician's reading:
 *
 *   adult_bands  derived here, from the published ADULT bands documented
 *                against each measure below. It is advisory: it says "a human
 *                should look at this", never "this patient is in danger".
 *   lab          a laboratory recorded the flag itself (blood sugar only —
 *                the only vital on this platform that arrives from a bench).
 *                It always wins; the laboratory saw the sample.
 *   null         `unknown`: NO OPINION. Nothing was compared, so nothing is
 *                claimed. Never `normal` — "this platform has no band for
 *                this measure" and "this value is normal" are different
 *                claims, and only one of them is reassuring.
 *
 * ── THE RULES THAT KEEP IT HONEST ─────────────────────────────────────────
 *
 *  1. A MEASURE WITH NO BAND RETURNS `unknown`. Weight is the worked example:
 *     a body weight has no adult normal range without a height, and neither
 *     vitals table is read with one on the surfaces that call this class. So
 *     weight gets no verdict rather than a plausible-looking one.
 *
 *  2. NO THRESHOLD IS INVENTED. Every number below is sourced in the comment
 *     beside it. A measure this platform meets but has no published band for
 *     is added to `resolve()` returning `unknown`, not given a guess.
 *
 *  3. A UNIT THIS CLASS DOES NOT RECOGNISE RETURNS `unknown`. A glucose of 5.6
 *     is normal in mmol/L and incompatible with life in mg/dL; a temperature of
 *     98.6 is normal in °F and unsurvivable in °C. Where the unit decides the
 *     meaning, an unreadable unit means no opinion.
 *
 *  4. THESE ARE ADULT BANDS. Paediatric vital-sign ranges are banded by age —
 *     a respiratory rate of 30 is an emergency in an adult and unremarkable in
 *     an infant — and NEWS2, from which most of the escalation boundaries here
 *     are taken, is explicitly for patients aged 16 and over and not for
 *     pregnancy. This class does not attempt paediatric bands and does not
 *     pretend to: that is what `source => 'adult_bands'` says on every verdict
 *     it returns, and a surface that knows it is showing a child's record must
 *     not present the verdict as a paediatric interpretation. Implementing
 *     paediatric bands means age-banded tables per measure, not a coefficient.
 *
 * Nothing here queries the database, exactly as ResultFlagResolver holds: the
 * value, its partner and its unit are handed in, so the same decision is
 * testable without a row and cheap inside a loop.
 */
final class VitalBandResolver
{
    public const CRITICAL = 'critical';
    public const HIGH     = 'high';
    public const LOW      = 'low';
    public const ABNORMAL = 'abnormal';
    public const NORMAL   = 'normal';

    /**
     * No opinion. The counterpart of ResultFlagResolver::UNFLAGGED, and spelled
     * `unknown` because that is the word MobileVitalsController has always put
     * on the wire for it and the mobile app already reads it.
     */
    public const UNKNOWN = 'unknown';

    /** The contract's band vocabulary. */
    public const BANDS = [self::CRITICAL, self::HIGH, self::LOW, self::ABNORMAL, self::NORMAL, self::UNKNOWN];

    /**
     * Bands that mean "outside what was expected". `unknown` is deliberately
     * NOT here and deliberately NOT `normal` — it means no comparison was made,
     * which is a third thing.
     */
    public const ALARMING_BANDS = [self::LOW, self::HIGH, self::ABNORMAL, self::CRITICAL];

    /** Derived here, from the adult bands documented below. */
    public const SOURCE_ADULT = 'adult_bands';

    /** Recorded by a laboratory. Blood sugar only. */
    public const SOURCE_LAB = 'lab';

    public const SOURCES = [self::SOURCE_ADULT, self::SOURCE_LAB];

    // ── The measures this class knows by name ────────────────────────────

    public const HEART_RATE        = 'heart_rate';
    public const BLOOD_PRESSURE    = 'blood_pressure';
    public const OXYGEN_SATURATION = 'oxygen_saturation';
    public const TEMPERATURE       = 'temperature';
    public const RESPIRATORY_RATE  = 'respiratory_rate';
    public const BLOOD_SUGAR       = 'blood_sugar';
    public const WEIGHT            = 'weight';

    /**
     * The spellings this platform's own tables and enums use for the same
     * measure, mapped to the canonical key.
     *
     * `triage_vital_signs` calls a pulse `pulse_rate`, `vital_signs` calls it
     * `pulse`, MobileVitalsController puts `heart_rate` on the wire and
     * SelfReportedVitalMeasure has no case for it at all. This is a map of
     * names this codebase actually writes — not a fuzzy match. Anything not in
     * it is unrecognised and gets no opinion.
     */
    private const ALIASES = [
        'heart_rate'         => self::HEART_RATE,
        'pulse'              => self::HEART_RATE,
        'pulse_rate'         => self::HEART_RATE,
        'blood_pressure'     => self::BLOOD_PRESSURE,
        'bp'                 => self::BLOOD_PRESSURE,
        'oxygen_saturation'  => self::OXYGEN_SATURATION,
        'spo2'               => self::OXYGEN_SATURATION,
        'temperature'        => self::TEMPERATURE,
        'respiratory_rate'   => self::RESPIRATORY_RATE,
        'respiration_rate'   => self::RESPIRATORY_RATE,
        'blood_sugar'        => self::BLOOD_SUGAR,
        'glucose'            => self::BLOOD_SUGAR,
        'weight'             => self::WEIGHT,
        'weight_kg'          => self::WEIGHT,
    ];

    /**
     * Measures this platform records and this class deliberately has NO band
     * for. Listed rather than left to the `default` arm so that "we thought
     * about weight and refused" is visible in the code, not inferred from its
     * absence.
     *
     *   weight  — a body weight is only interpretable against a height (BMI),
     *             an age, or the patient's own previous weights. The surfaces
     *             that call this class read none of those alongside it, and a
     *             weight banded without them would be meaningless at best.
     */
    private const NO_BAND = [self::WEIGHT];

    /**
     * Resolve one reading.
     *
     * @param  string|SelfReportedVitalMeasure  $measure    a canonical key, one of the ALIASES, or the enum
     * @param  mixed                            $value      the primary number, in any form the column holds
     * @param  mixed                            $secondary  the diastolic, for blood pressure only
     * @param  string|null                      $unit       null means "the unit this platform's own column is
     *                                                      defined in"; an unrecognised unit means no opinion
     * @return array{
     *     band: string,
     *     source: string|null,
     *     measure: string|null,
     *     numeric: float|null,
     *     secondary: float|null,
     * }
     */
    public static function resolve(
        string|SelfReportedVitalMeasure $measure,
        mixed $value,
        mixed $secondary = null,
        ?string $unit = null
    ): array {
        $key       = self::measureKey($measure);
        $primary   = self::parseNumeric($value);
        $companion = self::parseNumeric($secondary);

        $shape = static fn (string $band, ?string $source): array => [
            'band'      => $band,
            'source'    => $source,
            'measure'   => $key,
            'numeric'   => $primary,
            'secondary' => $companion,
        ];

        // A measure this codebase does not write, or one it writes and has no
        // band for. Both are "no opinion" — never a guess, never `normal`.
        if ($key === null || in_array($key, self::NO_BAND, true)) {
            return $shape(self::UNKNOWN, null);
        }

        if ($primary === null) {
            return $shape(self::UNKNOWN, null);
        }

        $band = match ($key) {
            self::HEART_RATE        => self::heartRate($primary, $unit),
            self::BLOOD_PRESSURE    => self::bloodPressure($primary, $companion, $unit),
            self::OXYGEN_SATURATION => self::oxygenSaturation($primary, $unit),
            self::TEMPERATURE       => self::temperature($primary, $unit),
            self::RESPIRATORY_RATE  => self::respiratoryRate($primary, $unit),
            self::BLOOD_SUGAR       => self::bloodSugar($primary, $unit),
            default                 => self::UNKNOWN,
        };

        return $shape($band, $band === self::UNKNOWN ? null : self::SOURCE_ADULT);
    }

    /**
     * Blood sugar as a laboratory published it.
     *
     * The laboratory's own flag always wins, exactly as it does for every other
     * analyte, and its spelling is read by ResultFlagResolver rather than by a
     * second dictionary here — one platform, one list of what "HH" means. Only
     * when the bench published no flag (or a code no laboratory dictionary in
     * this codebase recognises, which ResultFlagResolver treats as "derive it"
     * precisely because it could be a panic code we have not met) does the
     * adult band below apply.
     *
     * @return array{band: string, source: string|null, measure: string|null, numeric: float|null, secondary: float|null}
     */
    public static function forLabGlucose(?string $labFlag, ?string $value, ?string $unit): array
    {
        $fromLab = ResultFlagResolver::fromLabFlag($labFlag);

        if ($fromLab !== null) {
            return [
                'band'      => self::fromResultFlag($fromLab),
                'source'    => self::SOURCE_LAB,
                'measure'   => self::BLOOD_SUGAR,
                'numeric'   => self::parseNumeric($value),
                'secondary' => null,
            ];
        }

        return self::resolve(self::BLOOD_SUGAR, $value, null, $unit);
    }

    /**
     * ResultFlagResolver's laboratory vocabulary → this class's band
     * vocabulary. They are deliberately the same words; the mapping is written
     * out so that a future divergence in either is a compile-time concern
     * rather than a silent mistranslation of a critical value.
     */
    public static function fromResultFlag(string $flag): string
    {
        return match ($flag) {
            ResultFlagResolver::CRITICAL => self::CRITICAL,
            ResultFlagResolver::HIGH     => self::HIGH,
            ResultFlagResolver::LOW      => self::LOW,
            ResultFlagResolver::ABNORMAL => self::ABNORMAL,
            ResultFlagResolver::NORMAL   => self::NORMAL,
            default                      => self::UNKNOWN,
        };
    }

    // ── The bands ────────────────────────────────────────────────────────

    /**
     * HEART RATE, adult, at rest — bpm.
     *
     *   normal    60–100     the conventional adult resting range.
     *   low/high  outside it
     *   critical  <40 or >130
     *
     * Source: the 60–100 bpm resting range is the standard adult reference
     * (AHA / ACC). The escalation boundaries are the outer edges of NEWS2's
     * pulse scale (Royal College of Physicians, National Early Warning Score 2,
     * 2017), which scores 3 — the score that mandates urgent review — at ≤40
     * and at ≥131.
     */
    private static function heartRate(float $value, ?string $unit): string
    {
        if (! self::unitOk($unit, ['bpm', '/min', 'min', 'beats/min'])) {
            return self::UNKNOWN;
        }

        return match (true) {
            $value < 40 || $value > 130 => self::CRITICAL,
            $value < 60                 => self::LOW,
            $value > 100                => self::HIGH,
            default                     => self::NORMAL,
        };
    }

    /**
     * BLOOD PRESSURE, adult — mmHg. Two numbers or no verdict: a lone systolic
     * is not a blood pressure and is not banded as one.
     *
     *   critical  systolic ≥180 or diastolic ≥120  (hypertensive crisis)
     *             systolic <90                      (the NEWS2 panic bound)
     *   high      systolic ≥140 or diastolic ≥90    (stage 2 hypertension)
     *   low       systolic <100 or diastolic <60
     *   normal    otherwise
     *
     * Sources: ACC/AHA 2017 Hypertension Guideline defines hypertensive crisis
     * as >180 and/or >120 and stage 2 hypertension as ≥140/90; ESC/ESH and WHO
     * use the same ≥140/90 threshold. NEWS2 scores 3 for a systolic ≤90, which
     * is why a low systolic escalates even when the diastolic is unremarkable —
     * a shocked patient is the one this band exists to catch.
     *
     * The order of the arms is load-bearing: crisis and shock are tested before
     * the ordinary hypertension band, so 186/118 reads `critical` rather than
     * `high`.
     */
    private static function bloodPressure(float $systolic, ?float $diastolic, ?string $unit): string
    {
        if ($diastolic === null) {
            return self::UNKNOWN;
        }

        if (! self::unitOk($unit, ['mmhg', 'mm/hg'])) {
            return self::UNKNOWN;
        }

        return match (true) {
            $systolic >= 180 || $diastolic >= 120 || $systolic < 90 => self::CRITICAL,
            $systolic >= 140 || $diastolic >= 90                    => self::HIGH,
            $systolic < 100 || $diastolic < 60                      => self::LOW,
            default                                                 => self::NORMAL,
        };
    }

    /**
     * OXYGEN SATURATION, adult, on air — %.
     *
     *   critical  <90
     *   low       <94
     *   normal    94–100
     *   unknown   >100  — impossible; a broken probe is not a reassuring
     *                     reading, so nothing is endorsed
     *
     * Source: NEWS2 scale 1 scores 3 at ≤91% and 0 only at ≥96%, with 92–95%
     * scoring 1–2; the <94% advisory and the <90% escalation sit inside that.
     * NEWS2 scale 2 (for patients with hypercapnic respiratory failure, whose
     * target saturation is 88–92%) is NOT applied — nothing in this record says
     * which patients are on it, and applying it blind would silence a real
     * desaturation.
     */
    private static function oxygenSaturation(float $value, ?string $unit): string
    {
        if (! self::unitOk($unit, ['%', 'percent', 'pct'])) {
            return self::UNKNOWN;
        }

        return match (true) {
            $value < 90  => self::CRITICAL,
            $value < 94  => self::LOW,
            $value > 100 => self::UNKNOWN,
            default      => self::NORMAL,
        };
    }

    /**
     * TEMPERATURE, adult, core — °C.
     *
     *   critical  ≤35.0 or ≥40.0
     *   low       <36.0
     *   high      >38.0
     *   normal    36.0–38.0
     *
     * Source: NEWS2's temperature scale scores 0 for exactly 36.1–38.0 °C and
     * 3 at ≤35.0 °C, which is where the normal band and the hypothermia bound
     * come from. The 40.0 °C escalation is the conventional hyperpyrexia
     * threshold used in sepsis screening.
     *
     * A °F reading is converted rather than refused, because a band written in
     * °C applied to 98.6 would call a well patient critically hypothermic.
     */
    private static function temperature(float $value, ?string $unit): string
    {
        $celsius = self::toCelsius($value, $unit);

        if ($celsius === null) {
            return self::UNKNOWN;
        }

        return match (true) {
            $celsius <= 35.0 || $celsius >= 40.0 => self::CRITICAL,
            $celsius < 36.0                      => self::LOW,
            $celsius > 38.0                      => self::HIGH,
            default                              => self::NORMAL,
        };
    }

    /**
     * RESPIRATORY RATE, adult — breaths per minute.
     *
     *   critical  <8 or >30
     *   low       <12
     *   high      >20
     *   normal    12–20
     *
     * Source: NEWS2 scores 0 for exactly 12–20 breaths/min and 3 at ≤8, which
     * is the normal band and the lower escalation. NEWS2 scores 3 from ≥25
     * upwards; >30 is used here as the escalation bound because this is an
     * advisory pill on a patient-facing document rather than a bedside
     * observation chart, and it is deliberately the more conservative of the
     * two — a rate of 24 still reads `high`.
     */
    private static function respiratoryRate(float $value, ?string $unit): string
    {
        if (! self::unitOk($unit, ['/min', 'min', 'breaths/min', 'bpm', 'rpm', 'cpm'])) {
            return self::UNKNOWN;
        }

        return match (true) {
            $value < 8 || $value > 30 => self::CRITICAL,
            $value < 12               => self::LOW,
            $value > 20               => self::HIGH,
            default                   => self::NORMAL,
        };
    }

    /**
     * BLOOD SUGAR, adult — banded as a RANDOM glucose, in mg/dL.
     *
     *   critical  <54 or ≥300
     *   low       <70
     *   high      >140
     *   normal    70–140
     *
     * Sources (ADA Standards of Care): level 2 hypoglycaemia is <54 mg/dL
     * (3.0 mmol/L) — the value at which a patient needs help now; level 1 is
     * <70 mg/dL (3.9 mmol/L). The 140 mg/dL (7.8 mmol/L) upper bound is the ADA
     * normal 2-hour post-load / random glucose. The 300 mg/dL (16.7 mmol/L)
     * escalation is the conventional severe-hyperglycaemia threshold at which
     * ketones are checked.
     *
     * THE RANDOM BAND, ALWAYS. The fasting state is not recorded anywhere in
     * the schema this reads from, and a fasting band applied to a post-meal
     * sample would call a healthy patient diabetic. SelfReportedVitalMeasure
     * asks the patient for that context precisely because this class cannot
     * know it — and until a caller passes it, the wider random band is the only
     * honest one.
     *
     * THE UNIT IS REQUIRED. mg/dL and mmol/L differ by a factor of 18 and both
     * are in daily use in Cameroon; a missing unit is no opinion, never a
     * default.
     */
    private static function bloodSugar(float $value, ?string $unit): string
    {
        $mgdl = self::toMilligramsPerDecilitre($value, $unit);

        if ($mgdl === null) {
            return self::UNKNOWN;
        }

        return match (true) {
            $mgdl < 54 || $mgdl >= 300 => self::CRITICAL,
            $mgdl < 70                 => self::LOW,
            $mgdl > 140                => self::HIGH,
            default                    => self::NORMAL,
        };
    }

    // ── Vocabulary ───────────────────────────────────────────────────────

    /**
     * A surface's row severity for one band: critical | warning | success |
     * info. `info` is "nothing was compared", which is NOT `success` — the
     * same distinction ResultFlagResolver::severity() draws, and for the same
     * reason: a green pill on an unassessed value is a reassurance the record
     * never gave.
     */
    public static function severity(string $band): string
    {
        return match ($band) {
            self::CRITICAL                        => 'critical',
            self::HIGH, self::LOW, self::ABNORMAL => 'warning',
            self::NORMAL                          => 'success',
            default                               => 'info',
        };
    }

    public static function isAlarming(string $band): bool
    {
        return in_array($band, self::ALARMING_BANDS, true);
    }

    /** Whether this class has any band at all for a measure. */
    public static function hasBand(string|SelfReportedVitalMeasure $measure): bool
    {
        $key = self::measureKey($measure);

        return $key !== null && ! in_array($key, self::NO_BAND, true);
    }

    /**
     * The translation key for a band word, or null for `unknown` — which is
     * never printed as a word, because "no opinion" has no pill.
     *
     * The words live under `labs.flag.*`. They are the platform's existing
     * EN/FR-parity strings for exactly this vocabulary (Normal / Bas / Élevé /
     * Anormal / Critique) and are generic rather than laboratory-specific; no
     * vitals-specific keys exist yet. Naming them here rather than at each call
     * site means a later move to a `vitals.*` namespace is one edit, not a
     * hunt through every surface.
     */
    public static function labelKey(string $band): ?string
    {
        return in_array($band, [self::CRITICAL, self::HIGH, self::LOW, self::ABNORMAL, self::NORMAL], true)
            ? 'labs.flag.' . $band
            : null;
    }

    /**
     * The canonical key for a measure, or null when this codebase does not
     * write that name. Null is "unrecognised", and unrecognised is no opinion.
     */
    public static function measureKey(string|SelfReportedVitalMeasure $measure): ?string
    {
        $raw = $measure instanceof SelfReportedVitalMeasure
            ? $measure->value
            : strtolower(trim($measure));

        return self::ALIASES[$raw] ?? null;
    }

    // ── Parsing ──────────────────────────────────────────────────────────

    /**
     * The reading as a number, or null when it is not one.
     *
     * Mirrors ResultFlagResolver::parseNumeric: a value that is not a number is
     * not comparable, and returning null keeps it out of every comparison
     * rather than coercing it to 0. The comma decimal separator used in
     * French-language records is accepted.
     */
    public static function parseNumeric(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $raw = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $raw);

        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Whether a unit is one this band is written in.
     *
     * A NULL unit passes: every column that feeds this class is defined in a
     * single unit (`triage_vital_signs.pulse_rate` is bpm and cannot be
     * anything else), so a caller reading such a column has nothing to declare.
     * An unrecognised unit that WAS supplied fails, because a caller that
     * bothered to say "arb/units" is telling us the number is not in the unit
     * this band assumes.
     *
     * @param  list<string>  $accepted  lower-case, spaces already removed by the caller's normalisation
     */
    private static function unitOk(?string $unit, array $accepted): bool
    {
        $normalised = self::normaliseUnit($unit);

        return $normalised === null || in_array($normalised, $accepted, true);
    }

    /** Lower-cased, whitespace-stripped, or null when nothing was supplied. */
    private static function normaliseUnit(?string $unit): ?string
    {
        $normalised = strtolower(trim((string) $unit));
        $normalised = str_replace(["\u{00A0}", ' '], '', $normalised);

        return $normalised === '' ? null : $normalised;
    }

    /**
     * A temperature in °C, or null when the unit is one this class cannot read.
     * A null unit is °C — the unit every temperature column in this schema is
     * defined in and the only one SelfReportedVitalMeasure offers.
     */
    private static function toCelsius(float $value, ?string $unit): ?float
    {
        return match (self::normaliseUnit($unit)) {
            null, '°c', 'c', 'celsius', 'degc'  => $value,
            '°f', 'f', 'fahrenheit', 'degf'     => ($value - 32.0) * 5.0 / 9.0,
            default                             => null,
        };
    }

    /**
     * A glucose in mg/dL, or null when the unit is missing or unreadable.
     * Unlike every other measure here, a null unit is NOT accepted: see
     * bloodSugar().
     */
    private static function toMilligramsPerDecilitre(float $value, ?string $unit): ?float
    {
        return match (self::normaliseUnit($unit)) {
            'mg/dl', 'mgdl', 'mg/100ml' => $value,
            'mmol/l', 'mmoll'           => $value * 18.0,
            'g/l', 'gl'                 => $value * 100.0,
            default                     => null,
        };
    }
}
