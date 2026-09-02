<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PartnerAgreementAcceptanceResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'legal_document_version_id' => $this->legal_document_version_id,
            'partner_type'              => $this->partner_type,
            'partner_id'                => $this->partner_id,
            'accepted_by_name'          => $this->accepted_by_name,
            'accepted_by_email'         => $this->accepted_by_email,
            'accepted_via'              => $this->accepted_via,
            'ip_address'                => $this->ip_address,
            'accepted_at'               => $this->accepted_at?->toISOString(),
            'expires_at'                => $this->expires_at?->toISOString(),
            'created_at'                => $this->created_at?->toISOString(),
            'updated_at'                => $this->updated_at?->toISOString(),
        ];
    }
}
