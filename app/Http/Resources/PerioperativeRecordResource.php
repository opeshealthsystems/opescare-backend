<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PerioperativeRecordResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'facility_id'         => $this->facility_id,
            'provider_id'         => $this->provider_id,
            'record_type'         => $this->record_type,
            'procedure_name'      => $this->procedure_name,
            'procedure_code'      => $this->procedure_code,
            'procedure_datetime'  => $this->procedure_datetime?->toISOString(),
            'checklist_data'      => $this->checklist_data,
            'anaesthesia_type'    => $this->anaesthesia_type,
            'anaesthesiologist_id' => $this->anaesthesiologist_id,
            'intraoperative_notes' => $this->intraoperative_notes,
            'postop_notes'        => $this->postop_notes,
            'duration_minutes'    => $this->duration_minutes,
            'asa_grade'           => $this->asa_grade,
            'complications'       => (bool) $this->complications,
            'complications_detail' => $this->complications_detail,
            'status'              => $this->status,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
