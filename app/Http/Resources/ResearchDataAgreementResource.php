<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ResearchDataAgreementResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'research_access_request_id' => $this->research_access_request_id,
            'researcher_profile_id'      => $this->researcher_profile_id,
            'agreement_text'             => $this->agreement_text,
            'signed'                     => (bool) $this->signed,
            'signed_at'                  => $this->signed_at?->toISOString(),
            'effective_date'             => $this->effective_date?->toISOString(),
            'expiry_date'                => $this->expiry_date?->toISOString(),
            'created_at'                 => $this->created_at?->toISOString(),
            'updated_at'                 => $this->updated_at?->toISOString(),
        ];
    }
}
