<?php

namespace App\Enums;

/**
 * Where a vital-sign reading came from, and therefore how much weight it can
 * carry in a decision.
 *
 * This exists because the four measures a patient can log at home — blood
 * pressure, weight, temperature, blood sugar — are the same four a facility
 * records. The NUMBER is indistinguishable; only the provenance separates a
 * home cuff from a nurse's, and a strip glucometer from a venous sample the
 * laboratory ran. A list that renders both identically is how a clinician
 * titrates insulin against a reading nobody verified.
 *
 * Every vitals payload this API returns carries one of these on EVERY reading.
 * There is no default and no null: a reading whose origin is unknown is a
 * reading that must not be shown next to one whose origin is known.
 *
 * The values map onto real, separate tables — the provenance is structural,
 * not a label a writer chooses:
 *
 *   self_reported      → `patient_recorded_vitals`  (this feature's write path)
 *   facility_measured  → `triage_vital_signs`, `vital_signs`
 *   laboratory_result  → `lab_results`
 */
enum VitalProvenance: string
{
    /** The patient measured it themselves and typed it in. Unverified. */
    case SelfReported = 'self_reported';

    /** Taken at a facility by staff, during triage or a visit. */
    case FacilityMeasured = 'facility_measured';

    /** A published laboratory result — a sample the laboratory ran. */
    case LaboratoryResult = 'laboratory_result';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Was this reading produced under professional supervision?
     *
     * The distinction the UI must draw. Self-reported readings are real
     * evidence and are worth recording — they are simply not clinical
     * measurements, and nothing may present them as such.
     */
    public function isClinicallyVerified(): bool
    {
        return match ($this) {
            self::SelfReported => false,
            self::FacilityMeasured, self::LaboratoryResult => true,
        };
    }

    /**
     * Translation key for the short badge shown on every reading.
     * Resolved through lang/{locale}/patient_writes.php.
     */
    public function labelKey(): string
    {
        return 'patient_writes.provenance.' . $this->value . '.label';
    }

    /** Translation key for the one-line explanation of what the badge means. */
    public function descriptionKey(): string
    {
        return 'patient_writes.provenance.' . $this->value . '.description';
    }
}
