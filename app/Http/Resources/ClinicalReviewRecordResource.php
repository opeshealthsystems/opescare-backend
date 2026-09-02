<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ClinicalReviewRecordResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'facility_id'           => $this->facility_id,
            'reviewer_id'           => $this->reviewer_id,
            'patient_id'            => $this->patient_id,
            'review_type'           => $this->review_type,
            'review_date'           => $this->review_date?->toISOString(),
            'case_reference'        => $this->case_reference,
            'summary'               => $this->summary,
            'findings'              => $this->findings,
            'recommendations'       => $this->recommendations,
            'outcome'               => $this->outcome,
            'reported_to_authority' => $this->reported_to_authority,
            'status'                => $this->status,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
