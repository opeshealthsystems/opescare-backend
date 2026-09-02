<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EmergencyAccessEventResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'patient_id'     => $this->patient_id,
            'facility_id'    => $this->facility_id,
            'provider_id'    => $this->provider_id,
            'reason'         => $this->reason,
            'records_viewed' => $this->records_viewed,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
