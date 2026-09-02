<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConsentGrantResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'patient_id'         => $this->patient_id,
            'facility_id'        => $this->facility_id,
            'consent_request_id' => $this->consent_request_id,
            'authorizing_actor'  => $this->authorizing_actor,
            'scope'              => $this->scope,
            'status'             => $this->status,
            'expires_at'         => $this->expires_at?->toISOString(),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
