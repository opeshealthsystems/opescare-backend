<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\DrugFormulary. Add fields here deliberately;
 * never expose the model directly.
 */
class DrugFormularyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'facility_id'         => $this->facility_id,
            'generic_name'        => $this->generic_name,
            'brand_names'         => $this->brand_names,
            'drug_code'           => $this->drug_code,
            'drug_class'          => $this->drug_class,
            'form'                => $this->form,
            'strength'            => $this->strength,
            'unit'                => $this->unit,
            'is_available'        => (bool) $this->is_available,
            'is_controlled'       => (bool) $this->is_controlled,
            'requires_prior_auth' => (bool) $this->requires_prior_auth,
            'restricted_to'       => $this->restricted_to,
            'notes'               => $this->notes,
            'created_by'          => $this->created_by,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
