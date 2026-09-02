<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\User;

/**
 * FhirPractitionerMapper
 *
 * Maps OpesCare User (clinical staff) to FHIR R4 Practitioner resource.
 * Read-only transformation — no writes to any table.
 */
class FhirPractitionerMapper
{
    /**
     * Map a User to a FHIR R4 Practitioner resource.
     */
    public function toFhir(User $user): array
    {
        $resource = [
            'resourceType' => 'Practitioner',
            'id'           => $user->id,
            'meta'         => [
                'lastUpdated' => optional($user->updated_at)->toIso8601String(),
            ],
            'identifier'   => [
                [
                    'system' => 'https://opescare.com/fhir/practitioners',
                    'value'  => $user->id,
                ],
            ],
            'active' => ($user->status ?? 'active') === 'active',
            'name'   => [
                [
                    'use'  => 'official',
                    'text' => $user->name,
                ],
            ],
        ];

        // Email as telecom
        if ($user->email) {
            $resource['telecom'][] = [
                'system' => 'email',
                'value'  => $user->email,
                'use'    => 'work',
            ];
        }

        // Role → qualification
        if ($user->role) {
            $resource['qualification'][] = [
                'code' => [
                    'coding' => [
                        [
                            'system'  => 'https://opescare.com/fhir/roles',
                            'code'    => $user->role->name ?? $user->role_id,
                            'display' => ucfirst(str_replace('_', ' ', $user->role->name ?? '')),
                        ],
                    ],
                ],
            ];
        }

        return $resource;
    }

    /**
     * Map a collection of Users to a FHIR Bundle (searchset).
     *
     * @param \Illuminate\Support\Collection<User> $users
     */
    public function toBundle(\Illuminate\Support\Collection $users): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $users->count(),
            'entry'        => $users->map(fn($u) => ['resource' => $this->toFhir($u)])->values()->toArray(),
        ];
    }
}
