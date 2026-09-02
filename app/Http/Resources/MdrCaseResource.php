<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MdrCaseResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'patient_id'              => $this->patient_id,
            'facility_id'             => $this->facility_id,
            'registered_at'           => $this->registered_at?->toISOString(),
            'diagnosis_basis'         => $this->diagnosis_basis,
            'drug_resistance_profile' => $this->drug_resistance_profile,
            'treatment_regimen'       => $this->treatment_regimen,
            'treatment_start_date'    => $this->treatment_start_date?->toISOString(),
            'treatment_end_date'      => $this->treatment_end_date?->toISOString(),
            'treatment_outcome'       => $this->treatment_outcome,
            'supervising_doctor_id'   => $this->supervising_doctor_id,
            'status'                  => $this->status,
            'notes'                   => $this->notes,
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
