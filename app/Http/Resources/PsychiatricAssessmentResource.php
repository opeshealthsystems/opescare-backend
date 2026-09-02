<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PsychiatricAssessmentResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'patient_id'            => $this->patient_id,
            'facility_id'           => $this->facility_id,
            'clinician_id'          => $this->clinician_id,
            'assessment_date'       => $this->assessment_date?->toISOString(),
            'referral_source'       => $this->referral_source,
            'presenting_complaints' => $this->presenting_complaints,
            'psychiatric_history'   => $this->psychiatric_history,
            'family_history'        => $this->family_history,
            'substance_use_history' => $this->substance_use_history,
            'mental_state_examination' => $this->mental_state_examination,
            'risk_factors'          => $this->risk_factors,
            'diagnosis_icd'         => $this->diagnosis_icd,
            'diagnosis_narrative'   => $this->diagnosis_narrative,
            'management_plan'       => $this->management_plan,
            'medications_current'   => $this->medications_current,
            'risk_level'            => $this->risk_level,
            'follow_up_plan'        => $this->follow_up_plan,
            'notes'                 => $this->notes,
            'status'                => $this->status,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
