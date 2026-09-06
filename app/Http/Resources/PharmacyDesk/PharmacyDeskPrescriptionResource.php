<?php

namespace App\Http\Resources\PharmacyDesk;

use App\Http\Resources\ApiResource;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;

/**
 * A prescription as the pharmacy counter sees it.
 *
 *   { id, reference, status, pharmacy_routing_status, prescribed_at, expires_at,
 *     prescriber_name, patient: {id, health_id, display_name},
 *     items: [{id, drug_name, dose, frequency, duration_days, quantity, status}] }
 *
 * `status` is the CLINICAL state (active | dispensed | voided …) and
 * `pharmacy_routing_status` the counter's own (pending | sent | confirmed |
 * ready | dispensed). They are different questions — "may this be filled?"
 * and "where is it on my bench?" — and the app needs both.
 *
 * Nothing clinical beyond the script itself: no diagnosis, no notes from the
 * chart. The pharmacist identifies the patient by Health ID and name only.
 */
class PharmacyDeskPrescriptionResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Prescription $rx */
        $rx = $this->resource;

        $items = $rx->relationLoaded('items') ? $rx->items : $rx->items()->get();

        // The patient relation is demo-scoped like every other read of it; a
        // null here means scoped out, and the row still has to render.
        $patient = $rx->relationLoaded('patient') ? $rx->patient : null;

        return [
            'id'                      => $rx->id,
            'reference'               => $rx->reference,
            'status'                  => $rx->status,
            'pharmacy_routing_status' => $rx->pharmacy_routing_status,
            'prescribed_at'           => $rx->prescribed_at?->toIso8601String(),
            'expires_at'              => $rx->expires_at?->toIso8601String(),
            'dispensed_at'            => $rx->dispensed_at?->toIso8601String(),
            'notes'                   => $rx->notes,
            'prescriber_name'         => $rx->relationLoaded('prescriber') ? $rx->prescriber?->name : null,
            'patient'                 => [
                'id'           => $rx->patient_id,
                'health_id'    => $patient?->health_id,
                'display_name' => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
            ],
            'items'                   => $items->map(fn (PrescriptionItem $item) => [
                'id'             => $item->id,
                'drug_name'      => $item->drug_name,
                'medicine_id'    => $item->medicine_id,
                'dose'           => $item->dose,
                'frequency'      => $item->frequency,
                'route'          => $item->route,
                'duration_days'  => $item->duration_days,
                'quantity'       => $item->quantity,
                'status'         => $item->status,
                'dispense_notes' => $item->dispense_notes,
            ])->values()->all(),
        ];
    }
}
