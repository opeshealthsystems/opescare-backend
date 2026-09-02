<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\ImmunizationRecord;

/**
 * FHIR R4 Immunization Resource Mapper
 *
 * Maps an OpesCare ImmunizationRecord to a FHIR R4 Immunization resource.
 * Reference: https://hl7.org/fhir/R4/immunization.html
 */
class FhirImmunizationMapper
{
    public function toFhir(ImmunizationRecord $record): array
    {
        $resource = [
            'resourceType' => 'Immunization',
            'id'           => $record->id,
            'meta'         => [
                'lastUpdated' => $record->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'status' => $this->mapStatus($record->status),
            'vaccineCode' => [
                'coding' => [
                    [
                        'system'  => $record->vaccine_system ?? 'https://opescare.com/fhir/vaccine-codes',
                        'code'    => $record->vaccine_code,
                        'display' => $record->vaccine_name,
                    ],
                ],
                'text' => $record->vaccine_name,
            ],
            'patient' => [
                'reference' => 'Patient/' . $record->patient_id,
            ],
            'occurrenceDateTime' => $record->administered_at?->toIso8601String(),
            'primarySource'      => ! $record->is_historical,
        ];

        if ($record->lot_number) {
            $resource['lotNumber'] = $record->lot_number;
        }

        if ($record->manufacturer) {
            $resource['manufacturer'] = [
                'display' => $record->manufacturer,
            ];
        }

        if ($record->expiry_date) {
            $resource['expirationDate'] = $record->expiry_date->toDateString();
        }

        if ($record->site) {
            $resource['site'] = [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActSite',
                        'display' => $record->site,
                    ],
                ],
                'text' => $record->site,
            ];
        }

        if ($record->route) {
            $resource['route'] = [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-RouteOfAdministration',
                        'display' => $record->route,
                    ],
                ],
                'text' => $record->route,
            ];
        }

        if ($record->dose_quantity && $record->dose_unit) {
            $resource['doseQuantity'] = [
                'value'  => (float) $record->dose_quantity,
                'unit'   => $record->dose_unit,
                'system' => 'http://unitsofmeasure.org',
            ];
        }

        if ($record->administered_by_id) {
            $resource['performer'] = [
                [
                    'actor' => ['reference' => 'Practitioner/' . $record->administered_by_id],
                ],
            ];
        }

        if ($record->facility_id) {
            $resource['location'] = [
                'reference' => 'Organization/' . $record->facility_id,
            ];
        }

        if ($record->dose_number || $record->dose_sequence) {
            $resource['protocolApplied'] = [
                [
                    'doseNumberPositiveInt' => (int) ($record->dose_number ?? $record->dose_sequence),
                ],
            ];
        }

        if ($record->status === 'not-done' && $record->not_done_reason) {
            $resource['statusReason'] = [
                'text' => $record->not_done_reason,
            ];
        }

        return $resource;
    }

    public function toBundle(iterable $records): array
    {
        $entries = collect($records)
            ->map(fn ($r) => ['resource' => $this->toFhir($r)])
            ->all();

        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ];
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'completed', 'administered' => 'completed',
            'not-done', 'refused'       => 'not-done',
            'entered-in-error'          => 'entered-in-error',
            default                     => 'completed',
        };
    }
}
