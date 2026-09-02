<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AefiReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'patient_id'              => $this->patient_id,
            'facility_id'             => $this->facility_id,
            'immunization_record_id'  => $this->immunization_record_id,
            'reporter_id'             => $this->reporter_id,
            'report_date'             => $this->report_date?->toISOString(),
            'onset_date'              => $this->onset_date?->toISOString(),
            'severity'                => $this->severity,
            'event_description'       => $this->event_description,
            'vaccine_name'            => $this->vaccine_name,
            'vaccine_lot'             => $this->vaccine_lot,
            'batch_number'            => $this->batch_number,
            'causality_assessment'    => $this->causality_assessment,
            'outcome'                 => $this->outcome,
            'action_taken'            => $this->action_taken,
            'reported_to_authorities' => (bool) $this->reported_to_authorities,
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
