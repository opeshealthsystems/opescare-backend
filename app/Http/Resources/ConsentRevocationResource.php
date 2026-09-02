<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ConsentRevocationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'consent_grant_id' => $this->consent_grant_id,
            'revoked_by'       => $this->revoked_by,
            'reason'           => $this->reason,
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
