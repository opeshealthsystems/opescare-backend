<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LabPathReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'patient_id'      => $this->patient_id,
            'facility_id'     => $this->facility_id,
            'reported_by'     => $this->reported_by,
            'report_type'     => $this->report_type,
            'collected_date'  => $this->collected_date?->toISOString(),
            'reported_date'   => $this->reported_date?->toISOString(),
            'specimen_type'   => $this->specimen_type,
            'test_name'       => $this->test_name,
            'results'         => $this->results,
            'reference_range' => $this->reference_range,
            'interpretation'  => $this->interpretation,
            'critical_value'  => (bool) $this->critical_value,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
