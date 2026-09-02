<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\Visit;

/**
 * FHIR R4 Encounter Resource Mapper
 *
 * Maps an OpesCare Visit model to a FHIR R4 Encounter resource.
 * Reference: https://hl7.org/fhir/R4/encounter.html
 */
class FhirEncounterMapper
{
    public function toFhir(Visit $visit): array
    {
        $resource = [
            'resourceType' => 'Encounter',
            'id'           => $visit->id,
            'meta'         => [
                'lastUpdated' => $visit->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'status'  => $this->mapStatus($visit->status),
            'class'   => $this->mapClass($visit->visit_type),
            'subject' => [
                'reference' => 'Patient/' . $visit->patient_id,
            ],
        ];

        if ($visit->facility_id) {
            $resource['serviceProvider'] = [
                'reference'  => 'Organization/' . $visit->facility_id,
                'display'    => $visit->facility?->name,
            ];
        }

        if ($visit->provider_id) {
            $resource['participant'] = [
                [
                    'type'       => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'code' => 'PART']]]],
                    'individual' => ['reference' => 'Practitioner/' . $visit->provider_id],
                ],
            ];
        }

        if ($visit->created_at) {
            $resource['period']['start'] = $visit->created_at->toIso8601String();
        }
        if ($visit->updated_at && ($visit->status === 'discharged' || $visit->status === 'completed')) {
            $resource['period']['end'] = $visit->updated_at->toIso8601String();
        }

        return $resource;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'active', 'in_progress' => 'in-progress',
            'completed', 'discharged' => 'finished',
            'cancelled'               => 'cancelled',
            'planned'                 => 'planned',
            default                   => 'unknown',
        };
    }

    private function mapClass(string $visitType): array
    {
        $code = match (strtolower($visitType)) {
            'inpatient'    => ['code' => 'IMP',  'display' => 'Inpatient encounter'],
            'emergency'    => ['code' => 'EMER', 'display' => 'Emergency'],
            'ambulatory',
            'outpatient'   => ['code' => 'AMB',  'display' => 'Ambulatory'],
            'observation'  => ['code' => 'OBSENC','display' => 'Observation encounter'],
            default        => ['code' => 'AMB',  'display' => 'Ambulatory'],
        };

        return [
            'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
            'code'    => $code['code'],
            'display' => $code['display'],
        ];
    }
}
