<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SpecialCareRecordResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'patient_id'      => $this->patient_id,
            'facility_id'     => $this->facility_id,
            'clinician_id'    => $this->clinician_id,
            'care_type'       => $this->care_type,
            'record_date'     => $this->record_date?->toISOString(),
            'session_number'  => $this->session_number,
            'vitals'          => $this->vitals,
            'medications'     => $this->medications,
            'observations'    => $this->observations,
            'clinical_notes'  => $this->clinical_notes,
            'duration_minutes' => $this->duration_minutes,
            'outcome'         => $this->outcome,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
