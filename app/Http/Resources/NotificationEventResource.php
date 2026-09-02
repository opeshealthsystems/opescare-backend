<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NotificationEventResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'event_type' => $this->event_type,
            'communication_type' => $this->communication_type,
            'actor_id' => $this->actor_id,
            'recipient_user_id' => $this->recipient_user_id,
            'recipient_contact' => $this->recipient_contact,
            'recipient_type' => $this->recipient_type,
            'related_resource_type' => $this->related_resource_type,
            'related_resource_id' => $this->related_resource_id,
            'payload_json' => $this->payload_json,
            'priority' => $this->priority,
            'status' => $this->status,
            'requires_acknowledgement' => (bool) $this->requires_acknowledgement,
            'acknowledgement_status' => $this->acknowledgement_status,
            'acknowledged_by' => $this->acknowledged_by,
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'acknowledgement_deadline' => $this->acknowledgement_deadline?->toISOString(),
            'escalation_chain_id' => $this->escalation_chain_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
