<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientSurveyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'facility_id'  => $this->facility_id,
            'visit_id'     => $this->visit_id,
            'template_key' => $this->template_key,
            'status'       => $this->status,
            'sent_at'      => $this->sent_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'expires_at'   => $this->expires_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
