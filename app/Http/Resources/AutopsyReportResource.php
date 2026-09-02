<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AutopsyReportResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'mortuary_record_id'       => $this->mortuary_record_id,
            'facility_id'              => $this->facility_id,
            'type'                     => $this->type,
            'pathologist_id'           => $this->pathologist_id,
            'performed_at'             => $this->performed_at?->toISOString(),
            'gross_findings'           => $this->gross_findings,
            'microscopic_findings'     => $this->microscopic_findings,
            'toxicology_results'       => $this->toxicology_results,
            'cause_of_death_confirmed' => $this->cause_of_death_confirmed,
            'manner_of_death'          => $this->manner_of_death,
            'external_findings'        => $this->external_findings,
            'notes'                    => $this->notes,
            'status'                   => $this->status,
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
