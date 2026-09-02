<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AccessLogResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'patient_id'       => $this->patient_id,
            'actor_id'         => $this->actor_id,
            'actor_type'       => $this->actor_type,
            'organization_id'  => $this->organization_id,
            'facility_id'      => $this->facility_id,
            'purpose'          => $this->purpose,
            'data_category'    => $this->data_category,
            'resource_type'    => $this->resource_type,
            'resource_id'      => $this->resource_id,
            'access_type'      => $this->access_type,
            'emergency_access' => (bool) $this->emergency_access,
            'ip_address'       => $this->ip_address,
            'user_agent'       => $this->user_agent,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
