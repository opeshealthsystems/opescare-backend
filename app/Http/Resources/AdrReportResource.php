<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdrReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'patient_id'           => $this->patient_id,
            'facility_id'          => $this->facility_id,
            'reporter_id'          => $this->reporter_id,
            'suspect_drug'         => $this->suspect_drug,
            'suspect_drug_batch'   => $this->suspect_drug_batch,
            'suspect_drug_dose'    => $this->suspect_drug_dose,
            'suspect_drug_route'   => $this->suspect_drug_route,
            'indication_for_use'   => $this->indication_for_use,
            'reaction_onset_date'  => $this->reaction_onset_date?->toISOString(),
            'reaction_description' => $this->reaction_description,
            'severity'             => $this->severity,
            'causality_assessment' => $this->causality_assessment,
            'drug_stopped'         => (bool) $this->drug_stopped,
            'rechallenged'         => (bool) $this->rechallenged,
            'reaction_resolved'    => (bool) $this->reaction_resolved,
            'outcome'              => $this->outcome,
            'concomitant_drugs'    => $this->concomitant_drugs,
            'reporter_profession'  => $this->reporter_profession,
            'notes'                => $this->notes,
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
