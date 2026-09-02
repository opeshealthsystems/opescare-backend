<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AlliedHealthAssessmentResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'patient_id'           => $this->patient_id,
            'facility_id'          => $this->facility_id,
            'therapist_id'         => $this->therapist_id,
            'assessment_type'      => $this->assessment_type,
            'assessment_date'      => $this->assessment_date?->toISOString(),
            'referral_reason'      => $this->referral_reason,
            'subjective_findings'  => $this->subjective_findings,
            'objective_findings'   => $this->objective_findings,
            'assessment_narrative' => $this->assessment_narrative,
            'intervention_plan'    => $this->intervention_plan,
            'goals'                => $this->goals,
            'sessions_recommended' => $this->sessions_recommended,
            'follow_up_interval'   => $this->follow_up_interval,
            'outcome_measure'      => $this->outcome_measure,
            'status'               => $this->status,
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
