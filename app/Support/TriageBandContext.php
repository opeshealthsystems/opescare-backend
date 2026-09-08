<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * WHETHER this platform is allowed to interpret a patient's vital signs at all
 * — asked before any band is shown at the triage desk.
 *
 * ── WHY THIS CLASS EXISTS ──────────────────────────────────────────────────
 *
 * App\Support\VitalBandResolver is the platform's single vitals authority, and
 * its own doc-block is explicit about its limits: its escalation boundaries are
 * NEWS2 (Royal College of Physicians, 2017), which is published for patients
 * aged 16 and over and is NOT for pregnancy. Every verdict it returns carries
 * `source => 'adult_bands'` precisely so a caller cannot forget.
 *
 * A triage desk is the one surface where forgetting is lethal. A respiratory
 * rate of 30 is an emergency in an adult and unremarkable in an infant. Scoring
 * a child against adult bands does not produce a random error — it produces a
 * systematic one, in the dangerous direction: a sick child's tachypnoea and
 * tachycardia read as "normal", so the interface reassures the nurse about the
 * patient most likely to deteriorate. Under-triage of children is the failure
 * this class exists to prevent.
 *
 * So the desk asks HERE first, and the answer is binary. When this class says
 * no, the triage screen shows the recorded numbers and NO interpretation of any
 * kind — not a softened one, not a greyed-out one, not "probably fine". It says
 * plainly that this platform has no bands for this patient and the nurse must
 * assess clinically.
 *
 * ── WHAT IS DELIBERATELY NOT HERE ──────────────────────────────────────────
 *
 * Paediatric bands. Implementing them means an age-banded table per measure
 * from a published paediatric early-warning source, reviewed and signed off by
 * a clinician. Neither exists in this repository. Scaling an adult band by age,
 * or inventing thresholds that "look about right", would be worse than the
 * refusal below, because it would look authoritative. See VitalBandResolver
 * rule 2: no threshold is invented.
 */
final class TriageBandContext
{
    /** The youngest age NEWS2 is published for. Below this, no interpretation. */
    public const ADULT_BAND_MIN_AGE = 16;

    /** No date of birth on the record, so age cannot be established. */
    public const REASON_AGE_UNKNOWN = 'age_unknown';

    /** Under 16. Adult bands under-triage children. */
    public const REASON_PAEDIATRIC = 'paediatric';

    /** NEWS2 excludes pregnancy; physiology shifts and the bands do not. */
    public const REASON_PREGNANCY = 'pregnancy';

    /** Pregnancy answers the desk offers. Only PREGNANT withdraws the bands. */
    public const PREGNANCY_STATUSES = ['not_pregnant', 'pregnant', 'unknown', 'not_applicable'];

    private function __construct(
        public readonly bool $eligible,
        public readonly ?string $reason,
        public readonly ?int $ageYears,
    ) {
    }

    /**
     * Decide for one patient.
     *
     * @param  CarbonInterface|null  $dateOfBirth      null means unknown, never "assume adult"
     * @param  string|null           $pregnancyStatus  as recorded on the triage record
     */
    public static function for(?CarbonInterface $dateOfBirth, ?string $pregnancyStatus = null): self
    {
        // Pregnancy is tested first and independently of age: NEWS2 excludes it
        // outright, so a pregnant 30-year-old gets no bands either.
        if ($pregnancyStatus === 'pregnant') {
            $age = $dateOfBirth?->age;

            return new self(false, self::REASON_PREGNANCY, $age === null ? null : (int) $age);
        }

        if ($dateOfBirth === null) {
            return new self(false, self::REASON_AGE_UNKNOWN, null);
        }

        $age = (int) $dateOfBirth->age;

        if ($age < self::ADULT_BAND_MIN_AGE) {
            return new self(false, self::REASON_PAEDIATRIC, $age);
        }

        return new self(true, null, $age);
    }

    /** The translation key explaining a refusal, or null when bands do apply. */
    public function reasonKey(): ?string
    {
        return $this->reason === null ? null : 'triage.no_bands.' . $this->reason;
    }

    /**
     * Advisory per-reading flags for this patient, or an EMPTY LIST when this
     * platform has no bands for them.
     *
     * Empty is the whole point: a caller that loops over the result and prints
     * nothing is correct by construction, and there is no partially-applied
     * middle state for a template to render by accident.
     *
     * Each entry carries VitalBandResolver's own `source` verbatim, so a view
     * can label the flag as derived-and-advisory rather than as a reading
     * anybody made.
     *
     * @param  array<string, array{value: mixed, secondary?: mixed, unit?: string|null}>  $readings
     *         keyed by a measure name VitalBandResolver recognises
     * @return array<string, array{band: string, source: string|null, severity: string, label_key: string|null}>
     */
    public function interpret(array $readings): array
    {
        if (! $this->eligible) {
            return [];
        }

        $out = [];

        foreach ($readings as $measure => $reading) {
            if (! array_key_exists('value', $reading) || $reading['value'] === null || $reading['value'] === '') {
                continue;
            }

            $verdict = VitalBandResolver::resolve(
                $measure,
                $reading['value'],
                $reading['secondary'] ?? null,
                $reading['unit'] ?? null
            );

            // `unknown` means "nothing was compared". It is not `normal` and it
            // is not shown; printing it would invite reading it as reassurance.
            if ($verdict['band'] === VitalBandResolver::UNKNOWN) {
                continue;
            }

            $out[$measure] = [
                'band'      => $verdict['band'],
                'source'    => $verdict['source'],
                'severity'  => VitalBandResolver::severity($verdict['band']),
                'label_key' => VitalBandResolver::labelKey($verdict['band']),
            ];
        }

        return $out;
    }

    /**
     * The readings map for one `triage_vital_signs` row, in the column names
     * and units that table is defined in.
     *
     * @return array<string, array{value: mixed, secondary?: mixed, unit?: string|null}>
     */
    public static function readingsFrom(\App\Models\TriageVitalSign $vitals): array
    {
        return [
            'temperature'       => ['value' => $vitals->temperature,      'unit' => '°C'],
            'pulse_rate'        => ['value' => $vitals->pulse_rate,       'unit' => 'bpm'],
            'respiratory_rate'  => ['value' => $vitals->respiratory_rate, 'unit' => '/min'],
            'blood_pressure'    => ['value' => $vitals->systolic_bp, 'secondary' => $vitals->diastolic_bp, 'unit' => 'mmHg'],
            'oxygen_saturation' => ['value' => $vitals->oxygen_saturation, 'unit' => '%'],
        ];
    }
}
