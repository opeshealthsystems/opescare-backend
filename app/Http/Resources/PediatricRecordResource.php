<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PediatricRecordResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'patient_id'           => $this->patient_id,
            'facility_id'          => $this->facility_id,
            'clinician_id'         => $this->clinician_id,
            'record_type'          => $this->record_type,
            'record_date'          => $this->record_date?->toISOString(),
            'age_days'             => $this->age_days,
            'weight_kg'            => $this->weight_kg,
            'height_cm'            => $this->height_cm,
            'head_circumference_cm' => $this->head_circumference_cm,
            'apgar_1min'           => $this->apgar_1min,
            'apgar_5min'           => $this->apgar_5min,
            'gestational_age_weeks' => $this->gestational_age_weeks,
            'milestones'           => $this->milestones,
            'immunisations_given'  => $this->immunisations_given,
            'growth_data'          => $this->growth_data,
            'clinical_notes'       => $this->clinical_notes,
            'status'               => $this->status,
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
