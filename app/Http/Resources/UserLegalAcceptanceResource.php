<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserLegalAcceptanceResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'legal_document_version_id' => $this->legal_document_version_id,
            'accepted_via'              => $this->accepted_via,
            'ip_address'                => $this->ip_address,
            'user_agent'                => $this->user_agent,
            'accepted_at'               => $this->accepted_at?->toISOString(),
            'revoked_at'                => $this->revoked_at?->toISOString(),
            'created_at'                => $this->created_at?->toISOString(),
            'updated_at'                => $this->updated_at?->toISOString(),
        ];
    }
}
