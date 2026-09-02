<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AntenatalRecordResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'patient_id'      => $this->patient_id,
            'provider_id'     => $this->provider_id,
            'facility_id'     => $this->facility_id,
            'lmp'             => $this->lmp?->toISOString(),
            'edd'             => $this->edd?->toISOString(),
            'gravida'         => $this->gravida,
            'para'            => $this->para,
            'pregnancy_status' => $this->pregnancy_status,
            'blood_type'      => $this->blood_type,
            'rhesus_factor'   => $this->rhesus_factor,
            'high_risk'       => (bool) $this->high_risk,
            'risk_factors'    => $this->risk_factors,
            'notes'           => $this->notes,
            'registered_at'   => $this->registered_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),

            // Relations are emitted only when eager-loaded, preserving the
            // pre-resource wire shape of the endpoints that load them.
            'patient'         => $this->whenLoaded('patient'),
            'facility'        => $this->whenLoaded('facility'),
            'provider'        => $this->whenLoaded('provider'),
        ];
    }
}
