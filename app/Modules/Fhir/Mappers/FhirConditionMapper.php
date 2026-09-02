<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\Diagnosis;

/**
 * FHIR R4 Condition Resource Mapper
 *
 * Maps an OpesCare Diagnosis to a FHIR R4 Condition resource.
 * Reference: https://hl7.org/fhir/R4/condition.html
 */
class FhirConditionMapper
{
    public function toFhir(Diagnosis $diagnosis): array
    {
        $resource = [
            'resourceType' => 'Condition',
            'id'           => $diagnosis->id,
            'meta'         => [
                'lastUpdated' => $diagnosis->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code'   => $this->mapClinicalStatus($diagnosis->status),
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code'   => 'confirmed',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $diagnosis->patient_id,
            ],
            'recordedDate' => $diagnosis->created_at?->toIso8601String(),
        ];

        // Code (ICD-10, SNOMED-CT, or free text)
        $codings = [];

        if ($diagnosis->code) {
            $codings[] = [
                'system'  => $this->codeSystem($diagnosis->code_system),
                'code'    => $diagnosis->code,
                'display' => $diagnosis->display_name,
            ];
        }

        if (isset($diagnosis->snomed_code) && $diagnosis->snomed_code) {
            $codings[] = [
                'system'  => 'http://snomed.info/sct',
                'code'    => $diagnosis->snomed_code,
                'display' => $diagnosis->snomed_display ?? $diagnosis->display_name,
            ];
        }

        $resource['code'] = [
            'coding' => $codings ?: [['display' => $diagnosis->display_name]],
            'text'   => $diagnosis->display_name,
        ];

        // Category
        $resource['category'] = [
            [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/condition-category',
                        'code'    => 'encounter-diagnosis',
                        'display' => 'Encounter Diagnosis',
                    ],
                ],
            ],
        ];

        // Severity (primary = high concern)
        if ($diagnosis->is_primary) {
            $resource['severity'] = [
                'coding' => [
                    [
                        'system'  => 'http://snomed.info/sct',
                        'code'    => '24484000',
                        'display' => 'Severe',
                    ],
                ],
            ];
        }

        // Encounter reference
        if ($diagnosis->visit_id) {
            $resource['encounter'] = [
                'reference' => 'Encounter/' . $diagnosis->visit_id,
            ];
        }

        // Asserter (clinician)
        if ($diagnosis->provider_id) {
            $resource['asserter'] = [
                'reference' => 'Practitioner/' . $diagnosis->provider_id,
            ];
        }

        return $resource;
    }

    public function toBundle(iterable $diagnoses): array
    {
        $entries = collect($diagnoses)
            ->map(fn ($d) => ['resource' => $this->toFhir($d)])
            ->all();

        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ];
    }

    private function mapClinicalStatus(string $status): string
    {
        return match ($status) {
            'active', 'chronic'          => 'active',
            'resolved', 'inactive'       => 'resolved',
            'entered-in-error'           => 'inactive',
            default                      => 'active',
        };
    }

    private function codeSystem(?string $system): string
    {
        if (! $system) {
            return 'http://hl7.org/fhir/sid/icd-10';
        }

        return match (strtoupper($system)) {
            'ICD-10', 'ICD10'   => 'http://hl7.org/fhir/sid/icd-10',
            'ICD-11', 'ICD11'   => 'http://hl7.org/fhir/sid/icd-11',
            'SNOMED-CT', 'SNOMEDCT' => 'http://snomed.info/sct',
            default             => 'http://hl7.org/fhir/sid/icd-10',
        };
    }
}
