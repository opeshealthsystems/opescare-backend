<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DeathRecordResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'patient_id'              => $this->patient_id,
            'facility_id'             => $this->facility_id,
            'certifying_doctor_id'    => $this->certifying_doctor_id,
            'deceased_at'             => $this->deceased_at?->toISOString(),
            'place_of_death'          => $this->place_of_death,
            'manner_of_death'         => $this->manner_of_death,
            'primary_cause'           => $this->primary_cause,
            'secondary_causes'        => $this->secondary_causes,
            'duration_primary'        => $this->duration_primary,
            'contributing_conditions' => $this->contributing_conditions,
            'was_autopsy_performed'   => (bool) $this->was_autopsy_performed,
            'autopsy_report_id'       => $this->autopsy_report_id,
            'registrar_id'            => $this->registrar_id,
            'registered_at'           => $this->registered_at?->toISOString(),
            'official_document_id'    => $this->official_document_id,
            'status'                  => $this->status,
            'notes'                   => $this->notes,
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
