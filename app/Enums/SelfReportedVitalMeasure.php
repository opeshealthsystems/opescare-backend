<?php

namespace App\Enums;

/**
 * The vitals a patient can genuinely record at home — and the physiologically
 * possible bounds outside which this platform REFUSES the value rather than
 * storing it.
 *
 * DB column: patient_recorded_vitals.measure (string, backed value).
 *
 * ── Why only four ───────────────────────────────────────────────────────────
 *
 * Blood pressure, weight, temperature and blood sugar are measurable by a
 * patient with equipment they plausibly own. Oxygen saturation, respiratory
 * rate and heart rate are deliberately absent: a consumer pulse oximeter's
 * error at the exact saturations that matter is large enough that logging one
 * would create a false reassurance record, and a patient counting their own
 * respirations changes the thing they are counting.
 *
 * ── Why the bounds are what they are ────────────────────────────────────────
 *
 * These are NOT clinical normal ranges — they are "no living human ever
 * measured this" bounds. A systolic of 210 is dangerous but real, so it is
 * accepted and flagged for a human; a systolic of 900 is a typo or a broken
 * device, so it is refused with the reason and the accepted range, and NOTHING
 * is stored. The alternative — clamping 900 to 300 — invents a reading the
 * patient never took and hides the fact that their device is wrong.
 */
enum SelfReportedVitalMeasure: string
{
    case BloodPressure = 'blood_pressure';
    case Weight        = 'weight';
    case Temperature   = 'temperature';
    case BloodSugar    = 'blood_sugar';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Units this measure may be recorded in. The FIRST is the default.
     *
     * Blood sugar accepts both mg/dL and mmol/L because both are in daily use
     * in Cameroon and a patient must never have to convert their own glucometer
     * reading — a factor-of-18 mistake in that conversion is a dosing error.
     * The value is stored in the unit it was entered in and returned in that
     * unit; nothing is silently normalised.
     *
     * @return string[]
     */
    public function units(): array
    {
        return match ($this) {
            self::BloodPressure => ['mmHg'],
            self::Weight        => ['kg'],
            self::Temperature   => ['°C'],
            self::BloodSugar    => ['mg/dL', 'mmol/L'],
        };
    }

    public function defaultUnit(): string
    {
        return $this->units()[0];
    }

    /** Blood pressure is the only measure carrying a second number. */
    public function hasSecondaryValue(): bool
    {
        return $this === self::BloodPressure;
    }

    /**
     * Decimal places the value is stored and displayed with. Blood pressure and
     * mg/dL glucose are whole numbers; a "120.4 mmHg" would be false precision.
     */
    public function decimalsFor(string $unit): int
    {
        return match ($this) {
            self::BloodPressure => 0,
            self::Weight        => 1,
            self::Temperature   => 1,
            self::BloodSugar    => $unit === 'mmol/L' ? 1 : 0,
        };
    }

    /**
     * Refusal bounds for the primary value, per unit: [min, max], inclusive.
     *
     *   Systolic 50-300 mmHg   — below 50 is unrecordable, above 300 is
     *                            beyond any documented human measurement.
     *   Weight 0.5-500 kg      — spans a newborn to the heaviest person on
     *                            record; nothing outside it is a body weight.
     *   Temperature 25-45 °C   — the extremes of survivable core temperature,
     *                            widened past them so a real emergency reading
     *                            is never refused.
     *   Glucose 10-1000 mg/dL  — 0.5-55 mmol/L, the same span. Below 10 is not
     *                            compatible with life; above 1000 is beyond
     *                            what a home glucometer will even display.
     *
     * @return array{0: float, 1: float}
     */
    public function primaryBounds(string $unit): array
    {
        return match ($this) {
            self::BloodPressure => [50.0, 300.0],
            self::Weight        => [0.5, 500.0],
            self::Temperature   => [25.0, 45.0],
            self::BloodSugar    => $unit === 'mmol/L' ? [0.5, 55.0] : [10.0, 1000.0],
        };
    }

    /**
     * Refusal bounds for the secondary value (diastolic only).
     *
     * @return array{0: float, 1: float}|null
     */
    public function secondaryBounds(string $unit): ?array
    {
        return $this === self::BloodPressure ? [20.0, 200.0] : null;
    }

    /**
     * Contexts this measure may carry. Only glucose has one, and it matters:
     * App\Support\VitalBandResolver bands every laboratory glucose against the
     * wider RANDOM band precisely because "the fasting state is not recorded
     * anywhere in the schema, and applying a fasting band to a post-meal sample
     * would call a healthy patient diabetic". The patient knows; this asks
     * them.
     *
     * @return string[]
     */
    public function contexts(): array
    {
        return match ($this) {
            self::BloodSugar => ['fasting', 'before_meal', 'after_meal', 'bedtime', 'random'],
            default          => [],
        };
    }

    /** Translation key for the measure's name. */
    public function labelKey(): string
    {
        return 'patient_writes.measures.' . $this->value . '.label';
    }
}
