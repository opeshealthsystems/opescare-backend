<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\DeliveryRecord. Add fields here deliberately;
 * never expose the model directly.
 */
class DeliveryRecordResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'pregnancy_record_id'   => $this->pregnancy_record_id,
            'patient_id'            => $this->patient_id,
            'facility_id'           => $this->facility_id,
            'provider_id'           => $this->provider_id,
            'delivery_date'         => $this->delivery_date?->toISOString(),
            'delivery_mode'         => $this->delivery_mode,
            'indication'            => $this->indication,
            'duration_labour_hours' => $this->duration_labour_hours,
            'birth_weight_grams'    => $this->birth_weight_grams,
            'apgar_1min'            => $this->apgar_1min,
            'apgar_5min'            => $this->apgar_5min,
            'neonatal_outcome'      => $this->neonatal_outcome,
            'complications'         => $this->complications,
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),

            // Relations are emitted only when eager-loaded, preserving the
            // pre-resource wire shape of the endpoints that load them.
            'patient'               => $this->whenLoaded('patient'),
            'provider'              => $this->whenLoaded('provider'),
            'pregnancyRecord'       => $this->whenLoaded('pregnancyRecord'),
        ];
    }
}
