<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PublicHealthReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_type_id' => $this->report_type_id,
            'facility_id' => $this->facility_id,
            'district_id' => $this->district_id,
            'region_id' => $this->region_id,
            'reporting_period_start' => $this->reporting_period_start?->toISOString(),
            'reporting_period_end' => $this->reporting_period_end?->toISOString(),
            'status' => $this->status,
            'sensitivity_level' => $this->sensitivity_level,
            'data_classification' => $this->data_classification,
            'generated_by_system' => (bool) $this->generated_by_system,
            'data_quality_score' => $this->data_quality_score,
            'requires_review' => (bool) $this->requires_review,
            'requires_correction' => (bool) $this->requires_correction,
            'payload_json' => $this->payload_json,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
