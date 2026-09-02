<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConsentRequestResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'patient_id'               => $this->patient_id,
            'requesting_facility_id'   => $this->requesting_facility_id,
            'requesting_facility_name' => $this->whenLoaded('requestingFacility', fn () => $this->requestingFacility?->name),
            'requesting_facility_type' => $this->whenLoaded('requestingFacility', fn () => $this->requestingFacility?->type),
            'requesting_user_id'       => $this->requesting_user_id,
            'purpose'                  => $this->purpose,
            'requested_scope'          => $this->requested_scope,
            'duration_minutes'         => $this->duration_minutes,
            'status'                   => $this->status,
            // The active grant this request produced, if approved — the id a
            // patient-initiated revoke (POST /mobile/consents/{id}/revoke) targets.
            'grant_id'                 => $this->whenLoaded('grant', fn () => $this->grant?->id),
            'grant_status'             => $this->whenLoaded('grant', fn () => $this->grant?->status),
            'grant_expires_at'         => $this->whenLoaded('grant', fn () => $this->grant?->expires_at?->toISOString()),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
