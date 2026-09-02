<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ActionTaskResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'task_type' => $this->task_type,
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $this->assigned_to,
            'assigned_role' => $this->assigned_role,
            'facility_id' => $this->facility_id,
            'organization_id' => $this->organization_id,
            'patient_id' => $this->patient_id,
            'related_resource_type' => $this->related_resource_type,
            'related_resource_id' => $this->related_resource_id,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_at' => $this->due_at?->toISOString(),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'escalation_chain_id' => $this->escalation_chain_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
