<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PlatformSettingResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'key'         => $this->key,
            'group'       => $this->group,
            'value'       => $this->value,
            'value_type'  => $this->value_type,
            'description' => $this->description,
            'is_public'   => (bool) $this->is_public,
            'updated_by'  => $this->updated_by,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
