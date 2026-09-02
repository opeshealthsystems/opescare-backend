<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CarePlanInterventionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'care_plan_id'      => $this->care_plan_id,
            'intervention_type' => $this->intervention_type,
            'description'       => $this->description,
            'frequency'         => $this->frequency,
            'responsible_party' => $this->responsible_party,
            'status'            => $this->status,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
