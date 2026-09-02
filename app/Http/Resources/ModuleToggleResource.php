<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ModuleToggleResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'module_key'     => $this->module_key,
            'module_label'   => $this->module_label,
            'enabled'        => (bool) $this->enabled,
            'scope'          => $this->scope,
            'scope_value'    => $this->scope_value,
            'disable_reason' => $this->disable_reason,
            'updated_by'     => $this->updated_by,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
