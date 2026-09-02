<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReportSubmissionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'submission_profile_id' => $this->submission_profile_id,
            'submission_method' => $this->submission_method,
            'status' => $this->status,
            'external_reference' => $this->external_reference,
            'response_code' => $this->response_code,
            'safe_response_summary' => $this->safe_response_summary,
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'retry_count' => $this->retry_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
