<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PalliativeCarePlanResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'facility_id'         => $this->facility_id,
            'lead_clinician_id'   => $this->lead_clinician_id,
            'diagnosis'           => $this->diagnosis,
            'prognosis'           => $this->prognosis,
            'goals_of_care'       => $this->goals_of_care,
            'pain_management_plan' => $this->pain_management_plan,
            'symptom_management'  => $this->symptom_management,
            'psychological_support' => $this->psychological_support,
            'spiritual_support'   => $this->spiritual_support,
            'family_support'      => $this->family_support,
            'dnr_status'          => (bool) $this->dnr_status,
            'advance_directive_id' => $this->advance_directive_id,
            'status'              => $this->status,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
