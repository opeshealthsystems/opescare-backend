<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\Prescription;
use App\Models\PrescriptionItem;

/**
 * FHIR R4 MedicationRequest Resource Mapper
 *
 * Maps an OpesCare Prescription (or PrescriptionItem) to a FHIR R4
 * MedicationRequest resource.
 * Reference: https://hl7.org/fhir/R4/medicationrequest.html
 */
class FhirMedicationRequestMapper
{
    /**
     * Map a full Prescription (all items) to a FHIR Bundle of MedicationRequests.
     *
     * @return array[]
     */
    public function prescriptionToBundle(Prescription $prescription): array
    {
        return $prescription->items->map(
            fn ($item) => $this->itemToFhir($item, $prescription)
        )->values()->all();
    }

    /**
     * Map a single PrescriptionItem to a FHIR MedicationRequest.
     */
    public function itemToFhir(PrescriptionItem $item, Prescription $prescription): array
    {
        $resource = [
            'resourceType'      => 'MedicationRequest',
            'id'                => $item->id,
            'meta'              => [
                'lastUpdated' => $item->updated_at?->toIso8601String(),
                'source'      => 'OpesCare',
            ],
            'status'            => $this->mapStatus($item->status),
            'intent'            => 'order',
            'medicationCodeableConcept' => [
                'text' => $item->drug_name,
                'coding' => array_filter([
                    $item->drug_code ? [
                        'system'  => 'https://opescare.com/fhir/drug-code',
                        'code'    => $item->drug_code,
                        'display' => $item->drug_name,
                    ] : null,
                ]),
            ],
            'subject' => ['reference' => 'Patient/' . $prescription->patient_id],
        ];

        if ($prescription->visit_id) {
            $resource['encounter'] = ['reference' => 'Encounter/' . $prescription->visit_id];
        }

        if ($prescription->prescribed_by) {
            $resource['requester'] = ['reference' => 'Practitioner/' . $prescription->prescribed_by];
        }

        $resource['authoredOn'] = $prescription->prescribed_at?->toIso8601String();

        // Dosage instruction
        $dosage = [];
        if ($item->dose || $item->frequency || $item->route) {
            $instruction = [];
            if ($item->dose) {
                $instruction['text'] = $item->dose;
                if ($item->frequency) {
                    $instruction['text'] .= ' ' . $item->frequency;
                }
            }
            if ($item->route) {
                $instruction['route'] = ['text' => $item->route];
            }
            if ($item->duration_days) {
                $instruction['timing'] = [
                    'repeat' => ['duration' => $item->duration_days, 'durationUnit' => 'd'],
                ];
            }
            $dosage[] = $instruction;
        }
        if (!empty($dosage)) {
            $resource['dosageInstruction'] = $dosage;
        }

        // Dispense request
        if ($item->quantity) {
            $resource['dispenseRequest'] = [
                'quantity' => [
                    'value' => $item->quantity,
                ],
            ];
        }

        return $resource;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'dispensed'  => 'completed',
            'cancelled'  => 'cancelled',
            'pending'    => 'active',
            default      => 'active',
        };
    }
}
