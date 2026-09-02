<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\AllergyRecord. Add fields here deliberately;
 * never expose the model directly.
 */
class AllergyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'patient_id'  => $this->patient_id,
            'provider_id' => $this->provider_id,
            'substance'   => $this->substance,
            'severity'    => $this->severity,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
