<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\VitalSign;

/**
 * FHIR R4 Observation Resource Mapper (Vital Signs)
 *
 * Maps an OpesCare VitalSign model to one or more FHIR R4 Observation resources.
 * Each vital parameter becomes an individual Observation.
 * Reference: https://hl7.org/fhir/R4/observation.html
 */
class FhirObservationMapper
{
    /**
     * Map a VitalSign record to a FHIR Observation Bundle (one per parameter).
     *
     * @return array[]
     */
    public function toFhirBundle(VitalSign $vital): array
    {
        $observations = [];
        $patientId    = $vital->triageRecord?->visit?->patient_id ?? null;
        $encounterId  = $vital->triageRecord?->visit_id ?? null;

        $params = [
            'temperature' => [
                'loinc' => '8310-5',
                'display'=> 'Body temperature',
                'unit'   => '°C',
                'system' => 'http://unitsofmeasure.org',
                'code'   => 'Cel',
            ],
            'pulse' => [
                'loinc'  => '8867-4',
                'display'=> 'Heart rate',
                'unit'   => 'beats/min',
                'system' => 'http://unitsofmeasure.org',
                'code'   => '/min',
            ],
            'weight' => [
                'loinc'  => '29463-7',
                'display'=> 'Body weight',
                'unit'   => 'kg',
                'system' => 'http://unitsofmeasure.org',
                'code'   => 'kg',
            ],
            'height' => [
                'loinc'  => '8302-2',
                'display'=> 'Body height',
                'unit'   => 'cm',
                'system' => 'http://unitsofmeasure.org',
                'code'   => 'cm',
            ],
        ];

        foreach ($params as $field => $meta) {
            $value = $vital->{$field};
            if ($value === null) {
                continue;
            }

            $obs = [
                'resourceType' => 'Observation',
                'id'           => $vital->id . '-' . $field,
                'meta'         => ['source' => 'OpesCare'],
                'status'       => 'final',
                'category'     => [
                    [
                        'coding' => [
                            [
                                'system'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
                                'code'    => 'vital-signs',
                                'display' => 'Vital Signs',
                            ],
                        ],
                    ],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system'  => 'http://loinc.org',
                            'code'    => $meta['loinc'],
                            'display' => $meta['display'],
                        ],
                    ],
                    'text' => $meta['display'],
                ],
                'valueQuantity' => [
                    'value'  => (float) $value,
                    'unit'   => $meta['unit'],
                    'system' => $meta['system'],
                    'code'   => $meta['code'],
                ],
                'effectiveDateTime' => $vital->created_at?->toIso8601String(),
            ];

            if ($patientId) {
                $obs['subject'] = ['reference' => 'Patient/' . $patientId];
            }
            if ($encounterId) {
                $obs['encounter'] = ['reference' => 'Encounter/' . $encounterId];
            }

            $observations[] = $obs;
        }

        return $observations;
    }
}
