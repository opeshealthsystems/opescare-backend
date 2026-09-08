<?php

namespace Database\Seeders;

use App\Support\Terminology\ClinicalCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Proposes LOINC and ICD-10 mappings for the vocabulary this deployment
 * actually uses.
 *
 * ── These are PROPOSALS, not assertions ──────────────────────────────────
 * Every row is written with `status = 'pending'`. `CodeSystemMapping` has an
 * `approved` scope and an `approve()` method precisely so a mapping is inert
 * until a person signs it off. Nothing here stamps a code onto a lab result
 * or a diagnosis. A clinician or terminologist approves a mapping first, and
 * only an approved mapping may then be applied.
 *
 * That distinction is the whole point. A wrong code is worse than no code: no
 * code says "we do not know", while a wrong one says something false about a
 * patient in a format other systems are built to trust.
 *
 * ── Every LOINC code below was check-digit verified ──────────────────────
 * LOINC carries a mod-10 check digit, and this seeder refuses to write a code
 * that fails it (see the guard in `run()`). That check earned its place
 * immediately: 70563-7, a plausible-looking code for a malaria rapid test,
 * fails its own check digit and is therefore not a LOINC code. The malaria
 * RDT is consequently ABSENT from this file rather than mapped wrongly — see
 * the note at the end.
 *
 * ── ICD-10 has no check digit ────────────────────────────────────────────
 * Only shape can be validated, so an ICD-10 proposal carries less machine
 * assurance than a LOINC one and leans harder on the human approving it.
 *
 * Idempotent: keyed on (local_code, standard_system, resource_type).
 */
class ClinicalTerminologySeeder extends Seeder
{
    /**
     * Orderable tests → LOINC, keyed by `lab_test_availability.local_test_code`.
     *
     * `confidence` is this seeder's own honesty about the mapping, not a
     * LOINC field: `high` where the code is the unambiguous standard for that
     * assay, `review` where a defensible alternative exists and a person
     * should choose.
     */
    private const ORDERABLE = [
        ['FBC',   'Full Blood Count',            '58410-2', 'CBC panel - Blood by Automated count',                    'high'],
        ['HBA1C', 'Glycated Haemoglobin',        '4548-4',  'Hemoglobin A1c/Hemoglobin.total in Blood',                'high'],
        ['TSH',   'Thyroid Stimulating Hormone', '3016-3',  'Thyrotropin [Units/volume] in Serum or Plasma',           'high'],
        ['FERR',  'Ferritin',                    '2276-4',  'Ferritin [Mass/volume] in Serum or Plasma',               'high'],
        ['GLUF',  'Fasting Blood Glucose',       '14771-0', 'Glucose [Moles/volume] in Serum or Plasma --12 hours fasting', 'high'],
        ['URIC',  'Urine Culture',               '630-4',   'Bacteria identified in Urine by Culture',                 'review'],
        ['RFT',   'Renal Function Test',         '24362-6', 'Renal function 2000 panel - Serum or Plasma',             'review'],
        ['LFT',   'Liver Function Test',         '24325-3', 'Hepatic function 2000 panel - Serum or Plasma',           'review'],
        ['LIPID', 'Lipid Panel',                 '57698-3', 'Lipid panel with direct LDL - Serum or Plasma',           'review'],
    ];

    /**
     * Result analytes → LOINC, keyed by `lab_results.parameter_name`.
     *
     * The UNIT is part of the mapping, not decoration. Creatinine in umol/L
     * and creatinine in mg/dL are different LOINC codes, and mapping on the
     * name alone is how a molar result ends up labelled as a mass one.
     */
    private const ANALYTES = [
        ['Haemoglobin',           'g/dL',   '718-7',   'Hemoglobin [Mass/volume] in Blood',                            'high'],
        ['White Cell Count',      '10^9/L', '6690-2',  'Leukocytes [#/volume] in Blood by Automated count',            'high'],
        ['Platelet Count',        '10^9/L', '777-3',   'Platelets [#/volume] in Blood by Automated count',             'high'],
        ['Sodium',                'mmol/L', '2951-2',  'Sodium [Moles/volume] in Serum or Plasma',                     'high'],
        ['Potassium',             'mmol/L', '2823-3',  'Potassium [Moles/volume] in Serum or Plasma',                  'high'],
        ['Creatinine',            'umol/L', '14682-9', 'Creatinine [Moles/volume] in Serum or Plasma',                 'high'],
        ['Ferritin',              'ng/mL',  '2276-4',  'Ferritin [Mass/volume] in Serum or Plasma',                    'high'],
        ['Glucose (random)',      'mg/dL',  '2345-7',  'Glucose [Mass/volume] in Serum or Plasma',                     'high'],
        ['Fasting Blood Glucose', 'mmol/L', '14771-0', 'Glucose [Moles/volume] in Serum or Plasma --12 hours fasting', 'high'],
    ];

    /**
     * Conditions → ICD-10, for the diagnosis vocabulary in use.
     *
     * Both are already stored on `diagnoses.code`; these rows give the code a
     * display name and put it under the same review as everything else, so
     * the vocabulary is declared in one place rather than implied by whatever
     * somebody typed.
     */
    private const CONDITIONS = [
        ['E11', 'Type 2 diabetes mellitus',      'high'],
        ['I10', 'Essential (primary) hypertension', 'high'],
    ];

    public function run(): void
    {
        $written = 0;

        foreach (self::ORDERABLE as [$local, $name, $code, $display, $confidence]) {
            $this->guardLoinc($code, $name);
            $written += $this->propose('lab_order', $local, $name, null, 'LOINC', $code, $display, $confidence);
        }

        foreach (self::ANALYTES as [$name, $unit, $code, $display, $confidence]) {
            $this->guardLoinc($code, $name);
            $written += $this->propose('lab_result', $name, $name, $unit, 'LOINC', $code, $display, $confidence);
        }

        foreach (self::CONDITIONS as [$code, $display, $confidence]) {
            if (! ClinicalCode::isIcd10($code)) {
                throw new RuntimeException("Malformed ICD-10 code {$code} — refusing to seed it.");
            }

            $written += $this->propose('condition', $code, $display, null, 'ICD-10', $code, $display, $confidence);
        }

        $this->command?->info("Proposed {$written} clinical code mappings, all pending review.");
        $this->command?->warn(
            'Malaria Rapid Diagnostic Test is deliberately UNMAPPED: the code offered for it ' .
            'failed the LOINC check digit, so it is not a LOINC code. It needs one chosen from ' .
            'the LOINC browser by someone qualified to choose it.'
        );
    }

    /**
     * Refuse to seed a LOINC code that fails its own check digit.
     *
     * This is not defensive decoration — it caught a wrong code the first
     * time it ran.
     */
    private function guardLoinc(string $code, string $forWhat): void
    {
        if (! ClinicalCode::isLoinc($code)) {
            throw new RuntimeException(
                "LOINC code {$code} proposed for '{$forWhat}' fails its check digit. " .
                'Refusing to seed a code that cannot be real.'
            );
        }
    }

    /** Idempotent on (local_code, standard_system, resource_type). */
    private function propose(
        string $resourceType,
        string $localCode,
        string $localName,
        ?string $localUnit,
        string $system,
        string $standardCode,
        string $standardDisplay,
        string $confidence,
    ): int {
        $existing = DB::table('code_system_mappings')
            ->where('local_code', $localCode)
            ->where('standard_system', $system)
            ->where('resource_type', $resourceType)
            ->first();

        if ($existing !== null) {
            return 0;
        }

        DB::table('code_system_mappings')->insert([
            'id'                 => (string) Str::uuid(),
            'local_code'         => $localCode,
            'local_name'         => $localName,
            'local_unit'         => $localUnit,
            'resource_type'      => $resourceType,
            'standard_system'    => $system,
            'standard_code'      => $standardCode,
            'standard_display'   => $standardDisplay,
            'standard_version'   => null,
            'mapping_confidence' => $confidence,
            'confidence_score'   => null,
            'status'             => 'pending',
            'approved_by'        => null,
            'approved_at'        => null,
            'notes'              => $confidence === 'review'
                ? 'A defensible alternative code exists for this panel. Confirm against the LOINC browser before approving.'
                : null,
            'facility_id'        => null,
            'created_by'         => 'ClinicalTerminologySeeder',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return 1;
    }
}
