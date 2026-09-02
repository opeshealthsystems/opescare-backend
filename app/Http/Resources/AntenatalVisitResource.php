<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\AntenatalVisit. Add fields here deliberately;
 * never expose the model directly.
 */
class AntenatalVisitResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'pregnancy_record_id'   => $this->pregnancy_record_id,
            'patient_id'            => $this->patient_id,
            'facility_id'           => $this->facility_id,
            'provider_id'           => $this->provider_id,
            'visit_date'            => $this->visit_date?->toISOString(),
            'gestational_age_weeks' => $this->gestational_age_weeks,
            'gestational_age_days'  => $this->gestational_age_days,
            'fundal_height_cm'      => $this->fundal_height_cm,
            'fetal_heart_rate'      => $this->fetal_heart_rate,
            'presentation'          => $this->presentation,
            'weight_kg'             => $this->weight_kg,
            'bp_systolic'           => $this->bp_systolic,
            'bp_diastolic'          => $this->bp_diastolic,
            'urine_protein'         => $this->urine_protein,
            'urine_glucose'         => $this->urine_glucose,
            'oedema'                => $this->oedema,
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),

            // Relations are emitted only when eager-loaded, preserving the
            // pre-resource wire shape of the endpoints that load them.
            'patient'               => $this->whenLoaded('patient'),
            'provider'              => $this->whenLoaded('provider'),
            'pregnancyRecord'       => $this->whenLoaded('pregnancyRecord'),
        ];
    }
}
