<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MessageThreadResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'thread_type'     => $this->thread_type,
            'context_type'    => $this->context_type,
            'context_id'      => $this->context_id,
            'organization_id' => $this->organization_id,
            'facility_id'     => $this->facility_id,
            'patient_id'      => $this->patient_id,
            'title'           => $this->title,
            'priority'        => $this->priority,
            'status'          => $this->status,
            'created_by'      => $this->created_by,
            'assigned_to'     => $this->assigned_to,
            'legal_hold'      => (bool) $this->legal_hold,
            'closed_at'       => $this->closed_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
