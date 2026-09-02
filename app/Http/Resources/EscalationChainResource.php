<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EscalationChainResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'event_type' => $this->event_type,
            'facility_id' => $this->facility_id,
            'department_id' => $this->department_id,
            'steps_json' => $this->steps_json,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
