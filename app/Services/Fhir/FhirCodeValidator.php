<?php

namespace App\Services\Fhir;

use App\Exceptions\FhirIngestRefusal;
use App\Models\ClinicalCodeDictionary;
use App\Models\PartnerObservation;
use App\Support\Fhir\FhirOutcome;
use App\Support\Terminology\ClinicalCode;

/**
 * Terminology validation for everything pushed in over FHIR.
 *
 * -- What each check actually proves --------------------------------------
 * Two independent gates, and it matters which one catches what:
 *
 *   1. FORMAT + CHECK DIGIT (App\Support\Terminology\ClinicalCode). Real
 *      arithmetic. `70563-7` — offered once in this codebase for a malaria
 *      rapid test — fails its own mod-10 check digit and is therefore not a
 *      LOINC code at all. Nothing that fails here gets stored, ever.
 *
 *   2. MEMBERSHIP in a published vocabulary. A well-formed code can still be
 *      a code nobody publishes.
 *
 * -- Why membership is checked against two different lists ----------------
 * `clinical_code_dictionary` holds this deployment's imported vocabularies:
 * 19,314 curated LOINC terms (the laboratory set) and 290 CVX codes. It does
 * NOT carry the FHIR vital-signs LOINCs — 8867-4 heart rate, 8480-6 systolic,
 * 8310-5 body temperature and the rest are simply not in the import. Checking
 * a vital-sign Observation against the dictionary would therefore refuse
 * every legitimate vital a partner could possibly send.
 *
 * So vital-sign codes are checked against the FHIR R4 vital-signs profile's
 * own code list, which is normative, fixed and published by HL7 as part of
 * the specification this API implements. It is reproduced below rather than
 * invented, and it is a CLOSED list: a vital-sign Observation carrying
 * anything else is refused rather than quietly filed.
 *
 * Laboratory Observations, DiagnosticReports and Immunizations are checked
 * against the dictionary, which does carry their vocabularies.
 */
class FhirCodeValidator
{
    public const SYSTEM_LOINC = 'http://loinc.org';
    public const SYSTEM_CVX   = 'http://hl7.org/fhir/sid/cvx';

    /**
     * The FHIR R4 vital-signs profile code list (HL7 FHIR R4,
     * StructureDefinition/vitalsigns and its derived profiles).
     *
     * Closed on purpose. If a partner needs a vital this list does not carry,
     * that is a conversation about extending the profile, not a reason to let
     * an unchecked code through.
     */
    public const VITAL_SIGNS_LOINC = [
        '85353-1' => 'Vital signs, weight, height, head circumference, oxygen saturation and BMI panel',
        '9279-1'  => 'Respiratory rate',
        '8867-4'  => 'Heart rate',
        '2708-6'  => 'Oxygen saturation in Arterial blood',
        '59408-5' => 'Oxygen saturation in Arterial blood by Pulse oximetry',
        '8310-5'  => 'Body temperature',
        '8302-2'  => 'Body height',
        '9843-4'  => 'Head Occipital-frontal circumference',
        '29463-7' => 'Body weight',
        '39156-5' => 'Body mass index (BMI) [Ratio]',
        '85354-9' => 'Blood pressure panel with all children optional',
        '8480-6'  => 'Systolic blood pressure',
        '8462-4'  => 'Diastolic blood pressure',
    ];

    /**
     * Pull the LOINC coding out of a CodeableConcept and prove it.
     *
     * @param  array<string,mixed>  $codeableConcept
     * @return array{0:string,1:?string}  [code, display]
     *
     * @throws FhirIngestRefusal
     */
    public function requireLoinc(array $codeableConcept, string $observationKind, string $expression = 'code.coding'): array
    {
        $coding = $this->pickCoding($codeableConcept, self::SYSTEM_LOINC);

        if ($coding === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    'A LOINC coding (system "' . self::SYSTEM_LOINC . '") is required. This server '
                    . 'stores no clinical value without a code it can verify.',
                    $expression
                ),
                'code_missing',
                'no LOINC coding in ' . $expression
            );
        }

        $code = trim((string) ($coding['code'] ?? ''));

        // Gate 1 — arithmetic. This is what catches 70563-7.
        if (! ClinicalCode::isLoinc($code)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalidCode('LOINC', $code, 'it fails the LOINC mod-10 check digit, '
                    . 'so it is not a LOINC code'),
                'code_check_digit_failed',
                'LOINC check digit failed: ' . $code
            );
        }

        // Gate 2 — membership, against the right list for this kind.
        if ($observationKind === PartnerObservation::KIND_VITAL_SIGNS) {
            if (! array_key_exists($code, self::VITAL_SIGNS_LOINC)) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::invalidCode('LOINC', $code, 'it is not in the FHIR R4 vital-signs '
                        . 'profile code list, which this server treats as closed for '
                        . 'category "vital-signs"'),
                    'code_not_in_vital_signs_profile',
                    'not a vital-signs profile LOINC: ' . $code
                );
            }

            return [$code, $coding['display'] ?? self::VITAL_SIGNS_LOINC[$code]];
        }

        $row = $this->dictionaryLookup('LOINC', $code);

        if ($row === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalidCode('LOINC', $code, 'it is not an active code in this '
                    . "deployment's published LOINC vocabulary"),
                'code_not_in_dictionary',
                'LOINC not in dictionary: ' . $code
            );
        }

        // The publisher's display wins over the partner's. A partner may
        // mislabel a code; the vocabulary is the authority on what it means.
        return [$code, $row->display];
    }

    /**
     * Prove a CVX vaccine code.
     *
     * CVX has no check digit, so membership in the imported CDC vocabulary is
     * the whole of the proof — which is why an unrecognised code is refused
     * rather than stored as free text.
     *
     * @param  array<string,mixed>  $codeableConcept
     * @return array{0:string,1:?string}
     *
     * @throws FhirIngestRefusal
     */
    public function requireCvx(array $codeableConcept, string $expression = 'vaccineCode.coding'): array
    {
        $coding = $this->pickCoding($codeableConcept, self::SYSTEM_CVX);

        if ($coding === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    'A CVX coding (system "' . self::SYSTEM_CVX . '") is required on Immunization.'
                    . 'vaccineCode. This server stores no vaccine it cannot name from a published '
                    . 'vocabulary.',
                    $expression
                ),
                'code_missing',
                'no CVX coding in ' . $expression
            );
        }

        $code = trim((string) ($coding['code'] ?? ''));

        if (! preg_match('/^\d{1,4}$/', $code)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalidCode('CVX', $code, 'a CVX code is one to four digits'),
                'code_malformed',
                'CVX malformed: ' . $code
            );
        }

        $row = $this->dictionaryLookup('CVX', $code);

        if ($row === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalidCode('CVX', $code, 'it is not an active code in the imported '
                    . 'CDC CVX vocabulary'),
                'code_not_in_dictionary',
                'CVX not in dictionary: ' . $code
            );
        }

        return [$code, $row->display];
    }

    /** The first coding in a CodeableConcept that claims the given system. */
    private function pickCoding(array $codeableConcept, string $system): ?array
    {
        foreach ((array) ($codeableConcept['coding'] ?? []) as $coding) {
            if (is_array($coding) && ($coding['system'] ?? null) === $system) {
                return $coding;
            }
        }

        return null;
    }

    private function dictionaryLookup(string $system, string $code): ?ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::query()
            ->where('system', $system)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}
