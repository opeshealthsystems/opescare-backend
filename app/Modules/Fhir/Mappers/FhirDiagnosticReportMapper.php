<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\LabOrder;

/**
 * FHIR R4 DiagnosticReport Resource Mapper
 *
 * Maps an OpesCare LabOrder (with its LabResults) to a FHIR R4
 * DiagnosticReport resource.
 * Reference: https://hl7.org/fhir/R4/diagnosticreport.html
 */
class FhirDiagnosticReportMapper
{
    public function toFhir(LabOrder $order): array
    {
        $resource = [
            'resourceType' => 'DiagnosticReport',
            'id'           => $order->id,
            'meta'         => [
                'lastUpdated' => $order->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'status'       => $this->mapStatus($order->status),
            'category'     => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/v2-0074',
                            'code'    => 'LAB',
                            'display' => 'Laboratory',
                        ],
                    ],
                ],
            ],
            'code' => [
                'text' => $order->test_name,
                'coding' => array_filter([
                    $order->test_code ? [
                        'system'  => 'https://opescare.com/fhir/lab-code',
                        'code'    => $order->test_code,
                        'display' => $order->test_name,
                    ] : null,
                ]),
            ],
            'subject' => ['reference' => 'Patient/' . $order->patient_id],
        ];

        if ($order->visit_id) {
            $resource['encounter'] = ['reference' => 'Encounter/' . $order->visit_id];
        }

        if ($order->resulted_at) {
            $resource['effectiveDateTime'] = $order->resulted_at->toIso8601String();
            $resource['issued']            = $order->resulted_at->toIso8601String();
        }

        // Add result references (each LabResult becomes an Observation reference)
        if ($order->results && $order->results->isNotEmpty()) {
            $resource['result'] = $order->results->map(fn ($r) => [
                'reference' => 'Observation/lab-result-' . $r->id,
                'display'   => $r->parameter_name . ': ' . $r->value . ($r->unit ? ' ' . $r->unit : ''),
            ])->values()->all();

            // Inline conclusion from abnormal flags
            $abnormals = $order->results->filter(fn ($r) => $r->isAbnormal());
            if ($abnormals->isNotEmpty()) {
                $resource['conclusion'] = $abnormals->map(
                    fn ($r) => $r->parameter_name . ' (' . $r->flagLabel() . '): ' . $r->value . ($r->unit ? ' ' . $r->unit : '')
                )->join('; ');
                $resource['conclusionCode'] = [
                    ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '74400008', 'display' => 'Abnormal result']]]
                ];
            }
        }

        return $resource;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'resulted'   => 'final',
            'processing' => 'preliminary',
            'collected'  => 'registered',
            'cancelled'  => 'cancelled',
            default      => 'registered',
        };
    }
}
