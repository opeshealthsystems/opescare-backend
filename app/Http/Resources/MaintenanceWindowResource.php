<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MaintenanceWindowResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'title'                  => $this->title,
            'message'                => $this->message,
            'starts_at'              => $this->starts_at?->toISOString(),
            'ends_at'                => $this->ends_at?->toISOString(),
            'is_active'              => (bool) $this->is_active,
            'allow_emergency_access' => (bool) $this->allow_emergency_access,
            'created_by'             => $this->created_by,
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
