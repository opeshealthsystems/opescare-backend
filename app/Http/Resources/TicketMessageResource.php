<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TicketMessageResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'support_ticket_id'     => $this->support_ticket_id,
            'sender_type'           => $this->sender_type,
            'sender_id'             => $this->sender_id,
            'body_redacted'         => $this->body_redacted,
            'pii_redaction_summary' => $this->pii_redaction_summary,
            'internal'              => (bool) $this->internal,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
