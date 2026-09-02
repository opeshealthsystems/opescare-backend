<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PublicHealthSignalResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'signal_type'         => $this->signal_type,
            'status'              => $this->status,
            'scope_type'          => $this->scope_type,
            'scope_id'            => $this->scope_id,
            'facility_id'         => $this->facility_id,
            'district_id'         => $this->district_id,
            'region_id'           => $this->region_id,
            'condition_code'      => $this->condition_code,
            'indicator_code'      => $this->indicator_code,
            'baseline_value'      => $this->baseline_value,
            'current_value'       => $this->current_value,
            'increase_percentage' => $this->increase_percentage,
            'confidence_level'    => $this->confidence_level,
            'severity'            => $this->severity,
            'detected_at'         => $this->detected_at?->toISOString(),
            'reviewed_at'         => $this->reviewed_at?->toISOString(),
            'resolved_at'         => $this->resolved_at?->toISOString(),
            'metadata_json'       => $this->metadata_json,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
