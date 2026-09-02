<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\Facility;

/**
 * FhirOrganizationMapper
 *
 * Maps OpesCare Facility to FHIR R4 Organization resource.
 * Read-only transformation — no writes to any table.
 */
class FhirOrganizationMapper
{
    /**
     * Map a Facility to a FHIR R4 Organization resource.
     */
    public function toFhir(Facility $facility): array
    {
        $typeMap = [
            'hospital'      => ['code' => 'prov', 'display' => 'Healthcare Provider'],
            'clinic'        => ['code' => 'prov', 'display' => 'Healthcare Provider'],
            'laboratory'    => ['code' => 'other', 'display' => 'Laboratory'],
            'pharmacy'      => ['code' => 'other', 'display' => 'Pharmacy'],
            'health_centre' => ['code' => 'prov', 'display' => 'Healthcare Provider'],
        ];

        $typeEntry = $typeMap[$facility->type ?? ''] ?? ['code' => 'other', 'display' => 'Other'];

        $resource = [
            'resourceType' => 'Organization',
            'id'           => $facility->id,
            'meta'         => [
                'lastUpdated' => optional($facility->updated_at)->toIso8601String(),
            ],
            'identifier'   => [
                [
                    'system' => 'https://opescare.com/fhir/facilities',
                    'value'  => $facility->id,
                ],
            ],
            'active' => ($facility->status ?? 'active') === 'active',
            'type'   => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/organization-type',
                            'code'    => $typeEntry['code'],
                            'display' => $typeEntry['display'],
                        ],
                    ],
                    'text' => ucfirst(str_replace('_', ' ', $facility->type ?? '')),
                ],
            ],
            'name' => $facility->name,
        ];

        // Address
        if ($facility->country_code) {
            $resource['address'][] = [
                'country' => $facility->country_code,
                'use'     => 'work',
            ];
        }

        return $resource;
    }

    /**
     * Map a collection of Facilities to a FHIR Bundle (searchset).
     *
     * @param \Illuminate\Support\Collection<Facility> $facilities
     */
    public function toBundle(\Illuminate\Support\Collection $facilities): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $facilities->count(),
            'entry'        => $facilities->map(fn($f) => ['resource' => $this->toFhir($f)])->values()->toArray(),
        ];
    }
}
