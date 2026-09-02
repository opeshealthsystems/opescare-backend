<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\ConsentGrant;

/**
 * FhirConsentMapper
 *
 * Maps OpesCare ConsentGrant to FHIR R4 Consent resource.
 * Read-only transformation — no writes to any table.
 */
class FhirConsentMapper
{
    /**
     * Map a ConsentGrant to a FHIR R4 Consent resource.
     */
    public function toFhir(ConsentGrant $grant): array
    {
        $statusMap = [
            'granted'   => 'active',
            'revoked'   => 'inactive',
            'expired'   => 'inactive',
            'pending'   => 'proposed',
        ];

        $fhirStatus = $statusMap[$grant->status ?? ''] ?? 'active';

        $resource = [
            'resourceType' => 'Consent',
            'id'           => $grant->id,
            'meta'         => [
                'lastUpdated' => optional($grant->updated_at)->toIso8601String(),
            ],
            'status' => $fhirStatus,
            'scope'  => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/consentscope',
                        'code'    => 'patient-privacy',
                        'display' => 'Privacy Consent',
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://loinc.org',
                            'code'    => '59284-0',
                            'display' => 'Consent Document',
                        ],
                    ],
                ],
            ],
            'patient' => [
                'reference' => 'Patient/' . $grant->patient_id,
            ],
            'dateTime' => optional($grant->created_at)->toIso8601String(),
        ];

        // Organization (facility)
        if ($grant->facility_id) {
            $resource['organization'][] = [
                'reference' => 'Organization/' . $grant->facility_id,
            ];
        }

        // Expiry
        if ($grant->expires_at) {
            $resource['provision']['period']['end'] = $grant->expires_at->toIso8601String();
        }

        // Scope as provision data
        if (!empty($grant->scope)) {
            $resource['provision']['type'] = 'permit';
            $resource['provision']['purpose'] = collect($grant->scope)->map(fn($s) => [
                'system'  => 'https://opescare.com/fhir/consent-purpose',
                'code'    => $s,
                'display' => ucfirst(str_replace('_', ' ', $s)),
            ])->values()->toArray();
        }

        return $resource;
    }

    /**
     * Map a collection of ConsentGrants to a FHIR Bundle.
     *
     * @param \Illuminate\Support\Collection<ConsentGrant> $grants
     */
    public function toBundle(\Illuminate\Support\Collection $grants): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $grants->count(),
            'entry'        => $grants->map(fn($g) => ['resource' => $this->toFhir($g)])->values()->toArray(),
        ];
    }
}
