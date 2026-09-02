<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\ResearchAccessLog (append-only audit row).
 * Add fields here deliberately; never expose the model directly.
 */
class ResearchAccessLogResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'research_access_request_id' => $this->research_access_request_id,
            'researcher_profile_id'      => $this->researcher_profile_id,
            'action'                     => $this->action,
            'action_context'             => $this->action_context,
            'ip_address'                 => $this->ip_address,
            'occurred_at'                => $this->occurred_at?->toISOString(),
            'created_at'                 => $this->created_at?->toISOString(),
            'updated_at'                 => $this->updated_at?->toISOString(),
        ];
    }
}
