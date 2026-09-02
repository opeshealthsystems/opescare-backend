<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\PatientInsurancePolicy;

/**
 * FhirCoverageMapper
 *
 * Maps OpesCare PatientInsurancePolicy to FHIR R4 Coverage resource.
 * Read-only transformation — no writes to any table.
 */
class FhirCoverageMapper
{
    /**
     * Map a PatientInsurancePolicy to a FHIR R4 Coverage resource.
     */
    public function toFhir(PatientInsurancePolicy $policy): array
    {
        $statusMap = [
            'active'    => 'active',
            'inactive'  => 'cancelled',
            'suspended' => 'entered-in-error',
            'expired'   => 'cancelled',
        ];

        $fhirStatus = $statusMap[$policy->status ?? ''] ?? 'active';

        $resource = [
            'resourceType' => 'Coverage',
            'id'           => $policy->id,
            'meta'         => [
                'lastUpdated' => optional($policy->updated_at)->toIso8601String(),
            ],
            'identifier'   => [
                [
                    'system' => 'https://opescare.com/fhir/coverage',
                    'value'  => $policy->policy_number ?? $policy->id,
                ],
            ],
            'status'       => $fhirStatus,
            'beneficiary'  => [
                'reference' => 'Patient/' . $policy->patient_id,
            ],
            'subscriberId' => $policy->member_id ?? $policy->policy_number,
        ];

        // Insurance plan reference
        if ($policy->insurance_plan_id) {
            $resource['payor'][] = [
                'reference' => 'Organization/' . $policy->insurance_plan_id,
            ];
        }

        // Group number
        if ($policy->group_number) {
            $resource['class'][] = [
                'type'  => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/coverage-class',
                        'code'   => 'group',
                    ]],
                ],
                'value' => $policy->group_number,
            ];
        }

        // Coverage period
        if ($policy->effective_date || $policy->expiry_date) {
            $resource['period'] = [];
            if ($policy->effective_date) {
                $resource['period']['start'] = $policy->effective_date->toDateString();
            }
            if ($policy->expiry_date) {
                $resource['period']['end'] = $policy->expiry_date->toDateString();
            }
        }

        // Relationship
        if ($policy->relationship_to_primary) {
            $resource['relationship'] = [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/subscriber-relationship',
                        'code'    => $policy->relationship_to_primary === 'self' ? 'self' : 'other',
                        'display' => ucfirst($policy->relationship_to_primary),
                    ],
                ],
            ];
        }

        return $resource;
    }

    /**
     * Map a collection of policies to a FHIR Bundle.
     *
     * @param \Illuminate\Support\Collection<PatientInsurancePolicy> $policies
     */
    public function toBundle(\Illuminate\Support\Collection $policies): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $policies->count(),
            'entry'        => $policies->map(fn($p) => ['resource' => $this->toFhir($p)])->values()->toArray(),
        ];
    }
}
