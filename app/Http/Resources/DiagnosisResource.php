<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\Diagnosis. Add fields here deliberately;
 * never expose the model directly.
 */
class DiagnosisResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'patient_id'     => $this->patient_id,
            'visit_id'       => $this->visit_id,
            'provider_id'    => $this->provider_id,
            'display_name'   => $this->display_name,
            'code_system'    => $this->code_system,
            'code'           => $this->code,
            'snomed_code'    => $this->snomed_code,
            'snomed_display' => $this->snomed_display,
            'status'         => $this->status,
            'is_primary'     => (bool) $this->is_primary,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
