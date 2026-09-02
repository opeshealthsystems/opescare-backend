<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SpecialtyDiagnosticReportResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'patient_id'             => $this->patient_id,
            'facility_id'            => $this->facility_id,
            'reporting_clinician_id' => $this->reporting_clinician_id,
            'report_type'            => $this->report_type,
            'study_date'             => $this->study_date?->toISOString(),
            'indication'             => $this->indication,
            'findings'               => $this->findings,
            'impression'             => $this->impression,
            'recommendation'         => $this->recommendation,
            'measurements'           => $this->measurements,
            'image_refs'             => $this->image_refs,
            'status'                 => $this->status,
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
