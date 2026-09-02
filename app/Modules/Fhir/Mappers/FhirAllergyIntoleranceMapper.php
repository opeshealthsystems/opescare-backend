<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\AllergyRecord;

/**
 * FHIR R4 AllergyIntolerance Resource Mapper
 *
 * Maps an OpesCare AllergyRecord to a FHIR R4 AllergyIntolerance resource.
 * Reference: https://hl7.org/fhir/R4/allergyintolerance.html
 */
class FhirAllergyIntoleranceMapper
{
    public function toFhir(AllergyRecord $allergy): array
    {
        $resource = [
            'resourceType' => 'AllergyIntolerance',
            'id'           => $allergy->id,
            'meta'         => [
                'lastUpdated' => $allergy->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                        'code'   => $this->mapClinicalStatus($allergy->status),
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                        'code'   => 'confirmed',
                    ],
                ],
            ],
            'type'     => 'allergy',
            'category' => ['medication'],   // default; no category stored in AllergyRecord
            'criticality' => $this->mapCriticality($allergy->severity),
            'code' => [
                'text' => $allergy->substance,
            ],
            'patient' => [
                'reference' => 'Patient/' . $allergy->patient_id,
            ],
            'recordedDate' => $allergy->created_at?->toIso8601String(),
            'reaction' => [
                [
                    'substance' => ['text' => $allergy->substance],
                    'severity'  => $this->mapSeverity($allergy->severity),
                ],
            ],
        ];

        if ($allergy->provider_id) {
            $resource['recorder'] = [
                'reference' => 'Practitioner/' . $allergy->provider_id,
            ];
        }

        return $resource;
    }

    public function toBundle(iterable $allergies): array
    {
        $entries = collect($allergies)
            ->map(fn ($a) => ['resource' => $this->toFhir($a)])
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
            'active'           => 'active',
            'inactive'         => 'inactive',
            'entered-in-error' => 'resolved',
            default            => 'active',
        };
    }

    private function mapCriticality(string $severity): string
    {
        return match (strtolower($severity)) {
            'severe', 'high', 'life-threatening' => 'high',
            'moderate'                            => 'low',
            'low', 'mild'                         => 'low',
            default                               => 'unable-to-assess',
        };
    }

    private function mapSeverity(string $severity): string
    {
        return match (strtolower($severity)) {
            'severe', 'high', 'life-threatening' => 'severe',
            'moderate'                            => 'moderate',
            default                               => 'mild',
        };
    }
}
