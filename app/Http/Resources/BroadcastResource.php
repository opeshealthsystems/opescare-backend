<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BroadcastResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'uuid'                     => $this->uuid,
            'broadcast_type'           => $this->broadcast_type,
            'title'                    => $this->title,
            'body'                     => $this->body,
            'target_type'              => $this->target_type,
            'target_ids_json'          => $this->target_ids_json,
            'priority'                 => $this->priority,
            'language'                 => $this->language,
            'requires_acknowledgement' => (bool) $this->requires_acknowledgement,
            'status'                   => $this->status,
            'created_by'               => $this->created_by,
            'publish_at'               => $this->publish_at?->toISOString(),
            'expires_at'               => $this->expires_at?->toISOString(),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
