<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AlertOverrideResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'alert_id'          => $this->alert_id,
            'patient_id'        => $this->patient_id,
            'visit_id'          => $this->visit_id,
            'overridden_by'     => $this->overridden_by,
            'override_reason'   => $this->override_reason,
            'override_category' => $this->override_category,
            'overridden_at'     => $this->overridden_at?->toISOString(),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
