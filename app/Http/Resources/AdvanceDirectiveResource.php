<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\AdvanceDirective. Add fields here deliberately;
 * never expose the model directly.
 */
class AdvanceDirectiveResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                            => $this->id,
            'patient_id'                    => $this->patient_id,
            'facility_id'                   => $this->facility_id,
            'directive_type'                => $this->directive_type,
            'is_active'                     => (bool) $this->is_active,
            'effective_date'                => $this->effective_date?->toISOString(),
            'expiry_date'                   => $this->expiry_date?->toISOString(),
            'document_path'                 => $this->document_path,
            'witness_name'                  => $this->witness_name,
            'witness_date'                  => $this->witness_date?->toISOString(),
            'healthcare_proxy_name'         => $this->healthcare_proxy_name,
            'healthcare_proxy_phone'        => $this->healthcare_proxy_phone,
            'healthcare_proxy_relationship' => $this->healthcare_proxy_relationship,
            'instructions'                  => $this->instructions,
            'verified_by'                   => $this->verified_by,
            'verified_at'                   => $this->verified_at?->toISOString(),
            'created_at'                    => $this->created_at?->toISOString(),
            'updated_at'                    => $this->updated_at?->toISOString(),
        ];
    }
}
