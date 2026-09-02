<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SecurityIncidentResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incident_type' => $this->incident_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'summary' => $this->summary,
            'detected_at' => $this->detected_at?->toISOString(),
            'contained_at' => $this->contained_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
