<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OccupationalHealthAssessmentResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'patient_id'         => $this->patient_id,
            'facility_id'        => $this->facility_id,
            'examiner_id'        => $this->examiner_id,
            'assessment_date'    => $this->assessment_date?->toISOString(),
            'assessment_type'    => $this->assessment_type,
            'job_title'          => $this->job_title,
            'employer'           => $this->employer,
            'exposure_history'   => $this->exposure_history,
            'clinical_findings'  => $this->clinical_findings,
            'fitness_conclusion' => $this->fitness_conclusion,
            'restrictions'       => $this->restrictions,
            'next_review_date'   => $this->next_review_date?->toISOString(),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
