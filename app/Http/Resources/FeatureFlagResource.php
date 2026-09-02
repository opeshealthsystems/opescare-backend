<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FeatureFlagResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'key'         => $this->key,
            'label'       => $this->label,
            'description' => $this->description,
            'enabled'     => (bool) $this->enabled,
            'scope'       => $this->scope,
            'scope_value' => $this->scope_value,
            'updated_by'  => $this->updated_by,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
